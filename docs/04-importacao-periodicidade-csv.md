# SGR-M — Módulo 04: Importação das condições dos rodeiros por CSV

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-importacao-csv`  
**Situação:** código, interface e testes publicados no GitHub; aguardando execução local no Laragon.

## 1. Objetivo

Importar em um único arquivo a identificação da posição e os dados de condição do rodeiro atualmente instalado, atualizando **diâmetros LR/LO, periodicidade e data da condição** somente quando a data do CSV for **estritamente posterior** à última atualização registrada. O arquivo já informa a periodicidade em percentual; não são efetuados cálculos de periodicidade.

## 2. Regras de negócio

Formato do arquivo CSV (colunas obrigatórias, ordem livre):

```csv
frota;trem;carro;posicao;serie_rodeiro;diametro_lr;diametro_lo;periodicidade;data_atualizacao
I;I01;I011;1;RD-SIM-0112648;829.10;829.50;95.50;2026-11-21 12:00:00
```

| Regra | Tratamento |
|---|---|
| Permissão | Apenas administrador importa |
| Identificação | Frota, trem, carro, posição do eixo e número de série do rodeiro |
| Confirmação física | Confere a instalação ativa do rodeiro naquela posição |
| Dados | Diâmetro LR, diâmetro LO e periodicidade percentual |
| Data | `AAAA-MM-DD HH:MM:SS`, correspondente ao instante informado pelo arquivo |
| Atualização | Apenas se `data_atualizacao` for maior que `data_atualizacao_condicao`; data anterior/igual é ignorada |
| Rodeiro retirado | Linha ignorada se a série já não estiver instalada na posição informada |
| Série não cadastrada | Arquivo recusado integralmente |
| Posição inexistente | Arquivo recusado integralmente |
| Dados inválidos/duplicados | Arquivo recusado integralmente |
| Integridade | Atualizações em transação; erro desfaz alterações do lote |
| Relatório | Total de linhas, atualizados, ignorados por data, ignorados por posição |
| Formato | CSV UTF-8, preferencialmente separado por `;`, com valores decimais em ponto ou vírgula |
| Upload | Máximo de 2 MB e 10.000 linhas |
| Persistência | Não altera vínculo, histórico de substituições ou pendências |

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/ImportacaoPeriodicidadeCsvService.php` | Lê CSV, confere posição/série e compara datas antes de atualizar |
| `app/Http/Controllers/ImportacaoPeriodicidadeController.php` | Processa upload e exibe resumo do resultado |
| `resources/views/importacoes/periodicidade.blade.php` | Novo formulário e instruções para nove colunas |
| `tests/Feature/ImportacaoPeriodicidadeCsvTest.php` | Testes de importação, atualização por data, referência cruzada e rollback |
| `routes/web.php` | Rotas já existentes do módulo, protegidas para administrador |
| `resources/views/layouts/app.blade.php` | Atalho já existente para importação |
| `docs/04-importacao-periodicidade-csv.md` | Documentação acadêmica da evolução |

Não foi necessária uma migration: a tabela `rodeiros` já contém os campos `diametro_lr`, `diametro_lo`, `periodicidade` e `data_atualizacao_condicao`.

## 4. Métodos e técnicas utilizados

- **`ImportacaoPeriodicidadeCsvService::importar(UploadedFile)`:** valida cabeçalho, cada linha, séries e datas; percorre o lote sob transação.
- **`fgetcsv()`:** lê as linhas separadas por `;` ou `,`.
- **`Carbon::createFromFormat()`:** interpreta a data com hora, que é armazenada como origem da medição.
- **`whereHas()`:** valida que eixo/carro/trem/frota pertencem à hierarquia informada.
- **`InstalacaoRodeiro::whereNull('data_retirada')`:** recupera a instalação ativa.
- **`lockForUpdate()`:** protege eixo e rodeiro durante a atualização.
- **`greaterThan()`:** compara a data informada com a última registrada.
- **`DB::transaction()`:** impede atualizações parciais diante de falhas no lote.
- **`ValidationException::withMessages()`:** devolve erros legíveis.
- **`ImportacaoPeriodicidadeController::store()`:** valida upload, chama serviço e mostra totais atualizados/ignorados.

## 5. Procedimento no Laragon

Atualize a branch do Módulo 04:

```bash
git switch feature/modulo-importacao-csv
git pull origin feature/modulo-importacao-csv
php artisan test --filter=ImportacaoPeriodicidadeCsvTest
php artisan test
```

Se ainda não possuir a branch local, execute antes:

```bash
git fetch origin
git switch -c feature/modulo-importacao-csv --track origin/feature/modulo-importacao-csv
```

**Não usar `migrate:fresh`.** Nesta etapa não há migration nova.

## 6. Verificação funcional

1. Entrar no sistema como administrador.
2. Abrir `http://sgr-m.test/importacoes/periodicidade`.
3. Realizar backup do banco de desenvolvimento antes de importar arquivos de teste.
4. Importar o CSV fictício da Frota I; conferir número de linhas atualizadas e ignoradas.
5. Consultar rodeiros por série e conferir os três valores de condição e a data da atualização.
6. Reimportar o **mesmo arquivo**: nenhuma linha já atualizada poderá ser alterada novamente (data igual).
7. Enviar CSV contendo um número de série inexistente: a importação deve ser recusada.
8. Verificar em uma posição na qual ocorreu substituição anterior: a série antiga, ainda existente no banco, deve ser ignorada por não corresponder à instalação ativa.
9. Repetir importação das frotas J e L.

## 7. Testes, evidências e limitações

Testes Feature de autorização, atualização dos três valores, tratamento de data igual, data posterior, série divergente da posição, posição inexistente, série desconhecida, valores inválidos e duplicidades.

**Situação:** testes criados no GitHub, ainda sem execução confirmada pelo usuário para esta nova especificação.

**Limitações:** a importação não gera histórico de cada lote, tela de pré-visualização nem atualização de rodeiros fora da instalação ativa. Arquivos de instalações antigas não alteram a peça retirada. O registro `data_atualizacao_condicao` representa a data enviada no CSV, não o horário em que o upload foi efetuado.

## 8. Conceitos acadêmicos

- **Engenharia de requisitos:** especificação da estrutura e da regra temporal de atualização.
- **Integridade referencial:** validação cruzada de posição e número de série instalado.
- **Consistência temporal:** proteção contra sobrescrita por medições desatualizadas.
- **Atomicidade ACID:** todas as atualizações válidas do lote são aplicadas em transação.
- **Segurança:** importação restrita a administrador e validação de entradas.
- **Testes automatizados:** cenários válidos, inválidos e preservação de dados.
- **Desenvolvimento incremental:** evolução do primeiro importador para a carga unificada.

**Resumo para apresentação:** “A importação CSV atualiza diâmetros e periodicidade em conjunto, validando a posição do rodeiro e comparando a data da medição com o último registro para impedir sobrescritas por informações antigas.”
