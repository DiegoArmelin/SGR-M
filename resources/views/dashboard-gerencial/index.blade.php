@extends('layouts.app')
@section('title', 'Dashboard gerencial — SGR-M')

@push('styles')
<style>
    .dg-form {display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end}
    .dg-tabs{display:flex;gap:6px;margin:18px 0 25px;border-bottom:2px solid #cbd5e1}
    .dg-tabs a{padding:13px 20px;text-decoration:none;color:#475569;font-weight:bold}
    .dg-tabs .selected{background:#eff6ff;border-bottom:3px solid #2563eb;color:#17365d}
    .dg-section {margin-top:28px;border-top:1px solid #e2e8f0;padding-top:20px}
    .dg-heading {color:#17365d;margin:0 0 8px}
    .dg-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0}
    .dg-card {background:var(--dg-bg,#f8fafc);border:1px solid #e2e8f0;border-left:4px solid var(--dg-accent,#2563eb);border-radius:8px;padding:16px;min-width:0}
    .dg-card small {display:block;color:#475569;font-size:13px}
    .dg-card strong {display:block;color:#17365d;font-size:27px;margin:9px 0 4px}
    .dg-card .dg-sub {font-size:12px;color:#475569}
    .dg-track {height:7px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin-top:12px}
    .dg-fill {width:var(--dg-pct,0%);background:var(--dg-accent,#2563eb);height:100%}
    .dg-blue {--dg-bg:#eff6ff;--dg-accent:#2563eb}
    .dg-red {--dg-bg:#fef2f2;--dg-accent:#dc2626}
    .dg-orange {--dg-bg:#fff7ed;--dg-accent:#ea580c}
    .dg-amber {--dg-bg:#fffbeb;--dg-accent:#d97706}
    .dg-green {--dg-bg:#f0fdf4;--dg-accent:#16a34a}
    .dg-legend {font-size:12px;color:#475569;background:#f8fafc;border-radius:6px;padding:10px 12px}
    .dg-table {border-collapse:collapse;width:100%;text-align:left}
    .dg-table td,.dg-table th {padding:12px;border-bottom:1px solid #e2e8f0}
    .dg-table th {background:#f1f5f9;color:#17365d}
    @media(max-width:800px){.dg-form{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:520px){.dg-form{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Dashboard Gerencial do SGR-M</h1>
        <p>Visão consolidada do parque de equipamentos, pendências, condições e substituições. Os indicadores são calculados a partir do banco, sem modificar registros.</p>

        <nav class="dg-tabs" aria-label="Abas de indicadores">
            <a href="{{ route('dashboard.gerencial') }}" class="selected" aria-current="page">Visão geral</a>
            <a href="{{ route('dashboard.historico') }}">Histórico de substituições</a>
        </nav>
        <form method="GET" action="{{ route('dashboard.gerencial') }}" class="dg-form">
            <div><label class="form-label" for="frota">Frota</label>
                <select class="form-control" id="frota" name="frota">
                    <option value="">Todas</option>
                    @foreach($frotas as $frota)
                        <option value="{{ $frota->codigo }}" @selected(($filtros['frota'] ?? '') === $frota->codigo)>Frota {{ $frota->codigo }}</option>
                    @endforeach
                </select></div>
            <div><button class="btn-primary" type="submit">Filtrar</button>
                <a href="{{ route('dashboard.gerencial') }}" style="margin-left:6px">Limpar</a></div>
        </form>
        @if($errors->any())
            <div class="alert-error">@foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach</div>
        @endif

        @php
            // Denominadores independentes por tipo; evita dividir por zero.
            $pct = fn ($n, $base) => $base > 0 ? round($n*100/$base,1) : 0;
            $rodeiros = $dados['instalados']['rodeiro'];
            $motores = $dados['instalados']['motor'];
        @endphp

        <section class="dg-section">
            <h2 class="dg-heading">Parque instalado</h2>
            <div class="dg-grid">
                <div class="dg-card dg-blue" >
                    <small>Total de equipamentos instalados</small>
                    <strong>{{ number_format($rodeiros + $motores, 0, ',', '.') }}</strong>
                    <span class="dg-sub">{{ number_format($rodeiros, 0, ',', '.') }} rodeiros + {{ number_format($motores, 0, ',', '.') }} motores</span>
                </div>
                <div class="dg-card dg-green">
                    <small>Pendências encerradas nos últimos 30 dias</small>
                    <strong>{{ $dados['encerradas30'] }}</strong>
                    <span class="dg-sub">Período: {{ now()->subDays(29)->format('d/m/Y') }} a {{ now()->format('d/m/Y') }}</span>
                    <span class="dg-sub" style="display:block;margin-top:6px">Considera a data de resolução, não a substituição de equipamentos.</span>
                </div>
                @php
                    $movimento = $dados['tendencia'];
                    $direcao = $movimento['direcao'];
                    $corTendencia = $direcao === 'aumentando' ? 'dg-red' : ($direcao === 'diminuindo' ? 'dg-green' : 'dg-blue');
                    $seta = $direcao === 'aumentando' ? '↑' : ($direcao === 'diminuindo' ? '↓' : '→');
                @endphp
                <div class="dg-card {{ $corTendencia }}">
                    <small>Tendência do saldo de pendências (30 dias)</small>
                    <strong>{{ $seta }} {{ ucfirst($direcao) }}</strong>
                    <span class="dg-sub">Abertas: {{ $movimento['atual']['abertas'] }} · Encerradas: {{ $movimento['atual']['encerradas'] }}</span>
                    <span class="dg-sub" style="display:block;margin-top:6px">Saldo atual: {{ $movimento['atual']['saldo'] >= 0 ? '+' : '' }}{{ $movimento['atual']['saldo'] }} · Saldo anterior: {{ $movimento['anterior']['saldo'] >= 0 ? '+' : '' }}{{ $movimento['anterior']['saldo'] }}</span>
                    <span class="dg-sub" style="display:block;margin-top:6px">Variação líquida: {{ $movimento['variacao_saldo'] > 0 ? '+' : '' }}{{ $movimento['variacao_saldo'] }} em relação aos 30 dias anteriores</span>
                </div>
            </div>
            <p class="dg-legend"><strong>Como interpretar:</strong> aumento = mais aberturas líquidas que no período anterior; diminuição = menor crescimento ou maior redução do saldo; estabilidade = saldo líquido sem variação. O número de encerramentos pode ser zero quando ainda não há pendências efetivamente resolvidas no período.</p>
        </section>

        <section class="dg-section">
            <h2 class="dg-heading">Pendências técnicas abertas</h2>
            <div class="dg-grid">
                @foreach(['rodeiro'=>'Rodeiros','motor'=>'Motores'] as $tipo=>$rotulo)
                    @php
                        $q = $dados['pendencias'][$tipo];
                        $base = $dados['instalados'][$tipo];
                        $percentual = $pct($q['equipamentos'],$base);
                    @endphp
                    <div class="dg-card dg-blue">
                        <small>{{ $rotulo }} com pendências</small>
                        <strong>{{ $q['equipamentos'] }} <span style="font-size:15px">{{ number_format($percentual,1,',','.') }}%</span></strong>
                        <span class="dg-sub">{{ $q['total'] }} ocorrências abertas · {{ $base }} instalados</span>
                        <div class="dg-track"><div class="dg-fill" style="--dg-pct:{{ $percentual }}%"></div></div>
                    </div>
                    <div class="dg-card dg-red">
                        <small>{{ $rotulo }} — pendências críticas</small>
                        <strong>{{ $q['critica'] }}</strong>
                        <span class="dg-sub">Prioridade máxima</span>
                    </div>
                    <div class="dg-card dg-orange">
                        <small>{{ $rotulo }} — pendências altas</small>
                        <strong>{{ $q['alta'] }}</strong>
                        <span class="dg-sub">Alta prioridade</span>
                    </div>
                @endforeach
            </div>
            <p class="dg-legend"><strong>Legenda:</strong> a porcentagem considera equipamentos distintos com ao menos uma pendência aberta. As demais contagens são ocorrências e podem ser superiores ao número de equipamentos afetados.</p>
        </section>

        <section class="dg-section">
            <h2 class="dg-heading">Condições dos equipamentos instalados</h2>
            <div class="dg-grid">
                @foreach(['rodeiro'=>'Rodeiros','motor'=>'Motores'] as $tipo=>$rotulo)
                    @php $percentual=$pct($dados['periodicidade'][$tipo],$dados['instalados'][$tipo]); @endphp
                    <div class="dg-card dg-amber">
                        <small>{{ $rotulo }} com periodicidade ≥ 100%</small>
                        <strong>{{ $dados['periodicidade'][$tipo] }} <span style="font-size:15px">{{ number_format($percentual,1,',','.') }}%</span></strong>
                        <span class="dg-sub">de {{ $dados['instalados'][$tipo] }} instalados</span>
                        <div class="dg-track"><div class="dg-fill" style="--dg-pct:{{ $percentual }}%"></div></div>
                    </div>
                @endforeach
                @foreach([
                    ['Rodeiros com diâmetro < 745 mm',$dados['diametros']['atencao'],'dg-orange'],
                    ['Rodeiros com diâmetro ≤ 742 mm',$dados['diametros']['criticos'],'dg-red']
                ] as [$rotulo,$numero,$classe])
                    @php $percentual=$pct($numero,$rodeiros); @endphp
                    <div class="dg-card {{ $classe }}">
                        <small>{{ $rotulo }}</small>
                        <strong>{{ $numero }} <span style="font-size:15px">{{ number_format($percentual,1,',','.') }}%</span></strong>
                        <span class="dg-sub">de {{ $rodeiros }} instalados</span>
                        <div class="dg-track"><div class="dg-fill" style="--dg-pct:{{ $percentual }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="dg-legend"><strong>Legenda:</strong> a referência de diâmetro é a menor medição entre LR e LO. As faixas de diâmetro e periodicidade representam alertas, não pendências técnicas cadastradas.</p>
        </section>

        <section class="dg-section">
            <h2 class="dg-heading">Histórico de substituições</h2>
            <p>Gráficos mensais, motivos, comparação por frota e exportação em PDF estão disponíveis na aba de histórico.</p>
            <p><a href="{{ route('dashboard.historico', ['frota' => $filtros['frota'] ?? null]) }}">Abrir histórico e gráficos</a></p>
        </section>

        <p style="margin-top:20px"><a href="{{ route('ranking.index') }}">Consultar ranking detalhado</a> · <a href="{{ route('pendencias.index') }}">Consultar pendências</a></p>
    </section>
</main>
@endsection
