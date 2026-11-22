<?php

namespace App\Http\Controllers;

use App\Services\ImportacaoPeriodicidadeCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Interface de importação e consulta do último relatório processado.
 *
 * O relatório fica temporariamente no cache de arquivos (2 horas), e não
 * na sessão: um CSV com 600 ou 10.000 linhas não cabe em cookie de sessão.
 */
class ImportacaoPeriodicidadeController extends Controller
{
    private const CHAVE_SESSAO = 'relatorio_importacao_csv';
    private const PREFIXO_CACHE = 'sgrm_importacao_csv_';

    /** Apresenta formulário e relatório disponível apenas ao usuário da sessão. */
    public function create(Request $request): View
    {
        $relatorio = $this->relatorioDaSessao($request);

        return view('importacoes.periodicidade', compact('relatorio'));
    }

    /** Importa o CSV e gera um relatório individual por linha. */
    public function store(
        Request $request,
        ImportacaoPeriodicidadeCsvService $importacao
    ): RedirectResponse {
        $dados = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        // O serviço retorna resultados somente depois de concluir a transação.
        // Se a importação lançar erro, nenhum relatório de sucesso é gerado.
        $resultado = $importacao->importar($dados['arquivo']);
        $token = Str::random(40);

        Cache::store('file')->put(
            self::PREFIXO_CACHE . $token,
            $resultado,
            now()->addHours(2)
        );

        // Armazena somente a chave; os milhares de detalhes ficam no cache.
        $request->session()->put(self::CHAVE_SESSAO, $token);

        return redirect()->route('importacoes.periodicidade.create')
            ->with('success', sprintf(
                'Importação processada: %d linha(s), %d atualizado(s), %d ignorado(s) por data e %d ignorado(s) por posição/série divergente.',
                $resultado['linhas'], $resultado['atualizados'],
                $resultado['ignorados_data'], $resultado['ignorados_posicao']
            ));
    }

    /**
     * Exporta os resultados completos em CSV sem recriar a importação.
     * A autorização continua protegida pelo middleware da rota e pelo token
     * específico da sessão, sem aceitar nome de arquivo pela URL.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $relatorio = $this->relatorioDaSessao($request);
        abort_unless($relatorio, 404, 'Relatório indisponível ou expirado.');

        return response()->streamDownload(function () use ($relatorio): void {
            $saida = fopen('php://output', 'wb');

            // BOM torna os acentos legíveis no Excel, em especial no Windows.
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'linha', 'frota', 'trem', 'carro', 'posicao', 'serie_rodeiro',
                'diametro_lr', 'diametro_lo', 'periodicidade', 'data_atualizacao',
                'status', 'motivo',
            ], ';');

            foreach ($relatorio['detalhes'] as $item) {
                fputcsv($saida, [
                    $item['linha'], $item['frota'], $item['trem'], $item['carro'],
                    $item['posicao'], $item['serie_rodeiro'], $item['diametro_lr'],
                    $item['diametro_lo'], $item['periodicidade'],
                    $item['data_atualizacao'], $item['status'], $item['motivo'],
                ], ';');
            }

            fclose($saida);
        }, 'SGRM_resultado_importacao_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Recupera o relatório temporário correspondente à sessão atual. */
    private function relatorioDaSessao(Request $request): ?array
    {
        $token = $request->session()->get(self::CHAVE_SESSAO);

        if (!is_string($token) || $token === '') {
            return null;
        }

        $resultado = Cache::store('file')->get(self::PREFIXO_CACHE . $token);

        return is_array($resultado) ? $resultado : null;
    }
}
