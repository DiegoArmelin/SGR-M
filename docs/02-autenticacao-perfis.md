# SGR-M — Relatório Técnico de Desenvolvimento

**Entrega 02 — Autenticação e Controle de Acesso por Perfis**

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M)  
**Disciplina:** Laboratório de Engenharia de Software  
**Instituição:** Fatec São Paulo  
**Grupo:** 10  
**Data:** 08/11/2026  
**Branch:** `feature/autenticacao-perfis`  
**Situação:** Implementação concluída e testes manuais aprovados. Integração à branch `develop` pendente de confirmação.

---

## 1. Objetivo da entrega

Implementar o mecanismo inicial de autenticação e autorização do SGR-M, permitindo que diferentes categorias de usuários acessem o sistema de acordo com suas responsabilidades.

A implementação busca estabelecer uma base de segurança para os próximos módulos, restringindo o acesso às funcionalidades e impedindo que usuários não autorizados executem operações protegidas.

## 2. Tecnologias utilizadas

- **Framework:** Laravel 12.70.2.
- **Linguagem:** PHP 8.3.28.
- **Banco de dados:** MySQL 8.4.3.
- **Ambiente local:** Laragon.
- **Interface:** Blade, HTML e CSS.
- **Autenticação:** Laravel Auth, baseada em sessões.
- **Controle de acesso:** Middleware personalizado.
- **ORM:** Eloquent.
- **Versionamento:** Git e GitHub.

## 3. Estrutura de autenticação implementada

A autenticação foi desenvolvida utilizando os recursos nativos do Laravel, sem a instalação de pacotes externos específicos para essa finalidade.

O módulo contempla:

- Formulário de login.
- Validação de credenciais.
- Autenticação baseada em sessão.
- Regeneração do identificador de sessão após login.
- Encerramento da sessão.
- Invalidação da sessão no logout.
- Regeneração do token CSRF no logout.
- Proteção de rotas.
- Controle de acesso baseado em perfis.

### 3.1. LoginController

Foi criado o controlador `Auth/LoginController.php`, responsável pelo processamento das operações de autenticação.

Seus métodos são:

| Método | Responsabilidade |
|---|---|
| `showLoginForm()` | Exibir o formulário de login |
| `login()` | Validar credenciais e autenticar o usuário |
| `logout()` | Encerrar a sessão do usuário |

A autenticação utiliza o método `Auth::attempt()` do Laravel, que verifica as credenciais fornecidas pelo usuário.

Em caso de sucesso, o sistema regenera o identificador da sessão e redireciona o usuário ao dashboard.

Em caso de falha, retorna uma mensagem genérica de credenciais inválidas.

### 3.2. Rotas

Foram implementadas rotas para login, dashboard e logout.

| Método HTTP | Rota | Finalidade |
|---|---|---|
| GET | `/` | Redirecionamento inicial |
| GET | `/login` | Exibição do formulário |
| POST | `/login` | Processamento da autenticação |
| GET | `/dashboard` | Painel principal autenticado |
| POST | `/logout` | Encerramento da sessão |

As rotas utilizam os middlewares `guest`, `auth` e `throttle`, conforme sua finalidade.

## 4. Alterações no banco de dados

Foi desenvolvida uma migration para acrescentar o campo `perfil` à tabela `users`.

O campo utiliza o tipo `ENUM`, contemplando os seguintes valores:

- `administrador`
- `gestor`
- `tecnico`

O valor padrão definido é `tecnico`.

A alteração foi implementada por meio de uma nova migration, preservando o histórico de evolução do banco de dados.

## 5. Atualização do Model User

O Model `User` foi atualizado para contemplar o atributo `perfil` e oferecer métodos auxiliares de identificação e autorização.

Foram implementados:

- `isAdministrador()`
- `isGestor()`
- `isTecnico()`
- `temPerfil()`

Também foram definidas constantes para padronizar os valores dos perfis.

Os métodos foram validados manualmente utilizando o Laravel Tinker, apresentando os resultados esperados.

O armazenamento das senhas permanece protegido pelo mecanismo de hash do Laravel.

## 6. Controle de acesso por perfis

Foi implementado o middleware personalizado `VerificarPerfil`, responsável por verificar se o usuário autenticado possui um dos perfis autorizados para acessar determinada rota.

O middleware foi registrado no arquivo `bootstrap/app.php`, utilizando o alias `perfil`.

O acesso a uma funcionalidade depende de duas condições:

1. O usuário deve estar autenticado.
2. Seu perfil deve estar entre os perfis autorizados para a rota solicitada.

Quando um usuário autenticado tenta acessar uma funcionalidade incompatível com seu perfil, o sistema retorna o código HTTP `403 Forbidden`.

### 6.1. Matriz inicial de permissões

| Funcionalidade | Administrador | Gestor | Técnico |
|---|---|---|---|
| Consultar equipamentos | Permitido | Permitido | Permitido |
| Cadastrar equipamentos | Permitido | Restrito | Restrito |
| Registrar pendências | Permitido | Permitido | Permitido |
| Movimentar equipamentos | Permitido | Restrito | Permitido |
| Visualizar indicadores | Permitido | Permitido | Permitido |
| Gerenciar usuários | Permitido | Restrito | Restrito |

A matriz representa as permissões planejadas para os módulos do sistema. Sua aplicação integral ocorrerá conforme cada funcionalidade for desenvolvida.

## 7. Interfaces desenvolvidas

Foram criadas três views Blade.

### 7.1. Layout principal

**Arquivo:** `resources/views/layouts/app.blade.php`

Responsável por padronizar a apresentação visual das páginas, incluindo:

- Estrutura HTML.
- Estilos CSS.
- Barra superior de navegação.
- Identificação do usuário autenticado.
- Identificação do perfil de acesso.
- Botão de logout.

### 7.2. Interface de login

**Arquivo:** `resources/views/auth/login.blade.php`

Contempla:

- Identificação visual do SGR-M.
- Formulário de autenticação.
- Campos de e-mail e senha.
- Proteção CSRF.
- Mensagens de validação.
- Layout adaptável a diferentes tamanhos de tela.

### 7.3. Dashboard inicial

**Arquivo:** `resources/views/dashboard.blade.php`

Disponibiliza uma página inicial com a identificação do usuário autenticado e a apresentação dos módulos funcionais planejados.

Nesta etapa, os cards possuem caráter informativo, não representando ainda indicadores operacionais reais.

## 8. Dados de teste

Foi desenvolvido o seeder `UserTestSeeder`, destinado à criação de usuários fictícios para o ambiente de desenvolvimento.

Os perfis utilizados nos testes foram:

| Usuário | Perfil |
|---|---|
| Administrador Teste | Administrador |
| Gestor Teste | Gestor |
| Técnico Teste | Técnico |

O seeder foi configurado para impedir sua execução em ambiente de produção e utilizar uma senha de teste definida localmente.

Os usuários fictícios não representam contas reais de funcionários.

## 9. Testes realizados

Os testes foram executados manualmente no ambiente local, utilizando o navegador e os recursos do Laravel.

| Teste | Resultado |
|---|---|
| Verificação da migration | Aprovado |
| Identificação dos perfis pelo Model User | Aprovado |
| Registro das rotas | Aprovado |
| Exibição do formulário de login | Aprovado |
| Login com credenciais válidas | Aprovado |
| Login com credenciais inválidas | Aprovado |
| Redirecionamento ao dashboard | Aprovado |
| Logout | Aprovado |
| Restrição de acesso a rotas protegidas | Aprovado |
| Autorização do administrador | Aprovado |
| Autorização do gestor | Aprovado |
| Autorização do técnico | Aprovado |
| Bloqueio de perfis não autorizados | Aprovado |

Os resultados demonstraram o funcionamento esperado das principais operações de autenticação e autorização.

As rotas temporárias utilizadas para testar os perfis foram removidas após a validação.

## 10. Limitações e funcionalidades pendentes

A implementação estabelece a infraestrutura inicial de segurança da aplicação, mas ainda apresenta funcionalidades pendentes.

Entre elas:

- Interface administrativa de cadastro e gerenciamento de usuários.
- Recuperação de senha.
- Testes automatizados de autenticação e autorização.
- Implementação das permissões nas rotas dos módulos funcionais.
- Registro de auditoria das operações.
- Revisão de segurança antes de eventual disponibilização em ambiente de produção.

Os testes automatizados foram adiados por decisão de planejamento, considerando o estágio atual do projeto acadêmico.

A autenticação implementada não representa, isoladamente, uma validação completa de segurança da aplicação.

## 11. Próxima etapa de desenvolvimento

A próxima entrega contemplará a implementação do módulo de cadastro e consulta de equipamentos.

Serão desenvolvidas interfaces e operações para:

- Cadastro e consulta de frotas.
- Cadastro e consulta de trens.
- Cadastro e consulta de carros.
- Identificação dos eixos.
- Cadastro de rodeiros.
- Cadastro de motores.
- Consulta de equipamentos por número de série.
- Consulta hierárquica por frota, trem, carro e eixo.

O módulo utilizará a modelagem relacional implementada na Entrega 01 e a autenticação desenvolvida nesta entrega.

As operações de cadastro e alteração deverão respeitar as permissões dos usuários.

## 12. Conclusão

A Entrega 02 do SGR-M estabeleceu o mecanismo inicial de autenticação e controle de acesso da aplicação.

A implementação integrou recursos nativos do Laravel, interfaces Blade, autenticação por sessão e middleware personalizado para autorização baseada em perfis.

Os testes manuais realizados demonstraram o comportamento esperado para os cenários avaliados, incluindo autenticação, encerramento de sessão, autorização e bloqueio de acessos indevidos.

Com esta entrega, o SGR-M passa a possuir uma base funcional para proteger os próximos módulos e estruturar as permissões de acordo com as responsabilidades dos usuários.

A próxima fase concentrará os esforços na implementação das funcionalidades de cadastro e consulta dos equipamentos ferroviários.