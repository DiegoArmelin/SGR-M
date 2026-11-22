# SGR-M — Módulo 05: Ranking de priorização dos equipamentos

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-5-ranking-priorizacao`  
**Situação:** implementação publicada no GitHub, aguardando testes e validação no Laragon.

## 1. Objetivo

Consolidar as pendências abertas dos rodeiros e motores instalados e os alertas de periodicidade e diâmetros dos rodeiros em uma classificação única, destinada à consulta gerencial. Cada equipamento aparece uma vez, mesmo que possua várias ocorrências.

## 2. Regras de negócio

Ordem de precedência, da maior para a menor prioridade:

| Nível | Condição | Aplica-se a |
|---|---|---|
| 1 | Pendência aberta **crítica** | Rodeiro e motor |
| 2 | Pendência aberta **alta** | Rodeiro e motor |
| 3 | Pendência aberta **média** | Rodeiro e motor |
| 4 | Pendência aberta **baixa** | Rodeiro e motor |
| 5 | Periodicidade **maior ou igual a 100%** | Rodeiro |
| 6 | Menor diâmetro LR/LO **inferior a 745 mm** | Rodeiro |

**Precedência:** se um equipamento possui pendência crítica e periodicidade superior a 100%, sua classificação principal é nível 1; o alerta de periodicidade também fica visível na lista de motivos. O mesmo equipamento nunca aparece duas vezes.

Outras regras:
- A consulta considera apenas os equipamentos vinculados a instalações abertas, isto é, com `data_retirada IS NULL`.
- Pendências resolvidas não entram no ranking.
- Pendências são associadas ao equipamento **e ao eixo** de origem, evitando incluir ocorrências antigas de outra posição.
- Ações de movimentação são oferecidas somente aos perfis administrador e técnico; todos os três perfis podem visualizar o ranking.
- Não são criadas pendências formais automaticamente em função de percentual ou diâmetro.
- Rodeiros sem medição informada e sem pendência não entram no ranking.
- A tabela permite filtros por frota e tipo, apresenta 30 resultados por página e mostra contagem de equipamentos por nível.
- Dentro da mesma prioridade, o desempate usa maior periodicidade, menor diâmetro e depois posição, apenas para ordenação visual.
- Os parâmetros são acadêmicos e não representam critério operacional ou autorização de intervenção ferroviária.

## 3. Arquivos e responsabilidades

| Arquivo | Finalidade |
|---|---|
| `app/Services/RankingPriorizacaoService.php` | Consulta posições com instalações ativas, reúne pendências/medições e calcula ranking sem duplicar equipamentos |
| `app/Http/Controllers/RankingPriorizacaoController.php` | Valida filtros e entrega lista paginada à interface |
| `resources/views/ranking/index.blade.php` | Tabela de prioridade, filtros, contagem por nível e atalhos de consulta/substituição |
| `tests/Feature/RankingPriorizacaoTest.php` | Testa autorização, precedência, limites, filtros e exclusão de instalações históricas |
| `routes/web.php` | Rota GET protegida `/ranking` |
| `resources/views/layouts/app.blade.php` | Atalho Ranking na navegação |
| `README.md` | Atualização do estágio do projeto |
| `docs/05-ranking-priorizacao.md` | Documentação acadêmica da entrega |

Nenhuma tabela foi criada ou modificada nesta etapa.

## 4. Métodos e recursos de programação

- **`RankingPriorizacaoService::consultar(?string, ?string, int)`:** consulta a estrutura, identifica instalações ativas, agrupa pendências por equipamento/posição, define o menor nível numérico de criticidade e lista os alertas adicionais.
- **`whereHas('carro.trem.frota', ...)`:** limita consulta à frota selecionada.
- **`with()`:** carregamento antecipado de instalações e equipamentos para reduzir consultas repetitivas.
- **`groupBy()`:** associa pendências abertas ao tipo, número do equipamento e eixo de origem.
- **`min()`:** aplica a precedência; nível 1 tem prioridade sobre níveis 2–6.
- **Accessor `Rodeiro::menor_diametro`:** utiliza o menor valor existente entre LR e LO.
- **`LengthAwarePaginator`:** oferece páginas de 30 resultados e mantém os filtros da consulta.
- **`RankingPriorizacaoController::index()`:** valida filtros e retorna a view, sem operações de escrita.
- **Blade `@forelse`, `@foreach` e `@if`:** apresentam itens, motivos e controles de acordo com o perfil do usuário.

## 5. Procedimento no Laragon

Após integrar o Módulo 04, atualize o repositório local e entre na nova branch:

```bash
git status
git switch develop
git pull origin develop
git fetch origin
git switch -c feature/modulo-5-ranking-priorizacao --track origin/feature/modulo-5-ranking-priorizacao
php artisan test --filter=RankingPriorizacaoTest
php artisan test
```

Se a branch já existir, usar `git switch feature/modulo-5-ranking-priorizacao` e `git pull`.

**Não são necessárias migrations ou seeders novos.**

## 6. Verificação no navegador

1. Entrar como administrador, gestor ou técnico e acessar `http://sgr-m.test/ranking`.
2. Confirmar que pendências críticas aparecem acima das demais.
3. Verificar que equipamentos com periodicidade igual ou maior que 100% aparecem no nível de periodicidade, se não possuem pendências mais prioritárias.
4. Conferir rodeiros com menor diâmetro abaixo de 745 mm.
5. Confirmar que um rodeiro com múltiplos motivos possui apenas uma linha, com os alertas adicionais.
6. Filtrar por frota I, J ou L e por motor/rodeiro.
7. Acessar a consulta hierárquica pelo atalho na linha.
8. Conferir que administrador/técnico visualizam Substituir e gestor não.
9. Verificar que equipamentos já retirados e pendências resolvidas não aparecem indevidamente.

## 7. Testes, evidências e limitações

Os testes Feature cobrem acesso dos três perfis, classificação de pendência crítica com condições simultâneas, limites de periodicidade e diâmetro, filtros e exclusão de instalações encerradas/pendências resolvidas.

**Estado atual:** o usuário confirmou os testes do Módulo 04, que foi integrado à `develop`. O ranking do Módulo 05 foi implementado no GitHub, mas ainda não foi executado no Laragon.

**Limitações:** a primeira versão calcula o ranking em memória após consultar as instalações e pendências. Isso é adequado ao conjunto acadêmico de aproximadamente 1.752 eixos, mas, para bases maiores, exigirá agregação e paginação otimizadas diretamente no banco. Não há exportação do ranking nem histórico de alterações de classificação. Não substitui a avaliação profissional de segurança.

## 8. Conceitos acadêmicos e apresentação

- **Regras de negócio e algoritmos de ordenação:** precedência determinística e desempate.
- **Agregação de dados relacionais:** combinação entre pendências e condições.
- **Eloquent ORM e carregamento antecipado:** navegação eficiente na estrutura de equipamentos.
- **Reutilização e coesão:** lógica central em serviço independente da interface.
- **Controle de acesso:** leitura para todos e movimentação restrita.
- **Testes automatizados:** verifica limites exatos, exclusividade e precedência.
- **Desenvolvimento incremental:** usa dados e estruturas dos módulos anteriores.

**Resumo para apresentar:** “O ranking consolida as pendências técnicas abertas com os indicadores de periodicidade e diâmetro de rodeiros instalados, ordenando equipamentos por criticidade e mantendo visíveis os alertas que justificam a classificação.”
