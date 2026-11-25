# SGR-M — Roteiro de Apresentação Acadêmica

**Projeto:** Sistema de Gestão de Rodeiros e Motores (SGR-M)  
**Curso:** Análise e Desenvolvimento de Sistemas — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Grupo:** 10 (conforme README do repositório)  
**Situação:** roteiro proposto; adaptar ao tempo determinado pelo professor e à participação dos integrantes.

## 1. Objetivo da apresentação

Demonstrar como o grupo utilizou princípios de Engenharia de Software para desenvolver incrementalmente uma aplicação web acadêmica capaz de rastrear equipamentos por número de série, registrar pendências, acompanhar condições e preservar históricos de movimentação.

**Aviso de escopo:** todo o cenário demonstrado é fictício. O SGR-M não é um sistema oficial do Metrô e não substitui decisões técnicas de manutenção.

## 2. Sequência sugerida de 12 slides

### Slide 1 — Abertura

**Título:** SGR-M — Sistema de Gestão de Rodeiros e Motores  
**Subtítulo:** Projeto acadêmico de Laboratório de Engenharia de Software  
**Exibir:** instituição, disciplina, Grupo 10 e integrantes a confirmar.

**Fala sugerida:** “Nosso projeto propõe uma solução web para organizar o cadastro, as condições técnicas, as pendências e o histórico de rodeiros e motores em um ambiente ferroviário simulado.”

### Slide 2 — Contextualização do problema

**Pontos-chave:** equipamentos identificados por série; localização na hierarquia frota → trem → carro → eixo; necessidade de acompanhar condições e ocorrências; importância de preservar o histórico das substituições.

**Visual:** diagrama simples da hierarquia.  
**Fala sugerida:** “O principal desafio é manter a rastreabilidade de cada equipamento, mesmo quando ele muda de posição ou é substituído.”

### Slide 3 — Objetivos e escopo

**Pontos-chave:** consultas; gestão de pendências; importação de condições por CSV; ranking; movimentações; indicadores; relatório histórico.

**Limites:** não há cadastro manual individual de carros/eixos/equipamentos nem gerenciamento visual de usuários; exportação PDF pelo navegador; dados fictícios.

**Visual:** quadro de funcionalidades implementadas.

### Slide 4 — Tecnologias e arquitetura

**Tecnologias:** PHP 8.3, Laravel 12, MySQL, Blade, HTML/CSS/JavaScript, Git/GitHub, Laragon.

**Arquitetura:** rotas → controllers → services/models → banco; views Blade para apresentação.  
**Visual:** diagrama de blocos simples.  
**Fala sugerida:** “A divisão de responsabilidades facilita a manutenção e os testes. As regras de movimentação e importação não ficam concentradas nas telas.”

### Slide 5 — Modelagem e rastreabilidade

**Pontos-chave:** frotas, trens, carros, eixos, rodeiros, motores e instalações; série única; máximo de um rodeiro e um motor com instalação ativa por eixo; preservação do histórico.

**Visual:** recorte do modelo de dados ou diagrama lógico, com relacionamentos essenciais.

### Slide 6 — Perfis de acesso e navegação

**Perfis:** administrador, gestor, técnico.

**Pontos-chave:** login; central de funcionalidades; administração exclusiva; consultas disponíveis conforme perfil; movimentações restritas a administrador e técnico.

**Visual:** captura da página inicial com os cards e do menu Administração.

### Slide 7 — Gestão de pendências e substituições

**Pontos-chave:** prioridades crítica/alta/média/baixa, associação à posição e ao equipamento, catálogo de motivos, substituição por número de série, operação transacional, encerramento automático das pendências da peça retirada.

**Demonstração:** consultar uma pendência fictícia aberta, mostrar a tela de substituição e o histórico. Usar cópia de teste do banco para evitar modificar a base principal da apresentação.

### Slide 8 — Importação CSV

**Pontos-chave:** arquivo único com 11 colunas, incluindo dados de rodeiro e motor; validação de série e posição; datas mais recentes; atualização independente para os dois equipamentos; relatório de linhas atualizadas/ignoradas com justificativa.

**Visual:** trecho anonimizado de CSV e captura do relatório de importação.

### Slide 9 — Ranking de priorização

**Pontos-chave:** abas separadas de rodeiros e motores; pendências abertas por prioridade; diâmetro de rodeiros abaixo de 745 mm; periodicidade >=100%; filtros independentes de frota/trem/carro; cards percentuais sobre os instalados.

**Visual:** captura dos cards e tabelas com um filtro aplicado.

### Slide 10 — Dashboard gerencial e histórico

**Pontos-chave:** parque instalado, pendências críticas/altas, encerramentos nos últimos 30 dias, tendência de abertura x encerramento; gráficos de seis meses; roscas separadas de motivos de rodeiros e motores; comparação I/J/L; versão para salvar PDF pelo navegador.

**Visual:** duas capturas: visão geral e histórico.

### Slide 11 — Processo de desenvolvimento e qualidade

**Pontos-chave:** módulos incrementais; branches `feature/*`, integração via pull requests na `develop`; testes Feature do Laravel; validação no Laragon; documentação por entrega; plano de homologação com 20 cenários.

**Visual:** linha do tempo dos módulos 01–06 + pequena captura de testes aprovados.

**Cuidado:** a afirmação é que os testes locais foram aprovados pelo desenvolvedor. Não declarar homologação empresarial ou auditoria de segurança.

### Slide 12 — Conclusões e evolução

**Resultados:** centralização das informações, rastreabilidade por série, integração entre movimentação e pendências, consolidação de condições e indicadores de demonstração.

**Evoluções possíveis:** CRUD de equipamentos individuais, gestão de usuários, exportação PDF direta, otimização de relatórios maiores e auditoria de operações.

**Fala sugerida:** “O projeto permitiu aplicar modelagem de dados, regras de negócio, implementação incremental, testes automatizados e controle de versões em uma aplicação integrada.”

## 3. Demonstração prática sugerida

**Ordem:** login → central inicial → consulta hierárquica → pendências → ranking → indicadores → histórico de substituições → filtros → impressão PDF.

**Duração:** ajustar ao tempo de apresentação; evitar alterar o banco durante a demonstração se já houver capturas e evidências suficientes.

Se for mostrar uma substituição ao vivo, preparar previamente um equipamento de estoque e uma pendência fictícia na **base de demonstração**, verificando o número de série e a data de instalação. Depois, mostrar o registro histórico e o fechamento correspondente. Não executar seeders durante a apresentação.

## 4. Capturas necessárias

| Evidência | Tela | O que deve aparecer |
|---|---|---|
| E01 | Login | Entrada de usuário fictício, sem exibir senha |
| E02 | Página inicial | Cards de acesso rápido |
| E03 | Menu Administração | Visibilidade como administrador |
| E04 | Consulta hierárquica | Frota, trem, carro, eixo e séries |
| E05 | Pendências | Criticidades e filtros |
| E06 | Movimentação | Tipo, série de entrada/saída, motivo |
| E07 | Histórico de movimentação | Série retirada, data e motivo |
| E08 | Importação CSV | Status separado de rodeiro e motor |
| E09 | Ranking | Abas, percentuais e tabelas |
| E10 | Indicadores gerais | Parque, pendências e tendência |
| E11 | Histórico gráfico | Barras, duas roscas e frotas I/J/L |
| E12 | PDF | Relatório impresso/salvo a partir dos filtros |
| E13 | Terminal | Testes automatizados aprovados |

## 5. Divisão da apresentação no grupo

Atribuição sugerida, sem nomes previamente definidos:

1. **Integrante A:** introdução, contexto, objetivos e escopo.
2. **Integrante B:** arquitetura, modelagem e autenticação.
3. **Integrante C:** consultas, pendências, movimentações e importação.
4. **Integrante D:** ranking, indicadores, histórico, testes e conclusão.

Adaptar o número de participantes e o tempo de fala, mantendo coerência entre slides e demonstrações.

## 6. Perguntas prováveis da banca

**Como o sistema identifica o equipamento?** Pelo número de série único associado a instalações em posições de eixos.

**O que impede dois equipamentos do mesmo tipo no mesmo eixo?** Regras de integridade para instalações ativas, complementadas por validações de serviço.

**Por que usar transações?** Para preservar a consistência entre encerramento de instalação, criação da nova, alteração de status e fechamento das pendências da peça substituída.

**A importação substitui dados antigos sem controle?** Não. A data é comparada por equipamento e a série deve corresponder à instalação atual.

**O ranking define uma manutenção obrigatória?** Não. Os limites empregados são critérios acadêmicos de alerta e apoio à visualização.

**Os dados são reais?** Não. O cenário utiliza equipamentos e movimentações fictícios, com identificadores próprios.

**O relatório PDF é criado no servidor?** Não. É uma versão de impressão que pode ser salva em PDF pelo navegador.

**Como os módulos foram testados?** Com testes automatizados do Laravel, cenários funcionais no Laragon e integração por pull requests.

## 7. Checklist antes da apresentação

- [ ] Revisar nomes dos integrantes, disciplina e identificação da turma.
- [ ] Confirmar tempo disponível para apresentação e demonstração.
- [ ] Confirmar que o SGR-M abre pela central inicial.
- [ ] Preparar contas fictícias de administrador/gestor/técnico sem divulgar credenciais.
- [ ] Criar backup e/ou cópia da base acadêmica de demonstração.
- [ ] Registrar capturas legíveis e com dados fictícios.
- [ ] Conferir que a impressão em PDF apresenta gráficos e filtros.
- [ ] Organizar falas, transições e possíveis perguntas.
- [ ] Executar `php artisan test` e guardar a evidência do resultado.
- [ ] Ensaiar o fluxo completo e manter capturas como alternativa à apresentação ao vivo.

---

**Material de apoio:** `docs/07-validacao-integrada-entrega-final.md`, `README.md` e documentação dos módulos 01–06.
