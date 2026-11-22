# SGR-M — Módulo 05: Painel de priorização por tipo e condição

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-5-ranking-priorizacao`  
**Situação:** versão anterior aprovada no Laragon; atualização de interface e consultas enviada ao GitHub e aguardando testes locais.

## 1. Objetivo

Organizar a consulta de priorização em abas superiores distintas para **Rodeiros** e **Motores**, separando as ocorrências técnicas das informações quantitativas de condição. Na aba de rodeiros, são exibidas três tabelas na ordem **Pendências → Diâmetros → Periodicidade**. Na aba de motores, são exibidas as pendências, pois os motores não têm diâmetro de roda nem percentual de periodicidade modelados.

## 2. Regras de negócio

| Bloco | Regra de seleção | Ordenação | Quantidade exibida |
|---|---|---|---|
| Pendências | Somente pendências abertas do equipamento atualmente instalado | Crítica, alta, média, baixa | 10 primeiras |
| Diâmetros | Rodeiros instalados com menor diâmetro LR/LO inferior a 745 mm | Menor diâmetro primeiro | 10 primeiros |
| Periodicidade | Rodeiros instalados com periodicidade maior ou igual a 100% | Maior percentual primeiro | 10 primeiros |

**Filtros independentes:** cada bloco possui filtros próprios de trem e carro. O bloco de pendências também oferece prioridade. Ao aplicar os filtros de diâmetro, a lista de pendências e a de periodicidade mantêm seus critérios originais. A aba seleciona rodeiros ou motores e mantém a separação por tipo.

**Cards do bloco de diâmetros:** 
- **Total abaixo de 745 mm:** conta os rodeiros que atendem à condição após aplicação dos filtros de diâmetro.
- **Críticos com menor diâmetro menor ou igual a 742 mm:** subconjunto da contagem anterior.

Os cards consideram **todos os equipamentos encontrados**, mesmo que somente os 10 primeiros apareçam na tabela.

As mesmas regras de instalação ativa e de rastreabilidade permanecem: pendências encerradas e equipamentos retirados não são exibidos. O mesmo rodeiro pode aparecer em tabelas diferentes (por exemplo, com pendência e diâmetro reduzido), pois os quadros são visões independentes, não mais uma classificação única. Isso não duplica registros dentro da mesma tabela.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/RankingPriorizacaoService.php` | Monta consultas SQL independentes, aplica filtros, contagens e `LIMIT 10` |
| `app/Http/Controllers/RankingPriorizacaoController.php` | Valida aba, filtros e pertinência entre trem e carro |
| `resources/views/ranking/index.blade.php` | Abas, tabelas, tags coloridas, cards e ações |
| `resources/views/ranking/partials/filtros.blade.php` | Formulários reutilizáveis com filtros próprios |
| `tests/Feature/RankingPriorizacaoTest.php` | Cobertura dos três quadros, abas, filtros, limites e autorização |
| `docs/05-ranking-priorizacao.md` | Registro acadêmico da evolução |

Nenhuma migration ou seeder é necessária; o módulo utiliza os dados persistidos de instalações, medições e pendências.

## 4. Métodos e recursos utilizados

- **`RankingPriorizacaoService::consultar(string, array)`:** retorna listas e totais para cada bloco a partir da aba selecionada.
- **`instalacoes(string)`:** constrói os joins entre frota, trem, carro, eixo, instalação ativa e equipamento.
- **`filtrarLocalizacao(Builder, array, string)`:** aplica filtros com prefixo **p_**, **d_** ou **r_** conforme o bloco, isolando suas seleções.
- **`consultaPendencias()`:** seleciona somente pendências abertas cujo equipamento e eixo correspondem à instalação ativa, ordenadas por criticidade.
- **`menorDiametroSql()`:** expressão `CASE` compatível com SQLite e MySQL, que usa a menor medida disponível entre LR e LO mesmo quando uma das leituras está vazia.
- **`consultaDiametros()`:** aplica limite de 745 mm; permite contagens independentes para o card de menor ou igual a 742 mm.
- **`consultaPeriodicidades()`:** aplica percentual maior ou igual a 100% e ordena de modo decrescente.
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
4. Filtrar pendências por trem, carro e prioridade.
5. Conferir cards de diâmetros: abaixo de 745 mm e críticos com menor diâmetro até 742 mm.
6. Filtrar diâmetros por trem/carro e verificar que os cards acompanham essa seleção.
7. Confirmar a ordenação do menor diâmetro até o maior, mostrando no máximo 10 linhas.
8. Confirmar a periodicidade da maior para a menor, com no máximo 10 linhas.
9. Aplicar filtro em uma tabela e observar que as demais não mudam.
10. Alternar para Motores e confirmar que apenas pendências de motores são apresentadas.
11. Entrar como gestor: a consulta permanece liberada, mas a ação Substituir não aparece.
12. Verificar registros históricos: equipamentos retirados ou pendências resolvidas não entram no ranking.

## 7. Testes e limitações

A suíte Feature cobre autorização, separação de abas, precedência de pendências, ordenação e limites dos diâmetros, cálculo dos cards, ordenação percentual, filtros independentes, consistência de trem/carro, limite de dez resultados por tabela e exclusão de instalações retiradas.

**Evidência:** a versão anterior do Módulo 05 teve seus testes aprovados pelo usuário no Laragon. Os novos testes ainda precisam ser executados após a atualização desta interface.

**Limitações:** a primeira versão oferece até dez registros por bloco, sem paginação adicional; os valores são critérios acadêmicos e não constituem autorização de manutenção real. O módulo não registra nem altera pendências ao apresentar alertas de diâmetro ou periodicidade.

## 8. Conceitos acadêmicos e pontos para apresentação

- **Separação de responsabilidades:** controller valida filtros, service executa consultas e Blade organiza a interface.
- **Coesão:** três consultas independentes representam três perspectivas de priorização.
- **SQL e Eloquent Query Builder:** joins, filtros, `CASE`, `COUNT`, `ORDER BY` e `LIMIT`.
- **Integridade lógica:** correlação entre instalação ativa, posição e ocorrência.
- **Componentização Blade:** reaproveitamento de filtros sem repetição excessiva.
- **Usabilidade:** abas por tipo, hierarquia visual e tags de severidade.
- **Testes automatizados:** verificação de ordem, limites exatos e isolamento dos filtros.

**Resumo para apresentação:** “A página de priorização foi organizada em abas de rodeiros e motores, com três rankings independentes para pendências, diâmetros e periodicidade. Os filtros e indicadores de cada bloco permitem identificar ocorrências críticas sem misturar tipos distintos de alerta.”
