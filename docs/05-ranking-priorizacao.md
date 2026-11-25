# SGR-M — Módulo 05: Painel de priorização por tipo e condição

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-5-ranking-priorizacao`  
**Situação:** versão anterior validada no Laragon; novos cards proporcionais enviados ao GitHub e aguardando testes locais.

## 1. Objetivo

Organizar a consulta de priorização em abas superiores distintas para **Rodeiros** e **Motores**, separando as ocorrências técnicas das informações quantitativas de condição. Na aba de rodeiros, são exibidas três tabelas na ordem **Pendências → Diâmetros → Periodicidade**. Na aba de motores, são exibidas pendências e periodicidade; motores não possuem diâmetro de roda.

## 2. Regras de negócio

| Bloco | Regra de seleção | Ordenação | Quantidade exibida |
|---|---|---|---|
| Pendências | Somente pendências abertas do equipamento atualmente instalado | Crítica, alta, média, baixa | 10 primeiras |
| Diâmetros | Rodeiros instalados com menor diâmetro LR/LO inferior a 745 mm | Menor diâmetro primeiro | 10 primeiros |
| Periodicidade | Rodeiros **ou motores** instalados com periodicidade superior a 100% | Maior percentual primeiro | 10 primeiros |

**Filtros independentes:** cada bloco possui filtros próprios de **frota, trem e carro**. O bloco de pendências também oferece prioridade. Ao aplicar uma prioridade, os cinco cards continuam mostrando a distribuição por categoria da localização selecionada; a lista de 10 é filtrada pela prioridade. Ao aplicar os filtros de diâmetro, a lista de pendências e a de periodicidade mantêm seus critérios originais. A aba seleciona rodeiros ou motores e mantém a separação por tipo.

**Cards do bloco de pendências:** total com filtros de localização; quantidade por prioridade crítica, alta, média e baixa. Todos contam ocorrências abertas de equipamentos instalados, não apenas as 10 primeiras linhas.

**Cards do bloco de periodicidade:** total com periodicidade >100%, quantidade >=105% e >=110%, em rodeiros ou motores, respeitando os filtros de frota/trem/carro da própria seção.

**Cards do bloco de diâmetros:** 
- **Total abaixo de 745 mm:** conta os rodeiros que atendem à condição após aplicação dos filtros de diâmetro.
- **Críticos com menor diâmetro menor ou igual a 742 mm:** subconjunto da contagem anterior.

Os cards consideram **todos os equipamentos encontrados**, mesmo que somente os 10 primeiros apareçam na tabela.

As mesmas regras de instalação ativa e de rastreabilidade permanecem: pendências encerradas e equipamentos retirados não são exibidos. O mesmo rodeiro pode aparecer em tabelas diferentes (por exemplo, com pendência e diâmetro reduzido), pois os quadros são visões independentes, não mais uma classificação única. Isso não duplica registros dentro da mesma tabela.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/RankingPriorizacaoService.php` | Monta consultas SQL independentes, filtros por frota/trem/carro, cards por criticidade e faixas de periodicidade, e `LIMIT 10` |
| `app/Http/Controllers/RankingPriorizacaoController.php` | Valida aba, filtros e pertinência entre trem e carro |
| `resources/views/ranking/index.blade.php` | Abas, tabelas, tags coloridas, cards e ações |
| `resources/views/ranking/partials/filtros.blade.php` | Formulários reutilizáveis com filtros próprios |
| `tests/Feature/RankingPriorizacaoTest.php` | Cobertura dos três quadros, abas, filtros, limites e autorização |
| `docs/05-ranking-priorizacao.md` | Registro acadêmico da evolução |

Nenhuma migration ou seeder é necessária; o módulo utiliza os dados persistidos de instalações, medições e pendências.

## 4. Métodos e recursos utilizados

- **`RankingPriorizacaoService::consultar(string, array)`:** retorna listas e totais para cada bloco a partir da aba selecionada.
- **`instalacoes(string)`:** constrói os joins entre frota, trem, carro, eixo, instalação ativa e equipamento.
- **`filtrarLocalizacao(Builder, array, string)`:** aplica filtros de frota, trem e carro com prefixo **p_**, **d_** ou **r_** conforme o bloco, isolando suas seleções.
- **`consultaPendencias()`:** seleciona somente pendências abertas cujo equipamento e eixo correspondem à instalação ativa, ordenadas por criticidade.
- **`menorDiametroSql()`:** expressão `CASE` compatível com SQLite e MySQL, que usa a menor medida disponível entre LR e LO mesmo quando uma das leituras está vazia.
- **`consultaDiametros()`:** aplica limite de 745 mm; permite contagens independentes para o card de menor ou igual a 742 mm.
- **`consultaPeriodicidades()`:** consulta rodeiros ou motores instalados com percentual superior a 100%, conta as faixas >=105 e >=110 e ordena de modo decrescente.
- **`limit(10)`:** restringe a consulta de cada tabela às primeiras 10 posições, sem limitar os totais calculados para os cards.
- **`RankingPriorizacaoController::index()`:** valida tipos e códigos, e rejeita a associação de carro com trem incorreto.
- **Partial Blade de filtros:** reaproveita o layout dos três formulários. O JavaScript apenas ajusta visualmente as opções de carro em cada formulário; todas as validações permanecem no servidor.

## 5. Procedimento no Laragon

Na branch de trabalho já existente:

```bash
git status
git switch feature/modulo-5-ranking-priorizacao
git pull origin feature/modulo-5-ranking-priorizacao
php artisan test --filter=RankingPriorizacaoTest
php artisan test
```

A rota continua `http://sgr-m.test/ranking`.

**Não executar migrations ou seeders.** Não há alteração de estrutura de banco.

## 6. Verificação funcional

1. Entrar como administrador, gestor ou técnico e abrir o ranking.
2. Selecionar a aba Rodeiros e confirmar três tabelas na ordem solicitada.
3. Na primeira tabela, confirmar precedência crítica → alta → média → baixa.
4. Filtrar pendências por frota, trem, carro e prioridade e conferir os cinco cards (total, crítica, alta, média e baixa).
5. Conferir cards de diâmetros: abaixo de 745 mm e críticos com menor diâmetro até 742 mm.
6. Filtrar diâmetros por frota/trem/carro e verificar que os cards acompanham essa seleção.
7. Confirmar a ordenação do menor diâmetro até o maior, mostrando no máximo 10 linhas.
8. Confirmar a periodicidade da maior para a menor, com no máximo 10 linhas e três cards >100%, >=105% e >=110%.
9. Aplicar filtro em uma tabela e observar que as demais não mudam.
10. Alternar para Motores e confirmar que pendências e periodicidade dos motores aparecem, sem tabela de diâmetros.
11. Entrar como gestor: a consulta permanece liberada, mas a ação Substituir não aparece.
12. Verificar registros históricos: equipamentos retirados ou pendências resolvidas não entram no ranking.

## 7. Testes e limitações

A suíte Feature cobre autorização, separação de abas, precedência de pendências, ordenação dos diâmetros, cards por criticidade, cards de periodicidade dos motores nas faixas 100/105/110%, filtros por frota independentes, consistência de trem/carro, limite de dez resultados por tabela e exclusão de instalações retiradas.

**Evidência:** a versão anterior do Módulo 05 teve seus testes aprovados pelo usuário no Laragon. Os novos testes ainda precisam ser executados após a atualização desta interface.

**Limitações:** a versão oferece até dez registros por bloco, sem paginação adicional; os valores são critérios acadêmicos e não constituem autorização de manutenção real. O módulo não registra nem altera pendências ao apresentar alertas de diâmetro ou periodicidade.

## 8. Conceitos acadêmicos e pontos para apresentação

- **Separação de responsabilidades:** controller valida filtros, service executa consultas e Blade organiza a interface.
- **Coesão:** três consultas independentes representam três perspectivas de priorização.
- **SQL e Eloquent Query Builder:** joins, filtros, `CASE`, `COUNT`, `ORDER BY` e `LIMIT`.
- **Integridade lógica:** correlação entre instalação ativa, posição e ocorrência.
- **Componentização Blade:** reaproveitamento de filtros sem repetição excessiva.
- **Usabilidade:** abas por tipo, hierarquia visual e tags de severidade.
- **Testes automatizados:** verificação de ordem, limites exatos e isolamento dos filtros.

**Resumo para apresentação:** “A página de priorização foi organizada em abas de rodeiros e motores, com três rankings independentes para pendências, diâmetros e periodicidade. Os filtros e indicadores de cada bloco permitem identificar ocorrências críticas sem misturar tipos distintos de alerta.”

## 9. Evolução: motor com periodicidade, três níveis de filtragem e novos cards

O ranking passa a apresentar periodicidade tanto para rodeiros quanto para motores. A estrutura de dados dos motores é ampliada por uma migration do Módulo 04 (consulte a documentação de importação); não existem campos de diâmetro nos motores. As três seções utilizam filtros de frota, trem e carro independentes. Pendências exibem cinco cards: total e quatro níveis de criticidade. Periodicidade exibe três cards: valores >100%, >=105% e >=110%. Cada card é calculado sobre o conjunto filtrado completo, enquanto a tabela mantém somente o Top 10.

## 10. Evolução — Indicadores proporcionais ao parque instalado

Os cards de pendências, diâmetro e periodicidade agora apresentam **quantidade, porcentagem, total de equipamentos instalados e barra gráfica de participação**. Os denominadores são calculados de maneira independente para cada seção (filtros `p_`, `d_` e `r_`) e aba (rodeiro ou motor), sempre considerando **somente equipamentos com instalação ativa**.

### Definições dos indicadores

| Seção | Numerador | Denominador | Significado |
|---|---|---|---|
| Pendências — total | Equipamentos distintos com pelo menos uma pendência aberta | Equipamentos instalados na localização filtrada | Porcentagem de equipamentos afetados |
| Pendências — crítica/alta/média/baixa | Equipamentos distintos com ocorrência aberta da respectiva prioridade | Equipamentos instalados na localização filtrada | Porcentagem afetada por prioridade |
| Diâmetro abaixo de 745 mm | Rodeiros instalados cujo menor diâmetro é inferior a 745 mm | Rodeiros instalados na localização filtrada | Incidência de diâmetro de atenção |
| Diâmetro crítico ≤742 mm | Rodeiros instalados cujo menor diâmetro é menor ou igual a 742 mm | Rodeiros instalados na localização filtrada | Incidência de diâmetro crítico |
| Periodicidade >100%, ≥105% e ≥110% | Rodeiros ou motores instalados que atendem ao respectivo limite | Equipamentos do tipo instalados na localização filtrada | Incidência de periodicidade acima da faixa |

A fórmula para todos os cards é **percentual = numerador ÷ total instalado × 100**. Quando não existem equipamentos instalados no filtro, o percentual exibido é 0%, evitando divisão por zero.

**Distinção entre ocorrências e equipamentos:** várias pendências em um mesmo equipamento contam como várias ocorrências, mas **uma única unidade afetada** no percentual geral. Um equipamento com pendências de prioridades diferentes aparece nas respectivas faixas; por isso, a soma das porcentagens por prioridade pode superar a porcentagem geral de equipamentos afetados. As contagens de ocorrências continuam indicadas como dado complementar nos cards.

**Faixas cumulativas:** rodeiros ≤742 mm pertencem também à contagem abaixo de 745 mm. Da mesma forma, equipamentos com periodicidade ≥110% pertencem às três faixas de periodicidade.

**Apresentação visual:** cores para diferenciação de criticidade e faixas, barra proporcional, quantitativo instalado e legenda explicativa por seção. A tabela mantém o limite de 10 linhas; os cards usam o conjunto completo de dados da localização filtrada.

### Arquivos alterados nesta evolução

- `app/Services/RankingPriorizacaoService.php`: adição de `contarInstalados()` e `contarDistintos()`, com denominadores independentes e contagens de equipamentos distintos por prioridade.
- `app/Http/Controllers/RankingPriorizacaoController.php`: envio das contagens adicionais à view.
- `resources/views/ranking/index.blade.php`: cores, percentuais, barras e legendas.
- `tests/Feature/RankingPriorizacaoTest.php`: testes de ocorrência duplicada no mesmo equipamento, filtros independentes, motores e ausência de instalados.

**Validação no Laragon:** executar `php artisan test --filter=RankingPriorizacaoTest` e `php artisan test`; conferir visualmente os cards nas abas de rodeiros e motores e aplicar filtros separadamente em cada bloco.

Não há alteração de tabelas, migrations ou seeders nesta evolução.
