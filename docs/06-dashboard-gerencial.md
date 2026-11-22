# SGR-M — Módulo 06: Dashboard Gerencial

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-6-dashboard-gerencial`  
**Situação:** código publicado no GitHub; execução dos novos testes e validação visual pendentes no Laragon.

## 1. Objetivo

Consolidar os dados dos módulos anteriores em uma página gerencial somente leitura, permitindo acompanhar a composição do parque instalado, as pendências técnicas, as condições de diâmetro e periodicidade e as substituições de equipamentos.

## 2. Regras de negócio

| Indicador | Critério | Referência temporal |
|---|---|---|
| Rodeiros/motores instalados | Instalações com `data_retirada IS NULL` | Situação atual |
| Periodicidade >=100% | Equipamentos instalados cujo percentual atende ao limite | Situação atual |
| Sem leitura de periodicidade | Percentual nulo em equipamento instalado | Situação atual |
| Diâmetro <745 mm | Menor entre LR e LO inferior a 745 | Situação atual |
| Diâmetro crítico <=742 mm | Menor entre LR e LO até 742 | Situação atual |
| Pendências por prioridade | Ocorrências abertas do equipamento instalado no eixo original | Situação atual |
| Equipamentos com pendências | Equipamentos instalados **distintos** com ao menos uma ocorrência aberta | Situação atual |
| Substituições de rodeiros/motores | Históricos com `data_retirada` preenchida | Período selecionado |
| Trens por frota | Contagem de trens cadastrados | Cadastro atual |

Os filtros por frota afetam **todos** os indicadores. Os filtros de data inicial/final afetam **somente substituições**, já que os demais indicadores são retratos do estado atual e não representam séries históricas. Sem datas, as substituições representam todo o histórico registrado.

**Porcentagens** de condições e equipamentos com pendência usam como denominador o total do tipo instalado na frota. Várias pendências no mesmo equipamento contam como várias ocorrências, mas apenas uma peça afetada. Quando não existe equipamento instalado, o percentual é zero.

**Observação:** os indicadores são auxiliares para apresentação acadêmica; não representam autorização automática de intervenção em equipamentos reais.

## 3. Arquivos e responsabilidades

| Arquivo | Finalidade |
|---|---|
| `app/Services/DashboardGerencialService.php` | Agregação SQL de instalações, condições, pendências e retiradas |
| `app/Http/Controllers/DashboardGerencialController.php` | Validação dos filtros de frota e período |
| `resources/views/dashboard-gerencial/index.blade.php` | Cards, percentuais, legendas e apresentação gerencial |
| `tests/Feature/DashboardGerencialTest.php` | Testes de acesso, indicadores, deduplicação e filtros |
| `routes/web.php` | Rota GET `/indicadores` protegida por autenticação e perfis |
| `resources/views/layouts/app.blade.php` | Link Indicadores na navegação |
| `docs/06-dashboard-gerencial.md` | Documentação e roteiro acadêmico |
| `README.md` | Registro do avanço do projeto |

Nenhuma migration ou seeder foi necessária: os indicadores utilizam tabelas já existentes.

## 4. Métodos e técnicas de programação

- **`DashboardGerencialController::index()`:** valida `frota`, `inicio` e `fim`, rejeita período invertido e apresenta a view.
- **`DashboardGerencialService::consultar()`:** devolve indicadores separados por tipo e critérios.
- **`baseInstalacoes()`:** utiliza Query Builder com `JOIN` entre equipamento, instalação, eixo, carro, trem e frota.
- **`instalacoesAtivas()`:** restringe à situação física atual (`data_retirada IS NULL`).
- **`instalacoesHistoricas()`:** seleciona apenas instalações retiradas.
- **`pendenciasAtivas()`:** relaciona pendência aberta ao mesmo eixo e número de equipamento atualmente instalado.
- **`COUNT(DISTINCT i.id)`:** conta equipamentos com pendência sem inflar a porcentagem por múltiplas ocorrências.
- **`whereDate()`:** filtra retiradas por data, de forma inclusiva.
- **Expressão `CASE`:** seleciona a menor medição válida entre LR e LO, incluindo leituras parciais.
- **Blade:** apresenta quantidades, percentuais, barras de proporção e cores sem modificar dados.
- **`RefreshDatabase`:** testes isolados em banco temporário.

## 5. Execução no Laragon

Atualizar a branch `develop` que já possui o Módulo 05 e buscar a nova branch:

```bash
git status
git switch develop
git pull origin develop
git fetch origin
git switch -c feature/modulo-6-dashboard-gerencial --track origin/feature/modulo-6-dashboard-gerencial
php artisan test --filter=DashboardGerencialTest
php artisan test
```

Se a branch já existir localmente, fazer `git switch feature/modulo-6-dashboard-gerencial` e `git pull`.

Não executar `migrate:fresh`, seeders ou migrations adicionais.

## 6. Verificação funcional

1. Entrar como administrador, gestor ou técnico.
2. Abrir `http://sgr-m.test/indicadores`.
3. Verificar instalados e alertas dos dois tipos; comparar com o ranking existente.
4. Filtrar pela frota I e conferir que pendências, condições, parque e movimentações reflitam somente essa frota.
5. Definir datas inicial e final e confirmar que apenas as substituições alteram suas contagens.
6. Conferir equipamentos com mais de uma pendência: a quantidade de ocorrências é maior, mas o indicador de equipamentos afetados não os duplica.
7. Conferir a contagem de rodeiros críticos pelo menor LR/LO.
8. Verificar a lista de trens cadastrados por frota.
9. Abrir os links para Ranking e Pendências.

## 7. Testes, evidências e limitações

Os cinco testes Feature foram criados para validar autenticação, agregações de instalações e condições, ocorrências versus equipamentos distintos, filtros de retirada/frota e rejeição de filtros inválidos.

**Estado:** o usuário confirmou a aprovação dos testes do Módulo 05, integrado à `develop`. Os testes do Módulo 06 ainda precisam ser executados localmente.

**Limitações:** esta versão não gera gráficos temporais, exportações, séries históricas consolidadas nem captura do estado anterior dos indicadores. O filtro de datas não tenta reconstruir a condição histórica. O volume de trens é referente a cadastro, não necessariamente a trens operacionais.

## 8. Conceitos acadêmicos e apresentação

- **Integração incremental:** indicadores derivados das tabelas construídas em entregas anteriores.
- **Separação de responsabilidades:** controller, serviço de domínio e view independentes.
- **SQL relacional:** `JOIN`, agregações e filtros.
- **Consistência:** distinção entre peças instaladas, históricos de retirada e ocorrências abertas.
- **Rastreabilidade:** contagem de substituições por registro de retirada.
- **Usabilidade:** filtros, cards, cores e legendas objetivas.
- **Testes de integração:** valores de indicadores comparados com dados de teste controlados.

**Resumo para apresentar:** “O dashboard reúne, em uma única visão, a quantidade de equipamentos instalados, as ocorrências técnicas, as condições medidas e o histórico de substituições. Os filtros distinguem a situação atual da frota dos eventos registrados em um período.”
