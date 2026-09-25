<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/laravel-logo.svg" width="400" alt="Laravel Logo"></a></p>

# Auth Server - Granular OAuth Access Control

This is a Laravel 13 OAuth2 authentication server with granular access control capabilities for OAuth clients. It uses Passport for OAuth2 implementation and spatie/laravel-permission for role-based access management.

## Features

### Granular Access Control

The application implements a comprehensive access control system that allows administrators to restrict which users and roles can access specific OAuth client applications.

**Access Control Modes:**

1. **`all`** (default) - All authenticated users can access the client
2. **`roles`** - Only users with specific roles can access the client
3. **`users`** - Only specific users can access the client
4. **`roles_or_users`** - Users with specific roles OR specific users can access

### Implementation Architecture

- **Model**: `App\Models\AppClient` - Extended Passport Client with role/user relationships
- **Service**: `App\Services\ClientAccessChecker` - Validates user access against client rules
- **Middleware**: `App\Http\Middleware\EnsureUserCanAccessClient` - Applied to all auth:api routes
- **Admin UI**: Filament-based control panel for managing access rules

### Database Schema

#### Key Tables

- `oauth_clients` - OAuth client applications with `access_mode` field
- `oauth_client_roles` - Many-to-many: clients ↔ roles
- `oauth_client_users` - Many-to-many: clients ↔ users
- `roles` - Permission roles (uses `role_id` as primary key)
- `permissions` - Permission definitions (uses `permission_id` as primary key)
- `model_has_roles` - User ↔ role assignments
- `role_has_permissions` - Role ↔ permission assignments

#### Custom Primary Keys

The permission tables use custom primary keys from the university database:

- `roles.role_id` instead of `id`
- `permissions.permission_id` instead of `id`

Custom models handle the mapping:

- `App\Models\Role` - Extends spatie Role with `role_id` primary key
- `App\Models\Permission` - Extends spatie Permission with `permission_id` primary key

## Usage

### API Access

Clients must authenticate using OAuth2 Password Grant flow with a bearer token:

```bash
curl -H "Authorization: Bearer {token}" https://your-server/api/user
```

### Admin Panel (Filament)

Navigate to `/admin/oauth-clients/{id}` to manage access rules for each client:

1. Select the desired **access_mode**
2. For `roles` or `roles_or_users` mode: Select roles from dropdown
3. For `users` or `roles_or_users` mode: Select specific users
4. Click "Guardar cambios" to persist

### Programmatic Access

To check if a user can access a specific client:

```php
use App\Services\ClientAccessChecker;
use App\Models\AppClient;

$checker = app(ClientAccessChecker::class);
$client = AppClient::find($clientId);

if ($checker->userCanAccess($user, $client)) {
    // User can access
}
```

## Testing

All access control logic is tested with 12 comprehensive tests:

**Unit Tests** (8 tests in `ClientAccessCheckerTest`):

- Access mode: all, roles, users, roles_or_users
- Role-based and user-based access validation
- Edge cases and negative scenarios

**Feature Tests** (3 tests in `ClientAccessTest`):

- API authentication requirements
- Middleware application verification

**Run Tests:**

```bash
make artisan CMD="test tests/ --env=testing"
```

## Installation & Setup

1. Install dependencies:

    ```bash
    composer install
    npm install
    ```

2. Run migrations:

    ```bash
    php artisan migrate
    ```

3. Create Passport clients:

    ```bash
    php artisan passport:install
    ```

4. Access the admin panel at `http://your-server/admin`

## Development

The application runs in Docker with:

- PHP 8.3 with Laravel 13
- PostgreSQL 16
- Redis 7
- Nginx with TLS

Use the `Makefile` for common commands:

```bash
make up          # Start containers
make down        # Stop containers
make migrate     # Run migrations
make artisan CMD="..."  # Run artisan commands
make test        # Run tests
```

## License

This project is built on Laravel and respects the same licensing terms.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
