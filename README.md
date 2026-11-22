# SGR-M — Sistema de Gestão de Rodeiros e Motores

**Projeto Acadêmico — Laboratório de Engenharia de Software**  
**Fatec São Paulo | Análise e Desenvolvimento de Sistemas | Grupo 10**

> **Aviso:** projeto exclusivamente acadêmico, desenvolvido com dados fictícios. Não representa um sistema oficial do Metrô de São Paulo e não substitui procedimentos técnicos, avaliações de manutenção ou regras operacionais de segurança.

## 1. Sobre o projeto

O **SGR-M (Sistema de Gestão de Rodeiros e Motores)** é uma aplicação web acadêmica para apoiar o gerenciamento de equipamentos ferroviários, especialmente rodeiros e motores de tração. A proposta reúne a identificação por número de série, a localização dos equipamentos na composição ferroviária, o histórico de instalações e retiradas e o acompanhamento de pendências e condições de manutenção.

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

### 4.1. Cadastro e consulta de equipamentos — implementados no escopo acadêmico

A estrutura e a carga fictícia de frotas, trens, carros, eixos, rodeiros e motores foram implementadas. Há consulta por número de série, consulta hierárquica e CRUD de frotas/trens. Não há interfaces CRUD individuais de carros, eixos, rodeiros ou motores; no escopo entregue, esses dados são inicializados por seeders e consultados por número de série ou hierarquia. O CRUD administrativo de frotas/trens está disponível.

### 4.2. Gestão de pendências — implementada

Registro, consulta, filtragem e encerramento de pendências técnicas de rodeiros e motores, classificadas como **crítica, alta, média ou baixa**. Uma pendência aberta associada ao equipamento retirado é encerrada automaticamente após substituição bem-sucedida, preservando histórico e rastreabilidade. Há carga fictícia de 100 pendências abertas e 20 encerramentos adicionais para demonstração.

### 4.3. Importação de dados CSV — implementada e validada

Importação unificada de **periodicidade dos rodeiros e motores, diâmetros LR e LO**, identificando frota, trem, carro, eixo e ambas as séries em um único CSV de 11 colunas. O sistema só atualiza condições com data posterior à última registrada, verifica a instalação ativa e executa alterações em transação. A importação disponibiliza relatório individual por equipamento, filtros de resultado e exportação em CSV. Acesso restrito ao administrador.

### 4.4. Priorização de equipamentos — implementada e validada

A visualização é separada nas abas **Rodeiros** e **Motores**. Na aba de rodeiros, aparecem três tabelas: pendências técnicas (crítica → alta → média → baixa), diâmetros abaixo de 745 mm (menor primeiro, incluindo card de críticos <= 742 mm) e periodicidade >= 100% (maior percentual primeiro). A aba de motores apresenta **pendências e periodicidade**, sem diâmetro.

Cada tabela apresenta os **10 primeiros registros** após seus próprios filtros de frota/trem/carro, e a tabela de pendências também aceita filtro por prioridade. Os cards de pendências mostram total, críticas, altas, médias e baixas; os de periodicidade, totais >=100%, >=105% e >=110%. A seleção de uma tabela não altera as outras. Os limites são apenas parâmetros acadêmicos de apoio à gestão.

### 4.5. Movimentação de equipamentos — implementada

A aplicação permite substituir rodeiros e motores por número de série, selecionar peças em estoque, registrar motivos de retirada por tipo, atualizar os status e preservar o histórico de instalações em transação.

### 4.6. Dashboard e indicadores — implementados e validados

A página inicial apresenta uma central com cards de acesso rápido às funcionalidades, de acordo com o perfil. Separadamente, o Módulo 06 oferece a aba **Visão geral**, com parque de rodeiros e motores instalados, condições de diâmetro e periodicidade, pendências críticas/altas e pendências encerradas nos últimos 30 dias. A aba **Histórico de substituições** apresenta gráficos dos últimos seis meses, distribuição por motivos e comparação por frotas, com filtros e versão de impressão para **Salvar como PDF**. Um seeder específico gera 24 meses de movimentos totalmente fictícios (25 rodeiros e 20 motores por mês), sem modificar as instalações ativas.

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

A matriz abaixo resume o **controle de acesso implementado**. Os cadastros individuais de equipamentos e o gerenciamento visual de usuários não fazem parte do escopo funcional entregue.

| Funcionalidade | Administrador | Gestor | Técnico |
|---|:---:|:---:|:---:|
| Consultar equipamentos | Sim | Sim | Sim |
| Cadastrar frotas e trens | Sim | Não | Não |
| Importar condições por CSV | Sim | Não | Não |
| Registrar pendências | Sim | Sim | Sim |
| Movimentar equipamentos | Sim | Não | Sim |
| Visualizar indicadores | Sim | Sim | Sim |
| Consultar ranking e histórico | Sim | Sim | Sim |

## 5. Estrutura do banco de dados

A modelagem inicial contém oito tabelas centrais específicas da aplicação:

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

A aplicação também contém `pendencias_equipamentos` e `motivos_retirada`. Além dessas tabelas, o Laravel mantém estruturas como `users`, `sessions`, `cache` e `jobs`. A tabela `users` recebeu o campo `perfil` na Entrega 02.

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

Os módulos 01 a 06 foram implementados em branches de funcionalidade e integrados à `develop`, com testes automatizados executados e validados no Laragon durante o desenvolvimento.

| Módulo | Funcionalidade | Status |
|---|---|---|
| 01 | Modelagem do banco, integridade, relacionamentos e instalações ativas | Integrado e validado |
| 02 | Autenticação, sessão e controle de perfis | Integrado e validado |
| 03 | Cadastros de frotas/trens, consultas, movimentações e pendências | Integrado e validado |
| 04 | Importação unificada de condições CSV com relatório por equipamento | Integrado e validado |
| 05 | Ranking independente de pendências, diâmetros e periodicidades | Integrado e validado |
| 06 | Indicadores gerenciais, histórico de substituições, gráficos e central inicial | Integrado e validado |
| 07 | Revisão integrada, documentação e roteiro de demonstração | Em preparação |

**Limites de escopo:** não foram implementados CRUDs específicos de carros, eixos e equipamentos individuais nem gerenciamento visual de usuários. A exportação do histórico em PDF é realizada pela impressão do navegador (Salvar como PDF). A carga sintética de históricos não representa cronologia operacional real.

**Validação:** a aprovação dos testes no ambiente local confirma os cenários automatizados executados, mas não substitui auditoria de segurança, homologação empresarial ou validação para utilização operacional.

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

A branch `develop` integra os módulos validados. Para a entrega final, use a branch `feature/entrega-final-validacao-integrada` até a revisão ser aprovada.

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

O arquivo `database/seeders/UserTestSeeder.php` cria, se ainda não existirem, usuários fictícios para validação de perfis:

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

Após login válido, a aplicação redireciona para `/dashboard`, central de funcionalidades. O painel de indicadores permanece em `/indicadores`. O botão **Sair** encerra a sessão.

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
- [Entrega 03 — Cadastro e Consulta de Equipamentos](docs/03-cadastro-consulta-equipamentos.md).
- [Entrega 03 — Carga Inicial das Frotas I, J e L](docs/03-carga-inicial-frotas.md).
- [Entrega 03 — Carga Inicial de Rodeiros e Motores](docs/03-carga-inicial-equipamentos.md).
- [Entrega 03 — Consulta Hierárquica](docs/03-consulta-hierarquica.md).
- [Entrega 03 — Movimentação de Equipamentos](docs/03-movimentacao-equipamentos.md).
- [Entrega 03 — Catálogo de Motivos de Retirada](docs/03-motivos-retirada.md).
- [Entrega 03 — Gestão de Pendências Técnicas](docs/03-gestao-pendencias.md).
- [Entrega 04 — Importação de Condições CSV](docs/04-importacao-periodicidade-csv.md).
- [Entrega 05 — Ranking de Priorização](docs/05-ranking-priorizacao.md).
- [Módulo 06 — Dashboard Gerencial e Histórico](docs/06-dashboard-gerencial.md).

Os documentos descrevem as decisões técnicas, os componentes implementados, os testes e as limitações de cada etapa. Consulte também o [Plano de validação integrada e roteiro da entrega final](docs/07-validacao-integrada-entrega-final.md).

## 10. Participação e colaboração

O SGR-M é desenvolvido pelo **Grupo 10** da disciplina de Laboratório de Engenharia de Software da Fatec São Paulo.

Os integrantes podem colaborar no backend, frontend, banco de dados, testes, documentação e revisão de Pull Requests. Para contribuir diretamente, precisam ter acesso apropriado ao repositório; também podem utilizar forks e Pull Requests.

## 11. Segurança e uso dos dados

O SGR-M utiliza dados fictícios para desenvolvimento, testes e demonstração. Os seeders de demonstração identificam explicitamente dados sintéticos; o ambiente acadêmico não deve ser confundido com sistema operacional.

**Não inclua no repositório:** dados ferroviários operacionais reais, números de série reais, documentos internos de empresas, credenciais, senhas, arquivos `.env` ou dados pessoais não autorizados.

A autenticação e o controle de perfis constituem uma camada inicial de segurança. Antes de qualquer implantação real, serão necessárias validações complementares, revisão de permissões, testes e medidas operacionais adequadas.

---

**SGR-M — Grupo 10 | Fatec São Paulo | 2026**

