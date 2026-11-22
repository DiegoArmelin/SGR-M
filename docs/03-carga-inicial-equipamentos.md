# SGR-M — Entrega 03: Carga inicial de rodeiros e motores

**Projeto:** SGR-M — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 03 — Preparação dos equipamentos e instalações  
**Natureza:** dados fictícios, exclusivamente acadêmicos  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** código enviado ao GitHub; testes no Laragon pendentes de confirmação.

## 1. Objetivo

Cadastrar rodeiros e motores individuais para cada uma das 1.752 posições de eixo das frotas I, J e L. Preparar também 50 unidades extras de cada tipo, sem instalação, para simular substituições. Os registros de instalação criam uma linha histórica por posição e tipo, com `data_retirada = NULL`.

## 2. Regras de negócio

| Quantidade planejada | Rodeiros | Motores |
|---|---:|---:|
| Instalados nas 1.752 posições | 1.752 | 1.752 |
| Sobressalentes em estoque | 50 | 50 |
| Total de equipamentos | 1.802 | 1.802 |
| Total geral dos dois tipos | | **3.604** |

Cada eixo pode ter até um rodeiro ativo e um motor ativo. Um equipamento não pode ocupar dois eixos simultaneamente. Os índices únicos do banco de dados complementam as verificações feitas pelo seeder.

**Status padronizados:**
- `instalado`: possui instalação ativa em um eixo.
- `estoque`: não tem instalação ativa e está disponível para futura instalação.
- `em_manutencao`: retirado para avaliação/manutenção; será usado pelo futuro serviço de movimentação.
- `inativo`: indisponível para uso.

A antiga nomenclatura `disponivel` foi substituída por `estoque` em migration nova. Não é necessário mudar migrations antigas nem recriar o banco de dados.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `database/migrations/2026_11_22_100000_rename_disponivel_to_estoque_in_equipamentos.php` | Converte status existentes de `disponivel` para `estoque` e altera o ENUM no MySQL; inclui rollback |
| `database/seeders/EquipamentosIniciaisSeeder.php` | Cria ou reutiliza 1.802 rodeiros, 1.802 motores e suas 3.504 instalações ativas iniciais |
| `tests/Feature/EquipamentosIniciaisSeederTest.php` | Verifica quantidade, status, instalações e ausência de duplicação quando a rotina é repetida |
| `app/Http/Controllers/ConsultaEquipamentosController.php` | Atualiza filtro de status para `estoque` |
| `resources/views/equipamentos/index.blade.php` | Atualiza rótulos de status exibidos ao usuário |
| `app/Models/Rodeiro.php`, `app/Models/Motor.php` | Models existentes reutilizados, sem alteração |
| `app/Models/InstalacaoRodeiro.php`, `app/Models/InstalacaoMotor.php` | Models existentes para registro da posição e datas de movimentação |

## 4. Métodos e principais recursos de programação

- **`EquipamentosIniciaisSeeder::run()`:** recusa execução em produção, seleciona os eixos das três frotas, verifica a quantidade esperada e cria equipamento e instalação para cada posição, dentro de uma transação.
- **`serie(string $prefixo, int $indice)`:** produz números de série fictícios e *pseudoaleatórios*, mas determinísticos, a partir de uma sequência. A mesma posição recebe a mesma série em uma nova execução do seeder, evitando duplicidade. Prefixos `RD-SIM-` para rodeiros e `MT-SIM-` para motores.
- **`registrarRodeiro()` / `registrarMotor()`:** verificam ocupação ativa de eixo/equipamento, reaproveitam vínculos válidos e rejeitam conflitos ou histórico incompatível.
- **`garantirEstoqueRodeiro()` / `garantirEstoqueMotor()`:** criam ou reutilizam os 50 sobressalentes de cada tipo e confirmam que estão sem instalação.
- **`DB::transaction()`:** garante que falhas revertam a carga inteira, impedindo resultados parciais.
- **`firstOrCreate()`:** evita duplicações sem alterar silenciosamente cadastros preexistentes.
- **`whereNull('data_retirada')`:** reconhece instalação ativa sem necessidade de apagar instalações anteriores.

**Importante:** o seeder não é um módulo de movimentação. Ele não retira equipamentos, não encerra instalações nem define automaticamente `em_manutencao`. Isso será realizado em serviço próprio com transações e testes.

## 5. Procedimento de execução no Laragon

Antes de executar, confirmar que o ambiente é de desenvolvimento, com banco de dados fictício e a carga estrutural das três frotas completa. Se houver dados locais importantes, fazer backup do banco.

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan migrate:status
php artisan migrate
php artisan test --filter=EquipamentosIniciaisSeederTest
php artisan db:seed --class=EquipamentosIniciaisSeeder
```

A migração altera os valores de status existentes; não apaga linhas. O seeder interrompe a execução caso encontre divergências de ocupação. **Não executar** `migrate:fresh` nem `db:wipe` em banco com dados.

## 6. Verificação no Tinker

```php
App\Models\Rodeiro::where('status', 'instalado')->count();
App\Models\Motor::where('status', 'instalado')->count();
App\Models\Rodeiro::where('status', 'estoque')->count();
App\Models\Motor::where('status', 'estoque')->count();
App\Models\InstalacaoRodeiro::whereNull('data_retirada')->count();
App\Models\InstalacaoMotor::whereNull('data_retirada')->count();
```

Em base acadêmica sem equipamentos adicionais, os resultados esperados são `1752, 1752, 50, 50, 1752, 1752`. Caso existam dados preexistentes fora do escopo, os totais globais podem diferir.

## 7. Testes, evidências e limitações

Os dois testes de `EquipamentosIniciaisSeederTest` verificam: quantidades de cada status e instalação ativa; repetição do seeder sem duplicação. **Ainda aguardam execução e confirmação pelo desenvolvedor no Laragon.**

Os números de série são fictícios, pseudoaleatórios e reproduzíveis, não correspondendo a equipamentos reais. A data inicial de instalação é `2026-01-01`, adotada apenas para demonstração acadêmica.

**Limitações:** ausência de dados reais de diâmetro ou periodicidade; ainda não há interface de movimentação ou lógica para recalcular o status após uma substituição. A migração é específica para o ENUM atual do MySQL e usa tratamento distinto para SQLite de testes.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- Modelagem relacional e integridade: tabela de equipamento separada da tabela de instalação.
- Rastreabilidade temporal: instalação ativa versus histórico encerrado.
- Transações ACID e tratamento de erros.
- Idempotência de carga de dados fictícios.
- Compatibilidade de migrations entre MySQL e SQLite.
- Testes automatizados e desenvolvimento incremental.
- Consistência entre status cadastral e vínculo ativo.

**Resumo para apresentar:** “A nova carga cria 3.504 equipamentos fictícios instalados nas 1.752 posições de eixo e mais 100 sobressalentes. Cada vínculo é registrado nas tabelas de histórico, permitindo preparar as futuras funcionalidades de substituição e manutenção.”
