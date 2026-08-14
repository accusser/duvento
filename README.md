# Duvento

Трекер дедлайнов для веб-агентств: домены, SSL, хостинг, лицензии плагинов.

Self-host, лицензия AGPLv3. Docker не обязателен.

## Требования

- PHP 8.3+ (pdo_mysql или pdo_sqlite, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, curl)
- Composer 2, Node.js 20+
- MySQL/MariaDB (или SQLite для пробы)
- Cron: `php artisan schedule:run` каждую минуту (SSL, напоминания, очередь писем)

## Установка self-host (<15 минут)

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Либо одной командой: `composer run setup` (без сидера).

SQLite:

```bash
touch database/database.sqlite
```

MySQL в `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

```bash
php artisan migrate --seed
npm install && npm run build
```

Cron (единственный процесс на shared/VPS):

```
* * * * * cd /var/www/duvento && php artisan schedule:run >> /dev/null 2>&1
```

Почта: в `.env` `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT=587`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`. Для проверки `MAIL_MAILER=log`.  
Очередь: `QUEUE_CONNECTION=database`. Воркер поднимает тот же cron (`queue:work --stop-when-empty`). Отдельный демон не нужен.

Nginx: корень сайта — `public/`. PHP-FPM 8.3.

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

Публичная AGPL-копия без `packages/duvento-cloud`: `composer run export-oss`.

## Демо

Пароль `password`.

- Панель: `alex@severnaya.example` → http://127.0.0.1:8090
- Filament /admin: `admin@duvento.local`

`APP_EDITION=self-host` или `cloud`.  
Закрытые модули: `packages/duvento-cloud` (Paddle, Telegram, WHOIS, white-label). В публичный GitHub не входят (`export-ignore`).

Cloud без ключа Paddle: sandbox-оплата на `/settings/billing`.  
Живой Paddle: `PADDLE_API_KEY`, `PADDLE_PRICE_STARTER`, `PADDLE_PRICE_AGENCY`, webhook `POST /billing/paddle/webhook`.  
Вейтлист — форма на лендинге.

## Docker (опционально)

Сначала на хосте: `composer install && npm install && npm run build`.  
В `.env`: `DB_CONNECTION=mysql`, `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=duvento`, `DB_USERNAME=duvento`, `DB_PASSWORD=duvento`.

```bash
docker compose up -d --build
php artisan migrate --seed
```

Приложение: http://127.0.0.1:8090. Планировщик — сервис `scheduler`.

## Черновики запуска

**r/selfhosted / r/webdev:** AGPLv3 deadline tracker for agencies (domains, SSL, hosting). Composer + MySQL + one cron, no Docker required. Cloud exists if you do not want to self-host.

**Indie Hackers / Product Hunt:** Duvento — stop missing client domain/SSL renewals. 14-day trial, then $19/$49. Same product as the open-source self-host.
