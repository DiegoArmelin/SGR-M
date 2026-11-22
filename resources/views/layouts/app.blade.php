<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'SGR-M')</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fa;
            color: #263445;
        }

        /* Navegação superior responsiva com menus acessíveis via <details>.
           O CSS é isolado no prefixo "nav-" para não afetar as demais telas. */
        .navbar {
            background: linear-gradient(115deg, #102841 0%, #17365d 65%, #214c78 100%);
            color: #fff;
            box-shadow: 0 4px 18px rgba(9, 28, 50, .14);
            position: relative;
            z-index: 50;
        }

        .nav-inner {
            width: 100%;
            max-width: 1500px;
            min-height: 74px;
            margin: 0 auto;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
        }

        .nav-brand-symbol {
            display: grid;
            place-items: center;
            width: 39px;
            height: 39px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.21);
            border-radius: 11px;
            font-weight: 800;
            font-size: 17px;
            letter-spacing: -.5px;
        }

        .nav-brand-text strong {
            display: block;
            font-size: 19px;
            letter-spacing: .4px;
            line-height: 1.2;
        }

        .nav-brand-text small {
            display: block;
            font-size: 10px;
            letter-spacing: .1px;
            color: #b6cce4;
            margin-top: 3px;
        }

        .nav-links {
            display: flex;
            flex: 1;
            gap: 3px;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }

        .nav-link, .nav-menu-trigger {
            font: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
            color: #dce8f5;
            background: transparent;
            border: 1px solid transparent;
            text-decoration: none;
            border-radius: 9px;
            padding: 10px 13px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }

        .nav-link:hover, .nav-menu-trigger:hover,
        .nav-menu[open] > .nav-menu-trigger {
            color: #fff;
            background: rgba(255,255,255,.12);
        }

        .nav-link.active, .nav-menu-trigger.active {
            background: #eaf3ff;
            color: #17365d;
        }

        .nav-link.active:hover, .nav-menu-trigger.active:hover {
            color: #17365d;
            background: #fff;
        }

        .nav-menu {
            position: relative;
            flex-shrink: 0;
        }

        .nav-menu-trigger { list-style: none; }
        .nav-menu-trigger::-webkit-details-marker { display: none; }

        .nav-chevron {
            display: inline-block;
            border-right: 1.7px solid currentColor;
            border-bottom: 1.7px solid currentColor;
            width: 6px;
            height: 6px;
            transform: rotate(45deg) translateY(-2px);
            transition: transform .15s ease;
            margin-left: 2px;
        }

        .nav-menu[open] .nav-chevron { transform: rotate(225deg) translateY(-2px); }

        .nav-dropdown {
            position: absolute;
            top: calc(100% + 9px);
            left: 0;
            width: 280px;
            padding: 8px;
            border: 1px solid #dce4ee;
            border-radius: 13px;
            background: #fff;
            color: #263445;
            box-shadow: 0 16px 35px rgba(11, 31, 56, .18);
        }

        .nav-dropdown-right { left: auto; right: 0; }

        .nav-dropdown-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.1px;
            text-transform: uppercase;
            padding: 10px 11px 7px;
            color: #708198;
        }

        .nav-dropdown a {
            display: flex;
            align-items: flex-start;
            flex-direction: column;
            gap: 3px;
            padding: 10px 11px;
            color: #203953;
            text-decoration: none;
            font-size: 13px;
            border-radius: 8px;
            font-weight: 600;
        }

        .nav-dropdown a small {
            color: #77869a;
            font-size: 11px;
            font-weight: 400;
        }

        .nav-dropdown a:hover, .nav-dropdown a.active {
            background: #edf5ff;
            color: #164875;
        }

        .nav-dropdown-divider {
            height: 1px;
            background: #e5eaf1;
            margin: 7px 3px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            gap: 10px;
            margin-left: auto;
        }

        .nav-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.18);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 12px;
        }

        .user-info { max-width: 125px; line-height: 1.2; }
        .user-info strong {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
        }
        .user-info small { display: block; color: #b6cce4; margin-top: 3px; font-size: 11px; }

        .logout-button {
            background: rgba(255,255,255,.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,.2);
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .logout-button:hover { background: #b42332; border-color: #b42332; }

        .nav-mobile-toggle {
            display: none;
            font: inherit;
            color: #fff;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 9px;
            background: rgba(255,255,255,.1);
            padding: 9px 12px;
            cursor: pointer;
            margin-left: auto;
        }

        .nav-link:focus-visible, .nav-menu-trigger:focus-visible,
        .logout-button:focus-visible, .nav-mobile-toggle:focus-visible,
        .brand:focus-visible {
            outline: 3px solid #fbbf24;
            outline-offset: 3px;
        }

        @media (max-width: 1170px) {
            .nav-inner { flex-wrap: wrap; gap: 8px 15px; }
            .nav-mobile-toggle { display: inline-flex; }
            .nav-links {
                display: none;
                flex-basis: 100%;
                order: 3;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                padding: 10px 0 5px;
                border-top: 1px solid rgba(255,255,255,.2);
            }
            .nav-links.nav-expanded { display: flex; }
            .nav-link, .nav-menu-trigger { justify-content: flex-start; width: 100%; }
            .nav-menu { width: 100%; }
            .nav-menu-trigger { justify-content: space-between; }
            .nav-dropdown, .nav-dropdown-right {
                position: static;
                width: 100%;
                margin: 6px 0 5px;
                box-shadow: none;
            }
            .nav-user { margin-left: 0; }
        }

        @media (max-width: 530px) {
            .nav-inner { padding: 10px 15px; }
            .nav-brand-text small { display: none; }
            .nav-user { order: 4; flex-basis: 100%; justify-content: flex-end; padding-bottom: 5px; }
            .user-info { max-width: 160px; }
        }

        .container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .page-title {
            margin-top: 0;
            color: #17365d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        .btn-primary {
            background: #17365d;
            border: none;
            border-radius: 6px;
            padding: 12px 18px;
            color: white;
            font-size: 15px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #254f80;
        }

        .btn-block {
            width: 100%;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .field-error {
            color: #b91c1c;
            font-size: 13px;
            margin-top: 6px;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #17365d;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #64748b;
            line-height: 1.5;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(230px, 1fr)
            );
            gap: 20px;
            margin-top: 25px;
        }

        .module-card h3 {
            margin-top: 0;
            color: #17365d;
        }

        .module-card p {
            color: #64748b;
            line-height: 1.5;
        }

        .module-status {
            display: inline-block;
            background: #e2e8f0;
            color: #475569;
            border-radius: 5px;
            padding: 6px 10px;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            padding: 25px;
        }

        @media (max-width: 600px) {
            .container { margin-top: 20px; }
        }
    </style>

    @stack('styles')
</head>

<body>

    @auth
        @php
            $administrador = auth()->user()->temPerfil('administrador');
            $consultaAtiva = request()->routeIs('equipamentos.index', 'equipamentos.hierarquica', 'equipamentos.historico');
            $administracaoAtiva = $administrador && request()->routeIs(
                'frotas.*', 'trens.*', 'importacoes.*'
            );
        @endphp

        <header class="navbar">
            <div class="nav-inner">
                <a class="brand" href="{{ route('dashboard') }}" aria-label="SGR-M — Página inicial">
                    <span class="nav-brand-symbol" aria-hidden="true">SG</span>
                    <span class="nav-brand-text">
                        <strong>SGR-M</strong>
                        <small>Gestão de Rodeiros e Motores</small>
                    </span>
                </a>

                <button type="button" class="nav-mobile-toggle" id="nav-toggle"
                    aria-controls="nav-main-links" aria-expanded="false">
                    ☰ Menu
                </button>

                <nav class="nav-links" id="nav-main-links" aria-label="Navegação principal">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        Início
                    </a>

                    <details class="nav-menu">
                        <summary class="nav-menu-trigger {{ $consultaAtiva ? 'active' : '' }}">
                            Consultas <span class="nav-chevron" aria-hidden="true"></span>
                        </summary>
                        <div class="nav-dropdown">
                            <div class="nav-dropdown-title">Equipamentos</div>
                            <a href="{{ route('equipamentos.hierarquica') }}"
                                class="{{ request()->routeIs('equipamentos.hierarquica') ? 'active' : '' }}">
                                Consulta hierárquica
                                <small>Frota, trem, carro e eixo</small>
                            </a>
                            <a href="{{ route('equipamentos.index') }}"
                                class="{{ request()->routeIs('equipamentos.index') ? 'active' : '' }}">
                                Consulta por número de série
                                <small>Rodeiros e motores cadastrados</small>
                            </a>
                        </div>
                    </details>

                    <a href="{{ route('pendencias.index') }}"
                        class="nav-link {{ request()->routeIs('pendencias.*') ? 'active' : '' }}">
                        Pendências
                    </a>
                    <a href="{{ route('ranking.index') }}"
                        class="nav-link {{ request()->routeIs('ranking.*') ? 'active' : '' }}">
                        Ranking
                    </a>
                    <a href="{{ route('dashboard.gerencial') }}"
                        class="nav-link {{ request()->routeIs('dashboard.gerencial', 'dashboard.historico*') ? 'active' : '' }}">
                        Indicadores
                    </a>

                    @if($administrador)
                        <details class="nav-menu">
                            <summary class="nav-menu-trigger {{ $administracaoAtiva ? 'active' : '' }}">
                                Administração <span class="nav-chevron" aria-hidden="true"></span>
                            </summary>
                            <div class="nav-dropdown nav-dropdown-right">
                                <div class="nav-dropdown-title">Cadastros</div>
                                <a href="{{ route('frotas.index') }}"
                                    class="{{ request()->routeIs('frotas.*') ? 'active' : '' }}">
                                    Frotas
                                    <small>Consultar, incluir e editar frotas</small>
                                </a>
                                <a href="{{ route('trens.index') }}"
                                    class="{{ request()->routeIs('trens.*') ? 'active' : '' }}">
                                    Trens
                                    <small>Consultar, incluir e editar trens</small>
                                </a>
                                <a href="{{ route('equipamentos.index') }}">
                                    Equipamentos cadastrados
                                    <small>Consulta de rodeiros e motores por série</small>
                                </a>
                                <div class="nav-dropdown-divider"></div>
                                <div class="nav-dropdown-title">Atualização de dados</div>
                                <a href="{{ route('importacoes.periodicidade.create') }}"
                                    class="{{ request()->routeIs('importacoes.*') ? 'active' : '' }}">
                                    Importar condições (CSV)
                                    <small>Diâmetros e periodicidades de rodeiros e motores</small>
                                </a>
                            </div>
                        </details>
                    @endif
                </nav>

                <div class="nav-user">
                    <div class="nav-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="user-info">
                        <strong title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</strong>
                        <small>{{ ucfirst(auth()->user()->perfil) }}</small>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-button">Sair</button>
                    </form>
                </div>
            </div>
        </header>
    @endauth

    @yield('content')


    @auth
        <script>
            // Menu mobile acessível; links e detalhes funcionam sem JS.
            (() => {
                const botao = document.getElementById('nav-toggle');
                const links = document.getElementById('nav-main-links');
                if (!botao || !links) return;

                botao.addEventListener('click', () => {
                    const aberto = !links.classList.contains('nav-expanded');
                    links.classList.toggle('nav-expanded', aberto);
                    botao.setAttribute('aria-expanded', String(aberto));
                });

                // Um dropdown aberto de cada vez; Escape fecha o menu.
                const menus = links.querySelectorAll('details.nav-menu');
                menus.forEach(menu => menu.addEventListener('toggle', () => {
                    if (menu.open) menus.forEach(outro => {
                        if (outro !== menu) outro.open = false;
                    });
                }));
                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        menus.forEach(menu => menu.open = false);
                        links.classList.remove('nav-expanded');
                        botao.setAttribute('aria-expanded', 'false');
                    }
                });
                document.addEventListener('click', event => {
                    if (!event.target.closest('.nav-menu')) {
                        menus.forEach(menu => menu.open = false);
                    }
                });
            })();
        </script>
    @endauth

    @stack('scripts')

</body>
</html>
