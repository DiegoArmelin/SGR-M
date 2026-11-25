<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Monta três consultas independentes da posição instalada.
 *
 * Pendências: criticidade da ocorrência, sem incluir peças já retiradas.
 * Diâmetros: menor leitura LR/LO < 745 mm, ordenada do menor para o maior.
 * Periodicidade: percentual superior a 100%, ordenado do maior para o menor.
 *
 * A visão de motores possui somente pendências: motor não tem as medições
 * de diâmetro nem periodicidade no modelo atual.
 */
class RankingPriorizacaoService
{
    private const LIMITE = 10;

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function consultar(string $tipo, array $filtros): array
    {
        $pendencias = $this->consultaPendencias($tipo, $filtros);

        // Denominadores por seção: todos os equipamentos INSTALADOS na
        // localização filtrada, inclusive os que não têm medições/pendências.
        // Cada quadro usa seus próprios filtros, sem afetar os demais.
        $instaladosPendencias = $this->contarInstalados($tipo, $filtros, 'p');
        $instaladosDiametros = $tipo === 'rodeiro'
            ? $this->contarInstalados('rodeiro', $filtros, 'd') : 0;
        $instaladosPeriodicidade = $this->contarInstalados($tipo, $filtros, 'r');

        // O filtro opcional de prioridade limita a tabela, mas os cards
        // mostram a distribuição COMPLETA da localização selecionada.
        $semPrioridade = array_diff_key($filtros, ['p_prioridade' => true]);
        $consultaPendenciasGeral = $this->consultaPendencias($tipo, $semPrioridade);

        $resultado = [
            'pendencias' => $pendencias->limit(self::LIMITE)->get(),
            'totalPendencias' => (clone $pendencias)->reorder()->count(),
            'pendenciasPorPrioridade' => [],
            'pendenciasEquipamentosPorPrioridade' => [],
            'instaladosPendencias' => $instaladosPendencias,
            'totalPendenciasGeral' => (clone $consultaPendenciasGeral)->reorder()->count(),
            // Um equipamento pode ter várias pendências: usar DISTINCT para
            // calcular a parcela da frota afetada sem percentuais >100%.
            'equipamentosComPendencia' => $this->contarDistintos($consultaPendenciasGeral),
            'diametros' => collect(),
            'totalDiametros' => 0,
            'diametrosCriticos' => 0,
            'instaladosDiametros' => $instaladosDiametros,
            'periodicidades' => collect(),
            'totalPeriodicidades' => 0,
            'periodicidades105' => 0,
            'periodicidades110' => 0,
            'instaladosPeriodicidade' => $instaladosPeriodicidade,
        ];

        // Cards da seção de pendências: a prioridade do filtro p_prioridade
        // não limita as contagens, mas os filtros de localização sim.
        foreach (['critica', 'alta', 'media', 'baixa'] as $prioridade) {
            $base = $this->consultaPendencias($tipo, $semPrioridade)
                ->where('p.prioridade', $prioridade);
            $resultado['pendenciasPorPrioridade'][$prioridade] =
                (clone $base)->reorder()->count();
            $resultado['pendenciasEquipamentosPorPrioridade'][$prioridade] =
                $this->contarDistintos($base);
        }

        if ($tipo === 'rodeiro') {
            $diametros = $this->consultaDiametros($filtros);

            // Os cards de diâmetro respeitam os filtros deste bloco e
            // contabilizam todos os resultados, não apenas os primeiros 10.
            $resultado['totalDiametros'] = (clone $diametros)->count();
            $resultado['diametrosCriticos'] = (clone $diametros)
                ->whereRaw($this->menorDiametroSql() . ' <= ?', [742])
                ->count();
            $resultado['diametros'] = $diametros
                ->orderByRaw($this->menorDiametroSql() . ' ASC')
                ->orderBy('t.codigo')->orderBy('c.codigo')->orderBy('e.numero')
                ->limit(self::LIMITE)->get();

        }

        // Motores passam a compartilhar a seção de periodicidade.
        $periodicidades = $this->consultaPeriodicidades($tipo, $filtros);
        $resultado['totalPeriodicidades'] = (clone $periodicidades)->count();
        $resultado['periodicidades105'] = (clone $periodicidades)
            ->where('r.periodicidade', '>=', 105)->count();
        $resultado['periodicidades110'] = (clone $periodicidades)
            ->where('r.periodicidade', '>=', 110)->count();
        $resultado['periodicidades'] = $periodicidades
            ->orderByDesc('r.periodicidade')
            ->orderBy('t.codigo')->orderBy('c.codigo')->orderBy('e.numero')
            ->limit(self::LIMITE)->get();

        return $resultado;
    }

    /**
     * Retorna a quantidade de instalações ativas na localização do bloco.
     * O mesmo denominador é usado no total e nas faixas da respectiva tabela.
     */
    private function contarInstalados(string $tipo, array $filtros, string $prefixo): int
    {
        return $this->filtrarLocalizacao($this->instalacoes($tipo), $filtros, $prefixo)
            ->count();
    }

    /** Conta equipamentos distintos afetados, não linhas de ocorrências. */
    private function contarDistintos(Builder $consulta): int
    {
        return (clone $consulta)->reorder()->distinct()->count('i.id');
    }

    /**
     * Parte comum: apenas equipamentos vinculados a uma instalação ativa.
     * Os joins restringem a frota/trem/carro/eixo à posição correta.
     */
    private function instalacoes(string $tipo): Builder
    {
        $rodeiro = $tipo === 'rodeiro';
        $tabelaInstalacoes = $rodeiro ? 'instalacoes_rodeiros' : 'instalacoes_motores';
        $tabelaEquipamentos = $rodeiro ? 'rodeiros' : 'motores';
        $chave = $rodeiro ? 'rodeiro_id' : 'motor_id';

        return DB::table($tabelaInstalacoes . ' as i')
            ->join($tabelaEquipamentos . ' as r', 'r.id', '=', 'i.' . $chave)
            ->join('eixos as e', 'e.id', '=', 'i.eixo_id')
            ->join('carros as c', 'c.id', '=', 'e.carro_id')
            ->join('trens as t', 't.id', '=', 'c.trem_id')
            ->join('frotas as f', 'f.id', '=', 't.frota_id')
            ->whereNull('i.data_retirada');
    }

    /**
     * Cada bloco tem seus próprios filtros. Campos de outros blocos são
     * intencionalmente ignorados, impedindo interferência entre tabelas.
     */
    private function filtrarLocalizacao(Builder $consulta, array $filtros, string $prefixo): Builder
    {
        if (!empty($filtros[$prefixo . '_frota'])) {
            $consulta->where('f.codigo', $filtros[$prefixo . '_frota']);
        }
        if (!empty($filtros[$prefixo . '_trem'])) {
            $consulta->where('t.codigo', $filtros[$prefixo . '_trem']);
        }
        if (!empty($filtros[$prefixo . '_carro'])) {
            $consulta->where('c.codigo', $filtros[$prefixo . '_carro']);
        }

        return $consulta;
    }

    /** Pendências abertas com prioridade crítica, alta, média e baixa. */
    private function consultaPendencias(string $tipo, array $filtros): Builder
    {
        $chave = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';

        $consulta = $this->instalacoes($tipo)
            ->join('pendencias_equipamentos as p', function ($join) use ($chave) {
                $join->on('p.eixo_id', '=', 'e.id')
                    ->on('p.' . $chave, '=', 'r.id');
            })
            ->leftJoin('motivos_retirada as mr', 'mr.id', '=', 'p.motivo_retirada_id')
            ->where('p.situacao', 'aberta');

        $this->filtrarLocalizacao($consulta, $filtros, 'p');

        if (!empty($filtros['p_prioridade'])) {
            $consulta->where('p.prioridade', $filtros['p_prioridade']);
        }

        return $consulta
            ->select([
                'p.id as pendencia_id', 'p.prioridade', 'p.descricao',
                'p.observacoes', 'mr.descricao as motivo_catalogo',
                'f.codigo as frota', 't.codigo as trem', 'c.codigo as carro',
                'e.numero as posicao', 'e.id as eixo_id', 'r.numero_serie',
            ])
            ->orderByRaw(
                "CASE p.prioridade WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 "
                . "WHEN 'media' THEN 3 WHEN 'baixa' THEN 4 ELSE 5 END"
            )
            ->orderBy('t.codigo')->orderBy('c.codigo')->orderBy('e.numero')
            ->orderBy('p.id');
    }

    /**
     * Menor medição sem erro quando LR ou LO estiver ausente.
     * A mesma expressão é usada para filtro, ordenação e contagem crítica.
     */
    private function menorDiametroSql(): string
    {
        return 'CASE '
            . 'WHEN r.diametro_lr IS NULL THEN r.diametro_lo '
            . 'WHEN r.diametro_lo IS NULL THEN r.diametro_lr '
            . 'WHEN r.diametro_lr <= r.diametro_lo THEN r.diametro_lr '
            . 'ELSE r.diametro_lo END';
    }

    private function consultaDiametros(array $filtros): Builder
    {
        $consulta = $this->instalacoes('rodeiro');
        $this->filtrarLocalizacao($consulta, $filtros, 'd');

        return $consulta
            ->whereRaw($this->menorDiametroSql() . ' < ?', [745])
            ->select([
                'f.codigo as frota', 't.codigo as trem', 'c.codigo as carro',
                'e.numero as posicao', 'e.id as eixo_id', 'r.numero_serie',
                'r.diametro_lr', 'r.diametro_lo',
            ])
            ->selectRaw($this->menorDiametroSql() . ' as menor_diametro');
    }

    private function consultaPeriodicidades(string $tipo, array $filtros): Builder
    {
        $consulta = $this->instalacoes($tipo);
        $this->filtrarLocalizacao($consulta, $filtros, 'r');

        return $consulta
            ->where('r.periodicidade', '>', 100)
            ->select([
                'f.codigo as frota', 't.codigo as trem', 'c.codigo as carro',
                'e.numero as posicao', 'e.id as eixo_id',
                'r.numero_serie', 'r.periodicidade',
            ]);
    }
}
