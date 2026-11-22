<?php

namespace App\Services;

use App\Models\Eixo;
use App\Models\InstalacaoRodeiro;
use App\Models\Rodeiro;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa, em um único CSV, diâmetros LR/LO e periodicidade de rodeiros.
 *
 * A linha deve identificar a posição física e o número de série instalado.
 * Valores somente substituem os existentes quando data_atualizacao do CSV
 * for estritamente posterior à última data armazenada para aquele rodeiro.
 */
class ImportacaoPeriodicidadeCsvService
{
    private const MAX_LINHAS = 10000;

    private const COLUNAS = [
        'frota', 'trem', 'carro', 'posicao', 'serie_rodeiro',
        'diametro_lr', 'diametro_lo', 'periodicidade', 'data_atualizacao',
    ];

    /**
     * @return array{linhas:int,atualizados:int,ignorados_data:int,ignorados_posicao:int,detalhes:array}
     */
    public function importar(UploadedFile $arquivo): array
    {
        $handle = fopen($arquivo->getRealPath(), 'rb');

        if ($handle === false) {
            $this->falhar('Não foi possível abrir o arquivo enviado.');
        }

        try {
            $primeiraLinha = fgets($handle);

            if ($primeiraLinha === false) {
                $this->falhar('O arquivo CSV está vazio.');
            }

            // Cabeçalho UTF-8 com BOM é aceito (inclusive Excel).
            $primeiraLinha = preg_replace('/^\xEF\xBB\xBF/', '', $primeiraLinha);
            $delimitador = str_contains($primeiraLinha, ';') ? ';' : ',';
            $nomes = array_map(
                fn ($nome) => strtolower(trim((string) $nome)),
                str_getcsv($primeiraLinha, $delimitador)
            );

            if (count($nomes) !== count(self::COLUNAS)
                || count(array_unique($nomes)) !== count(self::COLUNAS)
                || array_diff(self::COLUNAS, $nomes)) {
                $this->falhar('Cabeçalho inválido. Use as nove colunas indicadas no formulário.');
            }

            $indices = array_flip($nomes);
            $dados = [];
            $numeroLinha = 1;
            $seriesUsadas = [];

            // Valida todo o conteúdo antes de qualquer atualização no banco.
            while (($campos = fgetcsv($handle, 0, $delimitador, '"', '\\')) !== false) {
                $numeroLinha++;

                if (count($campos) === 1 && trim((string) $campos[0]) === '') {
                    continue;
                }

                if (count($dados) >= self::MAX_LINHAS) {
                    $this->falhar('O CSV ultrapassa 10.000 registros.');
                }

                if (count($campos) !== count($nomes)) {
                    $this->falhar("Linha {$numeroLinha}: quantidade de colunas incorreta.");
                }

                $linha = [];
                foreach (self::COLUNAS as $nome) {
                    $linha[$nome] = trim((string) $campos[$indices[$nome]]);
                }

                if ($linha['frota'] === '' || $linha['trem'] === ''
                    || $linha['carro'] === '' || $linha['serie_rodeiro'] === ''
                    || strlen($linha['serie_rodeiro']) > 50
                    || !preg_match('/^[1-9]\d*$/D', $linha['posicao'])) {
                    $this->falhar("Linha {$numeroLinha}: frota, trem, carro, posição ou série inválidos.");
                }

                // A mesma série no arquivo não pode receber duas condições.
                if (isset($seriesUsadas[$linha['serie_rodeiro']])) {
                    $this->falhar("Linha {$numeroLinha}: série duplicada {$linha['serie_rodeiro']}.");
                }
                $seriesUsadas[$linha['serie_rodeiro']] = true;

                foreach (['diametro_lr', 'diametro_lo', 'periodicidade'] as $campo) {
                    $linha[$campo] = $this->numero($linha[$campo], $campo, $numeroLinha);
                }

                // Data e hora de medição do CSV; não utilizar now() como data
                // da condição, pois distorceria a ordem temporal das coletas.
                $data = $linha['data_atualizacao'];

                if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $data)) {
                    $this->falhar("Linha {$numeroLinha}: data inválida; use AAAA-MM-DD HH:MM:SS.");
                }

                try {
                    $instante = Carbon::createFromFormat('!Y-m-d H:i:s', $data);
                } catch (\Exception $e) {
                    $this->falhar("Linha {$numeroLinha}: data de atualização inexistente.");
                }

                if (!$instante || $instante->format('Y-m-d H:i:s') !== $data) {
                    $this->falhar("Linha {$numeroLinha}: data de atualização inexistente.");
                }

                $linha['data_atualizacao'] = $instante;
                $dados[] = $linha;
            }

            if (!$dados) {
                $this->falhar('O arquivo não contém registros.');
            }

            // Séries inexistentes indicam arquivo incompatível: recusa total.
            $series = array_keys($seriesUsadas);
            $encontradas = Rodeiro::whereIn('numero_serie', $series)
                ->pluck('numero_serie')->all();
            $naoEncontradas = array_values(array_diff($series, $encontradas));

            if ($naoEncontradas) {
                $this->falhar(
                    'Série não cadastrada: ' . implode(', ', array_slice($naoEncontradas, 0, 5))
                    . (count($naoEncontradas) > 5 ? ' (e outras)' : '')
                );
            }

            // A transação protege a atualização em lote. Eixos são bloqueados
            // com a mesma estratégia da movimentação para evitar troca
            // concorrente enquanto a condição do rodeiro está sendo atualizada.
            return DB::transaction(function () use ($dados): array {
                $resultado = [
                    'linhas' => count($dados),
                    'atualizados' => 0,
                    'ignorados_data' => 0,
                    'ignorados_posicao' => 0,
                    'detalhes' => [],
                ];

                foreach ($dados as $indice => $linha) {
                    // O relatório registra cada linha do CSV com sua posição e
                    // resultado final; é gerado somente quando o lote conclui.
                    $detalhe = [
                        'linha' => $indice + 2,
                        'frota' => $linha['frota'],
                        'trem' => $linha['trem'],
                        'carro' => $linha['carro'],
                        'posicao' => $linha['posicao'],
                        'serie_rodeiro' => $linha['serie_rodeiro'],
                        'diametro_lr' => $linha['diametro_lr'],
                        'diametro_lo' => $linha['diametro_lo'],
                        'periodicidade' => $linha['periodicidade'],
                        'data_atualizacao' => $linha['data_atualizacao']->format('Y-m-d H:i:s'),
                        'status' => 'atualizado',
                        'motivo' => 'Condição atualizada com sucesso.',
                    ];

                    $eixo = Eixo::query()
                        ->where('numero', (int) $linha['posicao'])
                        ->whereHas('carro', fn ($q) => $q
                            ->where('codigo', $linha['carro'])
                            ->whereHas('trem', fn ($t) => $t
                                ->where('codigo', $linha['trem'])
                                ->whereHas('frota', fn ($f) => $f
                                    ->where('codigo', $linha['frota']))))
                        ->lockForUpdate()
                        ->first();

                    // Uma localização que não existe indica erro estrutural
                    // no CSV e deve interromper todo o lote.
                    if (!$eixo) {
                        $this->falhar(
                            "Posição inexistente no cadastro: "
                            . "{$linha['frota']}/{$linha['trem']}/{$linha['carro']}-{$linha['posicao']}."
                        );
                    }

                    $instalacao = InstalacaoRodeiro::query()
                        ->where('eixo_id', $eixo->id)
                        ->whereNull('data_retirada')
                        ->lockForUpdate()
                        ->first();

                    $rodeiro = Rodeiro::query()
                        ->where('numero_serie', $linha['serie_rodeiro'])
                        ->lockForUpdate()->firstOrFail();

                    // Peça que já foi retirada da posição (por troca manual)
                    // não recebe dados dessa posição; mantém-se o histórico.
                    if (!$instalacao || $instalacao->rodeiro_id !== $rodeiro->id) {
                        $resultado['ignorados_posicao']++;
                        $detalhe['status'] = 'ignorado';
                        $detalhe['motivo'] = !$instalacao
                            ? 'Nenhum rodeiro instalado atualmente nesta posição.'
                            : 'Série divergente: o rodeiro atualmente instalado é '
                                . (Rodeiro::query()->whereKey($instalacao->rodeiro_id)->value('numero_serie') ?? 'não identificado')
                                . '.';
                        $resultado['detalhes'][] = $detalhe;
                        continue;
                    }

                    if ($rodeiro->data_atualizacao_condicao
                        && !$linha['data_atualizacao']->greaterThan($rodeiro->data_atualizacao_condicao)) {
                        $resultado['ignorados_data']++;
                        $detalhe['status'] = 'ignorado';
                        $detalhe['motivo'] = 'Data do CSV igual ou anterior à última atualização ('
                            . $rodeiro->data_atualizacao_condicao->format('d/m/Y H:i:s') . ').';
                        $resultado['detalhes'][] = $detalhe;
                        continue;
                    }

                    $rodeiro->update([
                        'diametro_lr' => $linha['diametro_lr'],
                        'diametro_lo' => $linha['diametro_lo'],
                        'periodicidade' => $linha['periodicidade'],
                        'data_atualizacao_condicao' => $linha['data_atualizacao'],
                    ]);
                    $resultado['atualizados']++;
                    $resultado['detalhes'][] = $detalhe;
                }

                return $resultado;
            }, 3);
        } finally {
            fclose($handle);
        }
    }

    /** Valida formato numérico, sem impor limites físicos de diâmetro. */
    private function numero(string $valor, string $campo, int $linha): string
    {
        $normalizado = str_replace(',', '.', trim($valor));

        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $normalizado)
            || !is_numeric($normalizado) || (float) $normalizado > 99999.99) {
            $this->falhar("Linha {$linha}: {$campo} inválido.");
        }

        return number_format((float) $normalizado, 2, '.', '');
    }

    /** Erros de validação são exibidos sem revelar mensagens internas de SQL. */
    private function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['arquivo' => $mensagem]);
    }
}
