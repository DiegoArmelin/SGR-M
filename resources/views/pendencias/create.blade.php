@extends('layouts.app')
@section('title', 'Registrar pendência — SGR-M')
@section('content')
<main class="container"><section class="card">
    <h1 class="page-title">Registrar pendência técnica</h1>
    <p>Vincule a ocorrência ao número de série de um rodeiro ou motor cadastrado.</p>
    @if($errors->any())<div class="alert-error"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif

    {{-- A troca de tipo atualiza apenas as sugestões e não grava alterações. --}}
    <form method="GET" action="{{ route('pendencias.create') }}">
        <div class="form-group">
            <label class="form-label" for="tipo_busca">Tipo de equipamento</label>
            <select class="form-control" id="tipo_busca" name="tipo" onchange="this.form.submit()">
                <option value="rodeiro" @selected($tipo === 'rodeiro')>Rodeiro</option>
                <option value="motor" @selected($tipo === 'motor')>Motor</option>
            </select>
        </div>
        <noscript><button type="submit">Atualizar tipo</button></noscript>
    </form>

    <form method="POST" action="{{ route('pendencias.store') }}">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">
        <div class="form-group">
            <label class="form-label" for="numero_serie">Número de série *</label>
            <input type="text" class="form-control" id="numero_serie" name="numero_serie"
                value="{{ old('numero_serie') }}" list="series_cadastradas" maxlength="50" required>
            {{-- Datalist sugere séries, mas também aceita digitação manual. --}}
            <datalist id="series_cadastradas">
                @foreach($series as $serie)<option value="{{ $serie }}"></option>@endforeach
            </datalist>
            <small>Selecione entre as sugestões ou digite a série do equipamento.</small>
        </div>
        <div class="form-group">
            <label class="form-label" for="descricao">Descrição da pendência *</label>
            <textarea class="form-control" id="descricao" name="descricao" maxlength="500" required rows="3">{{ old('descricao') }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="prioridade">Prioridade *</label>
            <select class="form-control" name="prioridade" id="prioridade" required>
                <option value="">Selecione</option>
                @foreach(['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'] as $valor=>$rotulo)
                    <option value="{{ $valor }}" @selected(old('prioridade') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Registrar pendência</button>
        <a style="margin-left:12px" href="{{ route('pendencias.index') }}">Cancelar</a>
    </form>
</section></main>
@endsection
