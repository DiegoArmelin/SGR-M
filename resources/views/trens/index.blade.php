@extends('layouts.app')
@section('title', 'Trens — SGR-M')
@section('content')
<main class="container"><div class="card">
    <h1 class="page-title">Trens</h1>
    <p><a href="{{ route('frotas.index') }}">Consultar frotas</a></p>
    @if(session('success'))<p role="status" style="color:#166534;">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('trens.index') }}">
        <div class="form-group"><label class="form-label" for="frota_id">Frota</label>
            <select class="form-control" id="frota_id" name="frota_id">
                <option value="">Todas</option>
                @foreach($frotas as $frota)<option value="{{ $frota->id }}" @selected(($filtros['frota_id'] ?? '') == $frota->id)>{{ $frota->codigo }}</option>@endforeach
            </select></div>
        <div class="form-group"><label class="form-label" for="codigo">Código do trem</label>
            <input class="form-control" id="codigo" name="codigo" maxlength="20" value="{{ $filtros['codigo'] ?? '' }}"></div>
        <button class="btn-primary" type="submit">Filtrar</button> <a href="{{ route('trens.index') }}">Limpar</a>
    </form>
    @if(auth()->user()->isAdministrador())<p><a href="{{ route('trens.create') }}">+ Novo trem</a></p>@endif
    <div style="overflow-x:auto;margin-top:20px"><table style="width:100%;border-collapse:collapse">
        <thead><tr><th align="left">Código</th><th align="left">Frota</th><th align="left">Descrição</th><th align="left">Situação</th><th align="left">Carros</th><th align="left">Ações</th></tr></thead>
        <tbody>
            @forelse($trens as $trem)
            <tr style="border-top:1px solid #e2e8f0">
                <td style="padding:12px 0">{{ $trem->codigo }}</td><td>{{ $trem->frota->codigo }}</td>
                <td>{{ $trem->descricao ?: '—' }}</td><td>{{ $trem->ativo ? 'Ativo' : 'Inativo' }}</td><td>{{ $trem->carros_count }}</td>
                <td>@if(auth()->user()->isAdministrador())
                    <a href="{{ route('trens.edit', $trem) }}">Editar</a>
                    <form method="POST" action="{{ route('trens.destroy', $trem) }}" style="display:inline" onsubmit="return confirm('Excluir este trem?');">
                        @csrf @method('DELETE')<button type="submit">Excluir</button>
                    </form>
                @else — @endif</td>
            </tr>
            @empty<tr><td colspan="6" style="padding:16px 0">Nenhum trem encontrado.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div style="margin-top:18px">{{ $trens->links() }}</div>
</div></main>
@endsection
