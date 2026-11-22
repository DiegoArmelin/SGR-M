@extends('layouts.app')
@section('title', 'Histórico do eixo — SGR-M')
@section('content')
<main class="container"><section class="card">
    <h1 class="page-title">Histórico de equipamentos do eixo</h1>
    <p>Frota {{ $eixo->carro->trem->frota->codigo }} → Trem {{ $eixo->carro->trem->codigo }} → Carro {{ $eixo->carro->codigo }} → Eixo {{ $eixo->numero }}</p>
    {{-- As instalações nunca são apagadas na substituição. Data de retirada nula indica vínculo ativo. --}}
    @foreach(['Rodeiros' => $rodeiros, 'Motores' => $motores] as $tipo => $registros)
        <h2>{{ $tipo }}</h2>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;text-align:left">
                <thead><tr><th style="padding:10px">Série</th><th>Instalação</th><th>Retirada</th><th>Motivo</th><th>Situação do vínculo</th></tr></thead>
                <tbody>
                    @forelse($registros as $registro)
                    <tr style="border-top:1px solid #e2e8f0">
                        <td style="padding:10px">{{ $tipo === 'Rodeiros' ? $registro->rodeiro->numero_serie : $registro->motor->numero_serie }}</td>
                        <td>{{ $registro->data_instalacao->format('d/m/Y') }}</td>
                        <td>{{ $registro->data_retirada?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $registro->motivo_retirada ?? '—' }}</td>
                        <td>{{ $registro->data_retirada === null ? 'Instalação ativa' : 'Retirado' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:10px">Nenhuma instalação registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
    <p><a href="{{ route('equipamentos.hierarquica', ['frota_id' => $eixo->carro->trem->frota_id, 'trem_id' => $eixo->carro->trem_id, 'carro_id' => $eixo->carro_id]) }}">Voltar à consulta hierárquica</a></p>
</section></main>
@endsection
