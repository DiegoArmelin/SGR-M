<?php

namespace App\Services;

use App\Models\Rodeiro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importação de periodicidade de rodeiros, com validação de todo o arquivo.
 *
 * Formato: numero_serie;periodicidade
 * Os percentuais são recebidos diretamente do CSV, sem calcular horas/dias.
 * A operação é atômica: se uma linha for inválida, nada é atualizado.
 */
class ImportacaoPeriodicidadeCsvService
{
    /** Limite operacional para evitar leituras muito grandes em requisições web. */
    private const MAX_LINHAS = 10000;

    /**
     * @return array{linhas: int, atualizados: int}
     */
    public function importar(UploadedFile $arquivo): array
    {
        $handle = fopen($arquivo->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'arquivo' => 'Não foi possível abrir o CSV enviado.',
            ]);
        }

        try {
            // Detecta ; ou , pelo cabeçalho; não interpreta vírgula decimal
            // como separador quando o arquivo utiliza ponto e vírgula.
            $cabecalhoBruto = fgets($handle);
            if ($cabecalhoBruto === false) {
                return $this->falhar('O arquivo CSV está vazio.');
            }

            $cabecalhoBruto = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalhoBruto);
            $delimitador = substr_count($cabecalhoBruto, ';') > 0 ? ';' : ',';
            $cabecalho = str_getcsv($cabecalhoBruto, $delimitador);

            // Identifica colunas por nome, independentemente da ordem.
            $nomes = array_map(
                fn ($nome) => mb_strtolower(trim((string) $nome)),
                $cabecalho
            );

            if (count($nomes) !== count(array_unique($nomes))
                || !in_array('numero_serie', $nomes, true)
                || !in_array('periodicidade', $nomes, true)) {
                return $this->falhar(
                    'Cabeçalho inválido. Use as colunas numero_serie e periodicidade, sem repetições.'
                );
            }

            $colunaSerie = array_search('numero_serie', $nomes, true);
            $colunaPeriodicidade = array_search('periodicidade', $nomes, true);
            $registros = [];
            $linhaNumero = 1;

            while (($colunas = fgetcsv($handle, 0, $delimitador, '"', '\\')) !== false) {
                $linhaNumero++;

                // Linhas efetivamente em branco são ignoradas.
                if (count($colunas) === 1 && trim((string) $colunas[0]) === '') {
                    continue;
                }

                if (count($registros) >= self::MAX_LINHAS) {
                    return $this->falhar('O CSV ultrapassa o limite de 10.000 registros.');
                }

                if (count($colunas) !== count($nomes)) {
                    return $this->falhar("Linha {$linhaNumero}: quantidade incorreta de colunas.");
                }

                $serie = trim((string) $colunas[$colunaSerie]);
                $valorOriginal = trim((string) $colunas[$colunaPeriodicidade]);

                if ($serie === '' || strlen($serie) > 50) {
                    return $this->falhar("Linha {$linhaNumero}: número de série vazio ou inválido.");
                }

                if (isset($registros[$serie])) {
                    return $this->falhar("Linha {$linhaNumero}: número de série duplicado ({$serie}).");
                }

                // Permite 95.25, 95,25, 95.25% e 95,25%.
                // Não remove separadores de milhar, evitando interpretações ambíguas.
                $normalizado = str_replace(',', '.', rtrim($valorOriginal, " \t\n\r\0\x0B%"));
                if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $normalizado)) {
                    return $this->falhar("Linha {$linhaNumero}: periodicidade inválida.");
                }

                $numero = (float) $normalizado;
                if ($numero < 0 || $numero > 999.99) {
                    return $this->falhar("Linha {$linhaNumero}: percentual fora do intervalo permitido (0 a 999,99).");
                }

                $registros[$serie] = number_format($numero, 2, '.', '');
            }

            if (!$registros) {
                return $this->falhar('O CSV não contém registros para importar.');
            }

            // Verifica todas as séries ANTES de começar a gravar.
            $existentes = Rodeiro::query()
                ->whereIn('numero_serie', array_keys($registros))
                ->pluck('numero_serie')
                ->all();
            $desconhecidos = array_diff(array_keys($registros), $existentes);

            if ($desconhecidos) {
                return $this->falhar(
                    'Série(s) não cadastrada(s): '
                    . implode(', ', array_slice($desconhecidos, 0, 5))
                    . (count($desconhecidos) > 5 ? ' (e outras).' : '.')
                );
            }

            // Atualização conjunta: se ocorrer erro SQL, rollback completo.
            DB::transaction(function () use ($registros): void {
                foreach ($registros as $serie => $periodicidade) {
                    Rodeiro::query()->where('numero_serie', $serie)->update([
                        'periodicidade' => $periodicidade,
                        'data_atualizacao_condicao' => now(),
                    ]);
                }
            }, 3);

            return ['linhas' => count($registros), 'atualizados' => count($registros)];
        } finally {
            fclose($handle);
        }
    }

    /** Centraliza a geração de erro legível na tela de importação. */
    private function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['arquivo' => $mensagem]);
    }
}
