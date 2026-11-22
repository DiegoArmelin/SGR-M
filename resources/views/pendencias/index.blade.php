@extends('layouts.app')
@section('title', 'Gestão de pendências — SGR-M')

@push('styles')
<style>
    .pendencias-filtros { display:grid;grid-template-columns:repeat(2,minmax(180px,1fr)) auto;gap:12px;align-items:end; }
    .pendencias-tabela { width:100%;border-collapse:collapse;text-align:left; }
    .pendencias-tabela th { background:#f1f5f9;color:#17365d;font-size:14px;white-space:nowrap; }
    .pendencias-tabela th,.pendencias-tabela td { padding:13px 12px;border-bottom:1px solid #e2e8f0;vertical-align:middle; }
    .pendencias-tabela tbody tr:hover { background:#f8fafc; }
    .pendencias-tabela td { font-size:14px; }
    .pendencias-tabela .serie { font-family:monospace;font-size:13px;white-space:nowrap; }
    .pendencias-tabela .motivo { min-width:200px;max-width:360px; }
    .pendencias-tabela .subtexto { color:#64748b;font-size:12px;margin-top:5px; }
    .pendencias-tabela .linha-critica { border-left:4px solid #dc2626; }
    .pendencias-tabela .linha-alta { border-left:4px solid #ea580c; }
    .pendencias-tabela .linha-media { border-left:4px solid #ca8a04; }
    .pendencias-tabela .linha-baixa { border-left:4px solid #16a34a; }
    .prioridade-tag { display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:bold;white-space:nowrap; }
    .tag-critica { background:#fee2e2;color:#991b1b; }
    .tag-alta { background:#ffedd5;color:#9a3412; }
    .tag-media { background:#fef9c3;color:#854d0e; }
    .tag-baixa { background:#dcfce7;color:#166534; }
    .situacao-tag { display:inline-block;padding:5px 9px;border-radius:5px;font-size:12px;white-space:nowrap; }
    .situacao-aberta { background:#dbeafe;color:#1e40af; }
    .situacao-resolvida { background:#e2e8f0;color:#475569; }
    .acao-substituir { display:inline-block;background:#17365d;color:white;text-decoration:none;border-radius:5px;padding:9px 12px;font-size:13px;font-weight:bold;white-space:nowrap; }
    .acao-substituir:hover { background:#254f80; }
    @media(max-width:740px) {
        .pendencias-filtros { grid-template-columns:1fr; }
        .pendencias-tabela th,.pendencias-tabela td { padding:9px; }
    }
</style>
@endpush

@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Gestão de pendências técnicas</h1>
        <p>Pendências abertas são ordenadas por criticidade. Para encerrá-las, realize a substituição do equipamento.</p>

        @if(session('success'))
            <p role="status" style="color:#166534">{{ session('success') }}</p>
        @endif
        @if($errors->any())
            <div class="alert-error"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
        @endif

        <p><a href="{{ route('pendencias.create') }}">+ Registrar pendência</a></p>

        {{-- Filtros GET não alteram registros e permanecem ativos na paginação. --}}
        <form method="GET" action="{{ route('pendencias.index') }}" class="pendencias-filtros">
            <div class="form-group">
                <label class="form-label" for="situacao">Situação</label>
                <select class="form-control" id="situacao" name="situacao">
                    <option value="">Todas</option>
                    <option value="aberta" @selected(($filtros['situacao'] ?? '') === 'aberta')>Abertas</option>
                    <option value="resolvida" @selected(($filtros['situacao'] ?? '') === 'resolvida')>Resolvidas</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo de equipamento</label>
                <select class="form-control" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="rodeiro" @selected(($filtros['tipo'] ?? '') === 'rodeiro')>Rodeiros</option>
                    <option value="motor" @selected(($filtros['tipo'] ?? '') === 'motor')>Motores</option>
                </select>
            </div>
            <div class="form-group">
                <button class="btn-primary" type="submit">Filtrar</button>
                <a style="margin-left:8px" href="{{ route('pendencias.index') }}">Limpar</a>
            </div>
        </form>

        <div style="overflow-x:auto;margin-top:12px">
            <table class="pendencias-tabela">
                <thead>
                    <tr>
                        <th>Prioridade</th>
                        <th>Trem</th>
                        <th>Carro–posição</th>
                        <th>Série do equipamento</th>
                        <th>Motivo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendencias as $pendencia)
                        @php
                            // A pendência permanece associada à série retirada e
                            // à posição original, mesmo após a movimentação.
                            $tipo = $pendencia->rodeiro_id !== null ? 'rodeiro' : 'motor';
                            $serie = $pendencia->rodeiro?->numero_serie ?? $pendencia->motor?->numero_serie ?? '—';
                            $rotulos = ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];
                            $eixo = $pendencia->eixo;
                            $instalacaoAtual = $tipo === 'rodeiro'
                                ? $eixo?->instalacoesRodeiros->first()
                                : $eixo?->instalacoesMotores->first();
                            $equipamentoAtualId = $tipo === 'rodeiro'
                                ? $instalacaoAtual?->rodeiro_id : $instalacaoAtual?->motor_id;
                            // Sem posição ou com peça já retirada, não apontar
                            // o botão para uma troca de equipamento diferente.
                            $podeSubstituir = $pendencia->situacao === 'aberta'
                                && $eixo !== null
                                && $equipamentoAtualId === ($tipo === 'rodeiro'
                                    ? $pendencia->rodeiro_id : $pendencia->motor_id)
                                && auth()->user()->temPerfil('administrador', 'tecnico');
                        @endphp
                        <tr class="linha-{{ $pendencia->prioridade }}">
                            <td>
                                <span class="prioridade-tag tag-{{ $pendencia->prioridade }}">
                                    {{ $rotulos[$pendencia->prioridade] ?? $pendencia->prioridade }}
                                </span>
                            </td>
                            <td><strong>{{ $eixo?->carro?->trem?->codigo ?? '—' }}</strong></td>
                            <td>
                                @if($eixo)
                                    <strong>{{ $eixo->carro->codigo }}-{{ $eixo->numero }}</strong>
                                @else
                                    <span class="subtexto">Não registrada</span>
                                @endif
                            </td>
                            <td>
                                <span class="serie">{{ $serie }}</span>
                                <div class="subtexto">{{ ucfirst($tipo) }}</div>
                            </td>
                            <td class="motivo">
                                <strong>{{ $pendencia->motivoRetirada?->descricao ?? $pendencia->descricao }}</strong>
                                @if($pendencia->observacoes)
                                    <div class="subtexto">{{ $pendencia->observacoes }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="situacao-tag {{ $pendencia->situacao === 'aberta' ? 'situacao-aberta' : 'situacao-resolvida' }}">
                                    {{ $pendencia->situacao === 'aberta' ? 'Aberta' : 'Resolvida' }}
                                </span>
                                @if($pendencia->situacao === 'resolvida' && $pendencia->resolvida_em)
                                    <div class="subtexto">{{ $pendencia->resolvida_em->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td>
                                @if($podeSubstituir)
                                    <a class="acao-substituir"
                                       href="{{ route('equipamentos.movimentacao.create', ['eixo'=>$eixo->id, 'tipo'=>$tipo, 'origem'=>'pendencias']) }}">
                                        Substituir
                                    </a>
                                @elseif($pendencia->situacao === 'resolvida')
                                    <span class="subtexto">Concluída</span>
                                @elseif(!auth()->user()->temPerfil('administrador', 'tecnico'))
                                    <span class="subtexto">Somente consulta</span>
                                @else
                                    <span class="subtexto">Equipamento não está mais nesta posição</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:18px">Nenhuma pendência encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px">{{ $pendencias->links() }}</div>
    </section>
</main>
@endsection
