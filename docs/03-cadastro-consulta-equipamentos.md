# SGR-M — Entrega 03: Cadastro e Consulta de Equipamentos

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M)  
**Disciplina:** Laboratório de Engenharia de Software — Fatec São Paulo  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Estado:** implementação parcial; consultas, frotas e trens desenvolvidos; testes automatizados reportados pelo desenvolvedor para os recursos iniciais; validação visual e módulos restantes pendentes.

## 1. Objetivo

Disponibilizar a consulta paginada de rodeiros e motores por número de série e situação, bem como gerenciamento de frotas e trens com restrições de acesso e integridade relacional. A estrutura hierárquica completa será expandida para carros e eixos, sem alterar a modelagem relacional original.

## 2. Arquitetura da solução

Padrão MVC do Laravel:
- **Model:** `Frota`, `Trem`, `Rodeiro` e `Motor` representam tabelas e relacionamentos Eloquent.
- **Controller:** recebem requisições, validam parâmetros, executam consultas ou persistência e retornam respostas.
- **View (Blade):** apresentam formulários, filtros, resultados e mensagens ao usuário.
- **Routes / Middleware:** associam URLs a métodos e verificam autenticação e autorização.
- **Feature tests:** exercitam rotas HTTP, validação de entrada, permissões e alterações no banco em memória.

## 3. Inventário: arquivos criados

| Arquivo | Finalidade |
|---|---|
| `app/Http/Controllers/ConsultaEquipamentosController.php` | Consulta de rodeiros ou motores, com filtros e paginação. |
| `resources/views/equipamentos/index.blade.php` | Interface de busca e tabela de resultados. |
| `tests/Feature/ConsultaEquipamentosTest.php` | Testes de acesso, filtro por série e tipo inválido. |
| `app/Http/Controllers/FrotaController.php` | Operações CRUD de frotas e validação das regras de exclusão. |
| `app/Http/Controllers/TremController.php` | Operações CRUD de trens e vínculo com frota. |
| `resources/views/frotas/index.blade.php` | Listagem paginada de frotas, status e número de trens associados. |
| `resources/views/frotas/form.blade.php` | Formulário reutilizado para cadastrar e editar frotas. |
| `resources/views/trens/index.blade.php` | Listagem paginada e filtros de trens por frota e código. |
| `resources/views/trens/form.blade.php` | Formulário reutilizado para cadastrar e editar trens. |
| `tests/Feature/CadastroFrotasTrensTest.php` | Oito testes de acesso, cadastro, unicidade, filtro e integridade. |
| `docs/03-cadastro-consulta-equipamentos.md` | Documentação técnica desta entrega. |

## 4. Arquivos existentes alterados

| Arquivo | Alteração |
|---|---|
| `routes/web.php` | GETs de consulta e rotas de escrita para frotas e trens, com middlewares `auth` e `perfil`. |
| `resources/views/layouts/app.blade.php` | Links na navegação para consulta de equipamentos, frotas e trens. |
| `tests/Feature/ExampleTest.php` | Substituição do teste genérico por testes de redirecionamento de visitante/autenticado. |
| `database/migrations/2026_11_02_153616_create_instalacao_rodeiros_table.php` | Substituição de `IF()` por `CASE WHEN` nas colunas geradas, compatível com SQLite em testes e MySQL. |
| `database/migrations/2026_11_02_154221_create_instalacao_motors_table.php` | Mesma compatibilidade para instalações de motores. |
| `app/Http/Controllers/FrotaController.php`, `TremController.php` e `ConsultaEquipamentosController.php` | Comentários explicativos sobre métodos, validações e regras de negócio. |

As migrations antigas foram ajustadas apenas em sua expressão SQL. **Não** executar `migrate:fresh` no banco com dados: esse comando elimina e recria tabelas.

## 5. Métodos e suas responsabilidades

### ConsultaEquipamentosController

- `index(Request $request)`: valida `tipo`, `numero_serie` e `status`; seleciona `Motor` ou `Rodeiro` via Eloquent; aplica filtro parcial pelo número de série e exato por status; ordena por série; pagina 20 resultados; renderiza `equipamentos.index`.

### FrotaController

- `index()`: lista frotas, calcula a quantidade de trens associados com `withCount()`, ordena e pagina.
- `create()`: entrega o formulário com objeto novo de `Frota`.
- `store(Request)`: valida código/descrição/atividade e persiste frota.
- `edit(Frota)`: carrega frota por Route Model Binding e exibe o formulário.
- `update(Request, Frota)`: valida os novos dados, preservando a própria chave na regra de unicidade, e atualiza.
- `destroy(Frota)`: não exclui frota com trens vinculados; caso contrário, exclui e informa o resultado.
- `validar(Request, ?Frota)`: centraliza as validações, inclusive unicidade de `codigo` com `Rule::unique()->ignore()`.

### TremController

- `index(Request)`: valida filtros; consulta trens com a frota relacionada (`with()`) e contagem de carros (`withCount()`), ordena e pagina.
- `create()`: envia frotas ativas ao formulário.
- `store(Request)`: valida código, frota, descrição e atividade; só cadastra em frota ativa.
- `edit(Trem)`: exibe edição e inclui a frota atual nas opções, mesmo se inativa.
- `update(Request, Trem)`: valida os dados; permite manter o vínculo atual, mas impede transferir para outra frota inativa.
- `destroy(Trem)`: bloqueia exclusão se existirem carros vinculados.
- `validar(Request, ?Trem)`: centraliza validação e unicidade global do código do trem.

## 6. Regras de negócio

- Código de frota: obrigatório, único, até 10 caracteres.
- Código de trem: obrigatório, único no sistema, até 20 caracteres.
- Trem deve pertencer a uma frota existente.
- Novos trens só podem ser cadastrados em frotas ativas.
- Não é permitido mover um trem para outra frota inativa.
- Frota com trens não pode ser excluída.
- Trem com carros não pode ser excluído.
- A desativação é alternativa à exclusão quando há dependências. Desativar não apaga histórico ou relacionamentos.
- Rodeiros e motores são consultados pelos valores cadastrais; nesta fase, a consulta não altera equipamentos.

## 7. Perfis e segurança

| Operação | Administrador | Gestor | Técnico |
|---|---|---|---|
| Consultar frotas/trens/equipamentos | Sim | Sim | Sim |
| Criar, editar e excluir frotas/trens | Sim | Não | Não |

As permissões são verificadas **no servidor** pelo middleware `perfil`; ocultar botões na interface é apenas uma segunda camada de experiência do usuário. Formulários incluem proteção CSRF; ações de atualização e exclusão usam os verbos HTTP correspondentes.

## 8. Fluxo de uma operação (exemplo)

1. O administrador faz login e abre `/frotas/criar`.
2. O middleware verifica sessão e perfil.
3. O controller `create()` apresenta a view `frotas.form`.
4. A view envia um POST com token CSRF para a rota `frotas.store`.
5. O método `store()` chama `validar()`, constrói os atributos e cria `Frota`.
6. O sistema retorna à listagem com mensagem de confirmação.

## 9. Testes e evidências

Na execução informada no Laragon em 22/11/2026, `CadastroFrotasTrensTest` teve **8 testes aprovados**, e `ConsultaEquipamentosTest` teve **3 testes aprovados**. O teste genérico `ExampleTest` falhou por esperar status HTTP 200 na rota `/` (que corretamente redireciona com 302). Esse teste foi posteriormente atualizado para verificar os redirecionamentos de visitante e autenticado, mas **a suíte completa após a atualização ainda precisa ser executada e confirmada no ambiente local**.

Comandos para repetir a validação:

```bash
git pull origin feature/entrega-03-consulta-equipamentos
php artisan route:list
php artisan test
```

Avaliar também manualmente: formulário de frota, cadastro de trem, filtro, mensagens de erro, tentativas de acesso por técnico/gestor e exclusão com dependências.

## 10. Próximas atividades

Implementação de carros e eixos, cadastro de rodeiros e motores, consulta hierárquica (frota → trem → carro → eixo), movimentação com preservação de histórico, pendências técnicas e priorização.

## 11. Pontos para explicar na apresentação

- **MVC:** separação entre Model, View e Controller.
- **Eloquent ORM:** mapeamento objeto-relacional e relacionamentos.
- **Integridade:** restrições de unicidade, chaves estrangeiras e regras de exclusão.
- **Autenticação x autorização:** login identifica usuário, middleware define o que cada perfil pode fazer.
- **Testes de funcionalidade:** simulação de requisições HTTP, validação de resposta e estado do banco.
- **Git:** trabalho isolado em branch e integração revisada por Pull Request.
- **Evolução incremental:** cada entrega acrescenta funcionalidade verificável à estrutura anterior.

**Limitação:** ainda não houve execução de interface em navegador pelo assistente nem confirmação de adequação para produção. O projeto utiliza dados fictícios.
