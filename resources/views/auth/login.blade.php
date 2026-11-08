@extends('layouts.app')

@section('title', 'Login - SGR-M')

@section('content')

<div class="login-container">

    <div class="login-card">

        <div class="login-header">
            <h1>SGR-M</h1>

            <p>
                Sistema de Gestão de Rodeiros e Motores
            </p>
        </div>

        <div class="card">

            <h2 class="page-title" style="text-align: center;">
                Acesso ao Sistema
            </h2>

            @if ($errors->any())
                <div class="alert-error" role="alert">
                    Não foi possível realizar o login.
                    Verifique os dados informados.
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">

                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        placeholder="Digite seu e-mail"
                        autocomplete="username"
                        required
                        autofocus
                    >

                    @error('email')
                        <div class="field-error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        Senha
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >

                    @error('password')
                        <div class="field-error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn-primary btn-block">
                    Entrar
                </button>

            </form>

        </div>

        <div class="footer">
            SGR-M — Projeto Acadêmico
            <p>Fatec São Paulo | Grupo 10</p>
        </div>

    </div>

</div>

@endsection
