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
            <p class="rank-small"><strong>{{ $totalPendencias }}</strong> pendência(s) com os filtros aplicados. Exibindo até 10.</p>
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
                            @if($item->observacoes)<div class="rank-small">{{ $item->observacoes }}</div>@endif
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
                <div class="rank-card"><strong>{{ $totalDiametros }}</strong><small>Diâmetros abaixo de 745 mm</small></div>
                <div class="rank-card"><strong>{{ $diametrosCriticos }}</strong><small>Críticos — menor ou igual a 742 mm</small></div>
            </div>
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
                        <td class="rank-action"><a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item->eixo_id,'tipo'=>'rodeiro']) }}">Consultar substituição</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8">Nenhum rodeiro abaixo de 745 mm com estes filtros.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="rank-section" id="periodicidades">
            <h2>3. Periodicidade dos rodeiros</h2>
            <p class="rank-description">Periodicidade vencida (maior ou igual a 100%), ordenada do maior percentual para o menor.</p>
            @include('ranking.partials.filtros', ['prefixo'=>'r','mostrarPrioridade'=>false])
            <p class="rank-small"><strong>{{ $totalPeriodicidades }}</strong> rodeiro(s) com periodicidade vencida. Exibindo os 10 maiores percentuais.</p>
            <div class="rank-scroll"><table class="rank-table">
                <thead><tr><th>Posição no ranking</th><th>Trem</th><th>Carro–posição</th><th>Série</th><th>Periodicidade</th><th>Ações</th></tr></thead>
                <tbody>
                @forelse($periodicidades as $item)
                    <tr>
                        <td><strong>{{ $loop->iteration }}º</strong></td>
                        <td>{{ $item->trem }}</td><td><strong>{{ $item->carro }}-{{ $item->posicao }}</strong></td>
                        <td>{{ $item->numero_serie }}</td>
                        <td><span class="rank-badge rank-alta">{{ number_format((float)$item->periodicidade,2,',','.') }}%</span></td>
                        <td class="rank-action"><a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item->eixo_id,'tipo'=>'rodeiro']) }}">Consultar substituição</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nenhum rodeiro com periodicidade a partir de 100%.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
        @endif

        <p class="rank-small">Os rankings apresentam apenas equipamentos instalados. Os indicadores são auxiliares e não substituem avaliação técnica.</p>
    </section>
</main>
@endsection

@push('scripts')
<script>
    // Atualiza as opções de carro quando o trem muda, independentemente
    // em cada tabela. Sem JavaScript, o servidor continua validando os filtros.
    document.querySelectorAll('.rank-filters').forEach(form => {
        const trem = form.querySelector('.rank-trem');
        const carro = form.querySelector('.rank-carro');
        const atualizarCarros = (limpar) => {
            if (limpar) carro.value = '';
            const codigoTrem = trem.value;
            Array.from(carro.options).forEach(opcao => {
                if (!opcao.value) return;
                opcao.hidden = codigoTrem !== '' && opcao.dataset.trem !== codigoTrem;
            });
        };
        trem.addEventListener('change', () => atualizarCarros(true));
        atualizarCarros(false);
    });
</script>
@endpush