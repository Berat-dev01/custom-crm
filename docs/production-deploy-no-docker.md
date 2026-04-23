# Production Deploy No Docker

Production ortaminda Docker veya Docker Compose kullanilmaz. Bu rehber CRM Engine'in klasik Laravel production mimarisiyle musteri sunucusuna kurulmasi icindir.

Hedef mimari:

- Ubuntu server
- Nginx
- PHP-FPM
- Composer
- MySQL
- Redis
- Supervisor queue worker
- Cron ile Laravel scheduler
- SSL/TLS
- Log rotation, backup ve rollback plani

## Server Requirements

Onerilen minimum:

- Ubuntu 22.04 LTS veya 24.04 LTS
- PHP 8.3+
- MySQL 8+
- Redis 7+
- Nginx
- Supervisor
- Composer 2
- Git
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

Ubuntu paket ornegi:

```bash
apt update
apt install nginx mysql-server redis-server supervisor git unzip curl
apt install php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-gd php8.3-intl php8.3-mbstring php8.3-mysql php8.3-redis php8.3-xml php8.3-zip
```

## Directory Layout

Onerilen release yapisi:

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

Release yapisi rollback'i kolaylastirir. Yeni deploy once `releases/<timestamp>` altina acilir, kontroller gecince `current` symlink'i yeni release'e alinir.

## Database

MySQL database ve kullanici ornegi:

```sql
CREATE DATABASE customer_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'customer_crm'@'localhost' IDENTIFIED BY 'secure-password';
GRANT ALL PRIVILEGES ON customer_crm.* TO 'customer_crm'@'localhost';
FLUSH PRIVILEGES;
```

Production'da demo veya performance seed calistirilmaz. Ilk kurulumda sadece permission ve deal stage seed'leri gerekir.

## Env

Production `.env` dosyasi shared path altinda tutulur:

```text
/var/www/customer-crm/shared/.env
```

Ornek:

```env
APP_NAME="Customer CRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://crm.example.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=customer_crm
DB_USERNAME=customer_crm
DB_PASSWORD=secure-password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=crm@example.com
MAIL_FROM_NAME="${APP_NAME}"

CRM_ROUTE_PREFIX=admin/crm
CRM_ROUTE_MIDDLEWARE=web
CRM_PERMISSIONS_ENABLED=true
CRM_CURRENCY=TRY
CRM_SUPPORTED_CURRENCIES=TRY,USD,EUR
CRM_TAX_RATE=20
CRM_QUOTE_NUMBER_PREFIX=CRM-
CRM_NOTIFY_TASK_REMINDERS=true
CRM_API_RATE_LIMIT_PER_MINUTE=120

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

`CRM_AI_DRIVER` degeri `openai`, `claude`, `gemini` veya `null` olabilir.

## Private Packages

`sanalkopru/admin-panel` private repository erisimi gerekiyorsa Composer auth sunucuda kullanici bazli ayarlanir. Token proje dosyalarina yazilmaz.

```bash
composer config --global github-oauth.github.com GITHUB_TOKEN
```

CI/CD kullaniliyorsa token secret olarak verilir.

## First Deploy

Release dizinine kodu al:

```bash
mkdir -p /var/www/customer-crm/releases/202604231200
cd /var/www/customer-crm/releases/202604231200
git clone git@github.com:company/customer-crm.git .
```

Shared dosyalari bagla:

```bash
ln -s /var/www/customer-crm/shared/.env .env
rm -rf storage
ln -s /var/www/customer-crm/shared/storage storage
```

Dependency kur:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

Ilk kurulumda app key olustur:

```bash
php artisan key:generate --force
```

Migration ve zorunlu CRM seed'leri:

```bash
php artisan migrate --force
php artisan db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmPermissionSeeder --force
php artisan db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmDealStageSeeder --force
```

Storage symlink:

```bash
php artisan storage:link
```

Cache:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Release'i aktif et:

```bash
ln -sfn /var/www/customer-crm/releases/202604231200 /var/www/customer-crm/current
chown -h www-data:www-data /var/www/customer-crm/current
```

## Subsequent Deploys

Yeni release icin genel akis:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Migration'dan once release notlari incelenir. Geriye uyumsuz migration varsa rollback plani netlestirilmeden deploy yapilmaz.

## Asset Build Strategy

Onerilen strateji asset build'i CI/CD icinde yapmak ve build artifact'i deploy etmektir. Bu durumda production server'da Node.js gerekmez.

Server uzerinde build yapilacaksa:

```bash
npm ci
npm run build
```

Build sonrasinda `public/build` veya uygulamanin asset cikti dizini release icinde bulunmalidir.

## File Permissions

Shared storage ve cache yazilabilir olmalidir:

```bash
mkdir -p /var/www/customer-crm/shared/storage
chown -R www-data:www-data /var/www/customer-crm/shared/storage
chown -R www-data:www-data /var/www/customer-crm/releases
chmod -R ug+rw /var/www/customer-crm/shared/storage
chmod -R ug+rw /var/www/customer-crm/current/bootstrap/cache
```

`.env` dosyasi public web root altinda degildir ve sadece deploy kullanicisi/www-data tarafindan okunmalidir.

## Nginx Server Block

HTTP isteklerini HTTPS'e yonlendir:

```nginx
server {
    listen 80;
    server_name crm.example.com;
    return 301 https://$host$request_uri;
}
```

HTTPS server block:

```nginx
server {
    listen 443 ssl http2;
    server_name crm.example.com;
    root /var/www/customer-crm/current/public;

    ssl_certificate /etc/letsencrypt/live/crm.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/crm.example.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;
    client_max_body_size 25M;

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

Nginx kontrol:

```bash
nginx -t
systemctl reload nginx
```

SSL/TLS icin Let's Encrypt veya musteri sertifika altyapisi kullanilir.

## PHP-FPM Pool Notes

Yuksek trafik icin PHP-FPM pool ayarlari server kapasitesine gore yapilir:

```ini
[customer-crm]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm-customer-crm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500
```

Nginx `fastcgi_pass` bu socket'e gore guncellenebilir:

```nginx
fastcgi_pass unix:/run/php/php8.3-fpm-customer-crm.sock;
```

Import ve logo upload icin PHP limitleri:

```ini
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 120
```

Degisiklikten sonra:

```bash
systemctl reload php8.3-fpm
```

## Supervisor Queue Worker

`/etc/supervisor/conf.d/customer-crm-worker.conf`:

```ini
[program:customer-crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/customer-crm/current/artisan queue:work redis --sleep=3 --tries=3 --timeout=90 --max-time=3600
directory=/var/www/customer-crm/current
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/customer-crm/shared/storage/logs/worker.log
stopwaitsecs=120
```

Uygula:

```bash
supervisorctl reread
supervisorctl update
supervisorctl restart customer-crm-worker:*
```

Deploy sonrasinda:

```bash
php artisan queue:restart
```

Queue worker CRM import job'lari, notification'lar ve reminder mail/database bildirimlerini isler.

## Cron Scheduler

Scheduler tek application node uzerinde calismalidir. `www-data` crontab:

```cron
* * * * * cd /var/www/customer-crm/current && php artisan schedule:run >> /dev/null 2>&1
```

CRM package scheduler icinde `crm:tasks:send-reminders` komutunu her bes dakikada bir calistirir. Bu komut due reminder'lari bulur ve notification islerini queue'ya aktarir.

Manuel kontrol:

```bash
php artisan schedule:list
php artisan crm:tasks:send-reminders
```

## Log Rotation

Laravel log dosyalari icin `/etc/logrotate.d/customer-crm`:

```text
/var/www/customer-crm/shared/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 664 www-data www-data
}
```

Nginx, PHP-FPM ve Supervisor loglari da sunucu log rotation politikasina dahil edilmelidir.

## Backup Strategy

Minimum backup stratejisi:

- MySQL daily dump
- `storage/app` dosyalari
- `.env` yedegi
- Haftalik off-site kopya
- Backup sifreleme
- Restore testi

MySQL dump ornegi:

```bash
mysqldump --single-transaction --routines --triggers customer_crm > /var/backups/customer-crm/customer_crm_$(date +%F).sql
```

Backup dosyalari public web root altinda tutulmaz.

## Rollback Strategy

Release dizini kullanan kurulumlarda rollback:

1. Eski release dizini secilir.
2. `current` symlink onceki release'e alinir.
3. `php artisan optimize:clear`
4. `php artisan config:cache`
5. `php artisan route:cache`
6. `php artisan view:cache`
7. `php artisan queue:restart`
8. `supervisorctl restart customer-crm-worker:*`
9. Nginx/PHP-FPM loglari kontrol edilir.

Migration rollback otomatik yapilmaz. Database schema geriye uyumsuz degistiyse rollback proseduru release oncesinde ayrica yazilmalidir.

## Security Checklist

- `APP_DEBUG=false`
- `.env` public root disinda
- HTTPS aktif
- MySQL kullanicisi sadece ilgili database'e yetkili
- Redis public internete acik degil
- Server firewall sadece SSH, HTTP ve HTTPS acik
- Admin kullanicilarinda guclu sifre politikasi
- AI API key'leri sadece server environment icinde
- Backup dosyalari sifreli ve public root disinda

## Smoke Test

Deploy sonrasi CLI kontrol:

```bash
php artisan about
php artisan route:list --path=admin/crm
php artisan route:list --path=api/crm
php artisan schedule:list
php artisan queue:restart
php artisan crm:tasks:send-reminders
```

Web uzerinden:

- `/admin/crm` dashboard acilir.
- Login ve rol kontrolleri calisir.
- Contact create/update denenir.
- Deal Kanban stage move denenir.
- Quote PDF indirilir.
- Queue worker loglari hata vermemelidir.
