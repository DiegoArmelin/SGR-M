<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGR-M — Histórico de substituições</title>
    @include('dashboard-gerencial.partials.historico-estilos')
    <style>
        body{font-family:Arial,Helvetica,sans-serif;color:#263445;margin:22px auto;max-width:960px;padding:0 16px}
        h1{color:#17365d;font-size:25px}
        .pdf-header{border-bottom:2px solid #17365d;padding-bottom:12px;margin-bottom:15px}
        .pdf-metrics{display:flex;gap:12px;margin:16px 0}
        .pdf-metric{flex:1;background:#eff6ff;padding:12px;border-radius:6px}
        .pdf-metric strong{display:block;font-size:24px}
        .pdf-action{background:#17365d;color:#fff;border:0;padding:12px 16px;cursor:pointer;border-radius:6px;margin-bottom:15px}
        .pdf-note{font-size:12px;color:#475569}
        @page{size:A4 landscape;margin:12mm}
        @media print {
            body{max-width:none;margin:0;padding:0;font-size:10pt}
            .pdf-action,.pdf-note{display:none}
            .pdf-metrics,.pdf-metric{print-color-adjust:exact;-webkit-print-color-adjust:exact}
        }
    </style>
</head>
<body>
    <button class="pdf-action" onclick="window.print()">Imprimir / Salvar como PDF</button>
    <p class="pdf-note">Na janela de impressão, escolha o destino “Salvar como PDF”. As informações e os gráficos seguem os filtros aplicados.</p>
    <div class="pdf-header">
        <h1>SGR-M — Histórico de substituições</h1>
        <p>Relatório gerado em {{ now()->format('d/m/Y H:i') }} — dados do sistema</p>
        <p>
            Período: {{ $filtros['inicio'] ?? 'Início do histórico' }} a {{ $filtros['fim'] ?? 'Data atual' }}
            · Frota: {{ $filtros['frota'] ?? 'Todas' }}
            · Motivo: {{ $motivo ?? 'Todos' }}
        </p>
    </div>
    <div class="pdf-metrics">
        <div class="pdf-metric">Total de substituições<strong>{{ $dados['total'] }}</strong></div>
        <div class="pdf-metric">Rodeiros<strong>{{ $dados['totais']['rodeiro'] }}</strong></div>
        <div class="pdf-metric">Motores<strong>{{ $dados['totais']['motor'] }}</strong></div>
    </div>
    @include('dashboard-gerencial.partials.historico-graficos')
    <p class="hg-muted">Mês do gráfico: {{ $dados['periodo_grafico'][0] }} a {{ $dados['periodo_grafico'][1] }}. Os totais podem abranger um intervalo mais amplo.</p>
</body>
</html>