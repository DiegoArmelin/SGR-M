@extends('layouts.app')

@section('title', 'Dashboard - SGR-M')

@section('content')

<main class="container">

    <div class="card">

        <h1 class="page-title">
            Painel Principal
        </h1>

        <p>
            Bem-vindo ao Sistema de Gestão de Rodeiros e Motores,
            <strong>{{ auth()->user()->name }}</strong>.
        </p>

        <p>
            Perfil de acesso:
            <strong>
                {{ ucfirst(auth()->user()->perfil) }}
            </strong>
        </p>

        <p>
            Este painel permitirá acompanhar as condições
            dos equipamentos ferroviários, as pendências
            de manutenção e as prioridades de intervenção.
        </p>

    </div>

    <div class="dashboard-grid">

        <div class="card module-card">
            <h3>Cadastro de Equipamentos</h3>

            <p>
                Gerenciamento das frotas, trens, carros,
                eixos, rodeiros e motores.
            </p>

            <span class="module-status">
                Em desenvolvimento
            </span>
        </div>

        <div class="card module-card">
            <h3>Gestão de Pendências</h3>

            <p>
                Registro e acompanhamento das
                pendências dos equipamentos.
            </p>

            <span class="module-status">
                Pendente
            </span>
        </div>

        <div class="card module-card">
            <h3>Condições dos Equipamentos</h3>

            <p>
                Consulta das periodicidades e dos
                diâmetros dos rodeiros, incluindo
                atualizações por arquivos CSV.
            </p>

            <span class="module-status">
                Pendente
            </span>
        </div>

        <div class="card module-card">
            <h3>Priorização</h3>

            <p>
                Classificação dos equipamentos conforme
                as regras de negócio estabelecidas.
            </p>

            <span class="module-status">
                Pendente
            </span>
        </div>

        <div class="card module-card">
            <h3>Indicadores</h3>

            <p>
                Visualização de informações gerenciais
                e indicadores de manutenção.
            </p>

            <span class="module-status">
                Pendente
            </span>
        </div>

        <div class="card module-card">
            <h3>Controle de Acesso</h3>

            <p>
                Autenticação dos usuários e
                controle de permissões.
            </p>

            <span class="module-status">
                Em desenvolvimento
            </span>
        </div>

    </div>

    <footer class="footer">
        SGR-M — Laboratório de Engenharia de Software
        <p>Fatec São Paulo | Grupo 10</p>
    </footer>

</main>

@endsection
