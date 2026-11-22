# SGR-M — Entrega 01: Modelagem inicial do banco de dados

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M) — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 01 — Modelagem inicial do banco de dados  
**Data original:** 02/11/2026  
**Branch original:** `feature/modelagem-banco`  
**Situação:** modelagem concluída e testes manuais relatados; entrega posteriormente integrada ao histórico do repositório.

> **Registro histórico:** o documento original identificava **Grupo 11**, enquanto os documentos posteriores e o README identificam **Grupo 10**. Esta diferença cadastral foi preservada como observação para conferência dos integrantes; não foi corrigida por suposição.

## 1. Objetivo

Criar a base relacional para organizar a estrutura **Frota → Trem → Carro → Eixo**, identificar rodeiros e motores por número de série e registrar instalações e retiradas sem perder o histórico. O foco é garantir integridade e rastreabilidade **na camada de persistência**, antes da construção das telas e dos serviços de movimentação.

## 2. Regras de negócio e estrutura dos dados

| Tabela | Finalidade | Regra principal |
|---|---|---|
| `frotas` | Identifica uma frota | Código único |
| `trens` | Associa um trem a uma frota | Um trem pertence a uma frota |
| `carros` | Identifica os carros de um trem | Código e posição únicos dentro do trem |
| `eixos` | Define posições de eixo por carro | Número do eixo único dentro do carro |
| `rodeiros` | Cadastro por número de série | Série única; condição LR/LO e periodicidade |
| `motores` | Cadastro por número de série | Série única |
| `instalacoes_rodeiros` | Histórico de vínculo rodeiro–eixo | Exclusividade da instalação ativa |
| `instalacoes_motores` | Histórico de vínculo motor–eixo | Exclusividade da instalação ativa |

**Relacionamentos:** uma frota possui vários trens; um trem possui vários carros; cada carro possui vários eixos. Rodeiros e motores não pertencem permanentemente a um eixo: as tabelas de instalação representam o vínculo e suas datas.

**Instalação ativa:** `data_retirada IS NULL`. Quando a retirada é informada, a instalação torna-se histórica, mas seu registro é mantido.

**Restrições de exclusividade:** os índices `uk_inst_rod_eixo_ativo`, `uk_inst_rod_rodeiro_ativo`, `uk_inst_mot_eixo_ativo` e `uk_inst_mot_motor_ativo` impedem tanto a duplicação do equipamento ativo quanto a ocupação simultânea da mesma posição por dois equipamentos do mesmo tipo.

**Condições do rodeiro:** número de série, diâmetros LR/LO, periodicidade percentual, status, data de atualização e observações; o menor diâmetro pode ser obtido por um atributo calculado no Model. Não foi implementado nessa entrega um serviço de decisão automática de manutenção.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `database/migrations/2026_11_02_151533_create_frotas_table.php` | Criação da tabela de frotas |
| `database/migrations/2026_11_02_151541_create_trems_table.php` | Trens e chave estrangeira para frotas |
| `database/migrations/2026_11_02_151548_create_carros_table.php` | Carros e unicidade de código/posição por trem |
| `database/migrations/2026_11_02_151554_create_eixos_table.php` | Eixos e unicidade de número por carro |
| `database/migrations/2026_11_02_151603_create_rodeiros_table.php` | Cadastro e atributos de condição dos rodeiros |
| `database/migrations/2026_11_02_151610_create_motors_table.php` | Cadastro dos motores |
| `database/migrations/2026_11_02_153616_create_instalacao_rodeiros_table.php` | Histórico de instalações e índices de exclusividade de rodeiros |
| `database/migrations/2026_11_02_154221_create_instalacao_motors_table.php` | Histórico de instalações e índices de exclusividade de motores |
| `app/Models/Frota.php`, `Trem.php`, `Carro.php`, `Eixo.php` | Representação Eloquent da hierarquia |
| `app/Models/Rodeiro.php`, `Motor.php` | Equipamentos, atributos e relações com instalações |
| `app/Models/InstalacaoRodeiro.php`, `InstalacaoMotor.php` | Registros históricos dos vínculos equipamento–eixo |

**Evolução posterior:** na Entrega 03, a expressão das colunas geradas nas duas migrations de instalações passou de `IF(...)` para `CASE WHEN ... THEN ... ELSE NULL END`, para compatibilidade com SQLite nos testes. A finalidade das restrições foi preservada. Essa alteração **não fazia parte da entrega original**.

## 4. Explicação dos principais recursos de programação

- **`up()` e `down()` nas migrations:** criam e removem estruturas do banco, respectivamente, permitindo controlar a evolução do esquema.
- **`foreignId()->constrained()`:** configura a chave estrangeira e faz o banco validar os vínculos.
- **`restrictOnDelete()`:** impede exclusão de um registro pai quando a regra de integridade exige manter dependentes.
- **`unique()`:** cria restrições de unicidade para identificadores e posições.
- **`storedAs()`:** cria coluna calculada usada na exclusividade de instalações ativas.
- **`belongsTo()` / `hasMany()`:** métodos Eloquent que representam relações de muitos-para-um e um-para-muitos.
- **`fillable` e `casts()`:** definem campos permitidos em preenchimento em massa e conversões automáticas de valores.
- **`getMenorDiametroAttribute()`:** calcula o menor valor disponível entre os diâmetros LR e LO, sem persistir esse resultado em uma coluna separada.

## 5. Procedimento de execução no Laragon

Para instalação de um **banco novo e vazio**, configurar o arquivo `.env`, instalar as dependências e executar:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan migrate:status
```

O nome do banco utilizado na documentação original é `sgr_m`. Não publicar credenciais ou o arquivo `.env`.

**Em banco existente, não executar `migrate:fresh`: esse comando exclui as tabelas e seus dados.** A modelagem já está presente no ambiente atual do projeto.

## 6. Verificação no banco e no Tinker

Exemplos de inspeção **somente leitura** no ambiente atual:

```php
Schema::hasTable('frotas');
Schema::hasTable('instalacoes_rodeiros');
App\Models\Frota::count();
App\Models\Trem::with('frota')->first();
App\Models\Rodeiro::first()?->numero_serie;
```

Para examinar índices e colunas geradas, utilizar `SHOW CREATE TABLE instalacoes_rodeiros;` e `SHOW CREATE TABLE instalacoes_motores;` no MySQL.

## 7. Testes, evidências e limitações

**Evidência histórica:** o documento original relata testes manuais aprovados no Tinker para criação dos registros hierárquicos, cadastro de rodeiros, instalação, rejeição de equipamentos duplicados em posições ativas, retirada/substituição e preservação do histórico. Relata também verificações equivalentes para motores.

**Limites da evidência:** não havia suíte automatizada específica desta entrega na época. Os testes posteriores do Seeder e da consulta pertencem à Entrega 03 e não devem ser apresentados como testes originalmente executados na Entrega 01. Serviços transacionais de movimentação, manutenção de status, importação CSV e priorização ainda requerem implementação.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **Modelagem relacional:** entidades, chaves primárias, chaves estrangeiras e cardinalidades.
- **Integridade referencial:** vínculos consistentes e impedimento de exclusões inadequadas.
- **Rastreabilidade:** separação entre identidade do equipamento e posição de instalação.
- **Histórico temporal:** instalações ativas e encerradas sem apagar movimentações passadas.
- **ORM Eloquent:** representação de tabelas e relações como objetos e métodos PHP.
- **Evolução incremental do banco:** migrations versionadas no Git.
- **Verificação e validação:** testes manuais da estrutura e planejamento de testes automatizados.

**Resumo para apresentar:** “A Entrega 01 estruturou oito tabelas do domínio, associou a hierarquia ferroviária aos equipamentos e incorporou restrições para evitar instalações simultâneas inconsistentes, preservando o histórico de cada movimentação.”
