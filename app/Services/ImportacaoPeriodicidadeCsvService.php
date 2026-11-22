<?php

namespace App\Services;

use App\Models\InstalacaoRodeiro;
use App\Models\Rodeiro;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa, no mesmo CSV, diâmetros LR/LO e periodicidade dos rodeiros.
 *
 * Cada linha identifica a frota, trem, carro, eixo, série e data de medição.
 * A série só é atualizada quando ocupa a posição informada e a data recebida
 * é estritamente posterior à data_atualizacao_condicao armazenada.
 *
 * Valida todo o arquivo ANTES de gravar. Linhas com data antiga ou posição
 * divergente são ignoradas e contabilizadas, sem alterar outros equipamentos.
 */
class ImportacaoPeriodicidadeCsvService
{
    private const MAX_LINHAS = 10000;

    private const COLUNAS = [
        'frota', 'trem', 'carro', 'posicao', 'serie_rodeiro',
        'diametro_lr', 'diametro_lo', 'periodicidade', 'data_atualizacao',
    ];

    /**
     * @return array{linhas:int,atualizados:int,desatualizados:int,divergentes:int}
     */
    public function importar(UploadedFile $arquivo): array
    {
        $handle = fopen($arquivo->getRealPath(), 'rb');
        if ($handle === false) {
            $this->falhar('Não foi possível abrir o arquivo CSV.');
        }

        try {
            $cabecalho = fgetcsv($handle, 0, ';', '"', '\\');

            if ($cabecalho === false) {
                $this->falhar('O arquivo está vazio.');
            }

            // Remove BOM UTF-8; valida as nove colunas exatamente como combinado.
            $cabecalho[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho[0]);
            $colunas = array_map(fn ($v) => strtolower(trim((string) $v)), $cabecalho);

            if ($colunas !== self::COLUNAS) {
                $this->falhar('Cabeçalho inválido. Utilize as nove colunas na ordem indicada, separadas por ponto e vírgula.');
            }

            $registros = [];
            $numeroLinha = 1;

            while (($valores = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
                $numeroLinha++;

                if (count($valores) === 1 && trim((string) $valores[0]) === '') {
                    continue;
                }

                if (count($registros) >= self::MAX_LINHAS) {
                    $this->falhar('O CSV ultrapassou 10.000 registros.');
                }

                if (count($valores) !== count(self::COLUNAS)) {
                    $this->falhar("Linha {$numeroLinha}: quantidade de colunas inválida.");
                }

                $linha = array_combine(self::COLUNAS, array_map('trim', $valores));
                $serie = $linha['serie_rodeiro'];

                if ($serie === '' || strlen($serie) > 50) {
                    $this->falhar("Linha {$numeroLinha}: série inválida.");
                }
                if (isset($registros[$serie])) {
                    $this->falhar("Linha {$numeroLinha}: série duplicada ({$serie}).");
                }

                // Frota, trem e carro identificam a posição física informada.
                foreach (['frota', 'trem', 'carro'] as $campo) {
                    if ($linha[$campo] === '' || strlen($linha[$campo]) > 30) {
                        $this->falhar("Linha {$numeroLinha}: {$campo} inválido.");
                    }
                }

                if (!preg_match('/^[1-9][0-9]*$/D', $linha['posicao'])) {
                    $this->falhar("Linha {$numeroLinha}: posição deve ser um inteiro positivo.");
                }

                // Decimal com ponto ou vírgula; sem separadores de milhar.
                foreach (['diametro_lr', 'diametro_lo', 'periodicidade'] as $campo) {
                    $numero = str_replace(',', '.', $linha[$campo]);
                    if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $numero)) {
                        $this->falhar("Linha {$numeroLinha}: {$campo} inválido.");
                    }
                    $limite = $campo === 'periodicidade' ? 999.99 : 9999.99;
                    if ((float) $numero > $limite) {
                        $this->falhar("Linha {$numeroLinha}: {$campo} fora do limite de armazenamento.");
                    }
                    $linha[$campo] = number_format((float) $numero, 2, '.', '');
                }

                // Aceita somente data ISO (dia) ou data/hora completa ISO.
                $formato = strlen($linha['data_atualizacao']) === 10
                    ? '!Y-m-d' : '!Y-m-d H:i:s';
                $data = DateTimeImmutable::createFromFormat($formato, $linha['data_atualizacao']);
                $erros = DateTimeImmutable::getLastErrors();
                if ($data === false || ($erros !== false
                    && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))) {
                    $this->falhar("Linha {$numeroLinha}: data inválida. Utilize AAAA-MM-DD ou AAAA-MM-DD HH:MM:SS.");
                }

                $linha['data_atualizacao'] = $data->format('Y-m-d H:i:s');
                $linha['posicao'] = (int) $linha['posicao'];
                $registros[$serie] = $linha;
            }

            if (!$registros) {
                $this->falhar('O CSV não contém registros para atualizar.');
            }

            // Confere todas as séries cadastradas antes de iniciar a transação.
            // Um erro de identificação é diferente de uma movimentação anterior:
            // série desconhecida invalida o arquivo; série retirada é ignorada.
            $rodeiros = Rodeiro::query()
                ->whereIn('numero_serie', array_keys($registros))
                ->get()->keyBy('numero_serie');

            $desconhecidos = array_diff(array_keys($registros), $rodeiros->keys()->all());
            if ($desconhecidos) {
                $this->falhar('Série não cadastrada: ' . implode(', ', array_slice($desconhecidos, 0, 5)));
            }

            // Atualiza e compara a data dentro da mesma transação; evita que uma
            // importação mais antiga sobrescreva uma medição já registrada.
            $resultado = DB::transaction(function () use ($registros, $rodeiros): array {
                $atualizados = 0;
                $desatualizados = 0;
                $divergentes = 0;

                foreach ($registros as $serie => $linha) {
                    $rodeiro = Rodeiro::query()->whereKey($rodeiros[$serie]->id)
                        ->lockForUpdate()->firstOrFail();

                    // Verifica o vínculo ATIVO do rodeiro à posição esperada.
                    $instalacao = InstalacaoRodeiro::query()
                        ->where('rodeiro_id', $rodeiro->id)
                        ->whereNull('data_retirada')
                        ->with('eixo.carro.trem.frota')
                        ->first();

                    $eixo = $instalacao?->eixo;
                    $carro = $eixo?->carro;
                    $trem = $carro?->trem;
                    $frota = $trem?->frota;

                    if (!$eixo
                        || $frota?->codigo !== $linha['frota']
                        || $trem?->codigo !== $linha['trem']
                        || $carro?->codigo !== $linha['carro']
                        || (int) $eixo->numero !== $linha['posicao']) {
                        $divergentes++;
                        continue;
                    }

                    $dataAnterior = $rodeiro->data_atualizacao_condicao;
                    if ($dataAnterior !== null
                        && $linha['data_atualizacao'] <= $dataAnterior->format('Y-m-d H:i:s')) {
                        $desatualizados++;
                        continue;
                    }

                    $rodeiro->update([
                        'diametro_lr' => $linha['diametro_lr'],
                        'diametro_lo' => $linha['diametro_lo'],
                        'periodicidade' => $linha['periodicidade'],
                        'data_atualizacao_condicao' => $linha['data_atualizacao'],
                    ]);
                    $atualizados++;
                }

                return compact('atualizados', 'desatualizados', 'divergentes');
            }, 3);

            return ['linhas' => count($registros)] + $resultado;
        } finally {
            fclose($handle);
        }
    }

    private function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['arquivo' => $mensagem]);
    }
}
