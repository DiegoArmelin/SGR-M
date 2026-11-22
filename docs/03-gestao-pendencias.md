# SGR-M — Gestão de pendências técnicas

**Projeto:** SGR-M — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** Módulo incremental de gestão de pendências  
**Branch:** `feature/entrega-03-consulta-equipamentos`  
**Situação:** motivo catalogado e observações complementares implementados; validação desta atualização pendente no Laragon.

## 1. Objetivo

Cadastrar e acompanhar pendências técnicas a partir da localização **Frota → Trem → Carro → Eixo** e do tipo de equipamento (rodeiro ou motor). O sistema resolve o número de série instalado automaticamente e preserva, no registro, tanto o equipamento identificado quanto a posição da ocorrência.

## 2. Regras de negócio

| Regra | Implementação |
|---|---|
| Tipo | Cada pendência pertence a um rodeiro **ou** a um motor |
| Seleção | Frota → Trem → Carro → Eixo → Rodeiro ou Motor |
| Vínculo | Busca automática da instalação ativa (`data_retirada IS NULL`) na posição escolhida |
| Posição de origem | Armazenada em `eixo_id` para preservar a localização do registro após a movimentação |
| Sem instalação | Rejeita cadastro se não houver equipamento do tipo escolhido no eixo |
| Integridade da seleção | Confere novamente no POST se trem pertence à frota, carro ao trem e eixo ao carro |
| Motivo | Obrigatório, selecionado na tabela `motivos_retirada`, filtrado por tipo de equipamento e ativo |
| Observações | Campo complementar opcional, até 2.000 caracteres |
| Descrição anterior | Mantida para compatibilidade com registros antigos; novos registros copiam a descrição do motivo |
| Prioridade | `critica`, `alta`, `media`, `baixa` |
| Situação | `aberta` ou `resolvida` |
| Registro | Todos os perfis autenticados podem registrar e consultar |
| Resolução | Na versão inicial, todos os perfis autenticados podem resolver com justificativa obrigatória |
| Rastreabilidade | A pendência acompanha o equipamento, mesmo após sua substituição |
| Ordenação | Abertas primeiro, depois prioridade crítica → alta → média → baixa |
| Integridade | Chaves estrangeiras e validação de número de série |
| Exclusão | Não foi disponibilizada exclusão; a resolução mantém o registro histórico |

A tabela não força uma única pendência aberta por equipamento: um mesmo rodeiro ou motor pode apresentar diferentes ocorrências independentes.

**Limitação de escopo:** a resolução não é automática quando um equipamento é substituído. A pendência só muda de situação pelo comando de resolução. A integração com o módulo de substituições poderá ser feita posteriormente, após definição da regra acadêmica.

## 3. Arquivos e responsabilidades

| Arquivo | Finalidade |
|---|---|
| `database/migrations/2026_11_22_120000_create_pendencias_equipamentos_table.php` | Cria pendências e vínculos com equipamento e usuário |
| `database/migrations/2026_11_22_130000_add_eixo_id_to_pendencias_equipamentos_table.php` | Adiciona vínculo opcional com eixo, sem alterar registros preexistentes |
| `database/migrations/2026_11_22_140000_add_motivo_and_observacoes_to_pendencias_equipamentos.php` | Acrescenta FK opcional do motivo e observações preservando pendências existentes |
| `app/Models/PendenciaEquipamento.php` | Model Eloquent e relações com rodeiro, motor e autor |
| `app/Http/Controllers/PendenciaEquipamentoController.php` | Listagem, cadastro, validação e resolução |
| `resources/views/pendencias/index.blade.php` | Listagem, filtros e formulário de resolução |
| `resources/views/pendencias/create.blade.php` | Seletores progressivos de frota, trem, carro, eixo e tipo; exibe a série instalada automaticamente |
| `tests/Feature/PendenciaEquipamentoTest.php` | Testes de autenticação, cadastro, validação e resolução |
| `routes/web.php` | Rotas protegidas para listagem, cadastro e resolução |
| `resources/views/layouts/app.blade.php` | Atalho para pendências na navegação |
| `README.md` | Atualização do estágio do projeto |

## 4. Métodos e principais recursos de programação

- **`index(Request)`:** valida filtros de situação e tipo; consulta as pendências com `with()` para evitar N+1; aplica ordem por situação e prioridade; pagina em 20 registros.
- **`create(Request)`:** valida filtros da hierarquia, identifica a série instalada e carrega apenas motivos ativos do catálogo para o tipo de equipamento selecionado.
- **`store(Request)`:** valida os IDs de frota/trem/carro/eixo e o tipo; verifica os vínculos entre os quatro níveis; bloqueia o eixo, consulta a instalação ativa do equipamento correspondente e salva o `eixo_id`, `rodeiro_id` ou `motor_id`, além do autor, prioridade, `motivo_retirada_id` obrigatório e observações complementares opcionais. O motivo deve existir, estar ativo e corresponder ao tipo de equipamento. O número de série não é digitado nem confiado ao formulário.
- **`resolver(Request, PendenciaEquipamento)`:** exige justificativa, bloqueia o registro via `lockForUpdate()` e altera o estado somente quando a pendência está aberta.
- **`DB::transaction()`:** garante atomicidade do encerramento.
- **`Rule::in()`:** impede prioridades e tipos fora dos conjuntos autorizados.
- **`belongsTo()`:** associa cada ocorrência ao Model do equipamento correspondente e ao autor.
- **`protected $table = 'pendencias_equipamentos'`:** informa explicitamente o nome da tabela do Model, evitando a inferência automática incompatível do Eloquent.
- **`paginate()->withQueryString()`:** mantém filtros na navegação entre páginas.

## 5. Procedimento de execução no Laragon

Antes da migration, faça um backup do banco de desenvolvimento, caso possua informações importantes.

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan migrate
php artisan test --filter=PendenciaEquipamentoTest
php artisan test
```

**Não executar `migrate:fresh`.** A migration adiciona a tabela de pendências, sem apagar cadastros e instalações anteriores.

Após os testes, acesse `http://sgr-m.test/pendencias` autenticado no sistema.

## 6. Verificação funcional

1. Abrir o módulo de pendências e selecionar **Registrar pendência**.
2. Selecionar frota, trem, carro e posição do eixo; em seguida, escolher rodeiro ou motor.
3. Confirmar a série instalada; escolher um motivo/defeito do catálogo, a prioridade e, opcionalmente, descrever observações complementares.
4. Conferir que o registro aparece como **Aberta** na listagem, com a posição de origem.
4. Aplicar filtros por tipo e situação.
5. Cadastrar uma pendência crítica e outra baixa para conferir a ordenação.
6. Resolver uma pendência fornecendo justificativa.
7. Confirmar que a resolução registra data/hora e que o vínculo com o equipamento é mantido.
8. Tentar adulterar IDs da hierarquia no POST ou selecionar uma posição sem instalação ativa; a aplicação deve rejeitar.
9. Tentar resolver novamente uma pendência fechada; a aplicação deve rejeitar.

## 7. Testes, evidências e limitações

A suíte `PendenciaEquipamentoTest.php` foi atualizada com doze cenários, incluindo recusa de motivo de tipo incorreto e obrigatoriedade do motivo com observações opcionais. Os dez cenários originais incluem: visitante redirecionado, acesso dos três perfis, exibição automática de série por posição, cadastro de rodeiro, cadastro de motor, hierarquia adulterada, posição sem instalação ativa, resolução com justificativa, dupla resolução e ordenação por prioridade.

**Evidência:** o desenvolvedor informou aprovação dos testes da versão anterior. A evolução para motivo catalogado e observações ainda não foi testada no ambiente local; a aprovação desta versão depende da execução.

**Limitações:** não inclui priorização combinada com periodicidade/diâmetro, regras automáticas de abertura, edição de pendências, auditoria do usuário resolutor ou painel gerencial. Esses pontos pertencem às próximas etapas.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- **CRUD parcial controlado:** inclusão, leitura e transição para resolvida, sem exclusão destrutiva.
- **Modelagem relacional:** vínculo da ocorrência com um equipamento e seu autor.
- **Integridade de domínio:** enumerações de prioridade/situação e validações HTTP.
- **RBAC:** permissões verificadas em middleware.
- **Transações e concorrência:** uso de `lockForUpdate()` durante resolução.
- **Rastreabilidade:** manutenção do histórico de resolução.
- **Testes de integração:** cenários positivos e negativos, com validação no banco.

**Resumo para apresentar:** “O módulo permite registrar pendências por número de série, classificá-las por criticidade e acompanhar sua resolução sem perder o vínculo com o equipamento, oferecendo filtros e testes automatizados.”

## 9. Evolução do cadastro: localização em vez de série

A mudança preserva as pendências antigas: a nova coluna `eixo_id` é opcional porque registros previamente criados não armazenavam a posição. No cadastro novo, o campo é obrigatório e a série é obtida pela instalação ativa. A listagem mostra a localização histórica quando disponível; nos registros antigos, informa que não foi registrada. Mesmo após a substituição do equipamento, a pendência permanece associada ao item originalmente apontado e à posição de origem. A interface utiliza seletores dependentes por GET; a verificação definitiva ocorre no POST, no servidor.

## 10. Evolução: motivo catalogado e observações complementares

A tela de cadastro passou a exigir um motivo ativo da tabela `motivos_retirada`, com filtragem por `tipo_equipamento` (rodeiro ou motor). Também passou a aceitar `observacoes` opcionais de até 2.000 caracteres. Os registros antigos permanecem compatíveis porque a nova chave estrangeira é nullable; o controller exige motivo somente nos novos cadastros. A coluna `descricao` é preenchida com a descrição catalogada para garantir compatibilidade com a listagem e com históricos anteriores. A regra de compatibilidade entre motivo e tipo é validada no servidor, não apenas na interface.

**Arquivo de migração:** `2026_11_22_140000_add_motivo_and_observacoes_to_pendencias_equipamentos.php`.

**Procedimento:** após backup do banco de desenvolvimento, executar `git pull origin feature/entrega-03-consulta-equipamentos`, `php artisan migrate`, `php artisan test --filter=PendenciaEquipamentoTest` e `php artisan test`. Não é necessário executar novamente o seeder de motivos se os 14 registros já constarem no banco.
