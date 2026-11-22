@extends('layouts.app')
@section('title', 'Importar condições dos rodeiros — SGR-M')
@push('styles')
<style>
    .import-summary { display:flex;gap:12px;flex-wrap:wrap;margin:22px 0; }
    .import-metric { border:1px solid #e2e8f0;border-radius:8px;padding:12px 17px;min-width:145px;flex:1; }
    .import-metric strong { display:block;font-size:23px;color:#17365d; }
    .import-metric span { color:#64748b;font-size:13px; }
    .import-toolbar { display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:16px 0; }
    .import-scroll { max-height:540px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px; }
    .import-table { border-collapse:collapse;width:100%;min-width:1050px;text-align:left;font-size:13px; }
    .import-table th { position:sticky;top:0;background:#eaf0f7;color:#17365d;z-index:1; }
    .import-table th,.import-table td { padding:10px;border-bottom:1px solid #e2e8f0;vertical-align:top; }
    .import-table tr.ignored-row { background:#fff7ed; }
    .import-status { display:inline-block;border-radius:999px;padding:4px 9px;font-weight:bold;font-size:12px; }
    .import-status.ok { color:#166534;background:#dcfce7; }
    .import-status.skip { color:#9a3412;background:#ffedd5; }
    .import-export { display:inline-block;padding:10px 14px;border-radius:6px;color:white;background:#17365d;text-decoration:none;font-size:14px; }
    .import-muted { color:#64748b;font-size:12px; }
</style>
@endpush
@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Importação de condições dos rodeiros</h1>
        <p>Atualize <strong>diâmetros LR/LO e periodicidade</strong> de uma só vez, desde que a data da medição no CSV seja mais recente que a registrada no sistema.</p>

        @if(session('success'))
            <p role="status" style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534">
                {{ session('success') }}
            </p>
        @endif
        @if($errors->any())
            <div class="alert-error">
                <strong>Nenhuma atualização foi aplicada.</strong>
                <ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif

        <h2>Estrutura do CSV</h2>
        <p>Use codificação UTF-8 e as nove colunas abaixo, separadas por ponto e vírgula. A data deve ter o formato <code>AAAA-MM-DD HH:MM:SS</code>.</p>
        <pre style="padding:14px;background:#f1f5f9;border-radius:6px;overflow-x:auto;font-size:12px">frota;trem;carro;posicao;serie_rodeiro;diametro_lr;diametro_lo;periodicidade;data_atualizacao
I;I01;I011;1;RD-SIM-0112648;829.10;829.50;95.50;2026-11-21 12:00:00</pre>

        <p><strong>Regras de importação:</strong> o sistema confirma que a frota, o trem, o carro e o eixo existem e que a série corresponde ao rodeiro instalado naquela posição. Registros de peças já substituídas são ignorados. Dados com data igual ou anterior à última medição também são ignorados.</p>
        <p>Se existir cabeçalho incorreto, uma série desconhecida, uma posição inexistente ou valor inválido, a importação inteira será recusada. O limite é de 2 MB e 10.000 linhas por arquivo.</p>

        @if($relatorio)
            {{-- Detalhes persistem no cache temporário para evitar cookie de sessão grande. --}}
            <div id="relatorio-importacao">
                <h2>Resultado detalhado da última importação</h2>
                <div class="import-summary">
                    <div class="import-metric"><strong>{{ $relatorio['linhas'] }}</strong><span>Linhas processadas</span></div>
                    <div class="import-metric"><strong>{{ $relatorio['atualizados'] }}</strong><span>Atualizados</span></div>
                    <div class="import-metric"><strong>{{ $relatorio['ignorados_data'] }}</strong><span>Ignorados por data</span></div>
                    <div class="import-metric"><strong>{{ $relatorio['ignorados_posicao'] }}</strong><span>Ignorados por posição/série</span></div>
                </div>

                <div class="import-toolbar">
                    <div>
                        <label class="form-label" for="filtro-resultado">Mostrar registros</label>
                        <select class="form-control" id="filtro-resultado">
                            <option value="todos">Todos</option>
                            <option value="ignorado">Apenas ignorados</option>
                            <option value="atualizado">Apenas atualizados</option>
                        </select>
                    </div>
                    <a class="import-export" href="{{ route('importacoes.periodicidade.exportar') }}">
                        Exportar relatório CSV
                    </a>
                    <span class="import-muted" id="quantidade-visivel" aria-live="polite"></span>
                </div>

                <div class="import-scroll">
                    <table class="import-table">
                        <thead>
                            <tr>
                                <th>Linha</th>
                                <th>Frota</th>
                                <th>Trem</th>
                                <th>Carro–posição</th>
                                <th>Série do rodeiro</th>
                                <th>LR / LO (mm)</th>
                                <th>Periodicidade</th>
                                <th>Data CSV</th>
                                <th>Resultado</th>
                                <th>Motivo / observação</th>
                            </tr>
                        </thead>
                        <tbody id="resultado-linhas">
                            @foreach($relatorio['detalhes'] as $item)
                                <tr data-status="{{ $item['status'] }}" class="{{ $item['status'] === 'ignorado' ? 'ignored-row' : '' }}">
                                    <td>{{ $item['linha'] }}</td>
                                    <td>{{ $item['frota'] }}</td>
                                    <td>{{ $item['trem'] }}</td>
                                    <td>{{ $item['carro'] }}-{{ $item['posicao'] }}</td>
                                    <td>{{ $item['serie_rodeiro'] }}</td>
                                    <td>{{ $item['diametro_lr'] }} / {{ $item['diametro_lo'] }}</td>
                                    <td>{{ $item['periodicidade'] }}%</td>
                                    <td>{{ $item['data_atualizacao'] }}</td>
                                    <td>
                                        <span class="import-status {{ $item['status'] === 'atualizado' ? 'ok' : 'skip' }}">
                                            {{ $item['status'] === 'atualizado' ? 'Atualizado' : 'Ignorado' }}
                                        </span>
                                    </td>
                                    <td>{{ $item['motivo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="import-muted">Relatório disponível durante duas horas no cache do sistema, vinculado a esta sessão. O CSV exportado contém todas as linhas, mesmo quando um filtro está selecionado.</p>
            </div>
        @endif

        <form action="{{ route('importacoes.periodicidade.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label" for="arquivo">Arquivo CSV *</label>
                <input class="form-control" id="arquivo" type="file" name="arquivo" accept=".csv,.txt,text/csv" required>
            </div>
            <button class="btn-primary" type="submit" onclick="return confirm('Importar dados? Somente registros com datas mais recentes serão atualizados.');">
                Importar dados dos rodeiros
            </button>
        </form>
    </section>
</main>
@endsection

@push('scripts')
<script>
    // A filtragem atua apenas na apresentação: não refaz a importação.
    const filtroResultado = document.getElementById('filtro-resultado');
    if (filtroResultado) {
        const linhas = Array.from(document.querySelectorAll('#resultado-linhas tr'));
        const contador = document.getElementById('quantidade-visivel');
        const atualizar = () => {
            let visiveis = 0;
            const filtro = filtroResultado.value;
            for (const linha of linhas) {
                const mostrar = filtro === 'todos' || linha.dataset.status === filtro;
                linha.hidden = !mostrar;
                // O atributo hidden precisa ser aplicado ao display da linha,
                // respeitando o CSS default do navegador.
                linha.style.display = mostrar ? '' : 'none';
                if (mostrar) visiveis++;
            }
            contador.textContent = visiveis + ' de ' + linhas.length + ' registro(s) exibido(s)';
        };
        filtroResultado.addEventListener('change', atualizar);
        atualizar();
    }
</script>
@endpush
