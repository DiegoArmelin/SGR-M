# SGR-M — Carga inicial de frotas, trens, carros e eixos

**Projeto:** SGR-M — Grupo 10 — Fatec São Paulo  
**Disciplina:** Laboratório de Engenharia de Software  
**Entrega:** 03 — Cadastro e Consulta de Equipamentos  
**Natureza:** dados exclusivamente fictícios para ambiente acadêmico  
**Implementação:** `database/seeders/EstruturaFrotasSeeder.php`  
**Teste:** `tests/Feature/EstruturaFrotasSeederTest.php`

## 1. Objetivo

Popular a hierarquia Frota → Trem → Carro → Eixo, utilizando os Models e migrations existentes. A rotina cadastra posições de eixo, não instala motores ou rodeiros; essas movimentações terão implementação específica posteriormente.

## 2. Regras de negócio e geração

| Frota | Intervalo inclusivo de trens | Quantidade de trens | Carros por trem | Eixos por carro | Total de carros | Total de eixos |
|---|---|---:|---:|---:|---:|---:|
| I | I01 a I25 | 25 | 6 | 4 | 150 | 600 |
| J | J26 a J51 | 26 | 6 | 4 | 156 | 624 |
| L | L26 a L47 | 22 | 6 | 4 | 132 | 528 |
| **Total** | | **73** | | | **438** | **1.752** |

O código de cada carro corresponde ao código do trem concatenado à posição do carro (1 a 6), sem separador: `I01` → `I011`, `I012`, `I013`, `I014`, `I015`, `I016`; `J26` → `J261` a `J266`; `L47` → `L471` a `L476`. Para cada carro, geram-se os eixos 1, 2, 3 e 4.

## 3. Arquivos e responsabilidades

| Arquivo | Responsabilidade |
|---|---|
| `database/seeders/EstruturaFrotasSeeder.php` | Percorre os três intervalos, cria ou reutiliza frotas, trens, carros e eixos, valida vínculos e executa tudo em transação. |
| `tests/Feature/EstruturaFrotasSeederTest.php` | Verifica totais, códigos iniciais e finais, seis carros de I01, quatro eixos de I011 e idempotência. |
| `app/Models/Frota.php` | Model existente da tabela `frotas`; relacionamento `trens()`. |
| `app/Models/Trem.php` | Model existente da tabela `trens`; relacionamentos `frota()` e `carros()`. |
| `app/Models/Carro.php` | Model existente da tabela `carros`; relacionamentos `trem()` e `eixos()`. |
| `app/Models/Eixo.php` | Model existente da tabela `eixos`; relacionamento `carro()`. |

**Não foram criadas migrations novas ou alterados os dados operacionais existentes.** O `DatabaseSeeder.php` não foi alterado: a carga deve ser disparada explicitamente, evitando execução involuntária.

## 4. Métodos e principais recursos de programação

- **`run()`:** método obrigatório do Seeder, chamado por `artisan db:seed`.
- **`app()->environment('production')`:** proteção contra execução intencional em ambiente de produção.
- **`DB::transaction()`:** executa as inserções de maneira atômica; se houver exceção, a transação é revertida.
- **`firstOrCreate()`:** consulta o registro pela chave informada; cria apenas se ainda não existir. Não sobrescreve valores preexistentes.
- **`str_pad()`:** completa números de um dígito com zero à esquerda, resultando em I01, e não I1.
- **Laços `for`:** o primeiro percorre os trens da frota; o segundo, as seis posições de carro; o terceiro, os quatro eixos.
- **Validação de vínculo:** ao localizar um trem existente, verifica se pertence à frota correta; ao localizar um carro por posição, confirma seu código.
- **Chaves estrangeiras e índices únicos:** reforçam a integridade referencial e impedem duplicidade de códigos e posições conforme a modelagem já criada.

## 5. Procedimento de execução no Laragon

Na branch `feature/entrega-03-consulta-equipamentos`:

```bash
git status
git pull origin feature/entrega-03-consulta-equipamentos
php artisan test --filter=EstruturaFrotasSeederTest
php artisan db:seed --class=EstruturaFrotasSeeder
```

Executar o último comando somente no banco local de desenvolvimento com dados fictícios. **Não executar** `migrate:fresh`.

## 6. Verificação no Tinker

```php
App\Models\Frota::whereIn('codigo', ['I', 'J', 'L'])->withCount('trens')->get(['id', 'codigo']);
App\Models\Trem::whereIn('codigo', ['I01', 'I25', 'J26', 'J51', 'L26', 'L47'])->orderBy('codigo')->pluck('codigo');
App\Models\Carro::whereHas('trem', fn ($q) => $q->where('codigo', 'I01'))->orderBy('posicao')->pluck('codigo');
App\Models\Eixo::whereHas('carro', fn ($q) => $q->where('codigo', 'I011'))->orderBy('numero')->pluck('numero');
```

Se o banco estiver vazio antes da carga, a contagem global será 3 frotas, 73 trens, 438 carros e 1.752 eixos. Caso existam registros anteriores, os totais globais poderão ser maiores. Registros existentes não são excluídos ou sobrescritos.

## 7. Testes, evidências e limitações

`test_seeder_cria_frotas_trens_carros_e_eixos_corretamente`: verifica a criação dos registros e a numeração hierárquica.

`test_seeder_pode_ser_executado_novamente_sem_duplicar`: executa duas vezes e verifica que os totais se mantêm.

**Evidência informada pelo desenvolvedor:** os testes desta carga foram executados com sucesso no ambiente local. Não foi disponibilizada a saída detalhada do comando nem a contagem final da base após o seeder, portanto a execução do carregamento e os totais efetivos do MySQL não são aqui tratados como comprovados.

**Limitações:** a rotina não cria instalações de rodeiros/motores, não substitui o CRUD visual de carros/eixos e não foi validada para produção. Ela mantém registros preexistentes e interrompe a carga quando encontra vínculos incompatíveis.

## 8. Conceitos acadêmicos aplicados e pontos para apresentação

- Modelagem relacional e integridade referencial.
- Eloquent ORM e associações entre entidades.
- Automação de dados de teste com Seeder.
- Idempotência e tratamento de inconsistências.
- Transações ACID (atomicidade na carga).
- Testes automatizados de integração com banco de dados.
- Desenvolvimento incremental e controle de versões.

**Resumo para apresentar:** “A carga inicial automatizou a criação de três frotas fictícias, 73 trens, 438 carros e 1.752 posições de eixo planejadas, usando transações, relações Eloquent e execução idempotente, com testes específicos de estrutura e repetição.”
