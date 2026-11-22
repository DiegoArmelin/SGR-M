@extends('layouts.app')
@section('title', 'Ranking de priorização — SGR-M')

@push('styles')
<style>
    .ranking-filtros { display:grid;grid-template-columns:repeat(2,minmax(170px,1fr)) auto;gap:12px;align-items:end; }
    .ranking-metricas { display:flex;flex-wrap:wrap;gap:10px;margin:18px 0; }
    .ranking-metrica { flex:1;min-width:110px;border:1px solid #e2e8f0;border-radius:8px;padding:12px; }
    .ranking-metrica strong { display:block;font-size:23px;color:#17365d; }
    .ranking-metrica small { color:#64748b; }
    .ranking-tabela { width:100%;border-collapse:collapse;text-align:left;min-width:1040px;font-size:13px; }
    .ranking-tabela th,.ranking-tabela td { padding:12px;border-bottom:1px solid #e2e8f0;vertical-align:top; }
    .ranking-tabela th { background:#eaf0f7;color:#17365d; }
    .nivel { display:inline-block;padding:5px 10px;border-radius:99px;font-weight:bold;white-space:nowrap; }
    .nivel-1 { background:#fee2e2;color:#991b1b; }
    .nivel-2 { background:#ffedd5;color:#9a3412; }
    .nivel-3 { background:#fef9c3;color:#854d0e; }
    .nivel-4 { background:#dcfce7;color:#166534; }
    .nivel-5 { background:#dbeafe;color:#1d4ed8; }
    .nivel-6 { background:#f3e8ff;color:#7e22ce; }
    .ranking-muted { color:#64748b;font-size:12px; }
    .ranking-alertas { margin:0;padding-left:18px; }
    .ranking-alertas li { margin-bottom:5px; }
    @media(max-width:760px) { .ranking-filtros { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Ranking de priorização dos equipamentos</h1>
        <p>Equipamentos instalados com pendências abertas ou alertas de condição. Cada equipamento aparece uma única vez, conforme sua maior prioridade.</p>

        {{-- Filtros GET não alteram os dados nem a prioridade persistida. --}}
        <form method="GET" action="{{ route('ranking.index') }}" class="ranking-filtros">
            <div class="form-group">
                <label for="frota" class="form-label">Frota</label>
                <select id="frota" name="frota" class="form-control">
                    <option value="">Todas</option>
                    @foreach($frotas as $frota)
                        <option value="{{ $frota->codigo }}" @selected(($filtros['frota'] ?? '') === $frota->codigo)>Frota {{ $frota->codigo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="tipo" class="form-label">Equipamento</label>
                <select id="tipo" name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="rodeiro" @selected(($filtros['tipo'] ?? '') === 'rodeiro')>Rodeiros</option>
                    <option value="motor" @selected(($filtros['tipo'] ?? '') === 'motor')>Motores</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-primary">Filtrar</button>
                <a style="margin-left:8px" href="{{ route('ranking.index') }}">Limpar</a>
            </div>
        </form>

        <div class="ranking-metricas">
            <div class="ranking-metrica"><strong>{{ $total }}</strong><small>Equipamentos priorizados</small></div>
            @foreach(['Crítica','Alta','Média','Baixa','Periodicidade','Diâmetro'] as $indice=>$rotulo)
                <div class="ranking-metrica"><strong>{{ $porNivel[$indice + 1] }}</strong><small>{{ $rotulo }}</small></div>
            @endforeach
        </div>

        <div style="overflow-x:auto">
            <table class="ranking-tabela">
                <thead>
                    <tr>
                        <th>Prioridade</th><th>Trem</th><th>Carro–posição</th>
                        <th>Equipamento / série</th><th>Periodicidade</th>
                        <th>Menor diâmetro</th><th>Motivos da priorização</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $item)
                        @php
                            $rotulos = [1=>'Crítica',2=>'Alta',3=>'Média',4=>'Baixa',5=>'Periodicidade',6=>'Diâmetro'];
                        @endphp
                        <tr>
                            <td><span class="nivel nivel-{{ $item['nivel'] }}">{{ $rotulos[$item['nivel']] }}</span></td>
                            <td><strong>{{ $item['trem'] }}</strong><div class="ranking-muted">Frota {{ $item['frota'] }}</div></td>
                            <td>{{ $item['carro'] }}-{{ $item['posicao'] }}</td>
                            <td>{{ $item['numero_serie'] }}<div class="ranking-muted">{{ ucfirst($item['tipo']) }}</div></td>
                            <td>{{ $item['periodicidade'] !== null ? number_format($item['periodicidade'],2,',','.') . '%' : '—' }}</td>
                            <td>{{ $item['menor_diametro'] !== null ? number_format($item['menor_diametro'],2,',','.') . ' mm' : '—' }}</td>
                            <td><ul class="ranking-alertas">
                                @foreach($item['alertas'] as $alerta)<li>{{ $alerta }}</li>@endforeach
                            </ul></td>
                            <td>
                                <a href="{{ route('equipamentos.hierarquica', [
                                    'frota_id' => $frotas->firstWhere('codigo', $item['frota'])?->id,
                                    'trem_id' => '', 'carro_id' => ''
                                ]) }}">Consultar frota</a>
                                @if(auth()->user()->temPerfil('administrador','tecnico'))
                                    <div><a href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$item['eixo_id'],'tipo'=>$item['tipo']]) }}">Substituir</a></div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:18px">Nenhum equipamento atende aos critérios nesta seleção.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px">{{ $registros->links() }}</div>
        <p class="ranking-muted">O ranking é apenas uma consulta: não altera pendências nem determina intervenções operacionais reais.</p>
    </section>
</main>
@endsection
