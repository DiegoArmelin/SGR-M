@extends('layouts.app')

@section('title', 'Consulta de Equipamentos — SGR-M')

@section('content')
{{-- Consulta somente leitura: filtros GET, sem alterar dados do equipamento. --}}
<main class="container">
    <div class="card">
        <h1 class="page-title">Consulta de equipamentos</h1>
        <p>Consulte rodeiros e motores cadastrados. Os dados de condição são informativos e não substituem avaliação técnica.</p>

        <form action="{{ route('equipamentos.index') }}" method="GET">
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo de equipamento</label>
                <select class="form-control" name="tipo" id="tipo">
                    <option value="rodeiro" @selected($tipo === 'rodeiro')>Rodeiros</option>
                    <option value="motor" @selected($tipo === 'motor')>Motores</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="numero_serie">Número de série (total ou parcial)</label>
                <input class="form-control" id="numero_serie" name="numero_serie" type="text"
                       maxlength="50" value="{{ $numeroSerie }}" placeholder="Digite para pesquisar">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Situação cadastral</label>
                <select class="form-control" name="status" id="status">
                    <option value="">Todas</option>
                    @foreach (['disponivel' => 'Disponível', 'instalado' => 'Instalado', 'em_manutencao' => 'Em manutenção', 'inativo' => 'Inativo'] as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($status === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary" type="submit">Filtrar</button>
            <a href="{{ route('equipamentos.index') }}" style="margin-left: 12px;">Limpar filtros</a>
        </form>

        <div style="overflow-x:auto; margin-top: 28px;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid #cbd5e1;">
                        <th style="padding:12px;">Número de série</th>
                        <th style="padding:12px;">Situação</th>
                        @if ($tipo === 'rodeiro')
                            <th style="padding:12px;">Diâmetro LR (mm)</th>
                            <th style="padding:12px;">Diâmetro LO (mm)</th>
                            <th style="padding:12px;">Periodicidade (%)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    {{-- Renderiza os registros da página ou mostra uma mensagem de lista vazia. --}}
                    @forelse ($equipamentos as $equipamento)
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:12px;">{{ $equipamento->numero_serie }}</td>
                            <td style="padding:12px;">{{ ['disponivel' => 'Disponível', 'instalado' => 'Instalado', 'em_manutencao' => 'Em manutenção', 'inativo' => 'Inativo'][$equipamento->status] ?? $equipamento->status }}</td>
                            @if ($tipo === 'rodeiro')
                                <td style="padding:12px;">{{ $equipamento->diametro_lr !== null ? number_format((float) $equipamento->diametro_lr, 2, ',', '.') : '—' }}</td>
                                <td style="padding:12px;">{{ $equipamento->diametro_lo !== null ? number_format((float) $equipamento->diametro_lo, 2, ',', '.') : '—' }}</td>
                                <td style="padding:12px;">{{ $equipamento->periodicidade !== null ? number_format((float) $equipamento->periodicidade, 2, ',', '.') : '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td style="padding:16px;" colspan="{{ $tipo === 'rodeiro' ? 5 : 2 }}">Nenhum equipamento encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:24px;">
            {{ $equipamentos->links() }}
        </div>
    </div>
</main>
@endsection
