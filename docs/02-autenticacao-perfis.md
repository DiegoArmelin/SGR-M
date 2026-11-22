# SGR-M — Entrega 02: Autenticação e controle de acesso por perfis

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M) — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 02 — Autenticação e Controle de Acesso  
**Data original:** 08/11/2026  
**Branch original:** `feature/autenticacao-perfis`  
**Situação:** autenticação, middleware e interfaces implementados; testes manuais relatados na entrega original. A implementação foi posteriormente integrada à `develop`.

## 1. Objetivo

Identificar usuários por login e senha, manter sessões autenticadas e aplicar autorização por papéis. A meta é disponibilizar uma base de segurança reutilizável para os módulos funcionais, distinguindo **autenticação** (identidade) de **autorização** (permissões do perfil).

## 2. Regras de negócio e perfis

| Perfil | Consulta de equipamentos | Cadastro de equipamentos | Registro de pendências (planejado) | Movimentação (planejada) | Gestão de usuários (planejada) |
|---|---|---|---|---|---|
| Administrador | Sim | Sim | Sim | Sim | Sim |
| Gestor | Sim | Não | Sim | Não | Não |
| Técnico | Sim | Não | Sim | Sim | Não |

A tabela representa a **matriz de permissões do projeto**. A Entrega 02 implementou o mecanismo de autorização e testou seu comportamento com rotas temporárias; as permissões dos recursos reais são aplicadas progressivamente conforme os módulos são construídos.

- Login com credenciais válidas inicia uma sessão.
- Credenciais inválidas geram mensagem genérica, sem revelar qual campo estava incorreto.
- Após o login, a sessão é regenerada; no logout, é invalidada e o token CSRF é renovado.
- Rotas autenticadas exigem sessão ativa; perfis incompatíveis recebem HTTP **403**.
- As tentativas de login são limitadas inicialmente por `throttle:5,1`.
- O campo `perfil` utiliza `administrador`, `gestor` ou `tecnico` (valor padrão `tecnico`).

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Controllers/Auth/LoginController.php` | Exibição do login, autenticação e logout |
| `app/Http/Middleware/VerificarPerfil.php` | Confere se o usuário tem perfil autorizado |
| `app/Models/User.php` | Atributo perfil, constantes e métodos de identificação de papel |
| `database/migrations/2026_11_08_143415_add_perfil_to_users_table.php` | Acrescenta `perfil` à tabela `users` |
| `database/seeders/UserTestSeeder.php` | Cria contas fictícias de gestor e técnico para testes locais |
| `bootstrap/app.php` | Registra o alias de middleware `perfil` |
| `routes/web.php` | Rotas de login, dashboard e logout protegidas por middlewares |
| `resources/views/auth/login.blade.php` | Formulário de autenticação, erros e token CSRF |
| `resources/views/layouts/app.blade.php` | Layout compartilhado, identificação do usuário e botão de logout |
| `resources/views/dashboard.blade.php` | Painel inicial com cartões informativos |

Os cartões de dashboard dessa etapa não eram indicadores operacionais reais.

## 4. Métodos e recursos de programação

| Método ou recurso | Explicação |
|---|---|
| `showLoginForm()` | Retorna a interface de login. |
| `login(Request)` | Valida entradas, tenta autenticar e redireciona após sucesso. |
| `Auth::attempt()` | Compara credenciais informadas com os dados protegidos pelo sistema de autenticação. |
| `session()->regenerate()` | Renova a identificação da sessão após autenticação. |
| `logout(Request)` | Encerra autenticação, invalida sessão e renova token CSRF. |
| `VerificarPerfil::handle()` | Inspeciona o usuário autenticado e verifica se o papel é autorizado para a rota. |
| `isAdministrador()`, `isGestor()`, `isTecnico()` | Identificam o papel por comparação exata com as constantes do Model. |
| `temPerfil(string ...$perfis)` | Retorna verdadeiro quando o perfil atual está entre os permitidos. |
| `middleware('guest')` | Restringe a tela de login a visitantes. |
| `middleware('auth')` | Exige usuário autenticado para a rota. |
| `throttle:5,1` | Limita tentativas da rota de login (cinco por minuto, na configuração adotada). |
| `Hash::make()` | Gera hash para senhas das contas fictícias de teste. |

**Fluxo resumido:** usuário acessa `/login` → envia e-mail e senha por POST → `LoginController` valida credenciais → `Auth::attempt()` autentica → sessão é regenerada → redirecionamento ao dashboard. Uma rota funcional pode ainda exigir `perfil:administrador`.

## 5. Procedimento de execução no Laragon

Verificar configurações do banco no `.env` e executar, **se ainda houver migrations pendentes**:

```bash
php artisan migrate:status
php artisan migrate
php artisan route:list
```

Para criar contas fictícias **apenas em desenvolvimento**, configurar `SGRM_TEST_PASSWORD` no `.env` com uma senha de pelo menos 12 caracteres e executar:

```bash
php artisan config:clear
php artisan db:seed --class=UserTestSeeder
```

**Observação importante:** o `UserTestSeeder` cria **gestor** e **técnico**, não cria administrador. O administrador dos testes originais foi cadastrado manualmente no ambiente local. `firstOrCreate()` não altera a senha de uma conta já existente. Não publicar senhas, capturas de credenciais ou `.env`.

## 6. Verificação de rotas e usuários

```bash
php artisan route:list
php artisan tinker
```

Dentro do Tinker, consultar usuários **sem exibir senhas**:

```php
App\Models\User::select('id', 'name', 'email', 'perfil')->get();
App\Models\User::where('perfil', 'administrador')->exists();
```

Conferir no navegador: redirecionamento da página inicial, acesso ao login, entrada com credenciais válidas/inválidas, dashboard, logout e resposta 403 para rotas incompatíveis com o perfil.

## 7. Testes, evidências e limitações

**Testes manuais da Entrega 02:** o relatório original registra sucesso na migration, métodos de identificação de perfil no Tinker, rotas, login válido/inválido, dashboard, logout e restrição de acesso para os três papéis, incluindo HTTP 403. As rotas temporárias de verificação foram removidas depois dos testes.

**Evolução posterior:** a Entrega 03 adicionou testes automatizados de permissões aplicadas a frotas e trens e testes de redirecionamento da rota inicial. Eles complementam, mas **não substituem retroativamente**, as evidências manuais da Entrega 02.

**Pendências:** recuperação de senha pela interface, gestão administrativa de usuários, auditoria das operações, testes automatizados específicos de todos os cenários de autenticação e revisão de segurança antes de eventual produção. Nenhum resultado de teste manual comprova, sozinho, segurança completa.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **Autenticação e autorização:** identidade e permissão são preocupações distintas.
- **RBAC:** autorização baseada em papéis de usuário.
- **Middleware:** componente transversal que protege rotas antes do controller.
- **Sessões e CSRF:** mecanismos para proteger o fluxo de autenticação e formulários.
- **Hash de senhas:** proteção de credenciais armazenadas.
- **MVC e reúso:** controlador, Model e views com layout compartilhado.
- **Testes positivos e negativos:** login autorizado, negação de acesso e verificação de status HTTP.

**Resumo para apresentar:** “A Entrega 02 implementou autenticação por sessão e três perfis de acesso. O middleware passou a verificar permissões no servidor, preparando a proteção dos módulos seguintes.”
