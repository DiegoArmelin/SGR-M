# SGR-M — Módulo 06: Indicadores e Histórico de Substituições

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-6-dashboard-gerencial`  
**Situação:** primeira versão validada pelo desenvolvedor; evolução para abas, gráficos e dados históricos publicada no GitHub, aguardando testes no Laragon.

## 1. Objetivo

Apresentar a situação atual do parque instalado e disponibilizar uma **aba de histórico de substituições** com gráficos de barras por mês, gráfico de rosca por motivo de retirada e comparação por frota. Os relatórios podem ser **salvos em PDF a partir da impressão do navegador**, mantendo os filtros selecionados.

## 2. Regras de negócio

### Visão geral

| Indicador | Critério |
|---|---|
| Parque instalado | **Um único card** com o total de rodeiros + motores em instalação ativa, e o detalhamento de ambos |
| Pendências abertas | Equipamentos distintos afetados e total de ocorrências abertas |
| Críticas e altas | Cards destacados separadamente, para rodeiros e motores |
| Pendências encerradas nos últimos 30 dias | Ocorrências com situação `resolvida` e `resolvida_em` nos últimos 30 dias corridos, inclusive hoje, respeitando o filtro de frota |
| Condições | Periodicidade >=100% para rodeiros e motores, diâmetros de rodeiros <745 mm e críticos <=742 mm |

A informação **trens cadastrados por frota** foi removida da tela. Também foram removidos os cards sobre equipamentos sem leitura de periodicidade. Todos os indicadores desta aba refletem a **situação atual**, salvo pendências encerradas, que usam a data de resolução.

### Histórico de substituições

- **Gráfico de barras:** quantidades de rodeiros e motores retirados em cada um dos últimos **seis meses**, terminando no mês da data final selecionada (ou no mês atual).
- **Dois gráficos de rosca:** motivos de retirada separados em **Rodeiros** e **Motores**, exibidos lado a lado no desktop/PDF e empilhados em telas pequenas. Cada rosca tem sua própria distribuição percentual e total.
- **Gráfico por frota:** quantidades de rodeiros e motores substituídos por frota no período filtrado, exibindo somente as frotas fictícias **I, J e L**; a frota extra de teste é omitida dessa comparação.
- **Filtros:** data inicial, data final, frota e motivo/defeito da tabela `motivos_retirada`. Os filtros são utilizados em **todos** os gráficos e nas contagens.
- **Totais:** número de rodeiros, motores e substituições somadas no período filtrado.
- **Exportação PDF:** o botão abre uma **versão de impressão** que contém os três gráficos e os mesmos filtros. Ao utilizar **Imprimir → Salvar como PDF** no navegador (Chrome/Edge), o arquivo é gerado no computador, sem instalar pacote adicional.
- **Diferença de escopo temporal:** os cards superiores consideram todo o período filtrado; o gráfico mensal sempre usa a janela de seis meses (e, dentro dela, os mesmos filtros). Isto é explicado na interface.

A movimentação histórica utiliza `data_retirada` das tabelas `instalacoes_rodeiros` e `instalacoes_motores`; o motivo categorizado vem da chave `motivo_retirada_id`. Não se utiliza o horário de criação do registro como data de retirada.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/DashboardGerencialService.php` | Indicadores atuais e encerramentos dos últimos 30 dias |
| `app/Services/HistoricoSubstituicoesService.php` | Agregação de substituições por mês, motivo e frota com filtros |
| `app/Http/Controllers/DashboardGerencialController.php` | Validação dos filtros e seleção das telas geral, histórica e de impressão |
| `resources/views/dashboard-gerencial/index.blade.php` | Visão geral simplificada |
| `resources/views/dashboard-gerencial/historico.blade.php` | Filtros e aba histórica |
| `resources/views/dashboard-gerencial/historico-pdf.blade.php` | Versão de impressão para exportar por “Salvar como PDF” |
| `resources/views/dashboard-gerencial/partials/historico-graficos.blade.php` | Gráficos reutilizáveis, exibidos na tela e no PDF |
| `resources/views/dashboard-gerencial/partials/historico-estilos.blade.php` | Estilos dos gráficos, incluindo impressão |
| `database/seeders/MotivosRetiradaSeeder.php` | Adiciona motivo de motor `MOT006` (periodicidade vencida) |
| `database/seeders/HistoricoSubstituicoesSeeder.php` | Gera 24 meses de instalações históricas totalmente fictícias |
| `tests/Feature/DashboardGerencialTest.php` | Testes da visão geral e pendências encerradas |
| `tests/Feature/HistoricoSubstituicoesTest.php` | Testes dos filtros, gráficos e impressão |
| `tests/Feature/HistoricoSubstituicoesSeederTest.php` | Testes do volume e idempotência da carga sintética |
| `tests/Feature/MotivosRetiradaTest.php` | Adequação ao novo motivo de motor |
| `routes/web.php` | Rotas protegidas da aba histórica e da versão imprimível |

## 4. Métodos e recursos utilizados

- **`DashboardGerencialService::consultar()`:** consulta instalações ativas, pendências por criticidade e encerramentos de 30 dias.
- **`HistoricoSubstituicoesService::consultar()`:** agrega os dados com filtros; inicializa os seis meses com zero, preservando meses sem trocas.
- **`HistoricoSubstituicoesService::base()`:** encapsula `JOIN` entre instalações, eixos, carros, trens e frotas e aplica os filtros de data, frota e motivo.
- **`DashboardGerencialController::filtrosHistorico()`:** valida o período (data final >= inicial), frota existente e motivo cadastrado.
- **`historico()` e `imprimirHistorico()`:** utilizam o mesmo serviço para evitar discrepâncias entre tela e relatório.
- **`GROUP BY` com `SUBSTR(data_retirada,1,7)`:** totaliza as retiradas por mês, compatível com MySQL e SQLite.
- **SVG e CSS:** barras horizontais/verticais e rosca sem dependência JavaScript externa; as mesmas figuras são preservadas ao salvar em PDF pelo navegador.
- **`HistoricoSubstituicoesSeeder::run()`:** prepara o histórico simulado em transação e com números de série únicos.

## 5. Carga acadêmica fictícia de 24 meses

**Finalidade:** produzir dados exclusivamente de demonstração; não confundir eventos simulados com histórico real do Metrô.

Janela fixa: **dezembro/2024 a novembro/2026**, total de 24 meses. Em cada mês:

| Tipo | Quantidade mensal | Total de 24 meses |
|---|---:|---:|
| Rodeiros | 25 | 600 |
| Motores | 20 | 480 |
| **Total** | **45** | **1.080** |

A distribuição se alterna pelas frotas I/J/L, com participação aproximadamente equilibrada. Nos rodeiros, predominam **periodicidade vencida (~43%)** e **diâmetro condenado (~32%)**, com os demais motivos variados. Nos motores, predomina **periodicidade vencida (~65%)**, pois `diâmetro condenado` não se aplica a motores. Foram utilizados números de série `RD-HIST-...` e `MT-HIST-...` para distinguir os registros sintéticos.

**Proteções:** o seeder impede execução em produção, utiliza transação, não remove equipamentos existentes, não altera instalações ativas, não muda pendências e não reutiliza números de série. A repetição do comando na mesma base não adiciona registros duplicados.

**Limitação do conjunto sintético:** para preservar o parque instalado, os novos históricos são criados como registros **já retirados** em posições da estrutura de demonstração. Por isso, não devem ser interpretados como uma sequência operacional real de ocupação física dos eixos; alguns períodos fictícios podem coincidir com instalações atuais. A carga existe exclusivamente para gráficos e exercícios acadêmicos.

### Executar no Laragon

Faça backup do banco de desenvolvimento antes de executar o seeder. No terminal:

```bash
git status
git switch feature/modulo-6-dashboard-gerencial
git pull origin feature/modulo-6-dashboard-gerencial
php artisan db:seed --class=MotivosRetiradaSeeder
php artisan db:seed --class=HistoricoSubstituicoesSeeder
```

**Não executar `migrate:fresh`.** A evolução não adiciona novas tabelas. O seeder de frotas/equipamentos já existentes também não deve ser repetido.

### Testes

```bash
php artisan test --filter=DashboardGerencialTest
php artisan test --filter=HistoricoSubstituicoesTest
php artisan test --filter=HistoricoSubstituicoesSeederTest
php artisan test --filter=MotivosRetiradaTest
php artisan test
```

## 6. Verificação no navegador

1. Acessar `http://sgr-m.test/indicadores` e confirmar a aba Visão geral.
2. Verificar o parque total, os cards de crítica/alta e as pendências encerradas em 30 dias.
3. Abrir `http://sgr-m.test/indicadores/historico`.
4. Conferir gráficos mensais, motivos e comparação I/J/L.
5. Filtrar por frota I e confirmar que cards e gráficos refletem somente I.
6. Filtrar por defeito e por intervalo de datas; conferir totais e distribuição.
7. Clicar em **Exportar PDF**; na nova aba, selecionar **Imprimir / Salvar como PDF**, escolher destino **Salvar como PDF** e confirmar legibilidade e filtros.
8. Conferir que perfis administrador, gestor e técnico visualizam ambas as abas; visitantes são redirecionados ao login.
9. Executar o seeder novamente e confirmar que não foram adicionadas duplicidades.

## 7. Testes, evidências e limitações

Os testes anteriores do dashboard foram validados pelo usuário no Laragon. Os testes e a apresentação desta evolução **ainda precisam ser executados e confirmados**.

Limitações: o PDF é exportado pelo recurso de impressão do navegador, **não por um serviço que entrega diretamente um PDF pronto**. O histórico contém registros artificiais identificados e não serve como evidência operacional. Os gráficos mensais têm janela fixa de seis meses, enquanto os totais podem abranger intervalos maiores. Não existe ainda trilha de importação/auditoria dos dados sintéticos por usuário.

## 8. Conceitos acadêmicos

- **Separação de responsabilidades (MVC + Service):** cálculo, filtros e visualização desacoplados.
- **Modelagem relacional:** instalações retiradas, motivos categorizados e hierarquia de frota.
- **Consultas analíticas:** `COUNT`, `GROUP BY`, agrupamentos temporais e múltiplas dimensões.
- **Reutilização:** mesmos gráficos nos relatórios de tela e impressão.
- **Integridade e segurança:** restrições de acesso, validação e geração sintética sem alterações nas instalações atuais.
- **Testes de integração:** dados de entrada controlados e assertivas sobre agregações.
- **Usabilidade:** distinção visual entre situação atual e eventos históricos.

**Resumo para apresentar:** “O dashboard separa a situação atual da frota do histórico de movimentações, visualiza séries mensais e distribuição das retiradas por motivo e frota, permite filtros e exportação por PDF e utiliza uma carga acadêmica fictícia identificada e isolada do parque instalado.”

## 9. Evolução — Indicadores de tendência e simulação de pendências

### 9.1 Painel de visão geral

Os três cards principais passam a aparecer juntos no topo:

| Card | Cálculo | Comportamento |
|---|---|---|
| Total de equipamentos instalados | Rodeiros + motores com instalações ativas | Atualiza com filtro de frota |
| Pendências encerradas nos últimos 30 dias | `situacao = resolvida` e `resolvida_em` entre hoje e 29 dias anteriores | Contagem real de encerramentos registrados; o número zero é válido |
| Tendência do saldo de pendências | Compara as janelas atual e anterior de 30 dias | Aumentando, diminuindo ou estável |

Para a tendência:
- **Aberturas** = quantidade de ocorrências com `created_at` no período.
- **Encerramentos** = quantidade com situação resolvida e `resolvida_em` no período.
- **Saldo** = aberturas − encerramentos.
- **Variação do saldo** = saldo dos últimos 30 dias − saldo dos 30 dias anteriores.
- **Aumentando** se a variação for positiva, **diminuindo** se negativa, e **estável** se zero.

O indicador compara o **fluxo líquido** de pendências, não a quantidade total acumulada de pendências abertas. Por exemplo, um saldo que muda de +10 para +3 significa que as pendências ainda crescem, mas em ritmo menor; por isso a tendência é classificada como “diminuindo”. Essa interpretação é explicitada na legenda.

Se o card de encerramentos estiver zerado, verifique se há ocorrências com situação resolvida **e data de resolução preenchida** dentro da janela. O fechamento de uma instalação, por si só, não constitui fechamento automático de uma pendência sem registro dessa resolução.

### 9.2 Carga fictícia de pendências técnicas

Novo seeder `database/seeders/PendenciasDemonstracaoSeeder.php`. Insere somente pendências **associadas a equipamentos atualmente instalados** nas frotas I, J e L.

| Prioridade | Rodeiros | Motores | Total |
|---|---:|---:|---:|
| Crítica | 5 | 2 | 7 |
| Alta | 10 | 4 | 14 |
| Média | 40 | 17 | 57 |
| Baixa | 15 | 7 | 22 |
| **Total de abertas** | **70** | **30** | **100** |

A carga inclui **20 registros resolvidos adicionais, exclusivamente fictícios**: 12 com encerramento nos últimos 30 dias e 8 nos 30 dias anteriores. Portanto, **120 registros fictícios no total**, dos quais 100 são abertos. Isso permite mostrar o card e o indicador de tendência em funcionamento sem modificar históricos ou resolver pendências reais.

Características de segurança e rastreabilidade:
- Restrito a ambiente de desenvolvimento/testes; bloqueado em produção.
- Usa marcador `[SGRM-DEMO-PEND]` na descrição para identificação.
- Usa motivo do catálogo, `eixo_id` e a FK correspondente à série ativa.
- Distribui posições entre frotas I/J/L, de modo reprodutível.
- Não altera equipamentos, instalações e pendências reais existentes.
- É idempotente: reexecução não duplica registros; se detectar conjunto incompleto, interrompe em vez de sobrescrever dados.
- Os fechamentos simulados são **registros adicionais**, não encerramentos das 100 pendências abertas.

### 9.3 Passos no Laragon

Faça backup do banco de desenvolvimento antes de inserir dados fictícios.

```bash
git switch feature/modulo-6-dashboard-gerencial
git pull origin feature/modulo-6-dashboard-gerencial

php artisan test --filter=DashboardGerencialTest
php artisan test --filter=HistoricoSubstituicoesTest
php artisan test --filter=PendenciasDemonstracaoSeederTest
php artisan test
```

Após a aprovação dos testes, execute:

```bash
php artisan db:seed --class=MotivosRetiradaSeeder
php artisan db:seed --class=PendenciasDemonstracaoSeeder
```

Não é necessário `migrate:fresh` nem recriar os equipamentos. Abra `/indicadores` para conferir os três cards superiores e `/indicadores/historico` para verificar as roscas por tipo e a ausência da frota de teste na comparação de frotas.

**Evidência pendente:** os testes e as telas desta evolução precisam ser aprovados no Laragon. A versão anterior do módulo havia sido validada.

## 10. Evolução — menu superior e agrupamento administrativo

O layout geral do sistema (`resources/views/layouts/app.blade.php`) passou a utilizar navegação responsiva com identidade visual aprimorada, indicadores de página ativa e menus suspensos. A estrutura da navegação é:

- **Início, Pendências, Ranking e Indicadores:** links principais de acesso rápido.
- **Consultas:** consulta hierárquica (frota/trem/carro/eixo) e consulta de equipamentos por série.
- **Administração (somente administrador):** Frotas, Trens, Equipamentos cadastrados (consulta) e Importar condições (CSV), contemplando diâmetros e periodicidade de rodeiros e motores.

O agrupamento não altera as permissões de acesso já protegidas por middleware. A consulta de equipamentos permanece disponível para todos os perfis; não foi criado cadastro manual de rodeiro ou motor, pois o módulo atual oferece consulta e importação de condições, sem formulário de inclusão individual desses equipamentos.

### Recursos de interface

- Menus suspensos nativos em `details/summary`, operáveis por teclado.
- Navegação adaptável em telas menores, com botão de expansão e atributos `aria-expanded`.
- Identificação da página ativa e foco visível para acessibilidade.
- Cabeçalho com perfil de usuário e encerramento de sessão por POST com CSRF.
- JavaScript limitado a expandir/recolher o menu e fechar submenus.

### Verificação

```bash
php artisan test --filter=MenuSuperiorTest
php artisan test
```

Verificar visualmente os menus como administrador, gestor e técnico, no desktop e em largura reduzida. O menu Administração deve aparecer somente para administrador, e o dropdown não deve substituir o controle de acesso aplicado às rotas.

**Situação:** implementação e testes enviados ao GitHub; validação no Laragon pendente.

## 11. Dashboard gerencial como página inicial

A rota autenticada `/dashboard` (nome `dashboard`) passou a utilizar `DashboardGerencialController::index`, que também atende `/indicadores`. Com isso, a aplicação exibe a mesma visão geral de indicadores ao entrar no sistema, ao clicar em **Início** ou na marca **SGR-M**, sem manter duas implementações distintas do painel.

O endereço raiz `/` continua direcionando visitantes ao login e usuários autenticados para `/dashboard`. O controlador de login preserva `redirect()->intended(route('dashboard'))`, portanto o comportamento pós-autenticação também leva aos indicadores. A lógica de filtros e permissões do dashboard permanece a mesma.

Teste adicionado em `DashboardGerencialTest`: acesso anônimo, redirecionamento autenticado, indicadores visíveis e funcionamento do filtro por frota. A view inicial anterior (`resources/views/dashboard.blade.php`) deixa de ser usada nessa navegação, mas não precisa ser excluída nesta alteração.

**Validação:** executar `php artisan test --filter=DashboardGerencialTest` e `php artisan test`; confirmar o redirecionamento após login e o conteúdo exibido por Início e pela marca do sistema.
