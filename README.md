# SGR-M — Sistema de Gestão de Rodeiros e Motores

**Projeto Acadêmico — Laboratório de Engenharia de Software**  
**Fatec São Paulo | Análise e Desenvolvimento de Sistemas | Grupo 10**

> **Aviso:** projeto exclusivamente acadêmico, desenvolvido com dados fictícios. Não representa um sistema oficial do Metrô de São Paulo e não substitui procedimentos técnicos, avaliações de manutenção ou regras operacionais de segurança.

## 1. Sobre o projeto

O **SGR-M (Sistema de Gestão de Rodeiros e Motores)** é uma aplicação web acadêmica para apoiar o gerenciamento de equipamentos ferroviários, especialmente rodeiros e motores de tração. A proposta reúne a identificação por número de série, a localização dos equipamentos na composição ferroviária, o histórico de instalações e retiradas e, nas próximas entregas, o acompanhamento de pendências e condições de manutenção.

O sistema procura consolidar informações que auxiliem a rastreabilidade e a identificação de prioridades, sem substituir a decisão técnica de profissionais responsáveis.

## 2. Objetivos

- Identificar individualmente rodeiros e motores por número de série.
- Organizar a estrutura **Frota → Trem → Carro → Eixo**.
- Preservar o histórico de instalações, retiradas e substituições.
- Registrar e acompanhar pendências técnicas.
- Importar periodicidade percentual e diâmetros LR/LO por CSV.
- Ordenar equipamentos conforme as regras de priorização do projeto.
- Disponibilizar consultas e indicadores para apoio à gestão.
- Restringir o acesso às funcionalidades conforme o perfil do usuário.

## 3. Tecnologias utilizadas

| Tecnologia | Utilização |
|---|---|
| PHP 8.3 | Linguagem de programação |
| Laravel 12 | Framework backend, autenticação e rotas |
| MySQL 8.4 | Banco de dados relacional |
| Eloquent | Models e relacionamentos |
| Blade, HTML e CSS | Interfaces web |
| JavaScript | Interatividade prevista para os módulos |
| Laragon | Ambiente de desenvolvimento local |
| Composer | Dependências PHP |
| Node.js e npm | Recursos frontend |
| Git e GitHub | Versionamento e colaboração |

## 4. Funcionalidades do sistema

### 4.1. Cadastro e consulta de equipamentos — em desenvolvimento

Cadastro de frotas, trens, carros, eixos, rodeiros e motores. Cada equipamento possui número de série único e pode ser acompanhado independentemente da posição de instalação. **A estrutura do banco e os models já foram implementados; as telas de cadastro e consulta ainda estão pendentes.**

### 4.2. Gestão de pendências — planejada

Registro e acompanhamento de pendências técnicas classificadas como **crítica, alta, média ou baixa**.

### 4.3. Importação de dados CSV — planejada

Importação e validação de informações sobre **periodicidade percentual** dos rodeiros e **diâmetros LR e LO**.

### 4.4. Priorização de equipamentos — planejada

O ranking seguirá a seguinte ordem de precedência:

1. Pendências críticas.
2. Pendências altas.
3. Pendências médias.
4. Pendências baixas.
5. Rodeiros com periodicidade superior a 100%.
6. Rodeiros com menor diâmetro LR/LO inferior a 745 mm.

Esses limites são parâmetros acadêmicos e **não representam critérios operacionais oficiais**.

### 4.5. Movimentação de equipamentos — parcialmente estruturada

A modelagem permite registrar instalações, retiradas e substituições e preservar o histórico. Os **serviços e interfaces de movimentação** ainda serão implementados com validações e transações de banco de dados.

### 4.6. Dashboard e indicadores — parcialmente implementado

Foi criada uma **página inicial autenticada**, com identificação do usuário e cartões informativos dos módulos. Os indicadores operacionais reais e as consultas consolidadas permanecem pendentes.

### 4.7. Autenticação e controle de acesso — implementada

A **Entrega 02** implementou:

- Login e logout com sessões nativas do Laravel.
- Validação de credenciais e armazenamento de senhas por hash.
- Regeneração de sessão após login; invalidação de sessão e renovação do token CSRF no logout.
- Proteção de rotas com os middlewares `auth` e `guest`.
- Limitação inicial de tentativas de login com `throttle`.
- Campo `perfil` na tabela `users` e métodos auxiliares no Model `User`.
- Middleware `VerificarPerfil`, registrado com o alias `perfil`.
- Interfaces Blade de login, layout compartilhado e dashboard inicial.
- Seeder para usuários fictícios de teste em ambiente local.

**Perfis disponíveis:** `administrador`, `gestor` e `tecnico`.

A matriz abaixo representa as **permissões planejadas para os módulos futuros**. O middleware de verificação já existe, mas as restrições específicas serão aplicadas à medida que cada módulo for desenvolvido.

| Funcionalidade | Administrador | Gestor | Técnico |
|---|:---:|:---:|:---:|
| Consultar equipamentos | Sim | Sim | Sim |
| Cadastrar equipamentos | Sim | Não | Não |
| Registrar pendências | Sim | Sim | Sim |
| Movimentar equipamentos | Sim | Não | Sim |
| Visualizar indicadores | Sim | Sim | Sim |
| Gerenciar usuários | Sim | Não | Não |

## 5. Estrutura do banco de dados

A modelagem inicial contém oito tabelas específicas da aplicação:

| Tabela | Finalidade |
|---|---|
| `frotas` | Cadastro das frotas |
| `trens` | Trens vinculados às frotas |
| `carros` | Carros vinculados aos trens |
| `eixos` | Posições dos eixos dos carros |
| `rodeiros` | Rodeiros identificados por número de série |
| `motores` | Motores identificados por número de série |
| `instalacoes_rodeiros` | Histórico de instalação e retirada de rodeiros |
| `instalacoes_motores` | Histórico de instalação e retirada de motores |

Além dessas tabelas, o Laravel mantém as estruturas necessárias à aplicação, incluindo `users`, `sessions`, `cache` e `jobs`. A tabela `users` recebeu o campo `perfil` na Entrega 02.

### 5.1. Relacionamentos

```text
Frota
└── Trem
    └── Carro
        └── Eixo
            ├── Instalações de rodeiros ── Rodeiro
            └── Instalações de motores  ── Motor
```

### 5.2. Integridade e rastreabilidade

A modelagem inclui restrições para:

- Garantir número de série único para cada equipamento.
- Impedir dois rodeiros ativos em um mesmo eixo ou um mesmo rodeiro ativo em dois eixos.
- Impedir dois motores ativos em um mesmo eixo ou um mesmo motor ativo em dois eixos.
- Preservar registros de instalações encerradas.

As restrições de exclusividade de instalações ativas utilizam **colunas geradas e índices únicos no MySQL**. Elas não substituem as validações e transações a serem implementadas nos serviços da aplicação.

## 6. Status do desenvolvimento

### Entregas

| Entrega | Escopo | Status |
|---|---|---|
| 01 | Modelagem inicial do banco, models e integridade | Concluída; testes manuais realizados |
| 02 | Autenticação e controle de acesso por perfis | Implementada; testes manuais aprovados |
| 03 | Cadastro e consulta de equipamentos | Próxima etapa |
| Futuras | Movimentações, pendências, importação CSV, priorização e indicadores | Planejadas |

### Concluído

- [x] Configuração do repositório, Laravel e MySQL.
- [x] Modelagem das oito tabelas e relacionamentos Eloquent.
- [x] Restrições de unicidade e exclusividade de instalações ativas.
- [x] Testes manuais de integridade e histórico.
- [x] Migration do campo `perfil` em `users`.
- [x] Login, logout e proteção de rotas.
- [x] Middleware de autorização por perfil.
- [x] Layout Blade, tela de login e dashboard inicial.
- [x] Seeder de usuários fictícios para testes locais.
- [x] Testes manuais de autenticação e autorização para os três perfis.

### Pendente

- [ ] Interfaces de cadastro e consulta de equipamentos.
- [ ] Gestão de pendências técnicas.
- [ ] Serviços transacionais de movimentação.
- [ ] Importação e validação de arquivos CSV.
- [ ] Ranking de priorização.
- [ ] Indicadores operacionais do dashboard.
- [ ] Interface administrativa de gerenciamento de usuários.
- [ ] Testes automatizados e testes finais de integração.

**Nota:** a suíte de testes automatizados foi adiada nesta fase. A aprovação dos testes manuais não equivale a uma auditoria de segurança ou a uma validação completa para produção.

## 7. Instalação e configuração

### 7.1. Pré-requisitos

- PHP compatível com Laravel 12 (mínimo PHP 8.2; ambiente utilizado: PHP 8.3).
- Composer.
- MySQL 8.4 ou versão compatível.
- Node.js e npm.
- Git.
- Laragon ou ambiente equivalente.

### 7.2. Clonar o repositório

```bash
cd C:\laragon\www
git clone https://github.com/DiegoArmelin/sgr-m.git
cd sgr-m
git switch develop
git pull origin develop
```

A branch `develop` é utilizada para integrar as funcionalidades em desenvolvimento. A disponibilidade da Entrega 02 nessa branch depende da conclusão do respectivo Pull Request.

### 7.3. Instalar dependências

```bash
composer install
npm install
```

### 7.4. Preparar o ambiente

No Windows:

```bat
copy .env.example .env
```

Configure no `.env` os valores adequados para o computador local, incluindo:

```dotenv
APP_NAME="SGR-M"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgr_m
DB_USERNAME=root
DB_PASSWORD=
```

Os valores de usuário e senha acima são apenas um **exemplo de configuração local**.

Gere a chave da aplicação:

```bash
php artisan key:generate
```

### 7.5. Criar o banco e executar migrations

No MySQL:

```sql
CREATE DATABASE IF NOT EXISTS sgr_m
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

No terminal do projeto:

```bash
php artisan config:clear
php artisan migrate
php artisan migrate:status
```

**Não execute `migrate:fresh` em um banco com dados que deseja preservar**, pois esse comando recria as tabelas.

### 7.6. Usuários fictícios para testes locais

Na versão que contém a Entrega 02, o arquivo `database/seeders/UserTestSeeder.php` cria, se ainda não existirem, os seguintes usuários:

| E-mail fictício | Perfil |
|---|---|
| `gestor@sgr-m.test` | Gestor |
| `tecnico@sgr-m.test` | Técnico |

Configure uma senha **exclusiva de desenvolvimento**, com pelo menos 12 caracteres, no `.env` local:

```dotenv
SGRM_TEST_PASSWORD="defina-aqui-uma-senha-forte-e-exclusiva"
```

Execute:

```bash
php artisan config:clear
php artisan db:seed --class=UserTestSeeder
```

O seeder não deve ser executado em produção. Ele **não cria o administrador automaticamente**: o usuário administrador utilizado nos testes iniciais foi criado manualmente no ambiente local. Não publique senhas em commits, screenshots ou documentação.

### 7.7. Executar a aplicação

```bash
npm run build
php artisan serve
```

Acesse `http://127.0.0.1:8000/login`. Em um Laragon configurado com hosts locais, também poderá ser utilizado `http://sgr-m.test/login`.

O dashboard é protegido: após login válido, a aplicação redireciona para `/dashboard`. O botão **Sair** encerra a sessão.

## 8. Organização do desenvolvimento no GitHub

| Branch | Finalidade |
|---|---|
| `main` | Versão estável do projeto |
| `develop` | Integração das entregas em desenvolvimento |
| `feature/...` | Desenvolvimento isolado de funcionalidades |
| `fix/...` | Correção de defeitos |
| `docs/...` | Atualização de documentação |

Fluxo recomendado:

```bash
git switch develop
git pull origin develop
git switch -c feature/nome-da-funcionalidade
# implementar e testar
git add .
git diff --cached --check
git commit -m "feat: descricao da funcionalidade"
git push -u origin feature/nome-da-funcionalidade
```

Em seguida, abra um Pull Request com **base `develop`** e **compare a branch da funcionalidade**, revisando os arquivos antes do merge. Evite desenvolver diretamente em `main` ou `develop`.

## 9. Documentação técnica

Os relatórios detalhados ficam na pasta [`docs/`](docs/):

- [Entrega 01 — Modelagem Inicial do Banco de Dados](docs/01-modelagem-banco.md).
- [Entrega 02 — Autenticação e Controle de Acesso](docs/02-autenticacao-perfis.md).

Os documentos descrevem as decisões técnicas, os componentes implementados, os testes manuais e as limitações de cada etapa.

## 10. Participação e colaboração

O SGR-M é desenvolvido pelo **Grupo 10** da disciplina de Laboratório de Engenharia de Software da Fatec São Paulo.

Os integrantes podem colaborar no backend, frontend, banco de dados, testes, documentação e revisão de Pull Requests. Para contribuir diretamente, precisam ter acesso apropriado ao repositório; também podem utilizar forks e Pull Requests.

## 11. Segurança e uso dos dados

O SGR-M utiliza exclusivamente dados fictícios para desenvolvimento, testes e demonstração.

**Não inclua no repositório:** dados ferroviários operacionais reais, números de série reais, documentos internos de empresas, credenciais, senhas, arquivos `.env` ou dados pessoais não autorizados.

A autenticação e o controle de perfis constituem uma camada inicial de segurança. Antes de qualquer implantação real, serão necessárias validações complementares, revisão de permissões, testes e medidas operacionais adequadas.

---

**SGR-M — Grupo 10 | Fatec São Paulo | 2026**
