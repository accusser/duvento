# Duvento

[English](README.md) · [Українська](README.uk.md) · [Русский](README.ru.md)

**Спокійне відстеження дедлайнів для вебагенцій.** Домени, SSL-сертифікати, хостинг, ліцензії плагінів та інші продовження — в одному місці.

Duvento — безкоштовна self-hosted система за ліцензією AGPL-3.0-or-later. Docker необов'язковий.

## Можливості

- Кольорові статуси продовжень Critical / Urgent / Soon / OK
- Автоматична перевірка терміну дії SSL без платного стороннього API
- Email-нагадування з налаштовуваними інтервалами
- Сума майбутніх платежів на 7, 30 і 90 днів
- Клієнти, власні типи активів, журнал дій та імпорт/експорт CSV
- Публічні read-only посилання зі статусом для клієнтів
- Запрошення команди, вбудовані звернення до підтримки, пошук і сповіщення
- Інтерфейс англійською, українською, російською, німецькою, іспанською та польською

## Вимоги

- PHP 8.3+ із `pdo_mysql` або `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json` та `intl`
- MySQL/MariaDB для production; SQLite підходить для ознайомлення
- Вебсервер із коренем сайту, спрямованим на `public/`
- Одне cron-завдання для перевірок SSL, нагадувань і поштової черги

Composer 2 і Node.js 20+ потрібні лише для встановлення з вихідного коду. До release-архіву залежності та статика вже включені.

## Рекомендовано: архів і вебінсталятор

1. Завантажте **[duvento.zip](https://github.com/accusser/duvento/releases/latest/download/duvento.zip)** з [останнього релізу](https://github.com/accusser/duvento/releases/latest).
2. Розпакуйте архів у каталог сайту.
3. Спрямуйте корінь сайту nginx або Apache на каталог `public/`.
4. Надайте PHP-користувачу права запису до кореня проєкту (для створення `.env`), `storage/` і `bootstrap/cache/`.
5. Відкрийте сайт — Duvento перенаправить вас на `/install`.
6. Пройдіть майстер: мова → перевірка сервера → база даних → міграції → адміністратор.
7. Збережіть згенеровану адресу адміністрування та додайте cron із фінального екрана.

В архіві вже є PHP-залежності та скомпільована статика, тому Composer, Node.js і Artisan на сервері не потрібні.

Інсталятор створює чисту production-систему без демонстраційних записів. Після встановлення `/install` повертає 404 і не може скинути наявну систему.

## Встановлення з вихідного коду

```bash
git clone https://github.com/accusser/duvento.git
cd duvento
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Налаштуйте `DB_*` і `APP_URL` у `.env`; для SQLite створіть `database/database.sqlite`. Потім виконайте:

```bash
php artisan migrate --force
php artisan duvento:install
```

Команда безпечно запитає пароль, створить власника першого робочого простору й адміністратора та згенерує приватний `ADMIN_PATH`. Не запускайте сідери в production.

Для локальної розробки використовуйте `composer run setup`.

## Налаштування production

Додайте планувальник до crontab користувача вебсервера:

```cron
* * * * * cd /var/www/duvento && php artisan schedule:run >> /dev/null 2>&1
```

Налаштуйте SMTP у `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` і `MAIL_FROM_ADDRESS`. Пошта потрібна для нагадувань і відновлення пароля. `MAIL_MAILER=log` використовуйте лише для тестування.

Стандартну чергу в базі обробляє планувальник; постійний queue worker не потрібен.

Приклад конфігурації nginx:

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

У production використовуйте HTTPS, тримайте `.env` поза публічним коренем і створюйте резервні копії бази даних та `storage/app/`.

## Оновлення

Перед оновленням зробіть резервні копії бази даних, `.env` і `storage/app/`. Замініть файли застосунку, зберігши ці дані, потім виконайте:

```bash
php artisan migrate --force
php artisan optimize:clear
```

## Docker (необов'язково)

```bash
cp .env.example .env
# Встановіть DB_CONNECTION=mysql і DB_HOST=db у .env
docker compose up -d --build
```

Застосунок доступний на `http://127.0.0.1:8090`; заплановані завдання виконує сервіс `scheduler`.

## Self-host і Cloud

Цей репозиторій містить безкоштовну self-host редакцію. Керована Duvento Cloud використовує те саме ядро, а також містить hosted billing, white-label звіти, публічний API/webhooks і пріоритетну підтримку. Закриті cloud-модулі не входять до цього AGPL-репозиторію.

## Ліцензія

[AGPL-3.0-or-later](LICENSE)
