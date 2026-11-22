@extends('layouts.app')
@section('title', 'Frotas — SGR-M')
@section('content')
{{-- Listagem de frotas: consulta permitida a todos os perfis autenticados. --}}
<main class="container"><div class="card">
    <h1 class="page-title">Frotas</h1>
    <p><a href="{{ route('trens.index') }}">Consultar trens</a></p>
    @if(session('success'))<p role="status" style="color:#166534;">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif
    {{-- A interface oculta ações administrativas; a rota também exige perfil administrador. --}}
    @if(auth()->user()->isAdministrador())<p><a href="{{ route('frotas.create') }}">+ Nova frota</a></p>@endif
    <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">
        <thead><tr><th align="left">Código</th><th align="left">Descrição</th><th align="left">Situação</th><th align="left">Trens</th><th align="left">Ações</th></tr></thead>
        <tbody>
        @forelse($frotas as $frota)
        <tr style="border-top:1px solid #e2e8f0">
            <td style="padding:12px 0">{{ $frota->codigo }}</td><td>{{ $frota->descricao ?: '—' }}</td>
            <td>{{ $frota->ativa ? 'Ativa' : 'Inativa' }}</td><td>{{ $frota->trens_count }}</td>
            <td>
                @if(auth()->user()->isAdministrador())
                    <a href="{{ route('frotas.edit', $frota) }}">Editar</a>
                    <form method="POST" action="{{ route('frotas.destroy', $frota) }}" style="display:inline" onsubmit="return confirm('Excluir esta frota?');">
                        @csrf @method('DELETE')
                        <button type="submit">Excluir</button>
                    </form>
                @else — @endif
            </td>
        </tr>
        @empty<tr><td colspan="5" style="padding:16px 0">Nenhuma frota cadastrada.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div style="margin-top:18px">{{ $frotas->links() }}</div>
</div></main>
@endsection
