# SGR-M — Catálogo de motivos de retirada por tipo de equipamento

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** Evolução do módulo de movimentação  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** código e testes enviados ao GitHub, aguardando execução no Laragon.

## 1. Objetivo

Padronizar e separar os motivos de retirada dos rodeiros e motores sem criar duas tabelas independentes. Permitir que a interface exiba apenas os motivos correspondentes ao tipo de equipamento movimentado. Preservar os textos históricos registrados em movimentações anteriores.

## 2. Regras de negócio e dados iniciais

A tabela única `motivos_retirada` contém os campos `id`, `codigo` (único), `descricao`, `tipo_equipamento` (`rodeiro` ou `motor`), `ativo` e timestamps.

### Motivos de rodeiros (9)

| Código | Motivo |
|---|---|
| ROD001 | Defeito no rolamento do mancal |
| ROD002 | Defeito no rolomento da caixa redutora |
| ROD003 | Acoplamento resiliente danificado |
| ROD004 | Diâmetro condenado |
| ROD005 | troca programada |
| ROD006 | periodicidade vencida |
| ROD007 | vazamento excessivo de óleo |
| ROD008 | ruído no mancal |
| ROD009 | ruído no redutor |

### Motivos de motores (5)

| Código | Motivo |
|---|---|
| MOT001 | Motor desbalanceado |
| MOT002 | Defeito no rolamento |
| MOT003 | falha de isolamento |
| MOT004 | sobreaquecimento |
| MOT005 | ruído anormal |

As descrições preservam a grafia fornecida na especificação da atividade; ajustes de nomenclatura poderão ser tratados de forma explícita posteriormente.

**Regras aplicadas:**
- Um motivo só pode ser selecionado para o tipo de equipamento indicado no cadastro.
- Apenas motivos ativos podem ser usados em novas movimentações.
- As instalações anteriores preservam o texto original de `motivo_retirada`, mesmo quando não há motivo catalogado correspondente.
- Novas movimentações gravam o `motivo_retirada_id` e uma cópia textual da descrição, permitindo consultar o histórico independentemente de futuras alterações no catálogo.
- A exclusão de motivos usados no histórico é restringida pela chave estrangeira.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `database/migrations/2026_11_22_110000_create_motivos_retirada_and_link_instalacoes.php` | Cria catálogo e adiciona referência opcional às duas tabelas de instalações |
| `app/Models/MotivoRetirada.php` | Model Eloquent do catálogo, com tipo e ativação |
| `database/seeders/MotivosRetiradaSeeder.php` | Carga idempotente dos nove motivos de rodeiro e cinco de motor |
| `app/Models/InstalacaoRodeiro.php` | Relaciona instalação ao motivo por `motivo_retirada_id` |
| `app/Models/InstalacaoMotor.php` | Relaciona instalação ao motivo por `motivo_retirada_id` |
| `app/Http/Controllers/MovimentacaoEquipamentoController.php` | Filtra motivos da tela conforme o tipo e valida ID informado |
| `app/Services/SubstituicaoEquipamentoService.php` | Revalida tipo/atividade dentro da transação, registra ID e texto histórico |
| `resources/views/equipamentos/movimentar.blade.php` | Substitui opções fixas por seleção dinâmica do banco |
| `tests/Feature/MovimentacaoEquipamentoTest.php` | Adapta cenários existentes ao novo campo |
| `tests/Feature/MotivosRetiradaTest.php` | Verifica carga, filtragem e rejeição de motivo incompatível |

## 4. Métodos e principais recursos de programação

- **Migration `up()`:** cria uma tabela de domínio e adiciona uma FK opcional em `instalacoes_rodeiros` e `instalacoes_motores`, preservando dados existentes.
- **Migration `down()`:** reverte as FKs e remove a tabela (cuidado: rollback intencional perde dados do catálogo).
- **`MotivosRetiradaSeeder::run()`:** usa `firstOrCreate()` por código estável para evitar duplicações.
- **`MotivoRetirada::casts()`:** converte `ativo` para booleano.
- **`motivoRetirada()`:** relacionamento `belongsTo()` em cada Model de instalação.
- **`MovimentacaoEquipamentoController::create()`:** busca `where('tipo_equipamento', $tipo)`, `where('ativo', true)` e ordena descrições.
- **`MovimentacaoEquipamentoController::store()`:** valida `motivo_retirada_id` como inteiro e registro existente.
- **`SubstituicaoEquipamentoService::substituir()`:** verifica novamente o tipo do motivo e se está ativo dentro da transação, gravando ID e descrição no vínculo encerrado.
- **`restrictOnDelete()`:** protege as referências históricas contra exclusões acidentais.

## 5. Procedimento de execução no Laragon

Recomendado backup do MySQL local antes de aplicar migrations. No projeto:

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan migrate
php artisan db:seed --class=MotivosRetiradaSeeder
php artisan test --filter=MotivosRetiradaTest
php artisan test --filter=MovimentacaoEquipamentoTest
php artisan test
```

O comando `migrate` executa somente migrations pendentes, preservando as tabelas existentes. **Não executar** `migrate:fresh`.

## 6. Verificação funcional e no Tinker

No Tinker:

```php
App\Models\MotivoRetirada::where('tipo_equipamento', 'rodeiro')->count();
App\Models\MotivoRetirada::where('tipo_equipamento', 'motor')->count();
App\Models\MotivoRetirada::orderBy('codigo')->get(['codigo', 'descricao', 'tipo_equipamento', 'ativo']);
```

Esperado: 9 motivos de rodeiros e 5 de motores. Na interface de substituição, escolher um eixo e verificar que o formulário de rodeiro não exibe motivos de motor e vice-versa. Ao realizar uma troca, conferir as colunas `motivo_retirada_id` e `motivo_retirada` da instalação encerrada.

## 7. Testes, evidências e limitações

- Carga idempotente, com 14 motivos.
- Filtragem no formulário conforme tipo.
- Rejeição de motivo de motor na operação de rodeiro.
- Testes de movimentação anteriores adaptados para o ID do motivo.

**Estado dos testes:** arquivos criados no GitHub; ainda não há confirmação da execução local após a alteração.

**Limitações:** não foi implementada uma interface administrativa para criar, editar ou desativar motivos. As descrições históricas não foram convertidas automaticamente em IDs, pois podem representar razões antigas diferentes das atuais. A escolha do motivo é feita exclusivamente durante a substituição.

## 8. Conceitos acadêmicos e pontos para apresentação

- **Normalização de dados:** catálogo compartilhado em vez de opções duplicadas no código.
- **Chaves estrangeiras e integridade:** vínculos catalogados e proteção dos históricos.
- **Separação lógica por tipo:** uma tabela, com filtros e validação de domínio.
- **Transações e validação em camadas:** regras no controller e no serviço.
- **Idempotência:** seeders podem ser repetidos sem replicar registros.
- **Compatibilidade evolutiva:** texto histórico preservado durante a introdução da FK.

**Resumo para apresentar:** “A evolução substituiu motivos fixos no formulário por um catálogo único, com nove causas específicas para rodeiros e cinco para motores, mantendo o histórico anterior e validando que cada movimentação utilize o motivo correto.”
