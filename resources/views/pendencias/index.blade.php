@extends('layouts.app')
@section('title', 'Gestão de pendências — SGR-M')
@section('content')
<main class="container"><section class="card">
    <h1 class="page-title">Gestão de pendências técnicas</h1>
    <p>Consulte pendências de rodeiros e motores, ordenadas pela situação e prioridade.</p>
    @if(session('success'))<p role="status" style="color:#166534">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="alert-error"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    <p><a href="{{ route('pendencias.create') }}">+ Registrar pendência</a></p>
    {{-- Filtros GET permitem compartilhar a URL sem modificar os registros. --}}
    <form method="GET" action="{{ route('pendencias.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
        <div class="form-group" style="flex:1;min-width:180px">
            <label class="form-label" for="situacao">Situação</label>
            <select class="form-control" id="situacao" name="situacao">
                <option value="">Todas</option>
                @foreach(['aberta'=>'Aberta','resolvida'=>'Resolvida'] as $valor=>$rotulo)
                    <option value="{{ $valor }}" @selected(($filtros['situacao'] ?? '') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="flex:1;min-width:180px">
            <label class="form-label" for="tipo">Equipamento</label>
            <select class="form-control" name="tipo" id="tipo">
                <option value="">Todos</option>
                <option value="rodeiro" @selected(($filtros['tipo'] ?? '') === 'rodeiro')>Rodeiros</option>
                <option value="motor" @selected(($filtros['tipo'] ?? '') === 'motor')>Motores</option>
            </select>
        </div>
        <div class="form-group"><button type="submit" class="btn-primary">Filtrar</button> <a href="{{ route('pendencias.index') }}">Limpar</a></div>
    </form>

    <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;text-align:left">
        <thead><tr style="border-bottom:2px solid #cbd5e1">
            <th style="padding:10px">Posição de origem</th><th>Equipamento / série</th><th>Motivo / observações</th><th>Prioridade</th><th>Situação</th><th>Registro</th><th>Ações</th>
        </tr></thead>
        <tbody>
        @forelse($pendencias as $pendencia)
            @php
                // A pendência pertence a exatamente um tipo de equipamento.
                $tipo = $pendencia->rodeiro_id ? 'Rodeiro' : 'Motor';
                $serie = $pendencia->rodeiro?->numero_serie ?? $pendencia->motor?->numero_serie;
                $prioridades = ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];
            @endphp
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="padding:10px">
                    @if($pendencia->eixo)
                        {{-- A posição é histórica: permanece ligada à ocorrência depois de uma troca. --}}
                        Frota {{ $pendencia->eixo->carro->trem->frota->codigo }}
                        <div>Trem {{ $pendencia->eixo->carro->trem->codigo }}</div>
                        <div>Carro {{ $pendencia->eixo->carro->codigo }} — Eixo {{ $pendencia->eixo->numero }}</div>
                    @else
                        <small>Não registrada (cadastro anterior)</small>
                    @endif
                </td>
                <td><strong>{{ $tipo }}</strong><div>{{ $serie }}</div></td>
                <td>
                    {{-- Para cadastros antigos, o texto original permanece disponível. --}}
                    <strong>{{ $pendencia->motivoRetirada?->descricao ?? $pendencia->descricao }}</strong>
                    @if($pendencia->observacoes)
                        <div style="margin-top:6px;font-size:13px;color:#475569">{{ $pendencia->observacoes }}</div>
                    @endif
                </td>
                <td>{{ $prioridades[$pendencia->prioridade] ?? $pendencia->prioridade }}</td>
                <td>{{ $pendencia->situacao === 'aberta' ? 'Aberta' : 'Resolvida' }}</td>
                <td>{{ $pendencia->created_at?->format('d/m/Y') }}<div style="font-size:12px;color:#64748b">{{ $pendencia->autor?->name }}</div></td>
                <td>
                    @if($pendencia->situacao === 'aberta')
                        <form method="POST" action="{{ route('pendencias.resolver', $pendencia) }}">
                            @csrf @method('PATCH')
                            <label for="resolucao_{{ $pendencia->id }}" style="display:block;font-size:12px">Justificativa</label>
                            <input type="text" id="resolucao_{{ $pendencia->id }}" name="observacoes_resolucao" maxlength="1000" required placeholder="Descreva a solução">
                            <button type="submit" onclick="return confirm('Confirmar resolução da pendência?')">Resolver</button>
                        </form>
                    @else
                        <small>Resolvida em {{ $pendencia->resolvida_em?->format('d/m/Y H:i') ?? '—' }}</small>
                        @if($pendencia->observacoes_resolucao)<div>{{ $pendencia->observacoes_resolucao }}</div>@endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:16px">Nenhuma pendência encontrada.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="margin-top:20px">{{ $pendencias->links() }}</div>
</section></main>
@endsection
