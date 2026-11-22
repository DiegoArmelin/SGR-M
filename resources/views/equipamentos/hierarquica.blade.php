@extends('layouts.app')

@section('title', 'Consulta hierárquica — SGR-M')

@push('styles')
<style>
    .hierarchy-filters { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px; }
    .hierarchy-actions { margin: 14px 0 24px; }
    .hierarchy-summary { background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:12px;margin:20px 0; }
    .hierarchy-table { width:100%;border-collapse:collapse;text-align:left; }
    .hierarchy-table th,.hierarchy-table td { padding:12px;border-bottom:1px solid #e2e8f0;vertical-align:top; }
    .hierarchy-table th { background:#f1f5f9; }
    .status-note { font-size:13px;color:#64748b; }
    @media (max-width:720px) { .hierarchy-filters {grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Consulta hierárquica de equipamentos</h1>
        <p>Selecione uma frota, um trem e um carro para visualizar os eixos e seus equipamentos atualmente instalados.</p>

        {{-- Formulário GET: não altera informações; as opções dependem da escolha anterior. --}}
        <form action="{{ route('equipamentos.hierarquica') }}" method="GET" id="consulta-hierarquica">
            <div class="hierarchy-filters">
                <div class="form-group">
                    <label for="frota_id" class="form-label">Frota</label>
                    <select name="frota_id" id="frota_id" class="form-control">
                        <option value="">Selecione uma frota</option>
                        @foreach($frotas as $frota)
                            <option value="{{ $frota->id }}" @selected($frotaId == $frota->id)>
                                Frota {{ $frota->codigo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="trem_id" class="form-label">Trem</label>
                    <select name="trem_id" id="trem_id" class="form-control" @disabled(!$frotaId)>
                        <option value="">Selecione um trem</option>
                        @foreach($trens as $trem)
                            <option value="{{ $trem->id }}" @selected($tremId == $trem->id)>{{ $trem->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="carro_id" class="form-label">Carro</label>
                    <select name="carro_id" id="carro_id" class="form-control" @disabled(!$tremId)>
                        <option value="">Selecione um carro</option>
                        @foreach($carros as $carro)
                            <option value="{{ $carro->id }}" @selected($carroId == $carro->id)>{{ $carro->codigo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="hierarchy-actions">
                <button type="submit" class="btn-primary">Consultar</button>
                <a href="{{ route('equipamentos.hierarquica') }}" style="margin-left:12px;">Limpar seleção</a>
            </div>
        </form>

        {{-- Estado vazio instrui a pessoa a continuar selecionando a hierarquia. --}}
        @if(!$frotaId)
            <p class="status-note">Comece selecionando a frota.</p>
        @elseif(!$tremId)
            <p class="status-note">Agora selecione o trem da frota {{ $frotaSelecionada->codigo }}.</p>
        @elseif(!$carroId)
            <p class="status-note">Agora selecione um dos carros do trem {{ $tremSelecionado->codigo }}.</p>
        @else
            <div class="hierarchy-summary">
                <strong>Frota {{ $frotaSelecionada->codigo }} → Trem {{ $tremSelecionado->codigo }} → Carro {{ $carroSelecionado->codigo }}</strong>
                <span class="status-note"> | {{ $eixos->count() }} eixo(s) cadastrado(s)</span>
            </div>

            {{-- Cada eixo possui no máximo uma instalação ativa de cada tipo,
                 regra garantida também pelos índices únicos no banco. --}}
            <div style="overflow-x:auto;">
                <table class="hierarchy-table">
                    <thead>
                        <tr>
                            <th>Eixo</th>
                            <th>Rodeiro instalado</th>
                            <th>Diâmetro LR / LO (mm)</th>
                            <th>Periodicidade (%)</th>
                            <th>Motor instalado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eixos as $eixo)
                            @php
                                // Utiliza as relações já carregadas, sem novas consultas SQL.
                                $rodeiro = $eixo->instalacoesRodeiros->first()?->rodeiro;
                                $motor = $eixo->instalacoesMotores->first()?->motor;
                            @endphp
                            <tr>
                                <td><strong>Eixo {{ $eixo->numero }}</strong></td>
                                <td>
                                    {{ $rodeiro?->numero_serie ?? 'Sem rodeiro instalado' }}
                                    @if($rodeiro)<div class="status-note">Status: {{ $rodeiro->status }}</div>@endif
                                </td>
                                <td>
                                    {{ $rodeiro?->diametro_lr !== null ? number_format((float)$rodeiro->diametro_lr, 2, ',', '.') : '—' }}
                                    /
                                    {{ $rodeiro?->diametro_lo !== null ? number_format((float)$rodeiro->diametro_lo, 2, ',', '.') : '—' }}
                                </td>
                                <td>{{ $rodeiro?->periodicidade !== null ? number_format((float)$rodeiro->periodicidade, 2, ',', '.') : '—' }}</td>
                                <td>
                                    {{ $motor?->numero_serie ?? 'Sem motor instalado' }}
                                    @if($motor)<div class="status-note">Status: {{ $motor->status }}</div>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Não há eixos cadastrados neste carro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="status-note">São exibidas apenas instalações sem data de retirada. Campos de condição não informados aparecem como “—”.</p>
        @endif
    </section>
</main>
@endsection

@push('scripts')
<script>
    // Melhora a navegação: ao mudar o nível pai, limpa os filtros filhos.
    // Sem JavaScript, o formulário continua funcionando pelo botão Consultar.
    const formConsulta = document.getElementById('consulta-hierarquica');
    const campoFrota = document.getElementById('frota_id');
    const campoTrem = document.getElementById('trem_id');
    const campoCarro = document.getElementById('carro_id');

    campoFrota.addEventListener('change', () => {
        campoTrem.value = '';
        campoCarro.value = '';
        formConsulta.submit();
    });
    campoTrem.addEventListener('change', () => {
        campoCarro.value = '';
        formConsulta.submit();
    });
    campoCarro.addEventListener('change', () => formConsulta.submit());
</script>
@endpush
