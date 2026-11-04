# SGR-M — Sistema de Gestão de Rodeiros e Motores

**Projeto Acadêmico — Laboratório de Engenharia de Software**  
**Fatec São Paulo | Curso de Análise e Desenvolvimento de Sistemas | Grupo 11**

## 1. Sobre o projeto

O **SGR-M (Sistema de Gestão de Rodeiros e Motores)** é uma aplicação web desenvolvida como projeto acadêmico da disciplina de Laboratório de Engenharia de Software da Fatec São Paulo.

O sistema tem como objetivo apoiar o gerenciamento de equipamentos utilizados na manutenção ferroviária, com foco no controle de rodeiros e motores de tração, na rastreabilidade dos equipamentos e no acompanhamento de pendências técnicas.

A proposta surgiu da necessidade de organizar e consolidar informações sobre equipamentos cuja condição pode impactar a segurança, a confiabilidade e a disponibilidade operacional dos trens.

O projeto busca oferecer uma ferramenta para centralizar registros, acompanhar condições técnicas e auxiliar na identificação de prioridades de manutenção.

**Importante:** trata-se de um projeto acadêmico, desenvolvido exclusivamente com dados fictícios. Não representa um sistema oficial do Metrô de São Paulo e não substitui procedimentos técnicos ou operacionais de segurança.

## 2. Objetivos

- Permitir o cadastro e a identificação individual de rodeiros e motores por número de série.
- Organizar os equipamentos conforme a estrutura de frotas, trens, carros e eixos.
- Manter o histórico de instalações, retiradas e substituições.
- Registrar e acompanhar pendências de manutenção.
- Importar informações técnicas por arquivos CSV.
- Identificar equipamentos que exigem maior atenção conforme regras de negócio.
- Disponibilizar consultas, indicadores e informações consolidadas para apoiar a gestão da manutenção.

## 3. Tecnologias utilizadas

| Tecnologia | Aplicação |
|---|---|
| PHP 8.3 | Linguagem de programação |
| Laravel 12 | Framework backend |
| MySQL 8.4 | Banco de dados relacional |
| Blade | Templates de interface |
| JavaScript | Interatividade das páginas |
| HTML e CSS | Estrutura e apresentação das interfaces |
| Laragon | Ambiente de desenvolvimento local |
| Composer | Gerenciamento de dependências PHP |
| Node.js e npm | Gerenciamento dos recursos frontend |
| Git e GitHub | Versionamento e desenvolvimento colaborativo |

## 4. Funcionalidades previstas

### 4.1. Cadastro de equipamentos

Cadastro de frotas, trens, carros, eixos, rodeiros e motores.

Cada rodeiro e motor será identificado por um número de série único, permitindo sua rastreabilidade independentemente da posição em que esteja instalado.

### 4.2. Gestão de pendências

Registro e acompanhamento de pendências técnicas relacionadas aos equipamentos.

As pendências poderão receber classificações de prioridade:

- Crítica;
- Alta;
- Média;
- Baixa.

### 4.3. Importação de dados CSV

Importação de informações técnicas provenientes de arquivos CSV, incluindo:

- Periodicidade percentual dos rodeiros;
- Diâmetro das rodas LR e LO.

Os arquivos serão validados antes do processamento para reduzir o risco de inconsistências nos registros.

### 4.4. Priorização dos equipamentos

O sistema apresentará um ranking dos equipamentos que necessitam de atenção, seguindo uma ordem de precedência definida nas regras de negócio:

1. Pendências críticas;
2. Pendências altas;
3. Pendências médias;
4. Pendências baixas;
5. Rodeiros com periodicidade superior a 100%;
6. Rodeiros com diâmetro inferior a 745 mm.

Os limites de periodicidade e diâmetro são parâmetros adotados para o projeto acadêmico, não devendo ser interpretados como limites operacionais oficiais.

### 4.5. Movimentação de equipamentos

Registro de instalações, retiradas e substituições de rodeiros e motores.

O sistema deverá preservar o histórico dos equipamentos e impedir instalações simultâneas incompatíveis.

### 4.6. Dashboard e indicadores

Painel de acompanhamento com informações consolidadas sobre equipamentos, pendências e condições de manutenção.

### 4.7. Autenticação e controle de acesso

Controle de acesso às funcionalidades conforme os perfis e permissões definidos para a aplicação.

## 5. Estrutura do banco de dados

A modelagem inicial contempla oito tabelas específicas:

| Tabela | Descrição |
|---|---|
| `frotas` | Cadastro das frotas |
| `trens` | Trens vinculados às frotas |
| `carros` | Carros vinculados aos trens |
| `eixos` | Posições de eixo dos carros |
| `rodeiros` | Cadastro dos rodeiros por número de série |
| `motores` | Cadastro dos motores por número de série |
| `instalacoes_rodeiros` | Histórico de instalação e retirada dos rodeiros |
| `instalacoes_motores` | Histórico de instalação e retirada dos motores |

### 5.1. Relacionamentos

A estrutura hierárquica principal é:

```text
Frota
 └── Trem
      └── Carro
           └── Eixo
                ├── Instalações de Rodeiros
                │    └── Rodeiro
                └── Instalações de Motores
                     └── Motor
```

Os equipamentos são cadastrados independentemente das posições em que estão instalados.

### 5.2. Integridade e rastreabilidade

O banco de dados possui restrições para garantir:

- Número de série único para cada equipamento.
- Apenas um rodeiro ativo em cada posição de eixo.
- Apenas um motor ativo em cada posição de eixo.
- Impossibilidade de um mesmo equipamento ocupar duas posições simultaneamente.
- Preservação das instalações anteriores no histórico.

As restrições de instalações ativas utilizam colunas geradas e índices únicos no MySQL.

## 6. Status do desenvolvimento

### Implementado

- [x] Criação do repositório e configuração do Git.
- [x] Configuração inicial do Laravel.
- [x] Conexão com o MySQL.
- [x] Modelagem das oito tabelas principais.
- [x] Criação dos Models Eloquent.
- [x] Implementação dos relacionamentos.
- [x] Restrições de integridade das instalações ativas.
- [x] Testes manuais de cadastro e relacionamento.
- [x] Testes manuais de instalação e substituição.
- [x] Verificação da preservação dos históricos.

### Pendente

- [ ] Implementação das interfaces de cadastro e consulta.
- [ ] Autenticação e controle de permissões.
- [ ] Gestão de pendências técnicas.
- [ ] Importação de dados CSV.
- [ ] Regras de priorização e ranking.
- [ ] Serviços de movimentação de equipamentos.
- [ ] Dashboard e indicadores.
- [ ] Seeders com dados fictícios.
- [ ] Testes automatizados.
- [ ] Testes de integração e validação final.

## 7. Instalação e configuração

### 7.1. Pré-requisitos

É necessário ter instalado:

- PHP 8.2 ou superior;
- Composer;
- MySQL 8.4 ou versão compatível;
- Node.js e npm;
- Git;
- Laragon ou ambiente equivalente.

### 7.2. Clonar o repositório

No terminal:

```bash
cd C:\laragon\www

git clone https://github.com/DiegoArmelin/sgr-m.git

cd sgr-m

git checkout develop
```

A branch `develop` contém a versão utilizada para integração das funcionalidades em desenvolvimento.

### 7.3. Instalar dependências

```bash
composer install
npm install
```

### 7.4. Configurar o ambiente

No Windows, crie o arquivo `.env` a partir do exemplo:

```bat
copy .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

### 7.5. Configurar o banco de dados

Crie um banco chamado `sgr_m` no MySQL.

Exemplo:

```sql
CREATE DATABASE IF NOT EXISTS sgr_m
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Configure no arquivo `.env`:

```env
APP_NAME="SGR-M"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgr_m
DB_USERNAME=root
DB_PASSWORD=
```

Altere o usuário e a senha conforme a configuração local.

### 7.6. Executar as migrations

```bash
php artisan config:clear
php artisan migrate
```

Para verificar as migrations:

```bash
php artisan migrate:status
```

### 7.7. Executar a aplicação

Compile os recursos frontend:

```bash
npm run build
```

Inicie o servidor Laravel:

```bash
php artisan serve
```

Acesse no navegador:

http://127.0.0.1:8000

**Observação:** nesta fase, a aplicação apresenta a estrutura inicial do Laravel. As interfaces específicas do SGR-M serão implementadas nas próximas etapas.

## 8. Organização do desenvolvimento no GitHub

O desenvolvimento utiliza o seguinte fluxo de branches:

| Branch | Finalidade |
|---|---|
| `main` | Versão estável do projeto |
| `develop` | Integração das funcionalidades em desenvolvimento |
| `feature/...` | Desenvolvimento isolado de novas funcionalidades |
| `fix/...` | Correções de problemas identificados |

### 8.1. Criar uma branch de funcionalidade

Antes de iniciar uma atividade:

```bash
git checkout develop
git pull origin develop
```

Crie a branch:

```bash
git checkout -b feature/nome-da-funcionalidade
```

### 8.2. Registrar alterações

Após implementar e testar:

```bash
git add .
git commit -m "feat: descricao da funcionalidade"
```

Envie ao GitHub:

```bash
git push -u origin feature/nome-da-funcionalidade
```

### 8.3. Abrir um Pull Request

No GitHub, crie um Pull Request com:

**Base:** `develop`  
**Compare:** branch da funcionalidade

As alterações deverão ser revisadas antes da integração.

Não é recomendado desenvolver diretamente nas branches `main` e `develop`.

## 9. Documentação técnica

A documentação complementar está organizada na pasta `docs/`.

Documento disponível:

**[01 — Modelagem Inicial do Banco de Dados](docs/01-modelagem-banco.md)**

Esse documento apresenta as entidades implementadas, os relacionamentos, as regras de integridade e os testes realizados.

Novos documentos serão adicionados conforme o desenvolvimento dos módulos.

## 10. Participação e colaboração

O projeto é desenvolvido pelo Grupo 11 da disciplina de Laboratório de Engenharia de Software.

Os integrantes poderão colaborar nas atividades de:

- Desenvolvimento backend;
- Desenvolvimento frontend;
- Modelagem e manutenção do banco de dados;
- Elaboração e execução de testes;
- Documentação técnica;
- Integração e revisão de funcionalidades.

O repositório é público para consulta e acompanhamento do desenvolvimento.

Para enviar alterações diretamente ao repositório, os integrantes deverão ser adicionados como colaboradores. Também é possível contribuir por meio de forks e Pull Requests.

## 11. Segurança e utilização dos dados

Este é um projeto exclusivamente acadêmico.

Todos os dados utilizados para testes, demonstrações e desenvolvimento deverão ser fictícios.

Não devem ser incluídos no repositório:

- Dados operacionais reais ou documentos internos de empresas;
- Números de série reais de equipamentos;
- Informações confidenciais de manutenção;
- Credenciais de acesso;
- Arquivos `.env` com dados sensíveis;
- Informações pessoais não autorizadas.

O SGR-M é uma ferramenta proposta para apoio à gestão da manutenção e não substitui avaliações técnicas, procedimentos de segurança ou decisões operacionais.

---

**SGR-M — Grupo 11 | Fatec São Paulo | 2026**