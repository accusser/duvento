# Duvento

[English](README.md) · [Українська](README.uk.md) · [Русский](README.ru.md)

**Calm deadline tracking for web agencies.** Keep domains, SSL certificates, hosting, plugin licenses, and every other renewal in one place.

Duvento is a free self-hosted application distributed under AGPL-3.0-or-later. Docker is optional.

## Features

- Color-coded Critical / Urgent / Soon / OK renewal status
- Automatic SSL expiry checks without a paid third-party API
- Email reminders at configurable intervals
- Upcoming payment totals for 7, 30, and 90 days
- Clients, custom asset types, activity history, and CSV import/export
- Read-only public client status links
- Team invitations, built-in support tickets, search, and notifications
- English, Ukrainian, Russian, German, Spanish, and Polish interface

## Requirements

- PHP 8.3+ with `pdo_mysql` or `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, and `intl`
- MySQL/MariaDB for production; SQLite is suitable for evaluation
- A web server whose document root can point to `public/`
- One cron entry for scheduled SSL checks, reminders, and queued email

Composer 2 and Node.js 20+ are only required when installing from source. They are already compiled into the release archive.

## Recommended: release archive and web installer

1. Download **[duvento.zip](https://github.com/accusser/duvento/releases/latest/download/duvento.zip)** from the [latest release](https://github.com/accusser/duvento/releases/latest).
2. Extract it into your website directory.
3. Point the nginx or Apache document root to the extracted `public/` directory.
4. Give the PHP user write access to the project root (to create `.env`), `storage/`, and `bootstrap/cache/`.
5. Open your website. Duvento redirects you to `/install`.
6. Follow the wizard: language → server check → database → migrations → administrator.
7. Save the generated administration URL and add the cron entry shown on the final screen.

The release archive contains PHP dependencies and compiled frontend assets, so Composer, Node.js, and Artisan are not needed on the server.

The installer creates a clean production instance without demo records. After installation, `/install` returns 404 and cannot be used to reset an existing instance.

## Install from source

```bash
git clone https://github.com/accusser/duvento.git
cd duvento
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Configure `DB_*` and `APP_URL` in `.env`; for SQLite, create `database/database.sqlite`. Then run:

```bash
php artisan migrate --force
php artisan duvento:install
```

The command securely asks for a password, creates the first workspace owner and administrator, and generates a private `ADMIN_PATH`. Do not run database seeders in production.

For local development, use `composer run setup`.

## Production setup

Add the scheduler to the web server user's crontab:

```cron
* * * * * cd /var/www/duvento && php artisan schedule:run >> /dev/null 2>&1
```

Configure SMTP in `.env` with `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS`. Email is required for reminders and password resets. Use `MAIL_MAILER=log` only for testing.

The default database queue is processed by the scheduler; a permanent queue worker is not required.

Example nginx configuration:

```nginx
server {
    listen 80;
    server_name duvento.example;
    root /var/www/duvento/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

Use HTTPS in production, keep `.env` outside the public document root, and back up both the database and `storage/app/`.

## Updating

Back up the database, `.env`, and `storage/app/` before updating. Replace the application files while preserving those paths, then run:

```bash
php artisan migrate --force
php artisan optimize:clear
```

## Docker (optional)

```bash
cp .env.example .env
# Set DB_CONNECTION=mysql and DB_HOST=db in .env
docker compose up -d --build
```

The application is available at `http://127.0.0.1:8090`; the `scheduler` service runs scheduled tasks.

## Self-host and Cloud

This repository contains the free self-host edition. Managed Duvento Cloud uses the same core but also includes hosted billing, white-label reports, public API/webhooks, and priority support. Closed cloud modules are not part of this AGPL repository.

## License

[AGPL-3.0-or-later](LICENSE)
