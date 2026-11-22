{{-- Filtros específicos de cada tabela: p_ (pendências), d_ (diâmetros), r_ (periodicidade). --}}
<form method="GET" action="{{ route('ranking.index') }}" class="rank-filters" data-prefix="{{ $prefixo }}">
    <input type="hidden" name="tipo" value="{{ $tipo }}">
    <div>
        <label class="form-label" for="{{ $prefixo }}_trem">Trem</label>
        <select class="form-control rank-trem" name="{{ $prefixo }}_trem" id="{{ $prefixo }}_trem">
            <option value="">Todos os trens</option>
            @foreach($trens as $trem)
                <option value="{{ $trem->codigo }}" @selected(($filtros[$prefixo . '_trem'] ?? '') === $trem->codigo)>{{ $trem->codigo }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="{{ $prefixo }}_carro">Carro</label>
        <select class="form-control rank-carro" name="{{ $prefixo }}_carro" id="{{ $prefixo }}_carro">
            <option value="">Todos os carros</option>
            @foreach($trens as $trem)
                @foreach($trem->carros as $carro)
                    <option value="{{ $carro->codigo }}" data-trem="{{ $trem->codigo }}"
                        @selected(($filtros[$prefixo . '_carro'] ?? '') === $carro->codigo)>{{ $carro->codigo }}</option>
                @endforeach
            @endforeach
        </select>
    </div>
    @if($mostrarPrioridade ?? false)
        <div>
            <label class="form-label" for="p_prioridade">Prioridade</label>
            <select class="form-control" name="p_prioridade" id="p_prioridade">
                <option value="">Todas</option>
                @foreach(['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'] as $valor=>$rotulo)
                    <option value="{{ $valor }}" @selected(($filtros['p_prioridade'] ?? '') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="rank-filter-actions">
        <button class="btn-primary" type="submit">Filtrar</button>
        <a href="{{ route('ranking.index', ['tipo' => $tipo]) }}">Limpar</a>
    </div>
</form>