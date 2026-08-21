#!/usr/bin/env sh
set -eu

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$project_dir"

env_file="${ENV_FILE:-.env.docker}"
compose_file="${COMPOSE_FILE:-compose.prod.yaml}"

if [ ! -f "$env_file" ]; then
    echo "Missing $env_file. Copy .env.docker.example and configure it first." >&2
    exit 1
fi

required_value() {
    key="$1"
    value="$(sed -n "s/^${key}=//p" "$env_file" | tail -n 1)"

    if [ -z "$value" ]; then
        echo "$key must be configured in $env_file." >&2
        exit 1
    fi
}

required_value APP_KEY
required_value APP_URL
required_value DB_PASSWORD

compose() {
    APP_ENV_FILE="$env_file" docker compose --env-file "$env_file" -f "$compose_file" "$@"
}

compose config >/dev/null
compose build --pull app
compose up -d pgsql redis
compose run --rm -e RUN_MIGRATIONS=true app true
compose up -d --remove-orphans app queue scheduler
compose exec -T app php artisan about --only=environment

echo "Nextour deployment completed."
