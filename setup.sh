#!/usr/bin/env bash
#
# Sobe o projeto inteiro do zero: containers, dependências, migrations e seeders.
# Pode ser executado quantas vezes quiser — migrations e seeders são idempotentes.
#
# Uso:  ./setup.sh

set -euo pipefail

cd "$(dirname "$0")"

BOLD=$'\033[1m'; BLUE=$'\033[1;34m'; GREEN=$'\033[1;32m'; RED=$'\033[1;31m'; OFF=$'\033[0m'

step()  { printf '\n%s==>%s %s%s%s\n' "$BLUE" "$OFF" "$BOLD" "$1" "$OFF"; }
info()  { printf '    %s\n' "$1"; }
fail()  { printf '\n%sErro:%s %s\n' "$RED" "$OFF" "$1" >&2; exit 1; }

APP_CONTAINER=hospital-app
APP_PORT=${APP_PORT:-8080}
SWAGGER_PORT=${SWAGGER_PORT:-8081}
HEALTH_TIMEOUT=300

# ---------------------------------------------------------------- pré-requisitos
step "Verificando pré-requisitos"
command -v docker >/dev/null 2>&1 || fail "Docker não encontrado. Instale o Docker Desktop e tente de novo."
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 não encontrado."
docker info >/dev/null 2>&1 || fail "O Docker não está rodando. Abra o Docker Desktop e tente de novo."
info "Docker $(docker version --format '{{.Server.Version}}') e Compose disponíveis"

# ------------------------------------------------------------------------- .env
step "Preparando o .env"
if [ -f .env ]; then
    info ".env já existe, mantido como está"
else
    cp .env.example .env
    info ".env criado a partir de .env.example"
fi

# ------------------------------------------------------------------- containers
step "Construindo e subindo os containers"
docker compose up -d --build

# ---------------------------------------------------------------------- healthcheck
step "Aguardando a aplicação responder (até ${HEALTH_TIMEOUT}s)"
info "primeira execução instala as dependências do Composer, pode demorar"

elapsed=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' "$APP_CONTAINER" 2>/dev/null || echo missing)" = "healthy" ]; do
    if [ "$(docker inspect -f '{{.State.Running}}' "$APP_CONTAINER" 2>/dev/null || echo false)" != "true" ]; then
        docker compose logs --tail=40 app
        fail "O container da aplicação parou. Os últimos logs estão acima."
    fi

    if [ "$elapsed" -ge "$HEALTH_TIMEOUT" ]; then
        docker compose logs --tail=40 app
        fail "A aplicação não ficou saudável em ${HEALTH_TIMEOUT}s. Os últimos logs estão acima."
    fi

    sleep 3
    elapsed=$((elapsed + 3))
    printf '.'
done
printf '\n'
info "aplicação saudável após ${elapsed}s"

# -------------------------------------------------------- migrations e seeders
step "Executando as migrations"
docker compose exec -T app php artisan migrate --force

step "Executando os seeders"
docker compose exec -T app php artisan db:seed --force

step "Estado do banco"
docker compose exec -T app php artisan migrate:status

# ------------------------------------------------------------------ verificação
step "Verificando a API"
beds=$(curl -fsS "http://localhost:${APP_PORT}/api/beds?per_page=100" -H 'Accept: application/json' \
    | grep -o '"code"' | wc -l | tr -d ' ') || fail "A API não respondeu em http://localhost:${APP_PORT}"
info "GET /api/beds respondeu com ${beds} leitos"

free_bed=$(curl -fsS "http://localhost:${APP_PORT}/api/beds?status=free" -H 'Accept: application/json' \
    | sed -n 's/.*"code":"\([^"]*\)".*/\1/p' | head -1)

# --------------------------------------------------------------------- resumo
printf '\n%sPronto.%s\n\n' "$GREEN" "$OFF"
printf '    API            http://localhost:%s/api\n' "$APP_PORT"
printf '    Swagger UI     http://localhost:%s\n' "$SWAGGER_PORT"
printf '    Healthcheck    http://localhost:%s/up\n\n' "$APP_PORT"
[ -n "$free_bed" ] && printf '    Um leito livre para começar: %s%s%s\n\n' "$BOLD" "$free_bed" "$OFF"
printf '    Testes         docker compose run --rm --no-deps app php vendor/bin/phpunit\n'
printf '    Recriar banco  docker compose exec app php artisan migrate:fresh --seed\n'
printf '    Derrubar       docker compose down\n\n'
