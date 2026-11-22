@extends('layouts.app')

@section('title', 'Início — SGR-M')

@push('styles')
<style>
    .home-hero {
        background: linear-gradient(120deg, #17365d 0%, #224d7a 70%, #33679b 100%);
        border-radius: 16px;
        padding: 30px 32px;
        color: #fff;
        box-shadow: 0 12px 28px rgba(20, 54, 88, .12);
        margin-bottom: 26px;
    }
    .home-hero h1 { margin: 0 0 8px; font-size: clamp(24px, 3vw, 33px); }
    .home-hero p { max-width: 720px; line-height: 1.6; margin: 0; color: #dbe9f7; }
    .home-subheading { margin: 27px 0 14px; font-size: 18px; color: #17365d; }
    .home-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .home-tile {
        text-decoration: none;
        color: inherit;
        background: #fff;
        border: 1px solid #e0e8f1;
        border-radius: 13px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        min-height: 212px;
        box-shadow: 0 3px 13px rgba(15, 40, 65, .035);
        transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    .home-tile:hover {
        transform: translateY(-3px);
        border-color: #8db2da;
        box-shadow: 0 10px 26px rgba(15, 40, 65, .10);
    }
    .home-tile:focus-visible { outline: 3px solid #2563eb; outline-offset: 3px; }
    .home-tile-icon {
        background: var(--tile-bg, #eff6ff);
        color: var(--tile-color, #20548a);
        border-radius: 11px;
        display: grid;
        place-items: center;
        width: 44px;
        height: 44px;
        font-size: 21px;
        font-weight: 700;
    }
    .home-tile h3 { margin: 0; font-size: 17px; line-height: 1.3; color: #17365d; }
    .home-tile p { margin: 0; color: #617185; line-height: 1.5; font-size: 13px; flex: 1; }
    .home-tile-action { font-size: 12px; font-weight: 800; color: #1e5d98; }
    .home-tile-secondary { min-height: 175px; }
    .home-footer { text-align: center; padding: 28px 0 8px; color: #708198; font-size: 12px; }
    @media (max-width: 920px) { .home-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) {
        .home-grid { grid-template-columns: 1fr; }
        .home-hero { padding: 23px; }
        .home-tile { min-height: 0; padding: 19px; }
    }
</style>
@endpush

@section('content')
<main class="container">
    <header class="home-hero">
        <h1>Bem-vindo ao SGR-M</h1>
        <p>Olá, {{ auth()->user()->name }}. Acesse as funcionalidades de gestão de rodeiros e motores a partir dos atalhos abaixo. Seu perfil de acesso é <strong>{{ ucfirst(auth()->user()->perfil) }}</strong>.</p>
    </header>

    <h2 class="home-subheading">Acesso rápido</h2>
    <div class="home-grid">
        <a href="{{ route('equipamentos.hierarquica') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#eaf3ff;--tile-color:#1d5c94" aria-hidden="true">⌕</span>
            <h3>Consulta hierárquica</h3>
            <p>Localize os equipamentos por frota, trem, carro e posição do eixo.</p>
            <span class="home-tile-action">Consultar equipamentos →</span>
        </a>

        <a href="{{ route('pendencias.index') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#fff1e6;--tile-color:#b45309" aria-hidden="true">!</span>
            <h3>Gestão de pendências</h3>
            <p>Consulte ocorrências abertas, prioridades, motivos e situações dos equipamentos.</p>
            <span class="home-tile-action">Visualizar pendências →</span>
        </a>

        <a href="{{ route('ranking.index') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#f3e8ff;--tile-color:#7c3aed" aria-hidden="true">↑</span>
            <h3>Ranking de priorização</h3>
            <p>Acompanhe pendências, diâmetros e periodicidades dos rodeiros e motores.</p>
            <span class="home-tile-action">Abrir ranking →</span>
        </a>

        <a href="{{ route('dashboard.gerencial') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#e0f2fe;--tile-color:#0369a1" aria-hidden="true">▥</span>
            <h3>Indicadores gerenciais</h3>
            <p>Visualize parque instalado, criticidades, condições e tendência das pendências.</p>
            <span class="home-tile-action">Visualizar indicadores →</span>
        </a>

        <a href="{{ route('dashboard.historico') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#dcfce7;--tile-color:#15803d" aria-hidden="true">↻</span>
            <h3>Histórico de substituições</h3>
            <p>Explore gráficos mensais, motivos das trocas, comparações por frota e relatório para PDF.</p>
            <span class="home-tile-action">Consultar histórico →</span>
        </a>

        <a href="{{ route('equipamentos.index') }}" class="home-tile">
            <span class="home-tile-icon" style="--tile-bg:#f1f5f9;--tile-color:#475569" aria-hidden="true">#</span>
            <h3>Consulta por série</h3>
            <p>Encontre rodeiros e motores pelo número de série e situação de cadastro.</p>
            <span class="home-tile-action">Pesquisar equipamento →</span>
        </a>

        <a href="{{ route('pendencias.create') }}" class="home-tile home-tile-secondary">
            <span class="home-tile-icon" style="--tile-bg:#fff1f2;--tile-color:#be123c" aria-hidden="true">+</span>
            <h3>Registrar pendência</h3>
            <p>Cadastre uma nova ocorrência vinculada ao equipamento instalado.</p>
            <span class="home-tile-action">Nova pendência →</span>
        </a>
    </div>

    @if(auth()->user()->isAdministrador())
        <h2 class="home-subheading">Administração</h2>
        <div class="home-grid">
            <a href="{{ route('frotas.index') }}" class="home-tile home-tile-secondary">
                <span class="home-tile-icon" aria-hidden="true">F</span>
                <h3>Cadastro de frotas</h3>
                <p>Consulte, adicione e gerencie as frotas cadastradas no sistema.</p>
                <span class="home-tile-action">Gerenciar frotas →</span>
            </a>
            <a href="{{ route('trens.index') }}" class="home-tile home-tile-secondary">
                <span class="home-tile-icon" aria-hidden="true">T</span>
                <h3>Cadastro de trens</h3>
                <p>Gerencie os trens vinculados às frotas cadastradas.</p>
                <span class="home-tile-action">Gerenciar trens →</span>
            </a>
            <a href="{{ route('importacoes.periodicidade.create') }}" class="home-tile home-tile-secondary">
                <span class="home-tile-icon" aria-hidden="true">⇪</span>
                <h3>Atualização por CSV</h3>
                <p>Importe as condições dos rodeiros e a periodicidade dos motores.</p>
                <span class="home-tile-action">Importar dados →</span>
            </a>
        </div>
    @endif

    <footer class="home-footer">
        SGR-M — Sistema de Gestão de Rodeiros e Motores · Projeto acadêmico de Engenharia de Software
    </footer>
</main>
@endsection
