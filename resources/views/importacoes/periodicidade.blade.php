@extends('layouts.app')
@section('title', 'Importação de periodicidade — SGR-M')
@section('content')
<main class="container">
    <section class="card">
        <h1 class="page-title">Importar periodicidade dos rodeiros</h1>
        <p>Atualize a periodicidade percentual dos rodeiros cadastrados por número de série.</p>

        @if(session('success'))
            <p role="status" style="color:#166534">{{ session('success') }}</p>
        @endif

        @if($errors->any())
            <div class="alert-error">
                <strong>O arquivo não foi importado.</strong>
                <ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif

        <h2>Formato esperado</h2>
        <p>Arquivo CSV, codificado em UTF-8, com cabeçalho obrigatório
            <code>numero_serie</code> e <code>periodicidade</code>.</p>

        <pre style="padding:15px;background:#f1f5f9;border-radius:6px;overflow-x:auto">numero_serie;periodicidade
RD-SIM-4010493;96,50
RD-SIM-6075655;105,00</pre>

        <p style="color:#475569">
            Aceita separador ponto e vírgula ou vírgula. O percentual pode conter ponto ou
            vírgula decimal e o símbolo %. Não utiliza horas ou dias para calcular
            a periodicidade: o valor informado no CSV é aplicado diretamente.
        </p>
        <p style="color:#475569">
            O arquivo pode ter até 2 MB e 10.000 registros. O sistema verifica
            a existência de todas as séries e rejeita valores inválidos ou duplicados.
            <strong>Em caso de erro, nenhum rodeiro é atualizado.</strong>
        </p>

        <form method="POST" enctype="multipart/form-data"
              action="{{ route('importacoes.periodicidade.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="arquivo">Arquivo CSV *</label>
                <input class="form-control" type="file" name="arquivo" id="arquivo"
                       accept=".csv,text/csv,.txt" required>
            </div>
            <button type="submit" class="btn-primary"
                onclick="return confirm('Confirmar importação? A periodicidade atual dos rodeiros encontrados será substituída.');">
                Importar periodicidades
            </button>
        </form>
    </section>
</main>
@endsection
