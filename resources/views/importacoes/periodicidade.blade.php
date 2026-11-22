@extends('layouts.app')
@section('title', 'Importar condições dos rodeiros — SGR-M')
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
