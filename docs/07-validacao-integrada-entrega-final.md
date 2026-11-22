# SGR-M — Módulo 07: Validação Integrada e Preparação da Entrega Final

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M)  
**Instituição:** Fatec São Paulo — Laboratório de Engenharia de Software  
**Branch:** `feature/entrega-final-validacao-integrada`  
**Estado:** plano, documentação e organização da homologação iniciados; os módulos 01–06 foram aprovados separadamente no Laragon pelo desenvolvedor.

## 1. Objetivo

Revisar o funcionamento conjunto dos módulos implementados, identificar regressões, verificar a coerência de dados apresentados entre telas, consolidar evidências e preparar uma demonstração acadêmica reproduzível. O objetivo **não** é implantar a aplicação em produção.

## 2. Escopo efetivamente implementado

- Autenticação e perfis **administrador**, **gestor** e **técnico**.
- Estrutura de frotas I/J/L, trens, carros, eixos e equipamentos por série.
- Consulta por série e consulta hierárquica.
- Movimentação transacional de rodeiros e motores com justificativa por catálogo de motivos.
- Gestão de pendências técnicas, com encerramento automático no contexto da substituição.
- Importação de CSV com onze colunas: frota, trem, carro, posição, série do rodeiro, LR, LO, periodicidade do rodeiro, série do motor, periodicidade do motor e data de atualização; relatório detalhado por tipo de equipamento.
- Ranking de pendências, condições de diâmetro e periodicidade, separado em rodeiros/motores.
- Visão geral de indicadores, tendência de pendências, histórico mensal de substituições, roscas de motivos e comparação entre frotas, além de versão para impressão/salvamento em PDF.
- Central inicial de funcionalidades e menus condicionados ao perfil.

**Fora do escopo entregue:** CRUD individual de carros, eixos, rodeiros e motores; gerenciamento de usuários pela interface; geração direta de arquivo PDF pelo servidor; operação ferroviária real.

## 3. Preparação segura do ambiente

1. Verifique a branch e a ausência de mudanças locais antes de alternar:
   ```bash
   git status
   git switch develop
   git pull origin develop
   git fetch origin
   git switch -c feature/entrega-final-validacao-integrada --track origin/feature/entrega-final-validacao-integrada
   ```
   Se a branch já existir localmente: `git switch feature/entrega-final-validacao-integrada` e `git pull`.
2. Faça backup do MySQL acadêmico antes de testes manuais que alterem dados.
3. Use somente dados fictícios. Evite capturas com senhas, dados internos ou informações pessoais.
4. Execute:
   ```bash
   php artisan optimize:clear
   php artisan migrate:status
   php artisan route:list
   php artisan test
   ```
5. Para análise de formato PHP sem reformatar o código automaticamente, execute `./vendor/bin/pint --test` no terminal do Windows (ou `vendor\\bin\\pint --test` conforme o terminal utilizado).
6. Não execute `migrate:fresh` em um banco que deseja preservar. Não repita seeders que não sejam idempotentes.

## 4. Matriz de homologação funcional

| ID | Cenário | Comportamento esperado | Evidência sugerida |
|---|---|---|---|
| H01 | Acessar `/` sem sessão | Encaminha ao login | Captura do login |
| H02 | Entrar como administrador | Home com cards principais e atalhos administrativos | Home e menu Administração |
| H03 | Entrar como gestor | Consultas, pendências, ranking e indicadores; sem cadastros administrativos | Home e menu |
| H04 | Entrar como técnico | Atalhos operacionais, incluindo consulta e registro de pendência | Home e menu |
| H05 | Acessar importação como não administrador | Acesso negado no servidor, mesmo por URL direta | Teste de autorização |
| H06 | Consulta hierárquica I/J/L | Frota → trem → carro → eixo, exibindo séries corretas | Consulta de carro |
| H07 | Consulta por série | Localiza o equipamento e sua situação | Consulta por número de série |
| H08 | Registrar pendência vinculada ao eixo | Mantém tipo, prioridade, motivo e equipamento instalado | Cadastro e lista |
| H09 | Substituir equipamento com série em estoque | Instala nova peça, encerra vínculo anterior e preserva histórico | Antes/depois e histórico |
| H10 | Conferir pendência da peça substituída | Pendência aberta da peça retirada/eixo passa a resolvida, sem encerrar pendências de outros equipamentos | Lista de pendências |
| H11 | Importar CSV de condições mais recentes | Atualiza rodeiro/motor independentemente, respeitando série, eixo e data | Relatório linha a linha |
| H12 | Reimportar mesmo CSV | Registros com data igual não são atualizados novamente | Relatório de ignorados |
| H13 | Ranking rodeiros | Pendências por criticidade, diâmetro crescente, periodicidade decrescente, cada tabela até dez resultados | Aba de rodeiros |
| H14 | Ranking motores | Pendências e periodicidade; sem diâmetro de roda | Aba de motores |
| H15 | Cards do ranking | Total instalado e percentuais acompanham filtros de cada tabela | Antes/depois do filtro |
| H16 | Indicadores gerais | Parque instalado, críticas, altas, encerradas em 30 dias e tendência coerentes | Visão geral |
| H17 | Histórico de substituições | Gráfico mensal, roscas por tipo e comparação somente I/J/L | Aba histórica |
| H18 | Filtros por data, frota e motivo | Gráficos e totais atualizados coerentemente | Gráficos filtrados |
| H19 | Exportar histórico para PDF | Página de impressão com filtros e gráficos; salvar como PDF pelo navegador | PDF salvo e verificado |
| H20 | Encerrar sessão | Rota protegida deixa de ser acessível sem novo login | Login após logout |

**Critério para aprovação:** nenhum teste automatizado com falha; todos os cenários relevantes verificados; divergências documentadas e corrigidas antes do merge final.

## 5. Consistência entre módulos

### 5.1 Movimentação e pendências

- Confirmar que a instalação ativa antiga é encerrada na data informada, enquanto a nova fica ativa.
- Confirmar que o número de série retirado não permanece vinculado como instalado.
- Confirmar encerramento automático apenas das pendências abertas correspondentes ao tipo/eixo/equipamento retirado.
- Confirmar o registro do motivo no histórico de substituições e o reflexo nos gráficos.
- Confirmar ausência de interferência de registros sintéticos em instalações atualmente ativas.

### 5.2 Importação e ranking

- Verificar diâmetros LR/LO e percentuais após carga CSV, incluindo motor e rodeiro no mesmo registro.
- Confirmar comparação da data de atualização de cada equipamento separadamente.
- Conferir que o ranking usa somente instalações atuais.
- Confirmar que os cards de porcentagem consideram equipamentos instalados na localização filtrada, e não apenas os dez primeiros.
- Pendências múltiplas do mesmo equipamento não devem inflar o percentual de equipamentos afetados.

### 5.3 Dashboard e histórico

- Comparar o parque instalado do painel gerencial com as instalações ativas cadastradas.
- Conferir se a tendência utiliza `created_at` para aberturas e `resolvida_em` para encerramentos em duas janelas consecutivas de 30 dias.
- Conferir que os 24 meses fictícios do histórico correspondem a aproximadamente 25 rodeiros e 20 motores por mês, quando a carga é executada.
- Verificar que o PDF exportado via impressão corresponde aos filtros selecionados no histórico.
- Identificar claramente dados de demonstração como **fictícios**, sem atribuí-los a operações reais.

## 6. Evidências e organização da entrega

Proposta de estrutura local para organizar evidências, sem expor credenciais:

```text
evidencias/
  01-login-perfis/
  02-estrutura-consultas/
  03-pendencias-movimentacoes/
  04-importacao-csv/
  05-ranking/
  06-indicadores-historico/
  07-testes-automatizados/
  08-relatorio-pdf/
```

Para cada módulo, guardar: captura da funcionalidade, um cenário de entrada e saída, mensagem ou resultado de sucesso, e o(s) teste(s) automatizado(s) relevante(s). Registrar data, branch, commit e resultado observado. Não é necessário publicar dados fictícios com qualquer identificação pessoal no repositório.

## 7. Roteiro de demonstração acadêmica

Uma demonstração concisa pode seguir a sequência:

1. **Contexto e problema:** necessidade de rastrear rodeiros/motores por série e organizar condições e pendências na estrutura Frota → Trem → Carro → Eixo.
2. **Arquitetura:** Laravel 12, PHP, MySQL, Blade, autenticação e divisão entre controllers, models e services.
3. **Navegação e permissões:** login, central inicial, menu Administração e perfis.
4. **Consulta:** localizar um rodeiro e um motor instalados em um carro de uma das frotas fictícias.
5. **Pendências e movimentação:** selecionar ocorrência aberta, executar uma substituição de teste e mostrar fechamento vinculado e histórico.
6. **Importação CSV:** apresentar as onze colunas e um relatório com linha atualizada/ignorada e justificativa.
7. **Ranking:** explicar critérios de criticidade, diâmetro e periodicidade, filtros e denominadores.
8. **Dashboard:** mostrar indicadores atuais, tendência e os gráficos históricos por mês, motivo e frota.
9. **Exportação:** abrir o relatório para impressão e demonstrar Salvar como PDF.
10. **Qualidade e conclusão:** apresentar testes automatizados, fluxo Git/PRs, limitações e possibilidades de evolução.

**Cuidado durante a demonstração:** utilizar uma base acadêmica separada ou backup para operações que mudem equipamento/pendência e não afirmar que os dados simulados são ocorrências operacionais.

## 8. Riscos e pendências para avaliação final

- **Documentação desatualizada:** atualizar textos antigos que ainda dizem “planejado”, “em desenvolvimento” ou “testes pendentes” em módulos já validados.
- **Dados sintéticos:** os registros históricos artificiais podem compartilhar posições de eixos com instalações atuais; são apropriados para gráficos, mas não para reconstruir uma sequência cronológica operacional completa.
- **Exportação PDF:** depende de “Salvar como PDF” do navegador; não há endpoint binário de PDF gerado no servidor.
- **Autorização:** exibição/ocultação de botões não é substituta de middleware. Testar acesso direto às URLs.
- **Desempenho:** avaliar consultas com a base de demonstração completa; o conjunto de testes automatizados não comprova capacidade operacional em escala.
- **Escopo limitado:** não sugerir telas de cadastro de equipamentos/usuários que não foram desenvolvidas.

## 9. Registro de homologação

| Campo | Registro |
|---|---|
| Branch | `feature/entrega-final-validacao-integrada` |
| Módulos anteriores | 01–06 integrados à `develop` |
| Testes dos módulos anteriores | Validados no Laragon pelo desenvolvedor em etapas |
| Suíte completa na branch final | Aguardando execução local |
| Testes manuais de ponta a ponta | Aguardando execução/documentação |
| Aprovação da entrega integrada | Pendente |

**Critério de fechamento:** concluir os testes na branch final, revisar documentação e evidências e somente então solicitar merge em `develop`.
