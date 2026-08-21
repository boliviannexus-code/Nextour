# Despliegue de Nextour con Docker en AWS

Esta configuracion esta pensada para una instancia EC2 con Docker Compose. Ejecuta cuatro procesos persistentes: Apache/PHP, worker de colas, scheduler de Laravel y Redis. PostgreSQL queda en un volumen Docker persistente, siguiendo la instalacion de Nido.

## 1. Preparar AWS

- Crear una instancia EC2 Linux con almacenamiento EBS suficiente.
- Permitir SSH solamente desde una IP administrativa.
- Publicar HTTP/HTTPS mediante un Application Load Balancer o un proxy TLS y dirigirlo al puerto configurado en `APP_PORT` (8080 por defecto).
- No publicar los puertos de PostgreSQL ni Redis; ambos servicios solo existen en la red privada de Compose.
- Asociar una IP estable o un dominio al balanceador/proxy.

Los datos persistentes viven en los volumenes `nextour-pgsql`, `nextour-redis` y `nextour-storage`. Un reemplazo de instancia requiere respaldar y trasladar esos volumenes o restaurar la base desde el modulo de respaldos de Nextour.

## 2. Configurar el servidor

Instalar Docker Engine con el complemento Compose y clonar el repositorio. Después:

```bash
cp .env.docker.example .env.docker
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Copiar el valor generado a `APP_KEY` y configurar como minimo:

- `APP_URL=https://tu-dominio`
- `DB_PASSWORD` con una clave larga y unica
- `MAIL_*` para el proveedor de correo real
- `AWS_*` si se usara S3

Nunca subir `.env.docker` al repositorio. El archivo está ignorado por Git y por el contexto Docker.

## 3. Primer despliegue

```bash
chmod +x docker/deploy.sh
./docker/deploy.sh
docker compose --env-file .env.docker -f compose.prod.yaml exec app \
  php artisan db:seed --class=RolePermissionSeeder --force
```

El script construye la imagen, levanta PostgreSQL y Redis, ejecuta migraciones en un contenedor temporal y después inicia web, cola y scheduler.

No ejecutar `DatabaseSeeder` en produccion: actualmente contiene credenciales de desarrollo. Crear el primer usuario administrador mediante un procedimiento seguro de la aplicacion o una orden controlada de Artisan.

## 4. Despliegues posteriores

Desde el directorio del proyecto:

```bash
git pull --ff-only
./docker/deploy.sh
```

El worker usa `--max-time=3600`, por lo que se recicla periodicamente y toma el codigo de la imagen nueva. El healthcheck consulta `/up`; web, cola y scheduler no se consideran listos hasta que PostgreSQL y Redis respondan.

## 5. Operacion

```bash
# Estado
docker compose --env-file .env.docker -f compose.prod.yaml ps

# Logs
docker compose --env-file .env.docker -f compose.prod.yaml logs -f app queue scheduler

# Reiniciar workers
docker compose --env-file .env.docker -f compose.prod.yaml restart queue

# Detener sin borrar datos
docker compose --env-file .env.docker -f compose.prod.yaml down
```

No usar `down -v` en produccion: elimina los volumenes que contienen la base de datos, Redis y los archivos subidos.

## 6. Respaldos

La imagen incluye `pg_dump` y `psql`, requeridos por **Administracion > Respaldos**. Los SQL se guardan dentro del volumen `nextour-storage` y estan firmados con `APP_KEY`; conservar la misma clave es obligatorio para poder restaurarlos.

Para resiliencia real ante perdida de la instancia, descargar los respaldos fuera del servidor o sincronizarlos periodicamente con S3. Un respaldo que solo permanece en el mismo volumen no protege frente a la perdida completa de EC2/EBS.
