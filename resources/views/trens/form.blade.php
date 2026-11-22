@extends('layouts.app')
@section('title', ($trem->exists ? 'Editar trem' : 'Novo trem') . ' — SGR-M')
@section('content')
<main class="container"><div class="card">
    <h1 class="page-title">{{ $trem->exists ? 'Editar trem' : 'Cadastrar trem' }}</h1>
    <form method="POST" action="{{ $trem->exists ? route('trens.update', $trem) : route('trens.store') }}">
        @csrf @if($trem->exists) @method('PUT') @endif
        <div class="form-group">
            <label class="form-label" for="frota_id">Frota *</label>
            <select class="form-control" name="frota_id" id="frota_id" required>
                <option value="">Selecione</option>
                @foreach($frotas as $frota)
                    <option value="{{ $frota->id }}" @selected(old('frota_id', $trem->frota_id) == $frota->id)>
                        {{ $frota->codigo }}{{ !$frota->ativa ? ' (inativa)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('frota_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="codigo">Código *</label>
            <input class="form-control" name="codigo" id="codigo" maxlength="20" required value="{{ old('codigo', $trem->codigo) }}">
            @error('codigo')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="descricao">Descrição</label>
            <input class="form-control" name="descricao" id="descricao" maxlength="150" value="{{ old('descricao', $trem->descricao) }}">
            @error('descricao')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <input type="hidden" name="ativo" value="0">
            <label><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $trem->exists ? $trem->ativo : true))> Trem ativo</label>
            @error('ativo')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">Salvar</button>
        <a href="{{ route('trens.index') }}" style="margin-left:12px">Cancelar</a>
    </form>
</div></main>
@endsection
