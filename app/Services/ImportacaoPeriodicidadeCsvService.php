<?php

namespace App\Services;

use App\Models\Eixo;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\Rodeiro;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa em um CSV os diâmetros e periodicidade dos rodeiros e a
 * periodicidade dos motores instalados no mesmo eixo.
 *
 * A data é comparada separadamente em cada equipamento. Uma medição antiga
 * de rodeiro não impede atualizar o motor (e vice-versa).
 * Erros de estrutura ou série inexistente revertem o lote inteiro.
 */
class ImportacaoPeriodicidadeCsvService
{
    private const MAX_LINHAS = 10000;

    /**
     * Faixa aceita para data_atualizacao. Datas futuras travariam o equipamento
     * (a regra "somente data mais recente" ignoraria as medições reais), e
     * datas fora de 1970–2038 nem cabem na coluna TIMESTAMP (erro 500).
     */
    private const DATA_MINIMA = '2000-01-01 00:00:00';
    private const TOLERANCIA_FUTURO_MINUTOS = 5;

    private const COLUNAS = [
        'frota', 'trem', 'carro', 'posicao',
        'serie_rodeiro', 'diametro_lr', 'diametro_lo', 'periodicidade',
        'serie_motor', 'periodicidade_motor', 'data_atualizacao',
    ];

    /** @return array<string,mixed> */
    public function importar(UploadedFile $arquivo): array
    {
        $arquivoAberto = fopen($arquivo->getRealPath(), 'rb');
        if ($arquivoAberto === false) {
            $this->falhar('Não foi possível abrir o CSV informado.');
        }

        try {
            $cabecalho = fgets($arquivoAberto);
            if ($cabecalho === false) {
                $this->falhar('O arquivo CSV está vazio.');
            }

            $cabecalho = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho);
            $separador = str_contains($cabecalho, ';') ? ';' : ',';
            $colunas = array_map(
                fn ($valor) => strtolower(trim((string) $valor)),
                str_getcsv($cabecalho, $separador)
            );

            if (count($colunas) !== count(self::COLUNAS)
                || count(array_unique($colunas)) !== count(self::COLUNAS)
                || array_diff(self::COLUNAS, $colunas)) {
                $this->falhar('Cabeçalho inválido: informe as onze colunas do modelo exibido na tela.');
            }

            $indices = array_flip($colunas);
            $linhas = [];
            $series = ['rodeiro' => [], 'motor' => []];
            $linhaNumero = 1;
            $dataMinima = Carbon::parse(self::DATA_MINIMA);
            $dataMaxima = now()->addMinutes(self::TOLERANCIA_FUTURO_MINUTOS);

            // Valida todo o arquivo antes de alterar os dados.
            while (($valores = fgetcsv($arquivoAberto, 0, $separador, '"', '\\')) !== false) {
                $linhaNumero++;

                if (count($valores) === 1 && trim((string) $valores[0]) === '') {
                    continue;
                }
                if (count($linhas) >= self::MAX_LINHAS) {
                    $this->falhar('O arquivo ultrapassa 10.000 linhas de dados.');
                }
                if (count($valores) !== count($colunas)) {
                    $this->falhar("Linha {$linhaNumero}: quantidade incorreta de colunas.");
                }

                $linha = [];
                foreach (self::COLUNAS as $nome) {
                    $linha[$nome] = trim((string) $valores[$indices[$nome]]);
                }

                if ($linha['frota'] === '' || $linha['trem'] === ''
                    || $linha['carro'] === ''
                    || !preg_match('/^[1-9]\d*$/D', $linha['posicao'])) {
                    $this->falhar("Linha {$linhaNumero}: localização inválida.");
                }

                foreach (['rodeiro' => 'serie_rodeiro', 'motor' => 'serie_motor'] as $tipo => $campo) {
                    $serie = $linha[$campo];
                    if ($serie === '' || strlen($serie) > 50) {
                        $this->falhar("Linha {$linhaNumero}: {$campo} inválido.");
                    }
                    if (isset($series[$tipo][$serie])) {
                        $this->falhar("Linha {$linhaNumero}: série de {$tipo} repetida: {$serie}.");
                    }
                    $series[$tipo][$serie] = true;
                }

                foreach (['diametro_lr','diametro_lo','periodicidade','periodicidade_motor'] as $campo) {
                    $linha[$campo] = $this->numero($linha[$campo], $campo, $linhaNumero);
                }

                $data = $linha['data_atualizacao'];
                if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $data)) {
                    $this->falhar("Linha {$linhaNumero}: data inválida; formato AAAA-MM-DD HH:MM:SS.");
                }
                try {
                    $instante = Carbon::createFromFormat('!Y-m-d H:i:s', $data);
                } catch (\Exception $e) {
                    $this->falhar("Linha {$linhaNumero}: data inexistente.");
                }
                if (!$instante || $instante->format('Y-m-d H:i:s') !== $data) {
                    $this->falhar("Linha {$linhaNumero}: data inexistente.");
                }
                if ($instante->greaterThan($dataMaxima)) {
                    $this->falhar("Linha {$linhaNumero}: data futura ({$instante->format('d/m/Y H:i:s')}); "
                        . 'a data de atualização não pode ser posterior ao momento da importação.');
                }
                if ($instante->lessThan($dataMinima)) {
                    $this->falhar("Linha {$linhaNumero}: data anterior a 01/01/2000 não é aceita.");
                }
                $linha['data_atualizacao'] = $instante;
                $linha['linha_csv'] = $linhaNumero;
                $linhas[] = $linha;
            }

            if (!$linhas) {
                $this->falhar('O CSV não contém registros.');
            }

            // Falha de cadastro em qualquer tipo impede atualizações parciais.
            foreach (['rodeiro' => Rodeiro::class, 'motor' => Motor::class] as $tipo => $modelo) {
                $informadas = array_keys($series[$tipo]);
                $existentes = $modelo::whereIn('numero_serie', $informadas)
                    ->pluck('numero_serie')->all();
                $ausentes = array_values(array_diff($informadas, $existentes));
                if ($ausentes) {
                    $this->falhar(
                        'Série de ' . $tipo . ' não cadastrada: '
                        . implode(', ', array_slice($ausentes, 0, 5))
                    );
                }
            }

            return DB::transaction(function () use ($linhas): array {
                $resultado = [
                    'linhas' => count($linhas),
                    'atualizados' => 0,
                    'ignorados_data' => 0,
                    'ignorados_posicao' => 0,
                    'detalhes' => [],
                    'rodeiros_atualizados' => 0,
                    'motores_atualizados' => 0,
                ];

                foreach ($linhas as $linha) {
                    $eixo = Eixo::query()
                        ->where('numero', (int) $linha['posicao'])
                        ->whereHas('carro', fn ($q) => $q
                            ->where('codigo', $linha['carro'])
                            ->whereHas('trem', fn ($t) => $t
                                ->where('codigo', $linha['trem'])
                                ->whereHas('frota', fn ($f) => $f
                                    ->where('codigo', $linha['frota']))))
                        ->lockForUpdate()->first();

                    if (!$eixo) {
                        $this->falhar(
                            "Posição inexistente: {$linha['frota']}/{$linha['trem']}/"
                            . "{$linha['carro']}-{$linha['posicao']}."
                        );
                    }

                    $detalhe = [
                        'linha' => $linha['linha_csv'],
                        'frota' => $linha['frota'],
                        'trem' => $linha['trem'],
                        'carro' => $linha['carro'],
                        'posicao' => $linha['posicao'],
                        'serie_rodeiro' => $linha['serie_rodeiro'],
                        'diametro_lr' => $linha['diametro_lr'],
                        'diametro_lo' => $linha['diametro_lo'],
                        'periodicidade' => $linha['periodicidade'],
                        'serie_motor' => $linha['serie_motor'],
                        'periodicidade_motor' => $linha['periodicidade_motor'],
                        'data_atualizacao' => $linha['data_atualizacao']->format('Y-m-d H:i:s'),
                    ];

                    // A ordem de bloqueio é estável: eixo → rodeiro → motor.
                    // Usa instalação ativa para proteger a posição de trocas.
                    $this->processarEquipamento(
                        $eixo->id, 'rodeiro', $linha, $detalhe, $resultado
                    );
                    $this->processarEquipamento(
                        $eixo->id, 'motor', $linha, $detalhe, $resultado
                    );

                    // Contador legado por linha: atualizada se ao menos um
                    // equipamento foi atualizado; caso contrário, ignorada.
                    $houveAtualizacao = $detalhe['status_rodeiro'] === 'atualizado'
                        || $detalhe['status_motor'] === 'atualizado';
                    $resultado[$houveAtualizacao ? 'atualizados' : 'ignorados_data'] +=
                        $houveAtualizacao ? 1 : (
                            $detalhe['status_rodeiro'] === 'ignorado_data'
                            && $detalhe['status_motor'] === 'ignorado_data' ? 1 : 0
                        );
                    if (!$houveAtualizacao && (
                        $detalhe['status_rodeiro'] === 'ignorado_posicao'
                        || $detalhe['status_motor'] === 'ignorado_posicao'
                    )) {
                        $resultado['ignorados_posicao']++;
                    }

                    $detalhe['status'] = $houveAtualizacao ? 'atualizado' : 'ignorado';
                    $detalhe['motivo'] = 'Rodeiro: ' . $detalhe['motivo_rodeiro']
                        . ' Motor: ' . $detalhe['motivo_motor'];
                    $resultado['detalhes'][] = $detalhe;
                }

                return $resultado;
            }, 3);
        } finally {
            fclose($arquivoAberto);
        }
    }

    /**
     * Aplica a condição somente se a série ainda estiver instalada naquele
     * eixo e a data informada for posterior à última medição do equipamento.
     *
     * @param array<string,mixed> $linha
     * @param array<string,mixed> $detalhe
     * @param array<string,mixed> $resultado
     */
    private function processarEquipamento(
        int $eixoId, string $tipo, array $linha, array &$detalhe, array &$resultado
    ): void {
        $rodeiro = $tipo === 'rodeiro';
        $modelo = $rodeiro ? Rodeiro::class : Motor::class;
        $instalacaoModelo = $rodeiro ? InstalacaoRodeiro::class : InstalacaoMotor::class;
        $colunaId = $rodeiro ? 'rodeiro_id' : 'motor_id';
        $colunaSerie = $rodeiro ? 'serie_rodeiro' : 'serie_motor';

        $instalacao = $instalacaoModelo::where('eixo_id', $eixoId)
            ->whereNull('data_retirada')->lockForUpdate()->first();
        $equipamento = $modelo::where('numero_serie', $linha[$colunaSerie])
            ->lockForUpdate()->firstOrFail();

        if (!$instalacao || $instalacao->{$colunaId} !== $equipamento->id) {
            $detalhe['status_' . $tipo] = 'ignorado_posicao';
            $atual = $instalacao ? $modelo::whereKey($instalacao->{$colunaId})
                ->value('numero_serie') : null;
            $detalhe['motivo_' . $tipo] = $atual
                ? "Série divergente; instalado: {$atual}."
                : 'Nenhum equipamento ativo nesta posição.';
            return;
        }

        if ($equipamento->data_atualizacao_condicao
            && !$linha['data_atualizacao']->greaterThan($equipamento->data_atualizacao_condicao)) {
            $detalhe['status_' . $tipo] = 'ignorado_data';
            $detalhe['motivo_' . $tipo] = 'Data igual ou anterior à última atualização ('
                . $equipamento->data_atualizacao_condicao->format('d/m/Y H:i:s') . ').';
            return;
        }

        $atributos = [
            'periodicidade' => $linha[$rodeiro ? 'periodicidade' : 'periodicidade_motor'],
            'data_atualizacao_condicao' => $linha['data_atualizacao'],
        ];
        if ($rodeiro) {
            $atributos['diametro_lr'] = $linha['diametro_lr'];
            $atributos['diametro_lo'] = $linha['diametro_lo'];
        }

        $equipamento->update($atributos);
        $detalhe['status_' . $tipo] = 'atualizado';
        $detalhe['motivo_' . $tipo] = 'Condição atualizada.';
        $resultado[$rodeiro ? 'rodeiros_atualizados' : 'motores_atualizados']++;
    }

    /** Validação de formato, sem impor limites específicos do CSV de teste. */
    private function numero(string $valor, string $campo, int $linha): string
    {
        $normalizado = str_replace(',', '.', trim($valor));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $normalizado)
            || (float) $normalizado > (str_starts_with($campo, 'periodicidade') ? 999.99 : 9999.99)) {
            $this->falhar("Linha {$linha}: {$campo} inválido.");
        }
        return number_format((float) $normalizado, 2, '.', '');
    }

    private function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['arquivo' => $mensagem]);
    }
}
