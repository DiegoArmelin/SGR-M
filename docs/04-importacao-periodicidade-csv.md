# SGR-M — Módulo de Importação CSV (etapa 1: periodicidade)

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Branch:** `feature/modulo-importacao-csv`  
**Situação:** implementação publicada no GitHub; testes e interface dependem de validação no Laragon.

## 1. Objetivo

Atualizar o campo `periodicidade` dos rodeiros cadastrados utilizando um CSV com número de série e periodicidade percentual. O CSV **já fornece o percentual**, portanto não existe cálculo de periodicidade a partir de datas, horas ou quilômetros.

A importação atua apenas sobre as condições dos rodeiros; não modifica instalações, equipamentos vinculados aos eixos, motivos, histórico ou pendências.

## 2. Regras de negócio

| Regra | Comportamento |
|---|---|
| Autorização | Apenas administrador pode importar |
| Equipamento | Rodeiro identificado por `numero_serie` cadastrado no banco |
| Colunas | `numero_serie` e `periodicidade` (ordem livre) |
| Separadores | Ponto e vírgula ou vírgula |
| Valor | Percentual já calculado, aceitando `95.50`, `95,50` ou `95,50%` |
| Intervalo | 0 até 999,99%, com até duas casas decimais |
| Arquivo | Até 2 MB e 10.000 registros |
| Repetições | A mesma série não pode aparecer duas vezes |
| Série ausente | Erro; toda a importação é recusada |
| Atualização | Altera `periodicidade` e `data_atualizacao_condicao` |
| Integridade | O arquivo inteiro é validado antes de gravar; gravação dentro de transação |
| Falhas | Nenhum rodeiro tem percentual alterado se o arquivo contiver inconsistências |
| Pendências | Não cria/encerra pendências automaticamente |

As faixas de atenção e os alertas de periodicidade serão implementados no módulo de priorização, não no processamento do arquivo.

### Exemplo de arquivo

```csv
numero_serie;periodicidade
RD-SIM-4010493;96,50
RD-SIM-6075655;105,00
```

Os números de série do arquivo **precisam existir no banco de desenvolvimento**. O exemplo é ilustrativo.

## 3. Arquivos e responsabilidades

| Arquivo | Finalidade |
|---|---|
| `app/Services/ImportacaoPeriodicidadeCsvService.php` | Abre e valida CSV, normaliza percentuais e executa gravação transacional |
| `app/Http/Controllers/ImportacaoPeriodicidadeController.php` | Valida upload e apresenta feedback de sucesso/erro |
| `resources/views/importacoes/periodicidade.blade.php` | Formulário de envio com instruções e exemplo |
| `routes/web.php` | Rotas GET/POST sob `auth` e `perfil:administrador` |
| `resources/views/layouts/app.blade.php` | Link Importar CSV apenas para administrador |
| `tests/Feature/ImportacaoPeriodicidadeCsvTest.php` | Testes de autenticação, dados válidos e inconsistências |
| `docs/04-importacao-periodicidade-csv.md` | Documentação acadêmica da implementação |

**Não há migrations novas:** são utilizadas as colunas de periodicidade e data de atualização já existentes no Model Rodeiro.

## 4. Métodos e recursos de programação

- **`ImportacaoPeriodicidadeController::create()`:** exibe a tela de importação.
- **`ImportacaoPeriodicidadeController::store()`:** valida upload CSV/TXT de até 2 MB, chama o serviço e retorna relatório sucinto de quantidade atualizada.
- **`ImportacaoPeriodicidadeCsvService::importar(UploadedFile)`:** abre arquivo via `fopen()`, lê cabeçalho e linhas com `fgets()` e `fgetcsv()`, valida colunas e valores, recusa séries duplicadas ou desconhecidas e atualiza registros por número de série.
- **`number_format()`:** normaliza percentual com duas casas decimais para persistência.
- **`DB::transaction()`:** garante atomicidade; falha no processamento de qualquer atualização reverte a operação.
- **`ValidationException::withMessages()`:** informa ao usuário o problema sem lançar mensagens SQL brutas.
- **`whereIn()` e `array_diff()`:** conferem previamente quais números de série existem.
- **`finally { fclose(...); }`:** fecha o arquivo mesmo em caso de erro.
- **`RefreshDatabase`:** testes independentes com SQLite em memória.

## 5. Procedimento no Laragon

A Entrega 03 foi integrada à `develop` pelo PR #6. Para atualizar seu repositório local e acessar a nova branch:

```bash
git status
git switch develop
git pull origin develop
git fetch origin
git switch -c feature/modulo-importacao-csv --track origin/feature/modulo-importacao-csv
php artisan test --filter=ImportacaoPeriodicidadeCsvTest
php artisan test
```

Se a branch já existir localmente, utilizar `git switch feature/modulo-importacao-csv` e `git pull`.

**Não executar `migrate:fresh`, `migrate` ou `db:seed` para esta etapa:** não criamos tabelas adicionais.

## 6. Verificação no navegador e banco

1. Realizar login como administrador e abrir `http://sgr-m.test/importacoes/periodicidade`.
2. Exportar ou montar CSV usando números de série fictícios existentes no banco.
3. Importar um CSV pequeno e conferir a mensagem de sucesso.
4. Consultar o rodeiro por série e verificar os percentuais atualizados.
5. Tentar enviar um CSV com série desconhecida, percentual inválido ou série duplicada.
6. Conferir que o sistema rejeitou o arquivo e não atualizou sequer as linhas válidas.
7. Realizar login com perfil gestor/técnico; o acesso ao módulo deve retornar 403.

No Tinker, após importar:

```php
App\Models\Rodeiro::where('numero_serie', 'RD-SIM-4010493')->first(['numero_serie', 'periodicidade', 'data_atualizacao_condicao']);
```

Substitua a série ilustrativa por uma série cadastrada no seu banco.

## 7. Testes, evidências e limitações

Os testes criados cobrem controle de acesso, importação de múltiplas linhas com separadores/formatos numéricos distintos, cabeçalho em ordem invertida, série inexistente, duplicidade, percentuais inválidos e arquivo vazio ou cabeçalho incorreto.

**Evidência ainda pendente:** execução da suíte no Laragon, inspeção da interface e uma importação de demonstração. Não declarar testes aprovados antes da confirmação local.

**Limitações:** o módulo implementa **somente periodicidade** nesta primeira versão; diâmetros LR/LO serão objeto da próxima etapa. Não há histórico por lote de importação ou função de pré-visualização de alterações. Para demonstrar no MySQL, faça backup do banco de desenvolvimento antes de importar, pois os valores existentes serão substituídos.

## 8. Conceitos acadêmicos e apresentação

- **Separação de responsabilidades (MVC + serviço):** a interface recebe o upload e o serviço implementa regras da importação.
- **Validação de entradas:** arquivo, cabeçalho, colunas, séries e domínio de percentuais.
- **Atomicidade transacional:** erros bloqueiam a importação completa.
- **Persistência controlada:** atualização dirigida pelo número de série, sem alteração de vínculos do equipamento.
- **Segurança por perfil:** importação administrativa.
- **Testes automatizados:** fluxos válidos, entradas malformadas e ausência de efeitos parciais.
- **Desenvolvimento incremental e GitFlow simplificado:** nova branch derivada da `develop` após conclusão da Entrega 03.

**Resumo para apresentar:** “O módulo de importação processa percentuais de periodicidade fornecidos por CSV, identifica rodeiros por série e valida o arquivo inteiro antes de atualizar os registros em transação, protegendo a integridade dos dados.”
