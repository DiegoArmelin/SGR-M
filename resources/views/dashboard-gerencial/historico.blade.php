@extends('layouts.app')
@section('title','Histórico de substituições — SGR-M')
@push('styles')
@include('dashboard-gerencial.partials.historico-estilos')
<style>
.hg-tabs{display:flex;gap:6px;margin-bottom:24px;border-bottom:2px solid #cbd5e1}
.hg-tabs a{padding:13px 22px;text-decoration:none;color:#475569;font-weight:bold}
.hg-tabs .selected{background:#eff6ff;border-bottom:3px solid #2563eb;color:#17365d}
.hg-filters{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;align-items:end}
.hg-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:18px 0}
.hg-button{display:inline-block;background:#17365d;color:#fff;padding:12px 16px;text-decoration:none;border-radius:6px}
.hg-summaries{display:flex;gap:12px;flex-wrap:wrap;margin:20px 0}
.hg-summary{flex:1;min-width:120px;background:#eff6ff;border:1px solid #bfdbfe;padding:14px;border-radius:8px}
.hg-summary strong{display:block;font-size:27px;color:#17365d}
@media(max-width:840px){.hg-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:500px){.hg-filters{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Indicadores do SGR-M</h1>
        <nav class="hg-tabs" aria-label="Abas de indicadores">
            <a href="{{ route('dashboard.gerencial') }}">Visão geral</a>
            <a href="{{ route('dashboard.historico') }}" class="selected" aria-current="page">Histórico de substituições</a>
        </nav>
        <p>Consulte o histórico real ou fictício registrado, conforme filtros de período, frota e defeito.</p>

        <form method="GET" action="{{ route('dashboard.historico') }}" class="hg-filters">
            <div><label class="form-label" for="inicio">Data inicial</label>
                <input class="form-control" type="date" name="inicio" id="inicio" value="{{ $filtros['inicio'] ?? '' }}"></div>
            <div><label class="form-label" for="fim">Data final</label>
                <input class="form-control" type="date" name="fim" id="fim" value="{{ $filtros['fim'] ?? '' }}"></div>
            <div><label class="form-label" for="frota">Frota</label>
                <select class="form-control" name="frota" id="frota">
                    <option value="">Todas</option>
                    @foreach($frotas as $frota)
                        <option value="{{ $frota->codigo }}" @selected(($filtros['frota'] ?? '') === $frota->codigo)>{{ $frota->codigo }}</option>
                    @endforeach
                </select></div>
            <div><label class="form-label" for="motivo_id">Defeito / motivo da troca</label>
                <select class="form-control" name="motivo_id" id="motivo_id">
                    <option value="">Todos</option>
                    @foreach($motivosCatalogo as $motivo)
                        <option value="{{ $motivo->id }}" @selected(($filtros['motivo_id'] ?? '') == $motivo->id)>{{ ucfirst($motivo->tipo_equipamento) }} — {{ $motivo->descricao }}</option>
                    @endforeach
                </select></div>
            <div class="hg-actions">
                <button class="btn-primary" type="submit">Filtrar</button>
                <a href="{{ route('dashboard.historico') }}">Limpar filtros</a>
            </div>
        </form>

        <div class="hg-actions">
            {{-- Os filtros são transmitidos para uma página de impressão.
                 Navegador: Imprimir > Salvar como PDF, preservando os gráficos. --}}
            <a class="hg-button" target="_blank" rel="noopener"
                href="{{ route('dashboard.historico.imprimir', $filtros) }}">Exportar PDF</a>
            <span class="hg-muted">Abre a versão para impressão; escolha “Salvar como PDF” no navegador.</span>
        </div>

        <div class="hg-summaries">
            <div class="hg-summary"><small>Substituições no período</small><strong>{{ $dados['total'] }}</strong></div>
            <div class="hg-summary"><small>Rodeiros</small><strong>{{ $dados['totais']['rodeiro'] }}</strong></div>
            <div class="hg-summary"><small>Motores</small><strong>{{ $dados['totais']['motor'] }}</strong></div>
        </div>
        @include('dashboard-gerencial.partials.historico-graficos')
        <p class="hg-muted">Os gráficos mensais mostram os últimos seis meses até a data final selecionada (ou até o mês atual). O total dos cards corresponde ao período filtrado, que pode ser maior que seis meses.</p>
    </section>
</main>
@endsection