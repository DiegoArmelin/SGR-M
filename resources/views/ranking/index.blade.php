@extends('layouts.app')
@section('title', 'Ranking de priorização — SGR-M')
@push('styles')
<style>
    .rank-tabs{display:flex;gap:0;border-bottom:2px solid #cbd5e1;margin:0 0 25px}
    .rank-tab{display:inline-block;padding:14px 26px;text-decoration:none;color:#475569;font-weight:700;border-bottom:3px solid transparent;margin-bottom:-2px}
    .rank-tab.active{color:#17365d;background:#eff6ff;border-bottom-color:#2563eb}
    .rank-section{margin:25px 0 36px;border:1px solid #e2e8f0;border-radius:9px;padding:20px}
    .rank-section h2{margin:0 0 7px;color:#17365d;font-size:21px}
    .rank-description{color:#64748b;font-size:14px}
    .rank-filters{display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin:18px 0}
    .rank-filters>div{flex:1;min-width:150px}
    .rank-filters .rank-filter-actions{display:flex;gap:12px;align-items:center;min-width:max-content}
    .rank-scroll{overflow-x:auto}
    .rank-table{width:100%;border-collapse:collapse;min-width:850px;text-align:left;font-size:13px}
    .rank-table th,.rank-table td{padding:11px;border-bottom:1px solid #e2e8f0;vertical-align:top}
    .rank-table th{background:#eef2f7;color:#17365d;white-space:nowrap}
    .rank-table tbody tr:hover{background:#f8fafc}
    .rank-badge{display:inline-block;padding:5px 9px;border-radius:99px;font-weight:700;white-space:nowrap;font-size:12px}
    .rank-critica,.rank-diametro-critico{background:#fee2e2;color:#991b1b}
    .rank-alta{background:#ffedd5;color:#9a3412}
    .rank-media{background:#fef9c3;color:#854d0e}
    .rank-baixa{background:#dcfce7;color:#166534}
    .rank-regular{background:#e0f2fe;color:#075985}
    .rank-cards{display:flex;gap:12px;flex-wrap:wrap;margin:16px 0}
    .rank-card{min-width:175px;flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:14px}
    .rank-card strong{font-size:27px;display:block;color:#17365d}
    .rank-card small{color:#64748b}
    /* Cada faixa possui cor semântica e barra sobre o parque instalado. */
    .rank-card {border-left:4px solid var(--rc,#64748b);background:var(--bg,#f8fafc)}
    .rank-card-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .rank-card-number{font-weight:800;font-size:27px;color:#17365d}
    .rank-card-percent{font-size:16px;font-weight:800;color:var(--rc,#334155)}
    .rank-card-sub{margin-top:7px;font-size:12px;color:#475569}
    .rank-track{height:7px;background:#e2e8f0;border-radius:50px;overflow:hidden;margin-top:12px}
    .rank-fill{height:100%;width:var(--pct,0%);background:var(--rc,#64748b);border-radius:50px}
    .rc-total{--rc:#2563eb;--bg:#eff6ff}
    .rc-critica{--rc:#dc2626;--bg:#fff1f2}
    .rc-alta{--rc:#ea580c;--bg:#fff7ed}
    .rc-media{--rc:#ca8a04;--bg:#fefce8}
    .rc-baixa{--rc:#16a34a;--bg:#f0fdf4}
    .rc-atencao{--rc:#d97706;--bg:#fffbeb}
    .rank-legend{font-size:12px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;margin:12px 0}
    .rank-legend strong{color:#1e293b}
    .rank-small{font-size:12px;color:#64748b}
    .rank-action{white-space:nowrap}
    @media(max-width:650px){.rank-tab{flex:1;text-align:center;padding:12px 7px}.rank-section{padding:12px}}
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Ranking de priorização</h1>

        {{-- A aba é um filtro GET: não modifica o banco nem as instalações. --}}
        <nav class="rank-tabs" aria-label="Tipo de equipamento">
            <a class="rank-tab {{ $tipo === 'rodeiro' ? 'active' : '' }}"
               @if($tipo === 'rodeiro') aria-current="page" @endif
               href="{{ route('ranking.index', ['tipo'=>'rodeiro']) }}">Rodeiros</a>
            <a class="rank-tab {{ $tipo === 'motor' ? 'active' : '' }}"
               @if($tipo === 'motor') aria-current="page" @endif
               href="{{ route('ranking.index', ['tipo'=>'motor']) }}">Motores</a>
        </nav>

        <section class="rank-section" id="pendencias">
            <h2>1. Pendências técnicas — {{ $tipo === 'rodeiro' ? 'Rodeiros' : 'Motores' }}</h2>
            <p class="rank-description">Pendências abertas, ordenadas por prioridade: crítica, alta, média e baixa.</p>
            @include('ranking.partials.filtros', ['prefixo'=>'p','mostrarPrioridade'=>true])

            {{-- Contagem de equipamentos distintos: várias ocorrências não
                 aumentam artificialmente a porcentagem da frota afetada. --}}
            @php
                $pct = fn ($valor, $base) => $base > 0 ? round(100*$valor/$base,1) : 0;
                $pAfetados = $pct($equipamentosComPendencia,$instaladosPendencias);
            @endphp
            <div class="rank-cards">
                <div class="rank-card rc-total">
                    <small>Equipamentos com pendências</small>
                    <div class="rank-card-top"><span class="rank-card-number">{{ $equipamentosComPendencia }}</span><span class="rank-card-percent">{{ number_format($pAfetados,1,',','.') }}%</span></div>
                    <div class="rank-card-sub">de {{ $instaladosPendencias }} instalados · {{ $totalPendenciasGeral }} ocorrências abertas</div>
                    <div class="rank-track"><div class="rank-fill" style="--pct:{{ $pAfetados }}%"></div></div>
                </div>
                @foreach(['critica'=>['Críticas','rc-critica'],'alta'=>['Altas','rc-alta'],'media'=>['Médias','rc-media'],'baixa'=>['Baixas','rc-baixa']] as $nivel=>[$rotulo,$classe])
                    @php
                        $quantidade = $pendenciasEquipamentosPorPrioridade[$nivel] ?? 0;
                        $porcentagem = $pct($quantidade,$instaladosPendencias);
                    @endphp
                    <div class="rank-card {{ $classe }}">
                        <small>{{ $rotulo }}</small>
                        <div class="rank-card-top"><span class="rank-card-number">{{ $quantidade }}</span><span class="rank-card-percent">{{ number_format($porcentagem,1,',','.') }}%</span></div>
                        <div class="rank-card-sub">de {{ $instaladosPendencias }} instalados · {{ $pendenciasPorPrioridade[$nivel] ?? 0 }} ocorrências</div>
                        <div class="rank-track"><div class="rank-fill" style="--pct:{{ $porcentagem }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="rank-legend"><strong>Legenda:</strong> % = equipamentos distintos com pendência ÷ equipamentos instalados na localização filtrada. Um equipamento pode ter várias pendências, inclusive em prioridades distintas. Os cards mostram todas as prioridades, mesmo quando a tabela é filtrada por uma delas.</p>
            <p class="rank-small">Exibindo as 10 primeiras pendências de acordo com a prioridade selecionada.</p>
            <div class="rank-scroll"><table class="rank-table">
                <thead><tr>
                    <th>Prioridade</th><th>Trem</th><th>Carro–posição</th><th>Série</th>
                    <th>Motivo</th><th>Ações</th>
                </tr></thead>
                <tbody>
                @forelse($pendencias as $item)
                    <tr>
                        <td><span class="rank-badge rank-{{ $item->prioridade }}">{{ ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'][$item->prioridade] ?? $item->prioridade }}</span></td>
                        <td>{{ $item->trem }}</td>
                        <td><strong>{{ $item->carro }}-{{ $item->posicao }}</strong></td>
                        <td>{{ $item->numero_serie }}</td>
                        <td>{{ $item->motivo_catalogo ?? $item->descricao }}
                            {{-- Oculta apenas a observação técnica usada na carga fictícia. --}}
                            @if($item->observacoes && trim($item->observacoes) !== 'Carga acadêmica para testes de filtros e dashboard.')
                                <div class="rank-small">{{ $item->observacoes }}</div>
                            @endif
                        </td>
                        <td class="rank-action">
                            @if(auth()->user()->temPerfil('administrador','tecnico'))
                                <a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item->eixo_id,'tipo'=>$tipo,'origem'=>'pendencias']) }}">Substituir</a>
                            @else
                                <span class="rank-small">Somente consulta</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nenhuma pendência aberta para os filtros selecionados.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        @if($tipo === 'rodeiro')
        <section class="rank-section" id="diametros">
            <h2>2. Diâmetros dos rodeiros</h2>
            <p class="rank-description">Rodeiros instalados cujo menor diâmetro LR/LO é inferior a 745 mm, ordenados pelo menor diâmetro.</p>
            @include('ranking.partials.filtros', ['prefixo'=>'d','mostrarPrioridade'=>false])

            <div class="rank-cards">
                @foreach([['Abaixo de 745 mm',$totalDiametros,'rc-atencao'],['Críticos · até 742 mm',$diametrosCriticos,'rc-critica']] as [$rotulo,$quantidade,$classe])
                    @php $porcentagem=$pct($quantidade,$instaladosDiametros); @endphp
                    <div class="rank-card {{ $classe }}">
                        <small>{{ $rotulo }}</small>
                        <div class="rank-card-top"><span class="rank-card-number">{{ $quantidade }}</span><span class="rank-card-percent">{{ number_format($porcentagem,1,',','.') }}%</span></div>
                        <div class="rank-card-sub">de {{ $instaladosDiametros }} rodeiros instalados</div>
                        <div class="rank-track"><div class="rank-fill" style="--pct:{{ $porcentagem }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="rank-legend"><strong>Legenda:</strong> percentual sobre os rodeiros instalados após os filtros desta tabela. Os críticos (≤742 mm) estão incluídos no total abaixo de 745 mm.</p>
            <p class="rank-small">Exibindo os 10 menores diâmetros com os filtros aplicados.</p>
            <div class="rank-scroll"><table class="rank-table">
                <thead><tr><th>Classificação</th><th>Trem</th><th>Carro–posição</th><th>Série</th><th>LR (mm)</th><th>LO (mm)</th><th>Menor (mm)</th><th>Ações</th></tr></thead>
                <tbody>
                @forelse($diametros as $item)
                    <tr>
                        <td><span class="rank-badge {{ (float)$item->menor_diametro <= 742 ? 'rank-diametro-critico' : 'rank-regular' }}">{{ (float)$item->menor_diametro <= 742 ? 'Crítico' : 'Atenção' }}</span></td>
                        <td>{{ $item->trem }}</td><td><strong>{{ $item->carro }}-{{ $item->posicao }}</strong></td>
                        <td>{{ $item->numero_serie }}</td>
                        <td>{{ $item->diametro_lr !== null ? number_format((float)$item->diametro_lr,2,',','.') : '—' }}</td>
                        <td>{{ $item->diametro_lo !== null ? number_format((float)$item->diametro_lo,2,',','.') : '—' }}</td>
                        <td><strong>{{ number_format((float)$item->menor_diametro,2,',','.') }}</strong></td>
                        <td class="rank-action"><a href="{{ route('equipamentos.historico', ['eixo'=>$item->eixo_id]) }}">Histórico</a>
                            @if(auth()->user()->temPerfil('administrador','tecnico'))
                                <div><a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item->eixo_id,'tipo'=>'rodeiro']) }}">Substituir</a></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Nenhum rodeiro abaixo de 745 mm com estes filtros.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        @endif

        <section class="rank-section" id="periodicidades">
            <h2>{{ $tipo === 'rodeiro' ? '3' : '2' }}. Periodicidade dos {{ $tipo === 'rodeiro' ? 'rodeiros' : 'motores' }}</h2>
            <p class="rank-description">Periodicidade vencida (superior a 100%), ordenada do maior percentual para o menor.</p>
            @include('ranking.partials.filtros', ['prefixo'=>'r','mostrarPrioridade'=>false])

            <div class="rank-cards">
                @foreach([['> 100%',$totalPeriodicidades,'rc-total'],['≥ 105%',$periodicidades105,'rc-atencao'],['≥ 110%',$periodicidades110,'rc-critica']] as [$rotulo,$quantidade,$classe])
                    @php $porcentagem=$pct($quantidade,$instaladosPeriodicidade); @endphp
                    <div class="rank-card {{ $classe }}">
                        <small>Periodicidade {{ $rotulo }}</small>
                        <div class="rank-card-top"><span class="rank-card-number">{{ $quantidade }}</span><span class="rank-card-percent">{{ number_format($porcentagem,1,',','.') }}%</span></div>
                        <div class="rank-card-sub">de {{ $instaladosPeriodicidade }} {{ $tipo === 'rodeiro' ? 'rodeiros' : 'motores' }} instalados</div>
                        <div class="rank-track"><div class="rank-fill" style="--pct:{{ $porcentagem }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="rank-legend"><strong>Legenda:</strong> porcentagem sobre os equipamentos instalados após os filtros de periodicidade. Faixas cumulativas: ≥110% também integra ≥105% e &gt;100%. Itens sem medição contam no total instalado.</p>
            <p class="rank-small">Exibindo os 10 maiores percentuais com os filtros aplicados.</p>
            <div class="rank-scroll"><table class="rank-table">
                <thead><tr><th>Posição no ranking</th><th>Trem</th><th>Carro–posição</th><th>Série</th><th>Periodicidade</th><th>Ações</th></tr></thead>
                <tbody>
                @forelse($periodicidades as $item)
                    <tr>
                        <td><strong>{{ $loop->iteration }}º</strong></td>
                        <td>{{ $item->trem }}</td><td><strong>{{ $item->carro }}-{{ $item->posicao }}</strong></td>
                        <td>{{ $item->numero_serie }}</td>
                        <td><span class="rank-badge rank-alta">{{ number_format((float)$item->periodicidade,2,',','.') }}%</span></td>
                        <td class="rank-action"><a href="{{ route('equipamentos.historico', ['eixo'=>$item->eixo_id]) }}">Histórico</a>
                            @if(auth()->user()->temPerfil('administrador','tecnico'))
                                <div><a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item->eixo_id,'tipo'=>$tipo]) }}">Substituir</a></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nenhum equipamento com periodicidade superior a 100%.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
        <p class="rank-small">Os rankings apresentam apenas equipamentos instalados. Os indicadores são auxiliares e não substituem avaliação técnica.</p>
    </section>
</main>
@endsection

@push('scripts')
<script>
    // Em cada formulário, frota restringe trens e carros; trem restringe
    // carros. A lógica é independente entre os três quadros da página.
    document.querySelectorAll('.rank-filters').forEach(form => {
        const frota = form.querySelector('.rank-frota');
        const trem = form.querySelector('.rank-trem');
        const carro = form.querySelector('.rank-carro');

        function ajustarTrens(limparDependentes = false) {
            if (limparDependentes) { trem.value = ''; carro.value = ''; }
            const f = frota.value;
            Array.from(trem.options).forEach(opcao => {
                if (!opcao.value) return;
                opcao.hidden = !!f && opcao.dataset.frota !== f;
            });
            ajustarCarros();
        }

        function ajustarCarros(limpar = false) {
            if (limpar) carro.value = '';
            const f = frota.value;
            const t = trem.value;
            Array.from(carro.options).forEach(opcao => {
                if (!opcao.value) return;
                opcao.hidden = (!!f && opcao.dataset.frota !== f)
                    || (!!t && opcao.dataset.trem !== t);
            });
        }

        frota.addEventListener('change', () => ajustarTrens(true));
        trem.addEventListener('change', () => ajustarCarros(true));
        ajustarTrens();
    });
</script>
@endpush