# Production Deploy No Docker

Production ortaminda Docker veya Docker Compose kullanilmaz. Hedef mimari klasik Laravel deployment modelidir:

- Ubuntu server
- Nginx
- PHP-FPM
- Composer
- MySQL
- Redis
- Supervisor queue worker
- Cron ile Laravel scheduler
- SSL/TLS

## Server Requirements

Onerilen minimum:

- Ubuntu 22.04 LTS veya 24.04 LTS
- PHP 8.3+
- MySQL 8+
- Redis 7+
- Nginx
- Supervisor
- Composer 2
- Node.js sadece asset build production server'da yapilacaksa gerekir

PHP extension listesi:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `filter`
- `gd`
- `intl`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `redis`
- `tokenizer`
- `xml`
- `zip`

## Release Dizini

Onerilen path:

```text
/var/www/customer-crm/current
/var/www/customer-crm/releases
/var/www/customer-crm/shared/.env
/var/www/customer-crm/shared/storage
```

Basit kurulumlarda tek dizin de kullanilabilir:

```text
/var/www/customer-crm
```

## Env

Production `.env` ornegi:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=customer_crm
DB_USERNAME=customer_crm
DB_PASSWORD=secure-password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CRM_ROUTE_PREFIX=admin/crm
CRM_PERMISSIONS_ENABLED=true
CRM_AI_ENABLED=false
CRM_AI_DRIVER=null
```

AI kullanilacaksa ilgili provider key'i eklenir:

```env
CRM_AI_ENABLED=true
CRM_AI_DRIVER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

## Deploy Komutlari

Production server'da:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Asset build CI/CD tarafinda yapiliyorsa build artifact deploy edilir. Server'da build yapilacaksa:

```bash
npm ci
npm run build
```

## File Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

## Nginx Server Block

```nginx
server {
    listen 80;
    server_name crm.example.com;
    root /var/www/customer-crm/current/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

SSL/TLS icin Let's Encrypt veya musteri sertifika altyapisi kullanilir.

## PHP-FPM Notlari

Yuksek trafik icin PHP-FPM pool ayarlari server kapasitesine gore yapilir:

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
```

Upload limitleri import ve logo upload gereksinimlerine gore ayarlanir:

```ini
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
```

## Supervisor Queue Worker

`/etc/supervisor/conf.d/customer-crm-worker.conf`:

```ini
[program:customer-crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/customer-crm/current/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/customer-crm/current/storage/logs/worker.log
stopwaitsecs=120
```

Uygula:

```bash
supervisorctl reread
supervisorctl update
supervisorctl restart customer-crm-worker:*
```

## Cron Scheduler

`www-data` crontab:

```cron
* * * * * cd /var/www/customer-crm/current && php artisan schedule:run >> /dev/null 2>&1
```

CRM task reminder scheduler icinden `crm:tasks:send-reminders` komutunu her bes dakikada bir calistirir.

## Log Rotation

Laravel log dosyalari icin `/etc/logrotate.d/customer-crm`:

```text
/var/www/customer-crm/current/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 664 www-data www-data
}
```

## Backup

Minimum backup stratejisi:

- MySQL daily dump
- `storage/app` dosyalari
- `.env` yedegi
- Haftalik off-site kopya
- Restore testi

Backup dosyalari public web root altinda tutulmaz.

## Rollback

Release dizini kullanan kurulumlarda rollback:

1. `current` symlink onceki release'e alinir.
2. `php artisan config:cache`
3. `php artisan route:cache`
4. `php artisan view:cache`
5. `supervisorctl restart customer-crm-worker:*`
6. Nginx/PHP-FPM loglari kontrol edilir.

Migration rollback otomatik yapilmaz. Geriye uyumsuz migration varsa release checklist'te ayrica planlanir.

## Smoke Test

Deploy sonrasi:

```bash
php artisan about
php artisan route:list --path=admin/crm
php artisan route:list --path=api/crm
php artisan queue:restart
php artisan crm:tasks:send-reminders
```

Web uzerinden:

- `/admin/crm` dashboard acilir.
- Login ve rol kontrolleri calisir.
- Contact create/update denenir.
- Quote PDF indirilir.
- Queue worker loglari hata vermemelidir.
