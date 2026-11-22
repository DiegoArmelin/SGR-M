@extends('layouts.app')
@section('title', ($frota->exists ? 'Editar frota' : 'Nova frota') . ' — SGR-M')
@section('content')
{{-- Formulário único: cria ou atualiza conforme o Model tenha ID persistido. --}}
<main class="container"><div class="card">
    <h1 class="page-title">{{ $frota->exists ? 'Editar frota' : 'Cadastrar frota' }}</h1>
    <form method="POST" action="{{ $frota->exists ? route('frotas.update', $frota) : route('frotas.store') }}">
        {{-- Token contra requisições forjadas; no modo edição, simula o método HTTP PUT. --}}
        @csrf
        @if($frota->exists) @method('PUT') @endif
        <div class="form-group">
            <label class="form-label" for="codigo">Código *</label>
            <input class="form-control" id="codigo" name="codigo" maxlength="10" required value="{{ old('codigo', $frota->codigo) }}">
            @error('codigo')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="descricao">Descrição</label>
            <input class="form-control" id="descricao" name="descricao" maxlength="150" value="{{ old('descricao', $frota->descricao) }}">
            @error('descricao')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            {{-- Campo oculto envia 0 quando o checkbox estiver desmarcado. --}}
            <input type="hidden" name="ativa" value="0">
            <label><input type="checkbox" name="ativa" value="1" @checked(old('ativa', $frota->exists ? $frota->ativa : true))> Frota ativa</label>
            @error('ativa')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button class="btn-primary" type="submit">Salvar</button>
        <a href="{{ route('frotas.index') }}" style="margin-left:12px">Cancelar</a>
    </form>
</div></main>
@endsection
