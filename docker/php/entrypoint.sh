#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "==> Criando .env a partir de .env.example"
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    echo "==> Instalando dependências (primeira execução, pode levar alguns minutos)"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "==> Gerando APP_KEY"
    php artisan key:generate --force
fi

# Migrations e seeders só fazem sentido ao subir o servidor. Comandos pontuais
# (`docker compose run --rm app php vendor/bin/pest`) pulam esta etapa e não
# precisam sequer do Postgres no ar.
if [ "$1" = "php-fpm" ]; then
    DB_HOST="${DB_HOST:-database}"
    DB_PORT="${DB_PORT:-5432}"

    echo "==> Aguardando o banco de dados em ${DB_HOST}:${DB_PORT}"
    php -r '
    $host = getenv("DB_HOST") ?: "database";
    $port = (int) (getenv("DB_PORT") ?: 5432);

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $socket = @fsockopen($host, $port, $errno, $errstr, 1);

        if ($socket !== false) {
            fclose($socket);
            exit(0);
        }

        sleep(1);
    }

    fwrite(STDERR, "Banco de dados indisponível após 60 tentativas.\n");
    exit(1);
    '

    echo "==> Executando migrations e seeders"
    php artisan migrate --force --seed

    chown -R www-data:www-data storage bootstrap/cache

    echo "==> API pronta em http://localhost:8080/api/v1"
fi

exec "$@"
