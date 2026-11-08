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

        .navbar {
            background: #17365d;
            color: white;
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            font-size: 21px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .user-info {
            font-size: 14px;
        }

        .logout-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-button:hover {
            background: #bb2d3b;
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
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                margin-top: 20px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @auth
        <header class="navbar">
            <div class="brand">SGR-M</div>

            <div class="navbar-right">
                <div class="user-info">
                    {{ auth()->user()->name }}
                    |
                    {{ ucfirst(auth()->user()->perfil) }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="logout-button">
                        Sair
                    </button>
                </form>
            </div>
        </header>
    @endauth

    @yield('content')

    @stack('scripts')

</body>
</html>
