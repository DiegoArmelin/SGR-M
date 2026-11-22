@php
    $maxMes = max(1, ...array_map(fn ($mes) => max($mes['rodeiro'], $mes['motor']), $dados['meses']));
    $maxFrota = max(1, ...array_map(fn ($v) => $v['rodeiro']+$v['motor'], array_values($dados['frotas'] ?: [['rodeiro'=>0,'motor'=>0]])));
    $cores = ['#2563eb','#ea580c','#16a34a','#7c3aed','#dc2626','#0891b2','#ca8a04','#64748b','#be185d','#0f766e'];
    $totalMotivos = array_sum($dados['motivos']);
    $acumulado = 0;
@endphp

<section class="hg-section">
    <h2>Substituições mensais — últimos seis meses</h2>
    <p class="hg-muted">Agrupamento pela data de retirada; filtros de data, frota e motivo são aplicados em conjunto.</p>
    <div class="hg-legend"><span><b class="hg-dot" style="background:#2563eb"></b> Rodeiros</span><span><b class="hg-dot" style="background:#ea580c"></b> Motores</span></div>
    <div class="hg-bars" role="img" aria-label="Gráfico de barras de substituições de rodeiros e motores por mês">
        @foreach($dados['meses'] as $mes)
            <div class="hg-bar-group">
                <div class="hg-bar-columns">
                    <div class="hg-bar hg-rod" style="height:{{ round(100*$mes['rodeiro']/$maxMes,1) }}%" title="{{ $mes['rodeiro'] }} rodeiros — {{ $mes['rotulo'] }}"></div>
                    <div class="hg-bar hg-mot" style="height:{{ round(100*$mes['motor']/$maxMes,1) }}%" title="{{ $mes['motor'] }} motores — {{ $mes['rotulo'] }}"></div>
                </div>
                <div class="hg-bar-label">{{ $mes['rotulo'] }}</div>
                <div class="hg-muted">{{ $mes['rodeiro'] }} / {{ $mes['motor'] }}</div>
            </div>
        @endforeach
    </div>
    <p class="hg-muted">Rodeiros / motores, respectivamente. Meses sem ocorrências permanecem visíveis.</p>
</section>

<section class="hg-section">
    <h2>Motivos das substituições</h2>
    <p class="hg-muted">Distribuição dos motivos no período filtrado (rodeiros e motores).</p>
    <div class="hg-donut-layout">
        <svg viewBox="0 0 240 240" class="hg-donut" role="img" aria-label="Gráfico de rosca com distribuição percentual dos motivos">
            <circle cx="120" cy="120" r="78" stroke="#e2e8f0" stroke-width="34" fill="none"/>
            @foreach($dados['motivos'] as $motivo => $n)
                @php
                    $parte = $totalMotivos > 0 ? ($n/$totalMotivos)*100 : 0;
                    $indice = $loop->index;
                    $cor = $cores[$indice % count($cores)];
                @endphp
                <circle cx="120" cy="120" r="78" fill="none" stroke="{{ $cor }}" stroke-width="34"
                    stroke-dasharray="{{ round($parte*4.90088,3) }} {{ round((100-$parte)*4.90088,3) }}"
                    stroke-dashoffset="{{ round(-$acumulado*4.90088,3) }}"
                    transform="rotate(-90 120 120)">
                    <title>{{ $motivo }}: {{ $n }} ({{ number_format($parte,1,',','.') }}%)</title>
                </circle>
                @php $acumulado += $parte; @endphp
            @endforeach
            <text x="120" y="113" text-anchor="middle" font-size="26" font-weight="700" fill="#17365d">{{ $totalMotivos }}</text>
            <text x="120" y="132" text-anchor="middle" font-size="12" fill="#475569">substituições</text>
        </svg>
        <div class="hg-motivos">
            @forelse($dados['motivos'] as $motivo => $n)
                <div class="hg-motivo-item">
                    <span class="hg-dot" style="background:{{ $cores[$loop->index % count($cores)] }}"></span>
                    <span>{{ $motivo }}</span>
                    <strong>{{ $n }} · {{ $totalMotivos ? number_format(100*$n/$totalMotivos,1,',','.') : '0,0' }}%</strong>
                </div>
            @empty
                <p>Nenhuma substituição no período selecionado.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="hg-section">
    <h2>Substituições por frota</h2>
    <p class="hg-muted">Comparativo de rodeiros e motores retirados por frota, dentro do período filtrado.</p>
    <div class="hg-frotas">
        @forelse($dados['frotas'] as $codigo => $quantidades)
            <div class="hg-frota-row">
                <strong>Frota {{ $codigo }}</strong>
                <div class="hg-frota-tracks">
                    <div class="hg-track-row">
                        <small>Rodeiros · {{ $quantidades['rodeiro'] }}</small>
                        <div class="hg-track"><div style="width:{{ round(100*$quantidades['rodeiro']/$maxFrota,1) }}%;background:#2563eb"></div></div>
                    </div>
                    <div class="hg-track-row">
                        <small>Motores · {{ $quantidades['motor'] }}</small>
                        <div class="hg-track"><div style="width:{{ round(100*$quantidades['motor']/$maxFrota,1) }}%;background:#ea580c"></div></div>
                    </div>
                </div>
            </div>
        @empty
            <p>Nenhuma frota apresenta substituições com esses filtros.</p>
        @endforelse
    </div>
</section>