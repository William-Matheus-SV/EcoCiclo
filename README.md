# EcoCiclo

Aplicação web em PHP para administrar **participantes**, **agendamentos de coleta**, **confirmação de materiais reciclados**, **carteira de EcoPontos** e **troca por cupons de desconto** em comércios parceiros.

Cada quilograma de material reciclável coletado gera **10 EcoPontos**, que o participante pode trocar por cupons.

---

## Tecnologias

- PHP 8.0 ou superior (PDO com MySQL)
- MySQL 8.0.16+ ou MariaDB 10.2+ (necessário para as restrições `CHECK`)
- HTML e CSS + Tailwind
- Servidor local: XAMPP (Apache + MySQL)

Padrão adotado: código simples, com **classes apenas nos models**, e as páginas cuidando de validação e exibição.

---

## Como executar

1. Copie a pasta `ecociclo` para `C:\xampp\htdocs\`.
2. Abra o XAMPP e inicie o **Apache** e o **MySQL**.
3. No phpMyAdmin (`http://localhost/phpmyadmin`), importe o arquivo `Config/banco.sql`. Ele cria o banco `ecociclo`, as tabelas e os dados de exemplo.
4. Confira o arquivo `Config/database.php`:
   ```php
   $servidor = 'localhost';
   $banco    = 'ecociclo';
   $usuario  = 'root';
   $senha    = '';
   ```
5. Acesse `http://localhost/ecociclo/Auth/login.php`.

### Usuários de exemplo

A senha de **todos** é `Eco@1234`.

| Perfil | E-mail | Observação |
|---|---|---|
| ADMIN | admin@ecociclo.com | Acesso total de gestão |
| COLETOR | roberto.coletor@ecociclo.com | Confirma coletas |
| PARTICIPANTE | ana.silva@email.com | Saldo inicial: 70 pts |
| PARTICIPANTE | carlos.oliveira@email.com | Saldo inicial: 50 pts |
| PARTICIPANTE | mariana.costa@email.com | Saldo inicial: 95 pts |

---

## Estrutura de pastas

```
ecociclo/
├── Auth/
│   ├── login.php              Tela de login e validação
│   └── logout.php             Encerra a sessão
├── Config/
│   ├── banco.sql              Criação e população do banco
│   ├── database.php           Conexão PDO
│   └── listas.php             Faixas de horário e status
├── CRUD_Agendamentos/
│   ├── criar.php              Solicitar coleta
│   ├── listar.php             Listagem e busca
│   └── editar.php             Editar, alterar status, cancelar
├── CRUD_Participantes/
│   ├── cadastro.php           Cadastrar participante
│   ├── listar.php             Listagem e busca
│   └── editar.php             Detalhes e edição
├── Logs/
│   ├── carteira.php           Saldo e extrato de EcoPontos
│   └── troca_cupom.php        Catálogo e troca de cupons
├── Models/
│   ├── Agendamento.php        Consultas de agendamentos: criar, listar (com busca e filtro por participante), buscar por ID e atualizar (só se estiver Solicitada ou Agendada)
│   ├── Carteira.php           Extrato de EcoPontos do participante (créditos e débitos)
│   ├── Coleta.php             Lista agendamentos pendentes e coletas recentes; confirmar() registra o peso, calcula os pontos, grava no extrato, atualiza o saldo e conclui o agendamento (em transação)
│   ├── Cupom.php              Catálogo de cupons válidos; trocar() faz a troca por EcoPontos (valida, debita, baixa estoque, registra a troca e o extrato, em transação)
│   ├── Material.php           Lista os materiais recicláveis cadastrados
│   ├── Participante.php       Cadastro, listagem com busca, busca por ID ou por usuário, e atualização de participantes
│   └── Usuario.php            Contas de acesso: buscar por e-mail ou ID e criar usuário (senha salva com hash)
├── coleta.php                 Confirmação da coleta e crédito de pontos
├── listarMateriais.php        Materiais recicláveis aceitos
└── index.php                  Painel principal

```

---

## Perfis e permissões

| Módulo | PARTICIPANTE | COLETOR / ADMIN |
|---|---|---|
| Agendamentos | Vê, cria e cancela apenas os próprios | Vê todos, cria para qualquer participante, agenda e cancela |
| Coletas (confirmação) | Sem acesso | Acesso |
| Participantes | Sem acesso | Cadastro, busca e edição |
| Materiais | Acesso | Acesso |
| Carteira | Saldo e extrato próprios | Aviso (não possuem carteira) |
| Cupons | Catálogo e troca | Apenas catálogo |

---

## Módulos

### Login e sessão
Autenticação por e-mail e senha. Em caso de falha, a mensagem é a mesma para e-mail inexistente e senha errada. As páginas restritas redirecionam para o login quando não há sessão, e o logout destrói a sessão e o cookie.

### Participantes
Cadastro (nome, e-mail, senha, telefone, endereço e bairro), listagem com busca por nome, e-mail ou bairro, e edição. O e-mail é único e não pode ser alterado. O cadastro cria o usuário e o participante dentro de uma transação.

### Agendamentos
Solicitação com material, endereço, bairro, data, faixa de horário e observações. O status segue este fluxo:

```
Solicitada ──> Agendada ──> Concluída (somente pela confirmação da coleta)
     │             │
     └─────────────┴──> Cancelada
```

Agendamentos concluídos ou cancelados ficam somente para leitura. Datas passadas não são aceitas em novas solicitações.

### Confirmação da coleta
Registra o peso (numérico e maior que zero), o responsável (usuário logado) e a data efetiva. Calcula os EcoPontos (`peso × 10`), grava o crédito no extrato, atualiza o saldo e conclui o agendamento, tudo em uma única transação.

### Carteira
Mostra o saldo atual e o histórico de créditos (coletas) e débitos (trocas de cupom).

### Cupons
Catálogo com comércio, descrição, custo em EcoPontos, validade e quantidade disponível. A troca exige participante autenticado, saldo suficiente e cupom válido e disponível. Gera um código de resgate (ex.: `ECO-3F9A1C2B`) que fica registrado no extrato.

---

## Regras de negócio atendidas

| Regra | Descrição | Onde está |
|---|---|---|
| RN01 | Banco com tabelas, chaves e 3+ registros por tabela | `Config/banco.sql` |
| RN02 | Login com mensagem de falha genérica | `Auth/login.php` |
| RN03 | Páginas restritas e opção de sair | Bloco de sessão no topo de cada página, `Auth/logout.php` |
| RN04 | Página principal com nome do usuário e módulos | `index.php` |
| RN05 | Cadastro e consulta de participantes, e-mail único | `CRUD_Participantes/`, `Models/Participante.php` |
| RN06 | Solicitação de coleta com status | `CRUD_Agendamentos/criar.php`, `Models/Agendamento.php` |
| RN07 | Validação de campos obrigatórios e data passada | `CRUD_Agendamentos/criar.php` e `editar.php` |
| RN08 | Coleta com data efetiva, responsável e peso > 0 | `coleta.php`, `Models/Coleta.php` |
| RN09 | 10 EcoPontos por kg, registrado no histórico | `Models/Coleta.php` |
| RN10 | Sem crédito duplicado | Veja "Proteções" abaixo |
| RN11 | Saldo e histórico | `Logs/carteira.php`, `Models/Carteira.php` |
| RN12 | Catálogo de cupons | `Logs/troca_cupom.php`, `Models/Cupom.php` |
| RN13 | Troca com saldo, validade e disponibilidade | `Models/Cupom.php` |
| RN14 | Troca e débito consistentes | Transação em `Models/Cupom.php` |
| RN15 | Busca por termo, detalhes e edição | `CRUD_Participantes/`, `CRUD_Agendamentos/` |
| RN16 | Prepared statements, hash de senha, dados mínimos | Todos os models, `password_hash`, sem CPF |
| RN17 | Relacionamentos entre agendamento, coleta, troca e movimentação | Chaves estrangeiras no banco |
| RN18 | Mensagens claras, sem erros técnicos | Blocos `try/catch` nas páginas e models |

---

## Banco de dados

Tabelas: `usuario`, `participante`, `material`, `agendamento`, `coleta`, `comercio`, `cupom`, `troca_cupom` e `movimentacao_ecopontos`.

```
usuario ──1:1── participante ──1:N── agendamento ──1:1── coleta
                     │                    │                 │
                     │                    └── N:1 material  └── N:1 usuario (responsável)
                     ├──1:N── troca_cupom ──N:1── cupom ──N:1── comercio
                     └──1:N── movimentacao_ecopontos ── (coleta_id ou troca_id)
```

O saldo do participante fica em `participante.saldo_ecopontos` e todo crédito ou débito é registrado em `movimentacao_ecopontos`.

---

## Segurança

- **Senhas** armazenadas com `password_hash` e conferidas com `password_verify`.
- **Prepared statements** (PDO) em todas as consultas.
- **Sessão regenerada** (`session_regenerate_id`) ao fazer login.
- **`htmlspecialchars`** em tudo que é exibido na tela, contra injeção de HTML/JavaScript.
- **Identificação pela sessão:** o participante e o responsável pela coleta vêm do usuário logado, nunca de campos do formulário ou da URL.
- **Controle por perfil:** páginas de gestão bloqueadas para participantes.
- **Mensagens de erro genéricas**, sem detalhes do banco.

### Proteções contra crédito duplicado (RN10)

1. O status do agendamento é conferido dentro da transação e a linha é travada com `SELECT ... FOR UPDATE`.
2. `coleta.agendamento_id` é `UNIQUE` (uma coleta por agendamento).
3. `movimentacao_ecopontos.coleta_id` é `UNIQUE` (um crédito por coleta).
4. A página redireciona após o sucesso, então recarregar com F5 não reenvia o formulário.

### Consistência da troca de cupom (RN14)

A troca debita o saldo (com a condição `saldo >= custo` no próprio `UPDATE`), baixa o estoque, registra a troca e grava o débito no extrato dentro de uma transação. Se qualquer etapa falhar, nada é salvo.

---

## Roteiro de teste

1. Entre como `admin@ecociclo.com`, cadastre um participante e busque por ele.
2. Crie um agendamento para esse participante (tente uma data passada: deve recusar).
3. Em **Coletas**, confirme o agendamento com 5,5 kg: devem ser creditados 55 pontos. Recarregue a página e confirme que nada se repete.
4. Saia e entre como o participante novo: o painel não mostra Coletas nem Participantes.
5. Em **Carteira**, confira o saldo e o crédito no extrato.
6. Em **Cupons**, troque um cupom de 50 pontos e depois tente um de custo maior que o saldo restante.
7. Clique em **Sair** e tente voltar com o botão do navegador: deve pedir login.

---

## Possíveis melhorias

- Cadastro do próprio participante pela tela de login.
- Troca e recuperação de senha.
- Cadastro e gestão de cupons e comércios pela interface.
- Proteção contra CSRF nos formulários.
- Paginação nas listagens.

---

## Projeto desenvolvido para fins de estudo.
