# Auth - Proyecto de Autenticacion (Laravel)

Proyecto de autenticacion construido con Laravel 13, ejecutado en contenedores Docker, con panel administrativo en Filament y autenticacion OAuth2 con Laravel Passport.

## Objetivo del proyecto

Este proyecto centraliza:

- Gestion de usuarios y autenticacion.
- Emision y validacion de tokens OAuth2 para APIs.
- Administracion interna mediante panel web.

## Stack tecnico

- PHP 8.3
- Laravel 13
- Filament 4
- Laravel Passport 13
- PostgreSQL 16
- Redis 7
- Nginx 1.27
- Docker + Docker Compose
- Vite 8 + Tailwind CSS 4

## Librerias clave y su finalidad

### Filament

Filament se usa para el panel de administracion.

- Panel configurado en `admin`.
- Login integrado para operadores/admins.
- Base para crear recursos CRUD de negocio.

En este proyecto, el panel vive en la ruta:

- `https://auth.test/admin`

### Laravel Passport

Passport se usa para implementar OAuth2 en la API.

- Genera y valida access tokens.
- Gestiona clients OAuth, refresh tokens y auth codes.
- Soporta flujos de autenticacion para integraciones externas.

En este proyecto ya existen migraciones OAuth de Passport en `src/database/migrations`.

## Arquitectura de servicios (Docker)

Servicios definidos en `docker-compose.yml`:

- `app`: contenedor PHP-FPM con el codigo Laravel (`./src`).
- `nginx`: proxy web con TLS para `auth.test`.
- `postgres`: base de datos principal.
- `redis`: cache/sesiones/colas (segun configuracion de Laravel).

## Prerrequisitos

Instalar en la maquina host:

- Docker
- Docker Compose (plugin `docker compose`)
- Make
- mkcert (para certificado local TLS)

## Configuracion local (primera vez)

### 1) Configurar dominio local

Agregar en `/etc/hosts`:

```txt
127.0.0.1 auth.test
```

### 2) Generar certificado local

Desde la raiz del proyecto:

```bash
make mkcert-auth
```

Esto crea:

- `docker/nginx/certs/auth.test.pem`
- `docker/nginx/certs/auth.test-key.pem`

### 3) Levantar contenedores

```bash
make up
```

Opcional, para reconstruir imagenes:

```bash
make build
```

### 4) Configurar entorno Laravel

Si no existe, crear archivo de entorno:

```bash
cp src/.env.example src/.env
```

Ajustar como minimo en `src/.env`:

```env
APP_URL=https://auth.test

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=auth
DB_USERNAME=auth
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
```

### 5) Instalar dependencias

Composer (dentro del contenedor app):

```bash
make composer CMD="install"
```

Dependencias frontend (Node/Vite):

```bash
docker compose exec app npm install
```

### 6) Clave de aplicacion, migraciones y Passport

```bash
make artisan CMD="key:generate"
make migrate
make passport-install
```

### 7) Instalar/configurar Filament y crear usuario admin

```bash
make filament-install
make filament-user
```

Credenciales por defecto del comando `filament-user`:

- Email: `admin@auth.test`
- Password: `Admin123!`

## Uso diario

Levantar y apagar:

```bash
make up
make down
```

Logs y estado:

```bash
make logs
make ps
```

Shell en el contenedor app:

```bash
make shell
```

Comandos Artisan:

```bash
make artisan CMD="route:list"
```

## URLs importantes

- App: `https://auth.test`
- Panel admin (Filament): `https://auth.test/admin`
- API protegida de ejemplo: `GET /api/user` (requiere `auth:api`)

## Comandos Make disponibles

- `make build`
- `make up`
- `make down`
- `make logs`
- `make ps`
- `make shell`
- `make composer CMD="..."`
- `make artisan CMD="..."`
- `make migrate`
- `make passport-install`
- `make filament-install`
- `make filament-user`
- `make mkcert-auth`

## Troubleshooting rapido

- Error TLS/certificado en navegador:
  - Reejecutar `make mkcert-auth`.
  - Verificar que `auth.test` apunte a `127.0.0.1` en `/etc/hosts`.

- No conecta a base de datos:
  - Revisar variables `DB_*` en `src/.env`.
  - Verificar estado de contenedores con `make ps`.

- Error de Passport al emitir tokens:
  - Ejecutar de nuevo `make passport-install`.
  - Confirmar que las tablas OAuth esten migradas.
