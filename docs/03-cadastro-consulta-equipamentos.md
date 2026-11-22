# SGR-M — Entrega 03: Cadastro e consulta de equipamentos

**Projeto:** SGR-M — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 03 — Cadastro e consulta (desenvolvimento incremental)  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** consulta de rodeiros/motores e CRUD de frotas/trens implementados; testes dos recursos informados como aprovados no ambiente local. Cadastro e consulta de carros/eixos ainda pendentes de interface.

## 1. Objetivo

Adicionar interfaces e controladores às tabelas criadas na Entrega 01, reaproveitando a autenticação e os perfis da Entrega 02. O recorte atual permite pesquisar rodeiros/motores e gerenciar frotas e trens com validações, paginação e restrições de acesso. A carga inicial de frotas, trens, carros e eixos é uma subetapa documentada separadamente em [03-carga-inicial-frotas.md](03-carga-inicial-frotas.md).

## 2. Regras de negócio

| Operação | Regra |
|---|---|
| Consulta de rodeiros/motores | Filtro por tipo, série total/parcial e status; sem escrita |
| Frota | Código obrigatório, único e de até 10 caracteres |
| Trem | Código obrigatório, único globalmente e de até 20 caracteres |
| Vínculo trem–frota | Frota existente e ativa para novo trem |
| Transferência de trem | Não permite mover para outra frota inativa |
| Exclusão de frota | Bloqueada se houver trens vinculados |
| Exclusão de trem | Bloqueada se houver carros vinculados |
| Desativação | Mantém registro e vínculos, sem excluir histórico |
| Autorização | Todos os perfis consultam; apenas administrador cadastra, edita ou exclui frotas/trens |

Consultas de equipamentos são paginadas em 20 registros; listagens de frotas/trens, em 15. O sistema ainda não expõe uma consulta hierárquica completa até o eixo.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Controllers/ConsultaEquipamentosController.php` | Valida filtros e consulta rodeiros ou motores |
| `app/Http/Controllers/FrotaController.php` | CRUD de frotas e integridade na exclusão |
| `app/Http/Controllers/TremController.php` | CRUD de trens, filtros e regras de vínculo |
| `resources/views/equipamentos/index.blade.php` | Formulário de busca e resultados |
| `resources/views/frotas/index.blade.php` | Listagem e operações de frotas |
| `resources/views/frotas/form.blade.php` | Formulário único de criação/edição de frota |
| `resources/views/trens/index.blade.php` | Listagem filtrada de trens |
| `resources/views/trens/form.blade.php` | Formulário único de criação/edição de trem |
| `tests/Feature/ConsultaEquipamentosTest.php` | Três testes de consulta e autorização |
| `tests/Feature/CadastroFrotasTrensTest.php` | Oito testes de cadastro, filtros e permissões |
| `routes/web.php` | Definição e proteção das rotas |
| `resources/views/layouts/app.blade.php` | Navegação para as novas páginas |
| `tests/Feature/ExampleTest.php` | Dois testes de redirecionamento da página inicial |

**Adequação de compatibilidade:** duas migrations existentes de instalações (rodeiros e motores) tiveram as expressões SQL `IF()` substituídas por `CASE WHEN` para permitir os testes com SQLite. Isso não altera a finalidade das colunas geradas e índices de unicidade.

## 4. Métodos e recursos de programação

| Classe | Método | Responsabilidade |
|---|---|---|
| `ConsultaEquipamentosController` | `index(Request)` | Valida entrada, seleciona Model, aplica filtros e pagina |
| `FrotaController` | `index()` | Lista frotas e total de trens |
| `FrotaController` | `create()` / `edit(Frota)` | Exibe o formulário no modo correto |
| `FrotaController` | `store(Request)` / `update(Request,Frota)` | Valida e grava dados |
| `FrotaController` | `destroy(Frota)` | Impede excluir frota com trens |
| `FrotaController` | `validar(Request,?Frota)` | Centraliza obrigatoriedade, tamanho e unicidade |
| `TremController` | `index(Request)` | Aplica filtros de frota/código e pagina |
| `TremController` | `create()` / `edit(Trem)` | Exibe o formulário com opções de frota |
| `TremController` | `store(Request)` / `update(Request,Trem)` | Valida campos e vínculo com frota |
| `TremController` | `destroy(Trem)` | Impede excluir trem com carros |
| `TremController` | `validar(Request,?Trem)` | Regras de entrada e unicidade |

**Recursos importantes:** `Request::validate()` rejeita parâmetros inválidos; `Rule::unique()->ignore()` permite editar o código sem conflito consigo mesmo; `with()` evita consultas repetidas de relações; `withCount()` calcula total de associados; `paginate()` limita registros; `withQueryString()` mantém filtros na navegação; Route Model Binding recebe Models automaticamente pelo identificador da URL.

## 5. Procedimento de execução no Laragon

```bash
git switch feature/entrega-03-consulta-equipamentos
git pull origin feature/entrega-03-consulta-equipamentos
php artisan route:list
php artisan test
```

Com Laravel em execução, acessar `/equipamentos`, `/frotas` e `/trens` após login. Para operações de cadastro/alteração, usar conta com perfil `administrador`.

**Não executar `migrate:fresh` no banco com dados.** Os controllers da Entrega 03 utilizam tabelas já criadas na Entrega 01.

## 6. Verificação funcional

1. Entrar como administrador e cadastrar uma frota por `/frotas/criar`.
2. Acessar `/trens/criar`, selecionar a frota e cadastrar um trem.
3. Conferir filtro por frota e código; verificar número de trens e carros exibidos.
4. Tentar cadastrar código duplicado para confirmar a validação.
5. Tentar excluir uma frota com trem para confirmar bloqueio.
6. Entrar como gestor ou técnico e verificar que as consultas funcionam, mas as rotas de escrita retornam HTTP 403.
7. Pesquisar rodeiro/motor por série e status; conferir tratamento de listagem vazia.

## 7. Testes, evidências e limitações

**Confirmado pelo desenvolvedor no Laragon:** `CadastroFrotasTrensTest`: 8 testes aprovados; `ConsultaEquipamentosTest`: 3 testes aprovados. Um teste de exemplo inicialmente esperava HTTP 200 na rota `/`, mas a aplicação passou a redirecionar (HTTP 302). O teste foi ajustado para verificar login/dashboard. O desenvolvedor posteriormente informou que os testes estavam **OK**, sem fornecer nova saída detalhada da suíte após a atualização.

**Carga de estrutura:** o Seeder de frotas I, J e L e os testes próprios estão descritos no documento [03-carga-inicial-frotas.md](03-carga-inicial-frotas.md). O desenvolvedor informou que os testes dessa subetapa passaram.

**Pendências:** interfaces para carros/eixos, cadastro de rodeiros e motores, consulta de localização hierárquica, serviços de instalação/retirada e movimentação, importação CSV, pendências e priorização. A execução visual completa das interfaces não foi verificada por este relatório.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **MVC:** separação entre consulta/validação (controllers), persistência (models) e apresentação (views).
- **Eloquent ORM:** queries, relações, filtros e contagem dos dependentes.
- **CRUD:** operações de criação, leitura, atualização e exclusão.
- **Integridade referencial:** bloqueio de exclusão com dependências.
- **RBAC:** permissão controlada pelo middleware de perfil.
- **Validação de entrada:** regras de unicidade, obrigatoriedade e vínculos.
- **Testes Feature:** respostas HTTP, autorização e persistência.
- **Git e evolução incremental:** alterações em branch de funcionalidade e revisão por Pull Request.

**Resumo para apresentar:** “A Entrega 03 tornou operacionais a consulta de equipamentos e os cadastros de frotas/trens, utilizando Models existentes, proteção por perfil e testes automatizados; a expansão para carros/eixos permanece como próximo passo.”
