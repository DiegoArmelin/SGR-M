@extends('layouts.app')
@section('title', 'Registrar pendência — SGR-M')

@push('styles')
<style>
    .pendencia-filtros { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px; }
    .pendencia-equipamento { padding:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;margin-bottom:20px; }
    @media(max-width:680px) { .pendencia-filtros {grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Registrar pendência técnica</h1>
        <p>Selecione a frota, o trem, o carro e a posição do eixo. O SGR-M identificará automaticamente o rodeiro ou motor instalado.</p>

        @if($errors->any())
            <div class="alert-error">
                <ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Esta etapa usa GET apenas para navegar pela hierarquia.
             Ao escolher um nível, o JavaScript limpa os níveis dependentes. --}}
        <form id="selecionar-posicao" method="GET" action="{{ route('pendencias.create') }}">
            <div class="pendencia-filtros">
                <div class="form-group">
                    <label class="form-label" for="frota_id">Frota *</label>
                    <select class="form-control" name="frota_id" id="frota_id">
                        <option value="">Selecione</option>
                        @foreach($frotas as $frota)
                            <option value="{{ $frota->id }}" @selected($frotaId == $frota->id)>Frota {{ $frota->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="trem_id">Trem *</label>
                    <select class="form-control" name="trem_id" id="trem_id" @disabled(!$frotaId)>
                        <option value="">Selecione</option>
                        @foreach($trens as $trem)
                            <option value="{{ $trem->id }}" @selected($tremId == $trem->id)>{{ $trem->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="carro_id">Carro *</label>
                    <select class="form-control" name="carro_id" id="carro_id" @disabled(!$tremId)>
                        <option value="">Selecione</option>
                        @foreach($carros as $carro)
                            <option value="{{ $carro->id }}" @selected($carroId == $carro->id)>{{ $carro->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="eixo_id">Eixo (posição) *</label>
                    <select class="form-control" name="eixo_id" id="eixo_id" @disabled(!$carroId)>
                        <option value="">Selecione</option>
                        @foreach($eixos as $eixo)
                            <option value="{{ $eixo->id }}" @selected($eixoId == $eixo->id)>Eixo {{ $eixo->numero }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tipo">Tipo de equipamento *</label>
                    <select class="form-control" name="tipo" id="tipo">
                        <option value="rodeiro" @selected($tipo === 'rodeiro')>Rodeiro</option>
                        <option value="motor" @selected($tipo === 'motor')>Motor</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">Consultar posição</button>
            <a href="{{ route('pendencias.create') }}" style="margin-left:12px">Limpar</a>
        </form>

        @if($eixoSelecionado)
            <div class="pendencia-equipamento">
                <strong>Equipamento instalado na posição selecionada:</strong>
                @if($equipamentoInstalado)
                    {{ $equipamentoInstalado->numero_serie }}
                    <small>({{ $tipo === 'rodeiro' ? 'rodeiro' : 'motor' }})</small>
                @else
                    <p>Não existe {{ $tipo }} com instalação ativa neste eixo. Não é possível registrar uma pendência nesta posição.</p>
                @endif
            </div>

            @if($equipamentoInstalado)
                {{-- O POST contém os IDs da hierarquia, não o número de série.
                     O controller confere o equipamento instalado novamente. --}}
                <form method="POST" action="{{ route('pendencias.store') }}">
                    @csrf
                    <input type="hidden" name="frota_id" value="{{ $frotaId }}">
                    <input type="hidden" name="trem_id" value="{{ $tremId }}">
                    <input type="hidden" name="carro_id" value="{{ $carroId }}">
                    <input type="hidden" name="eixo_id" value="{{ $eixoId }}">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <div class="form-group">
                        <label class="form-label" for="descricao">Descrição da pendência *</label>
                        <textarea class="form-control" id="descricao" name="descricao" maxlength="500" rows="3" required>{{ old('descricao') }}</textarea>
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
                    <button class="btn-primary" type="submit">Registrar pendência</button>
                    <a href="{{ route('pendencias.index') }}" style="margin-left:12px">Cancelar</a>
                </form>
            @endif
        @else
            <p style="color:#64748b;margin-top:16px;">Complete a seleção da posição para continuar o cadastro.</p>
        @endif
    </section>
</main>
@endsection

@push('scripts')
<script>
    // Navegação progressiva por GET, sem alterar o banco de dados.
    const selecao = document.getElementById('selecionar-posicao');
    const frota = document.getElementById('frota_id');
    const trem = document.getElementById('trem_id');
    const carro = document.getElementById('carro_id');
    const eixo = document.getElementById('eixo_id');
    const tipo = document.getElementById('tipo');

    frota.addEventListener('change', () => {
        trem.value = '';
        carro.value = '';
        eixo.value = '';
        selecao.submit();
    });
    trem.addEventListener('change', () => {
        carro.value = '';
        eixo.value = '';
        selecao.submit();
    });
    carro.addEventListener('change', () => {
        eixo.value = '';
        selecao.submit();
    });
    eixo.addEventListener('change', () => selecao.submit());
    tipo.addEventListener('change', () => selecao.submit());
</script>
@endpush
