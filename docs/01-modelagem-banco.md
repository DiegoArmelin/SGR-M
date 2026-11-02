# SGR-M — Relatório Técnico de Desenvolvimento

**Entrega 01 — Modelagem Inicial do Banco de Dados**

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M)  
**Disciplina:** Laboratório de Engenharia de Software  
**Instituição:** Fatec São Paulo  
**Grupo:** 11  
**Data:** 02/11/2026  
**Branch:** `feature/modelagem-banco`  
**Situação:** Implementação inicial concluída e testes manuais de integridade realizados.

## 1. Objetivo da entrega

Implementar a estrutura inicial do banco de dados do SGR-M, permitindo o cadastro hierárquico dos trens, a identificação individual de rodeiros e motores e o armazenamento do histórico de instalações e substituições.

A modelagem busca garantir a rastreabilidade dos equipamentos por número de série e impedir inconsistências relacionadas à instalação simultânea de um mesmo equipamento em diferentes posições.

## 2. Tecnologias utilizadas

- **Framework:** Laravel 12.70.2
- **Linguagem:** PHP 8.3.28
- **Banco de dados:** MySQL 8.4.3
- **Ambiente local:** Laragon
- **ORM:** Eloquent
- **Controle de versões:** Git e GitHub
- **Banco da aplicação:** `sgr_m`

## 3. Estrutura do banco de dados

Foram implementadas oito tabelas específicas do SGR-M.

| Tabela | Finalidade |
|---|---|
| `frotas` | Cadastro das frotas ferroviárias |
| `trens` | Cadastro dos trens vinculados às frotas |
| `carros` | Cadastro dos carros que compõem os trens |
| `eixos` | Identificação das posições de eixo de cada carro |
| `rodeiros` | Cadastro individual dos rodeiros por número de série |
| `motores` | Cadastro individual dos motores por número de série |
| `instalacoes_rodeiros` | Histórico das instalações e retiradas dos rodeiros |
| `instalacoes_motores` | Histórico das instalações e retiradas dos motores |

As tabelas padrão do Laravel, como `users`, `cache` e `jobs`, também foram criadas pelas migrations iniciais do framework.

## 4. Relacionamentos implementados

A hierarquia principal é:

**Frota → Trem → Carro → Eixo**

Cada frota pode possuir vários trens. Cada trem pode possuir vários carros e cada carro pode possuir várias posições de eixo.

Os rodeiros e motores são cadastrados independentemente de sua posição atual.

As tabelas `instalacoes_rodeiros` e `instalacoes_motores` estabelecem os vínculos entre equipamentos e posições, preservando o histórico de suas movimentações.

## 5. Regras de integridade implementadas

### 5.1. Identificação individual

Cada rodeiro e motor possui um número de série único, protegido por restrição de unicidade no banco de dados.

### 5.2. Exclusividade das instalações ativas

O sistema de dados impede:

- Dois rodeiros ativos em uma mesma posição de eixo.
- Um rodeiro ativo em duas posições diferentes.
- Dois motores ativos em uma mesma posição de eixo.
- Um motor ativo em duas posições diferentes.

### 5.3. Preservação do histórico

Cada instalação possui uma data de instalação e uma data de retirada opcional.

Uma instalação sem data de retirada é considerada ativa.

Ao registrar a retirada, a instalação permanece armazenada para consultas históricas, e a posição pode receber outro equipamento.

### 5.4. Restrições no MySQL

As tabelas de instalações utilizam colunas geradas e índices únicos para controlar a exclusividade das instalações ativas.

Foram criadas as seguintes restrições:

- `uk_inst_rod_eixo_ativo`
- `uk_inst_rod_rodeiro_ativo`
- `uk_inst_mot_eixo_ativo`
- `uk_inst_mot_motor_ativo`

Essas restrições são aplicadas diretamente pelo MySQL, complementando as futuras validações da aplicação.

## 6. Informações de condição dos rodeiros

O cadastro de rodeiros contempla:

- Número de série.
- Diâmetro LR.
- Diâmetro LO.
- Periodicidade percentual.
- Situação cadastral.
- Data de atualização das condições.
- Observações.

O Model `Rodeiro` também possui um atributo calculado para consultar o menor diâmetro registrado entre LR e LO.

Esses dados serão utilizados futuramente pelo módulo de priorização, que aplicará as regras de negócio estabelecidas na especificação do SGR-M.

## 7. Testes realizados

Foram realizados testes manuais por meio do Laravel Tinker, utilizando registros fictícios.

| Teste | Resultado |
|---|---|
| Cadastro de frota | Aprovado |
| Cadastro de trem vinculado à frota | Aprovado |
| Cadastro de carro vinculado ao trem | Aprovado |
| Cadastro de eixo vinculado ao carro | Aprovado |
| Cadastro de rodeiros por número de série | Aprovado |
| Instalação inicial de rodeiro | Aprovado |
| Bloqueio de dois rodeiros ativos no mesmo eixo | Aprovado |
| Retirada e substituição de rodeiro | Aprovado |
| Preservação do histórico de instalações | Aprovado |
| Bloqueio do mesmo rodeiro em dois eixos | Aprovado |
| Testes equivalentes de instalação e substituição de motores | Aprovado, conforme execução manual |

Os testes confirmaram o comportamento esperado das principais restrições de exclusividade e da preservação dos registros históricos.

A implementação ainda não possui uma suíte de testes automatizados.

## 8. Limitações e funcionalidades pendentes

Esta entrega contempla a modelagem inicial e os relacionamentos básicos.

Ainda serão necessários:

- Desenvolvimento das telas de cadastro e consulta.
- Autenticação e autorização por perfis.
- Implementação dos serviços de instalação e substituição.
- Atualização consistente do status dos equipamentos.
- Validação das datas e das situações cadastrais.
- Transações para movimentações de equipamentos.
- Cadastro e acompanhamento de pendências.
- Importação dos arquivos CSV.
- Ranking de priorização e indicadores.
- Testes automatizados e testes de integração.
- Validação das regras de negócio com os responsáveis pela manutenção.

As restrições atualmente implementadas não substituem os procedimentos operacionais de segurança ou a avaliação técnica dos profissionais.

## 9. Orientações para os integrantes

Após a integração desta entrega à branch `develop`, cada integrante deverá clonar ou atualizar o repositório, configurar seu próprio arquivo `.env`, criar o banco MySQL local e executar:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

O arquivo `.env` deverá ser criado localmente a partir do `.env.example`, com as configurações de conexão apropriadas.

Os dados fictícios utilizados nos testes manuais não são compartilhados automaticamente pelo GitHub. Os seeders reproduzíveis serão desenvolvidos em uma etapa posterior.

## 10. Conclusão

A primeira etapa de modelagem do SGR-M estabeleceu a estrutura fundamental para o controle de equipamentos ferroviários, incluindo identificação por número de série, relacionamentos hierárquicos e histórico de instalações.

Os testes manuais demonstraram o funcionamento das principais restrições de integridade relacionadas à exclusividade dos equipamentos.

Essa entrega fornece a base para o desenvolvimento colaborativo dos módulos de gestão de pendências, movimentação, importação CSV, priorização e dashboard.