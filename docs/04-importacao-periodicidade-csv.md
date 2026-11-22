# SGR-M — Módulo 04: Importação das condições dos rodeiros por CSV

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-importacao-csv`  
**Situação:** código, interface e testes publicados no GitHub; aguardando execução local no Laragon.

## 1. Objetivo

Importar em um único arquivo a localização, os **diâmetros LR/LO e a periodicidade do rodeiro**, e a **série e periodicidade do motor** instalado no mesmo eixo. A data do arquivo é comparada separadamente com a última atualização de cada equipamento. O arquivo já informa a periodicidade em percentual; não são efetuados cálculos de periodicidade.

## 2. Regras de negócio

Formato do arquivo CSV (colunas obrigatórias, ordem livre):

```csv
frota;trem;carro;posicao;serie_rodeiro;diametro_lr;diametro_lo;periodicidade;serie_motor;periodicidade_motor;data_atualizacao
I;I01;I011;1;RD-SIM-0112648;829.10;829.50;95.50;MT-SIM-0112648;108.25;2026-11-22 08:00:00
```

| Regra | Tratamento |
|---|---|
| Permissão | Apenas administrador importa |
| Identificação | Frota, trem, carro, posição do eixo e números de série do rodeiro e do motor |
| Confirmação física | Confere as instalações ativas de rodeiro e motor naquela posição, separadamente |
| Dados | Diâmetros LR/LO e periodicidade percentual do rodeiro; periodicidade percentual do motor |
| Data | `AAAA-MM-DD HH:MM:SS`, correspondente ao instante informado pelo arquivo |
| Atualização | Para **cada** equipamento, somente se `data_atualizacao` for posterior a `data_atualizacao_condicao`; um tipo pode atualizar enquanto o outro é ignorado |
| Equipamento retirado | Dados do equipamento correspondente são ignorados se sua série não estiver mais instalada na posição |
| Série não cadastrada de qualquer tipo | Arquivo recusado integralmente |
| Posição inexistente | Arquivo recusado integralmente |
| Dados inválidos/duplicados | Arquivo recusado integralmente |
| Integridade | Atualizações em transação; erro desfaz alterações do lote |
| Relatório | Resultado separado para rodeiro e motor na mesma linha, incluindo status e motivo por equipamento |
| Filtros | Mostrar todos, somente atualizados ou somente ignorados |
| Exportação | CSV com todas as linhas, condições informadas, status e justificativa |
| Retenção do relatório | Cache de arquivos por duas horas, vinculado à sessão do administrador; sem nova tabela |
| Formato | CSV UTF-8, preferencialmente separado por `;`, com valores decimais em ponto ou vírgula |
| Upload | Máximo de 2 MB e 10.000 linhas |
| Persistência | Não altera vínculo, histórico de substituições ou pendências |

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/ImportacaoPeriodicidadeCsvService.php` | Lê CSV, confere posição/série e compara datas antes de atualizar |
| `app/Http/Controllers/ImportacaoPeriodicidadeController.php` | Processa upload, guarda resultado no cache temporário da sessão e permite exportar CSV |
| `resources/views/importacoes/periodicidade.blade.php` | Formulário, indicadores e tabela de resultados com filtro por status |
| `tests/Feature/ImportacaoPeriodicidadeCsvTest.php` | Testes de importação, atualização por data, referência cruzada e rollback |
| `routes/web.php` | Rotas já existentes do módulo, protegidas para administrador |
| `resources/views/layouts/app.blade.php` | Atalho já existente para importação |
| `docs/04-importacao-periodicidade-csv.md` | Documentação acadêmica da evolução |

A tabela `rodeiros` já contém os campos de condição. A migration `2026_11_22_150000_add_periodicidade_to_motores_table.php` acrescenta `motores.periodicidade` sem apagar registros existentes; o Model `Motor` também passou a aceitar esse campo.

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
- **`ImportacaoPeriodicidadeController::store()`:** valida upload, chama serviço e guarda o resultado detalhado no cache de arquivos por duas horas, identificado por token na sessão.
- **`ImportacaoPeriodicidadeController::create()`:** apresenta formulário e o relatório da última importação válida da sessão.
- **`ImportacaoPeriodicidadeController::exportar()`:** produz CSV do relatório completo por meio de stream, com BOM UTF-8 e separador ponto e vírgula.
- **`Cache::store('file')`:** evita armazenar até 10.000 linhas dentro de um cookie ou sessão HTTP; armazena apenas um identificador de acesso.
- **Detalhamento no serviço:** cada registro processado recebe status `atualizado` ou `ignorado` e motivo individual, com indicação da série atual ou da última data quando aplicável.

## 5. Procedimento no Laragon

Atualize a branch do Módulo 04:

```bash
git switch feature/modulo-importacao-csv
git pull origin feature/modulo-importacao-csv
php artisan migrate
php artisan test --filter=ImportacaoPeriodicidadeCsvTest
php artisan test
```

Se ainda não possuir a branch local, execute antes:

```bash
git fetch origin
git switch -c feature/modulo-importacao-csv --track origin/feature/modulo-importacao-csv
```

**Executar `php artisan migrate` antes de testar ou importar** para criar a coluna `motores.periodicidade`. **Nunca usar `migrate:fresh`.**

## 6. Verificação funcional

1. Entrar no sistema como administrador.
2. Abrir `http://sgr-m.test/importacoes/periodicidade`.
3. Realizar backup do banco de desenvolvimento antes de importar arquivos de teste.
4. Importar o CSV unificado da Frota I com 11 colunas; conferir os totais de rodeiros e motores atualizados e as razões individuais de ignorados.
5. Consultar rodeiros por série e conferir os três valores de condição e a data da atualização.
6. Reimportar o **mesmo arquivo**: motores e rodeiros já atualizados não devem ter dados sobrescritos (data igual).
7. Enviar CSV contendo um número de série inexistente: a importação deve ser recusada.
8. Verificar em uma posição na qual ocorreu substituição anterior: a série antiga, ainda existente no banco, deve ser ignorada por não corresponder à instalação ativa.
9. Exportar o relatório em CSV, abrir no Excel e confirmar as colunas de status e motivo.
10. Repetir importação das frotas J e L.

## 7. Testes, evidências e limitações

Testes Feature de autorização, atualização dos três valores, tratamento de data igual, data posterior, série divergente da posição, posição inexistente, série desconhecida, valores inválidos e duplicidades; adicionados testes da tabela detalhada, justificativa de divergência e exportação CSV protegida.

**Situação:** o usuário informou que o importador anterior processou 600 linhas (599 atualizadas e uma ignorada por posição/série). A versão com relatório linha a linha foi enviada ao GitHub, mas os novos testes e a interface ainda exigem validação local.

**Limitações:** o relatório detalhado da última importação não é um histórico permanente de lotes e expira após duas horas; a importação não possui pré-visualização nem atualiza rodeiros fora da instalação ativa. Arquivos de instalações antigas não alteram a peça retirada. O registro `data_atualizacao_condicao` representa a data enviada no CSV, não o horário em que o upload foi efetuado.

## 8. Conceitos acadêmicos

- **Engenharia de requisitos:** especificação da estrutura e da regra temporal de atualização.
- **Integridade referencial:** validação cruzada de posição e número de série instalado.
- **Consistência temporal:** proteção contra sobrescrita por medições desatualizadas.
- **Atomicidade ACID:** todas as atualizações válidas do lote são aplicadas em transação.
- **Segurança:** importação restrita a administrador e validação de entradas.
- **Testes automatizados:** cenários válidos, inválidos, preservação de dados e auditabilidade do resultado de cada linha.
- **Observabilidade operacional:** resultado individual e motivo de não atualização para facilitar a reconciliação de importações.
- **Desenvolvimento incremental:** evolução do primeiro importador para a carga unificada.

**Resumo para apresentação:** “A importação CSV atualiza diâmetros e periodicidade em conjunto, validando a posição do rodeiro e comparando a data da medição com o último registro para impedir sobrescritas por informações antigas.”

## 9. Evolução: relatório individual após importação

A tela mantém a mensagem geral, mas agora apresenta **um registro por linha do CSV** com frota, trem, carro–posição, série, LR/LO, periodicidade, data, status e motivo. Linhas ignoradas aparecem em destaque, e o usuário pode filtrar por `todos`, `atualizado` ou `ignorado`. A exportação gera um CSV com **todos** os resultados, independentemente do filtro visual.

**Exemplos dos motivos exibidos:** medição mais antiga ou igual à última data registrada (com a data existente); divergência entre série do arquivo e peça atualmente instalada (com a série atual); ausência de rodeiro ativo no eixo. Erros estruturais continuam interrompendo o lote e não produzem relatório de sucesso.

O relatório é guardado no cache em arquivo por duas horas, acessível somente a partir da sessão que realizou a importação, protegida pela autenticação e pelo perfil administrativo. Esta evolução inclui migration de adição da periodicidade no motor.

## 9. Evolução — carga unificada de rodeiros e motores

Os campos `serie_motor` e `periodicidade_motor` são obrigatórios no arquivo. A importação atualiza os dois tipos de equipamento de maneira independente, de acordo com a instalação ativa e a data da última condição de cada um, mantendo atomicidade do lote. O relatório e a exportação detalham status e justificativa de cada tipo. A nova migration adiciona `motores.periodicidade` (`decimal(5,2)`, nullable) sem modificar dados preexistentes. O CSV passa a exigir 11 colunas.

Arquivos fictícios de teste fornecidos separadamente por frota I, J e L. Datas dos exemplos: 22/11/2026 às 08:00:00. Registros já substituídos podem ser ignorados por divergência de série, comportamento esperado e documentado no relatório.
