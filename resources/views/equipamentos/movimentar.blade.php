@extends('layouts.app')
@section('title', 'Substituir equipamento — SGR-M')
@section('content')
<main class="container"><section class="card">
    <h1 class="page-title">Substituição de {{ $tipo === 'rodeiro' ? 'rodeiro' : 'motor' }}</h1>
    <p><strong>Frota {{ $eixo->carro->trem->frota->codigo }}</strong> → Trem {{ $eixo->carro->trem->codigo }} → Carro {{ $eixo->carro->codigo }} → Eixo {{ $eixo->numero }}</p>
    @if($errors->any())<div class="alert-error"><strong>Verifique os dados:</strong>
        <ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    @if(!$atual)
        <div class="alert-error">Este eixo não possui equipamento instalado desse tipo. A função de substituição exige uma instalação ativa.</div>
    @else
        <p><strong>Equipamento instalado:</strong> {{ $atual->numero_serie }} (desde {{ $dataInstalacao }})</p>
        {{-- Confirma o número de série retirado, evitando substituir item diferente. --}}
        <form action="{{ route('equipamentos.movimentacao.store', ['eixo' => $eixo, 'tipo' => $tipo]) }}" method="POST">
            @csrf
            <input type="hidden" name="numero_serie_saida" value="{{ $atual->numero_serie }}">
            @if($origemPendencias)
                <input type="hidden" name="origem" value="pendencias">
            @endif
            {{-- Opção principal: lista dos equipamentos disponíveis em estoque. --}}
            <div class="form-group">
                <label class="form-label" for="numero_serie_estoque">
                    Selecionar {{ $tipo === 'rodeiro' ? 'rodeiro' : 'motor' }} em estoque
                </label>
                <select class="form-control" id="numero_serie_estoque" name="numero_serie_estoque">
                    <option value="">Selecione um número de série</option>
                    @foreach($equipamentosEstoque as $serie)
                        <option value="{{ $serie }}" @selected(old('numero_serie_estoque') === $serie)>
                            {{ $serie }}
                        </option>
                    @endforeach
                </select>
                <small>{{ $equipamentosEstoque->count() }} equipamento(s) com status <strong>estoque</strong>.</small>
            </div>
            {{-- Alternativa: permite digitação manual. Se preenchido, este campo
                 prevalece sobre a seleção feita na lista. A disponibilidade é
                 sempre verificada novamente no servidor antes da gravação. --}}
            <div class="form-group">
                <label class="form-label" for="numero_serie_entrada">Ou digite o número de série manualmente</label>
                <input class="form-control" id="numero_serie_entrada" name="numero_serie_entrada"
                       value="{{ old('numero_serie_entrada') }}" maxlength="50"
                       placeholder="Ex.: RD-SIM-0123456">
                <small>Informe a série manualmente somente se preferir não utilizar a lista. É necessário preencher ao menos uma das duas opções.</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="data_movimentacao">Data da substituição *</label>
                <input class="form-control" type="date" id="data_movimentacao" name="data_movimentacao" value="{{ old('data_movimentacao', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="motivo_retirada_id">Motivo da retirada *</label>
                <select class="form-control" id="motivo_retirada_id" name="motivo_retirada_id" required>
                    <option value="">Selecione</option>
                    {{-- Os motivos são consultados na tabela e filtrados por tipo no controller. --}}
                    @foreach($motivos as $motivo)
                        <option value="{{ $motivo->id }}" @selected(old('motivo_retirada_id') == $motivo->id)>{{ $motivo->descricao }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="observacoes">Observações (opcional)</label>
                <textarea class="form-control" id="observacoes" name="observacoes" maxlength="1000" rows="3">{{ old('observacoes') }}</textarea>
            </div>
            <button class="btn-primary" type="submit" onclick="return confirm('Confirmar substituição? Esta operação alterará o histórico e os status dos equipamentos.');">Confirmar substituição</button>
        </form>
    @endif
    @if($origemPendencias)
        <p><a href="{{ route('pendencias.index') }}">Voltar às pendências</a></p>
    @else
    <p><a href="{{ route('equipamentos.hierarquica', ['frota_id' => $eixo->carro->trem->frota_id, 'trem_id' => $eixo->carro->trem_id, 'carro_id' => $eixo->carro_id ]) }}">Voltar à consulta hierárquica</a></p>
    @endif
</section></main>
@endsection
