# Zuora Workflow Manager

Laravel admin panel for managing Zuora workflows with role-based access control.

## Stack

- Laravel 12, Filament 4, Spatie Permission, Google OAuth, MariaDB

## Setup

```bash
# Clone repository
git clone https://github.com/FrancoStino/zuora-laravel.git
cd "Zuora Workflow"

# Copy env
cp .env.example .env

# Start Lando (automatically runs composer install, yarn install, and yarn build)
lando start

# Generate application key
lando artisan key:generate

# Run migrations
lando artisan migrate

# Generate permissions
lando artisan shield:generate --all --panel=admin --force

# Make first user super admin
lando artisan shield:super-admin --user=1 --panel=admin

# Build assets
lando yarn build

# Clear cache
lando artisan optimize:clear
```

## Environment

Add to `.env`:

```
APP_URL=https://your-domain.lndo.site
SESSION_DRIVER=file

GOOGLE_CLIENT_ID=your-id
GOOGLE_CLIENT_SECRET=your-secret
```

Google redirect URI auto-generates from `APP_URL`.

## Permissions

Grant permissions to users via Tinker:

```bash
lando artisan tinker
```

```php
use App\Models\User;

$user = User::find(2);
$user->givePermissionTo([
    'Create:Customer',
    'Update:Customer',
    'ViewAny:Customer',
    'View:Customer',
    'Delete:Customer'
]);

exit
```

## Features

- Zuora API integration with token caching
- Role-based access control (Spatie Permission)
- Google OAuth login
- Customer & Workflow management
- Public workflow dashboard
- Filament admin panel

## Development Commands

```bash
# Install dependencies
lando composer install
lando yarn install

# Build assets for production
lando yarn build

# Watch assets for development
lando yarn dev

# Clear all caches
lando artisan optimize:clear

# Clear specific caches
lando artisan cache:clear
lando artisan config:clear
lando artisan view:clear
lando artisan route:clear

# Run migrations
lando artisan migrate
lando artisan migrate:fresh --seed

# Generate permissions
lando artisan shield:generate --all --panel=admin

# Access database
lando mariadb

# View logs
lando artisan pail
lando logs
```

## Docs

- [Filament](https://filamentphp.com/docs)
- [Filament Awin Theme](https://github.com/resmatech/filament-awin-theme)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Zuora API](https://knowledgecenter.zuora.com/Zuora_API)
