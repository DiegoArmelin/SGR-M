# SGR-M — Entrega 03: Consulta hierárquica de equipamentos

**Projeto:** SGR-M — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 03 — Consulta hierárquica de frotas, trens, carros e eixos  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** implementação enviada ao GitHub; execução dos testes e validação visual no Laragon ainda pendentes.

## 1. Objetivo

Permitir visualizar, em uma única consulta, o equipamento atualmente instalado em cada posição de eixo, respeitando os vínculos entre frota, trem e carro. A funcionalidade é **somente leitura** e usa as informações de carga inicial já armazenadas nas tabelas de equipamentos e de instalações.

## 2. Regras de negócio

- O usuário seleciona primeiro uma frota, depois um trem pertencente a essa frota e um carro pertencente ao trem.
- Não é permitido consultar um trem de outra frota nem um carro de outro trem, mesmo ao alterar manualmente a URL.
- Cada eixo apresenta somente instalações com `data_retirada IS NULL`.
- Para cada eixo, podem existir até um rodeiro e um motor ativos. A unicidade é protegida pelos índices definidos na modelagem do banco.
- Para o rodeiro são apresentados número de série, status, diâmetros LR/LO e periodicidade; para o motor, número de série e status.
- Medições ausentes aparecem como travessão, sem inventar valores.
- A leitura não altera vínculo, status ou data de movimentação.
- Administrador, gestor e técnico podem consultar. Visitantes precisam entrar no sistema.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Controllers/ConsultaHierarquicaController.php` | Valida filtros, consulta a hierarquia e carrega apenas as instalações ativas dos eixos do carro escolhido |
| `resources/views/equipamentos/hierarquica.blade.php` | Seletores dependentes e tabela dos equipamentos por eixo |
| `tests/Feature/ConsultaHierarquicaTest.php` | Testes de acesso, hierarquia válida/inválida e consulta de instalações ativas |
| `routes/web.php` | Nova rota GET `/equipamentos/hierarquica`, protegida por autenticação e perfil |
| `resources/views/layouts/app.blade.php` | Link para a consulta na navegação superior |
| `app/Models/Frota.php`, `Trem.php`, `Carro.php`, `Eixo.php` | Models existentes utilizados sem alteração |
| `app/Models/InstalacaoRodeiro.php`, `InstalacaoMotor.php` | Models existentes para recuperar equipamento e situação da instalação |

## 4. Métodos e principais recursos de programação

- **`ConsultaHierarquicaController::index(Request $request)`:** recebe os parâmetros GET, valida IDs, verifica relações de pertencimento e devolve a view com as opções de cada nível e os eixos do carro selecionado.
- **`Request::validate()`:** restringe os IDs a inteiros existentes em suas respectivas tabelas.
- **`firstWhere('id', ...)`:** verifica se o trem selecionado realmente pertence à frota consultada e se o carro pertence ao trem.
- **`abort(404)`:** impede exibir resultados para combinações de filtros incompatíveis.
- **`whereHas()` não é necessário nesta consulta:** o filtro de trem e carro é feito pelos respectivos IDs estrangeiros, após verificar a hierarquia.
- **`with([...])` (eager loading):** recupera instalações e equipamentos associados em consultas agrupadas, evitando uma consulta por eixo (problema N+1).
- **`whereNull('data_retirada')`:** inclui somente as instalações ativas, sem apagar o histórico.
- **`?->` (operador nullsafe do PHP):** protege a exibição quando uma posição está sem equipamento.
- **`@forelse` do Blade:** apresenta cada eixo ou uma mensagem quando não há registros.
- **Eventos `change` do JavaScript:** ao alterar a seleção, atualizam a página e limpam escolhas dependentes; o botão Consultar mantém a navegação funcional sem JavaScript.

## 5. Procedimento de execução no Laragon

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan route:list --name=equipamentos.hierarquica
php artisan test --filter=ConsultaHierarquicaTest
php artisan test
```

Se os testes estiverem aprovados, iniciar ou acessar o servidor Laravel e abrir `http://sgr-m.test/equipamentos/hierarquica` após login.

**Não há migrations novas nesta subetapa.** Não é necessário recriar tabelas nem executar novamente os seeders.

## 6. Verificação funcional

1. Entrar como administrador, gestor ou técnico.
2. Abrir a consulta hierárquica na navegação.
3. Selecionar frota `I`, trem `I01`, carro `I011`.
4. Confirmar a presença dos eixos `1`, `2`, `3` e `4`.
5. Confirmar os números de série distintos de rodeiros e motores atualmente instalados.
6. Verificar que os campos de diâmetro e periodicidade não informados mostram `—`.
7. Trocar de frota ou trem; seleções dependentes devem ser limpas.
8. Testar uma URL com IDs de trem/carro pertencentes a outros registros: a página deve retornar 404.

## 7. Testes, evidências e limitações

Os testes implementados cobrem: visitante redirecionado ao login; leitura por todos os perfis; exibição de rodeiro/motor ativos; ocultação de instalação histórica; rejeição de carro pertencente a outro trem; rejeição de trem de outra frota; parâmetros inválidos ou incompletos.

**Estado:** código e testes adicionados ao GitHub, mas ainda não executados no Laragon nesta subetapa. Não afirmar aprovação até receber o resultado local.

**Limitações:** não há ainda atualização de diâmetros, movimentação de equipamentos, substituição, inspeção das pendências ou histórico visual de retiradas. A interface apresenta dados já persistidos e não realiza essas operações.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **MVC:** controller processa filtros e view organiza os dados.
- **Relacionamentos Eloquent:** navegação entre entidades de uma estrutura hierárquica.
- **Integridade lógica:** validação da associação frota–trem–carro.
- **Rastreabilidade:** vínculo atual identificado a partir do histórico de instalações.
- **Consultas eficientes:** eager loading reduz consultas repetitivas.
- **Controle de acesso:** middleware de autenticação e papéis.
- **Testes Feature:** valida rotas, permissões e resultados de consulta.
- **Desenvolvimento incremental:** reutilização de migrations e Models existentes, sem reiniciar o banco.

**Resumo para apresentar:** “A consulta hierárquica conecta a estrutura da frota aos equipamentos instalados em cada eixo e filtra os vínculos atuais, mantendo o histórico separado e aplicando controle de acesso aos três perfis.”
