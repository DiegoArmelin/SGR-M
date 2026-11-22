# SGR-M — Módulo de movimentação de rodeiros e motores

**Projeto:** SGR-M — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** Desenvolvimento incremental — movimentação de equipamentos  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** implementação no GitHub; testes automatizados e validação visual ainda precisam ser confirmados no Laragon.

## 1. Objetivo

Implementar a substituição de um rodeiro ou motor instalado em um eixo por outro equipamento previamente cadastrado com status `estoque`. A operação encerra o vínculo anterior, cria nova instalação, altera status e preserva todo o histórico. O sistema não movimenta os dois tipos simultaneamente: cada troca é individual.

## 2. Regras de negócio

| Condição | Comportamento |
|---|---|
| Quem pode movimentar | Administrador e técnico |
| Quem consulta o histórico | Administrador, gestor e técnico |
| Equipamento de saída | Deve ser o equipamento atualmente instalado no eixo |
| Equipamento de entrada | Deve estar cadastrado, com status `estoque` e sem instalação ativa |
| Motivo de retirada | Troca programada, Diâmetro condenado, Calo acentuado, Defeito identificado, Outro |
| Data da movimentação | Não pode ser futura nem anterior à data da instalação atual |
| Equipamento retirado | Status alterado para `em_manutencao` |
| Equipamento instalado | Status alterado para `instalado` |
| Histórico | Instalação anterior recebe `data_retirada`; novo registro recebe `data_instalacao` |
| Garantia de integridade | As atualizações ocorrem em uma única transação; índices únicos impedem duplicidade ativa |

**Observação:** o cadastro dos equipamentos usados nesta simulação é fictício. A movimentação não representa autorização operacional para intervenção em equipamentos ferroviários reais.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/SubstituicaoEquipamentoService.php` | Regras de substituição em transação, validação de estado e histórico |
| `app/Http/Controllers/MovimentacaoEquipamentoController.php` | Exibe formulário, valida requisição, aciona serviço e consulta histórico |
| `resources/views/equipamentos/movimentar.blade.php` | Formulário de substituição com série atual, série de entrada, motivo e data |
| `resources/views/equipamentos/historico.blade.php` | Lista instalações atuais e antigas de rodeiros e motores por eixo |
| `resources/views/equipamentos/hierarquica.blade.php` | Atalhos para substituir equipamentos e consultar histórico |
| `routes/web.php` | Rotas de substituição e histórico protegidas pelos perfis |
| `tests/Feature/MovimentacaoEquipamentoTest.php` | Testes de troca válida, controle de acesso e falhas sem gravação parcial |

Não foram necessárias novas tabelas: a implementação utiliza `rodeiros`, `motores`, `instalacoes_rodeiros`, `instalacoes_motores` e `eixos` já existentes.

## 4. Métodos e recursos de programação

### Serviço de domínio

- **`substituir(Eixo $eixo, ...)`:** executa a substituição completa, independentemente do tipo, reaproveitando a regra de negócio.
- **`DB::transaction(..., 3)`:** operação atômica com até três tentativas em caso de deadlock.
- **`lockForUpdate()`:** bloqueia registros durante a atualização transacional, evitando concorrência entre operações na mesma posição.
- **`whereNull('data_retirada')`:** identifica instalações atualmente ativas.
- **`ValidationException::withMessages()`:** devolve erros de regra de negócio aos formulários de forma legível.
- **`update()` e `create()`:** encerram instalação antiga, criam vínculo novo e atualizam status dos equipamentos.

### Controller

- **`create(Eixo $eixo, string $tipo)`:** localiza equipamento ativo e mostra formulário de troca.
- **`store(Request, Eixo, string, SubstituicaoEquipamentoService)`:** valida número de série, data, motivo e observações, chama o serviço e retorna à consulta hierárquica.
- **`historico(Eixo $eixo)`:** consulta todos os vínculos de rodeiros e motores do eixo, inclusive os encerrados.
- **`instalacaoAtual()`:** método auxiliar privado para recuperar o vínculo ativo conforme o tipo.

## 5. Procedimento de execução no Laragon

Atualizar a branch com o Git e verificar rotas:

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan route:list
php artisan test --filter=MovimentacaoEquipamentoTest
php artisan test
```

**Importante:** por se tratar de operação que altera o banco de desenvolvimento, realizar backup antes de testar movimentações reais pela interface. Os testes automatizados usam SQLite temporário (`:memory:`) e não alteram o MySQL local. Não é necessário executar migrations nem seeders novamente.

## 6. Verificação funcional

1. Entrar como técnico ou administrador e abrir `/equipamentos/hierarquica`.
2. Selecionar frota, trem e carro, até visualizar os eixos.
3. Clicar em **Trocar rodeiro** ou **Trocar motor** na posição desejada.
4. Inserir a série de um sobressalente com status `estoque`.
5. Escolher data e motivo e confirmar a substituição.
6. Conferir o novo equipamento na consulta hierárquica.
7. Abrir o **Histórico** do eixo e confirmar que o equipamento anterior passou a ter data de retirada, preservando sua instalação antiga.
8. Consultar o equipamento anterior por série e confirmar status `em_manutencao`.
9. Testar entrada inexistente ou não disponível: o sistema deve bloquear a operação sem movimentação parcial.
10. Tentar acessar a rota como gestor: deve retornar HTTP 403.

## 7. Testes, evidências e limitações

Os testes definidos em `MovimentacaoEquipamentoTest.php` incluem seis cenários:
- troca de rodeiro com histórico preservado;
- troca de motor;
- bloqueio de gestor nas rotas de movimentação, com histórico acessível;
- recusa de equipamento que não está em estoque;
- recusa quando o número de série de saída difere do instalado;
- recusa de retirada anterior à instalação.

**Estado atual:** os testes foram criados e enviados ao GitHub, porém sua execução local ainda não foi confirmada. A interface também necessita de validação manual.

**Limitações:** o módulo trata apenas substituição; não há processo de envio do equipamento à manutenção, retorno da manutenção para estoque ou instalação em eixo vazio. Também não implementa auditoria de usuário, operação simultânea de rodeiro e motor, ou vínculo com futuras pendências de manutenção.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **Separação de responsabilidades:** controller recebe dados e service implementa a regra de negócio.
- **Transações ACID:** encerramento e abertura de instalações são atômicos.
- **Integridade e exclusividade:** índices únicos do banco e validações aplicacionais.
- **Rastreabilidade:** histórico de instalações preservado em vez de sobrescrito.
- **RBAC:** diferencia permissão de movimentar e de consultar.
- **Segurança de entrada:** token CSRF, validação do request, confirmação da série atual.
- **Testes Feature:** cenários positivos e negativos, inclusive checagens no banco.

**Resumo para apresentar:** “O módulo de movimentação substitui rodeiros e motores por número de série, verifica a disponibilidade da peça, encerra o vínculo anterior e registra a nova instalação em uma transação, mantendo histórico e status coerentes.”
