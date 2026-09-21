# Hospital Beds API

API REST para gerenciamento da ocupação de leitos de um hospital, em **PHP 8.4 + Laravel 12 + PostgreSQL 16**, rodando inteiramente em Docker.

## O que a API faz

Gerencia quem está em qual leito. Seis operações:

- **incluir** um paciente em um leito;
- **desocupar** um leito, dando alta ao paciente;
- **transferir** um paciente para outro leito;
- **descobrir o leito** de um paciente a partir do CPF;
- **descobrir o status** de ocupação de um leito;
- **listar** os leitos com seu respectivo status.

Duas regras valem sempre, e não podem ser violadas nem sob requisições simultâneas:

> Um mesmo paciente não pode estar em mais de um leito ao mesmo tempo.
> Cada leito só pode ter um paciente por vez.

Neste projeto essas regras são **invariantes do banco de dados**, garantidas por índices únicos parciais — não checagens da aplicação. A seção [Modelagem](#modelagem) explica por que isso importa.

---

## Sumário

- [Pré-requisitos](#pré-requisitos)
- [Como executar](#como-executar)
- [Documentação da API](#documentação-da-api)
- [Endpoints](#endpoints)
- [Roteiro de verificação](#roteiro-de-verificação)
- [Contrato de erro](#contrato-de-erro)
- [Como executar os testes](#como-executar-os-testes)
- [Modelagem](#modelagem)

---

## Pré-requisitos

**Apenas Docker.** PHP, Composer, PostgreSQL e Nginx rodam dentro dos containers — nada precisa estar instalado na máquina.

- Docker 20.10 ou superior
- Docker Compose v2 (`docker compose`, sem hífen)

Portas usadas: `8080` (API), `8081` (Swagger UI) e `5432` (Postgres). Se alguma estiver ocupada, veja [Configuração](#configuração).

---

## Como executar

### 1. Clone o repositório

```bash
git clone https://github.com/viniciusdevlacerda/hospital-beds-api.git
cd hospital-beds-api
```

### 2. Execute o script de setup

```bash
./setup.sh
```

**É só isso.** O script faz tudo e reporta cada passo:

| | Passo |
| --- | --- |
| 1 | Confere se o Docker está instalado e rodando |
| 2 | Cria o `.env` a partir do `.env.example` |
| 3 | Constrói as imagens e sobe os containers |
| 4 | Instala as dependências do Composer |
| 5 | Aguarda o PHP-FPM responder ao healthcheck |
| 6 | Executa as **migrations** |
| 7 | Executa os **seeders** (22 leitos em 3 setores, 7 já ocupados) |
| 8 | Faz uma requisição real na API para confirmar que está no ar |

A primeira execução leva alguns minutos, quase todos no `composer install`. As seguintes são rápidas.

O script pode ser executado quantas vezes quiser: migrations e seeders são idempotentes.

### 3. Confirme

Ao terminar, o script imprime as URLs e **sugere um código de leito livre** para você começar a testar:

```
Pronto.

    API            http://localhost:8080/api
    Swagger UI     http://localhost:8081
    Healthcheck    http://localhost:8080/up

    Um leito livre para começar: UTI-03
```

Verificação manual, se preferir:

```bash
curl http://localhost:8080/api/beds -H 'Accept: application/json'
```

### Alternativa sem o script

O `docker compose` sozinho também funciona — o entrypoint do container roda migrations e seeders no boot:

```bash
cp .env.example .env
docker compose up -d --build
```

A diferença é que o `setup.sh` valida os pré-requisitos, espera o healthcheck e confirma que a API respondeu, em vez de deixar isso por conta de quem executou.

### Configuração

As portas e as credenciais ficam no `.env` (criado a partir do `.env.example`). Para mudar a porta da API, por exemplo:

```env
APP_PORT=9000
```

E suba de novo com `./setup.sh`.

### Demais comandos

```bash
./setup.sh                                                            # sobe tudo do zero, de novo
docker compose logs -f app                                            # logs da aplicação
docker compose exec app php artisan migrate --force                   # só as migrations
docker compose exec app php artisan db:seed --force                   # só os seeders
docker compose exec app php artisan migrate:fresh --seed              # recria o banco
docker compose exec app php artisan route:list                        # confere as rotas
docker compose exec app sh                                            # shell no container
docker compose down                                                   # derruba a stack
docker compose down -v                                                # derruba e apaga o banco
```

---

## Documentação da API

### Swagger UI (interativo)

Com a stack no ar, abra:

**http://localhost:8081**

O Swagger UI sobe como um container junto com a aplicação, lendo o [`openapi.yaml`](openapi.yaml) do repositório. Dá para navegar por todos os endpoints, ver os schemas de requisição e resposta e disparar chamadas direto do navegador pelo botão **Try it out**.

### Especificação OpenAPI 3

O arquivo [`openapi.yaml`](openapi.yaml) está na raiz do projeto e pode ser aberto em qualquer ferramenta compatível — Swagger Editor, Postman, Insomnia, Redoc, Stoplight.

### Coleção do Insomnia

Há uma coleção pronta em [`insomnia-collection.json`](insomnia-collection.json). No Insomnia: **Import → From File**.

São sete requisições, uma por rota, na ordem em que o desafio lista os casos de uso, com os códigos de erro documentados na descrição de cada uma. Os dados variáveis (`base_url`, `bed_code`, `cpf`) ficam no *Base Environment*, e os filtros da listagem vêm como query parameters desabilitados, prontos para ligar.

---

## Endpoints

Base: `http://localhost:8080/api`

| Ação do desafio | Método | Rota | Sucesso |
| --- | --- | --- | --- |
| Incluir um paciente em um leito | `POST` | `/beds/{code}/occupation` | `201` |
| Desocupar um leito | `DELETE` | `/beds/{code}/occupation` | `200` |
| Transferir um paciente para outro leito | `POST` | `/transfers` | `201` |
| Descobrir o leito de um paciente pelo CPF | `GET` | `/patients/{cpf}/bed` | `200` |
| Descobrir o status de ocupação de um leito | `GET` | `/beds/{code}` | `200` |
| Listagem de leitos com status | `GET` | `/beds` | `200` |
| *(extra)* Histórico de ocupações de um leito | `GET` | `/beds/{code}/occupations` | `200` |

**Filtros da listagem:** `?status=free|occupied`, `?sector=UTI|Enfermaria|Apartamento`, `?per_page=1..100`. São cumulativos.

**Identificação do leito:** pelo **código** (`UTI-01`, `ENF-07`), não pelo id numérico — a URL fica legível e o identificador é o mesmo que a equipe do hospital usa.

**CPF:** aceito com ou sem formatação, tanto no corpo quanto na URL. O dígito verificador é validado.

**Cadastro de pacientes:** o paciente é criado na primeira internação e reaproveitado pelo CPF nas seguintes — o enunciado dispensa um CRUD de pacientes.

---

## Roteiro de verificação

Sequência que exercita as seis operações e as duas regras de negócio. Copie e cole bloco a bloco.

**Preparação** — define as variáveis e escolhe dois leitos livres automaticamente:

```bash
export BASE=http://localhost:8080/api
export CPF='529.982.247-25'

read -r BED TARGET <<< "$(curl -s "$BASE/beds?status=free&per_page=100" -H 'Accept: application/json' \
  | grep -o '"code":"[^"]*"' | cut -d'"' -f4 | head -2 | tr '\n' ' ')"

echo "leito: $BED    destino: $TARGET"
```

**1. Incluir um paciente em um leito** → espera `201`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X POST "$BASE/beds/$BED/occupation" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"cpf\": \"$CPF\", \"name\": \"Maria Andrade\"}"
```

```json
{
  "data": {
    "id": 8,
    "active": true,
    "occupied_at": "2026-09-21T14:02:11-03:00",
    "released_at": null,
    "release_reason": null,
    "bed": { "code": "UTI-03", "sector": "UTI" },
    "patient": { "name": "Maria Andrade", "cpf": "529.982.247-25" }
  }
}
```

**2. Descobrir o leito do paciente pelo CPF** → espera `200`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' "$BASE/patients/$CPF/bed" -H 'Accept: application/json'
```

**3. Ver o status do leito** → espera `200` com `"status": "occupied"`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' "$BASE/beds/$BED" -H 'Accept: application/json'
```

**4. Regra: o leito só aceita um paciente** → espera `409 BED_ALREADY_OCCUPIED`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X POST "$BASE/beds/$BED/occupation" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"cpf": "123.456.789-09", "name": "Outro Paciente"}'
```

**5. Regra: o paciente só ocupa um leito** → espera `409 PATIENT_ALREADY_ADMITTED`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X POST "$BASE/beds/$TARGET/occupation" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"cpf\": \"$CPF\", \"name\": \"Maria Andrade\"}"
```

**6. Transferir para outro leito** → espera `201`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X POST "$BASE/transfers" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"cpf\": \"$CPF\", \"to_bed\": \"$TARGET\"}"
```

O leito de origem volta a ficar livre e a ocupação anterior é encerrada com motivo `transfer`.

**7. Histórico do leito de origem** → mostra a ocupação encerrada

```bash
curl -s -w '\n→ HTTP %{http_code}\n' "$BASE/beds/$BED/occupations" -H 'Accept: application/json'
```

**8. Desocupar (alta)** → espera `200` com `release_reason: "discharge"`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X DELETE "$BASE/beds/$TARGET/occupation" -H 'Accept: application/json'
```

**9. Desocupar de novo** → espera `409 BED_NOT_OCCUPIED`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' -X DELETE "$BASE/beds/$TARGET/occupation" -H 'Accept: application/json'
```

**10. Listar apenas os livres da enfermaria** → espera `200`

```bash
curl -s -w '\n→ HTTP %{http_code}\n' "$BASE/beds?status=free&sector=Enfermaria" -H 'Accept: application/json'
```

---

## Contrato de erro

Toda falha — de domínio, de validação ou de rota — responde no mesmo formato:

```json
{
  "error": {
    "code": "BED_ALREADY_OCCUPIED",
    "message": "O leito UTI-01 já está ocupado.",
    "details": { "bed": "UTI-01" }
  }
}
```

| `code` | HTTP | Quando acontece |
| --- | --- | --- |
| `BED_ALREADY_OCCUPIED` | 409 | Internar ou transferir para um leito já ocupado |
| `PATIENT_ALREADY_ADMITTED` | 409 | O paciente já ocupa outro leito |
| `BED_NOT_OCCUPIED` | 409 | Desocupar um leito que já está livre |
| `PATIENT_NOT_ADMITTED` | 404 | O paciente existe, mas não está internado |
| `PATIENT_NOT_FOUND` | 404 | Nenhum paciente cadastrado com aquele CPF |
| `TRANSFER_TO_SAME_BED` | 422 | Transferência para o leito que o paciente já ocupa |
| `INVALID_CPF` | 422 | CPF com dígito verificador inválido |
| `VALIDATION_FAILED` | 422 | Corpo ou query string inválidos (`details` traz os campos) |
| `RESOURCE_NOT_FOUND` | 404 | Leito ou rota inexistente |

O `code` é estável e serve para o cliente tratar o erro programaticamente; a `message` é legível por humanos e o `details` traz o contexto.

---

## Como executar os testes

Não precisa da stack no ar — a suíte roda em SQLite em memória:

```bash
docker compose run --rm --no-deps app php vendor/bin/phpunit
```

Saída esperada: **46 testes, todos verdes, em menos de um segundo.**

Com a stack já rodando, `docker compose exec app php vendor/bin/phpunit` também funciona.

### O que a suíte cobre

- **Feature** — um arquivo por caso de uso, com o caminho feliz e cada erro: leito ocupado, paciente já internado, leito já livre, transferência para leito ocupado, transferência para o mesmo leito, CPF inválido no corpo e na URL, paciente não internado, recursos inexistentes, filtros da listagem e histórico.
- **Unit** — o value object `Cpf`: dígito verificador, dígitos repetidos, tamanho, formatação, máscara.
- **Invariantes** — `tests/Feature/DatabaseInvariantsTest.php` ataca o banco **direto, sem passar pelo service**, e espera `QueryException`. É o teste que prova que a regra vive no schema, e não apenas no código da aplicação.
- **Apoio por composição** — os utilitários de cenário (criar leito, internar, contar ocupações ativas) ficam na trait `Tests\Concerns\InteractsWithOccupations`, não numa classe base: cada caso estende apenas a `TestCase` do framework.

### Formatação e análise estática

```bash
docker compose run --rm --no-deps app php vendor/bin/pint --test
docker compose run --rm --no-deps app php vendor/bin/phpstan analyse
```


---

## Modelagem

```
patients                beds                    bed_occupations
--------                ----                    ---------------
id                      id                      id
name                    code       (unique)     bed_id          FK -> beds
cpf      (unique)       sector                  patient_id      FK -> patients
timestamps              description             occupied_at
                        timestamps              released_at     NULL = ocupação ativa
                                                release_reason  discharge | transfer
                                                timestamps
```

A ocupação é uma **entidade própria com histórico**, não uma coluna `patient_id` em `beds`. Todo o estado do hospital é derivado de `bed_occupations`, e cada internação, alta e transferência fica registrada — auditoria de graça, sem uma linha de código a mais.

### As regras de negócio são invariantes do banco

As duas regras do desafio não dependem de um `if` na aplicação:

```sql
CREATE UNIQUE INDEX bed_occupations_active_bed_unique
  ON bed_occupations (bed_id)     WHERE released_at IS NULL;

CREATE UNIQUE INDEX bed_occupations_active_patient_unique
  ON bed_occupations (patient_id) WHERE released_at IS NULL;
```

Esse é o ponto central do projeto. Uma checagem em PHP entre o `SELECT` e o `INSERT` perde a corrida sob concorrência; o índice único parcial torna a violação **impossível**, mesmo que a aplicação falhe.

Em cima disso, cada comando roda numa transação com `SELECT ... FOR UPDATE` no leito e no paciente, para que requisições concorrentes sejam serializadas e o cliente receba um `409` legível em vez de um erro de constraint.

Índice único parcial existe em PostgreSQL e em SQLite, então a suíte de testes — que roda em SQLite em memória — valida exatamente as mesmas invariantes que rodam em produção.
