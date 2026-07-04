# Troubleshooting

Bu dokuman sik karsilasilan CRM Engine sorunlari icin hizli kontrol listesidir.

## Container Acilmiyor

Durum:

```bash
make ps
```

Log:

```bash
make logs
```

Port cakismasi varsa `.env` icindeki forward portlari degistir:

```env
DB_FORWARD_PORT=3307
REDIS_FORWARD_PORT=6380
MAILPIT_FORWARD_PORT=8026
```

## Composer Private Repository Hata Veriyor

`sanalkopru/admin-panel` private repository erisimi icin GitHub token gerekebilir.

```bash
COMPOSER_AUTH='{"github-oauth":{"github.com":"GITHUB_TOKEN"}}' make composer CMD="install"
```

Token repoya commit edilmez.

## Class Not Found veya Package Discover Sorunu

Autoload yenile:

```bash
make composer CMD="dump-autoload"
make artisan CMD="package:discover"
```

Config cache temizle:

```bash
make artisan CMD="config:clear"
```

## Migration Hata Veriyor

Fresh development reset:

```bash
make fresh
```

Production'da fresh/reset kullanilmaz. Production icin once migration planlanir, sonra:

```bash
php artisan migrate --force
```

## Permission veya Role Calismiyor

Permission seeder'i tekrar calistir:

```bash
make artisan CMD="db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmPermissionSeeder"
```

Cache temizle:

```bash
make artisan CMD="cache:clear"
make artisan CMD="permission:cache-reset"
```

Kullanici rol atamasini kontrol et.

## Admin CRM 403 Donuyor

Kontrol et:

- Kullanici login mi?
- Admin guard dogru mu?
- Kullanici CRM rollerinden birine sahip mi?
- `CRM_PERMISSIONS_ENABLED=true` mi?
- Route middleware admin panel ile uyumlu mu?

## `/admin/crm` 404 Donuyor

Route list:

```bash
make artisan CMD="route:list --path=admin/crm"
```

Config:

```env
CRM_ROUTE_PREFIX=admin/crm
CRM_ROUTE_MIDDLEWARE=web
```

Config cache varsa temizle:

```bash
make artisan CMD="config:clear"
```

## Assetler Yuklenmiyor

Asset publish:

```bash
make artisan CMD="vendor:publish --tag=crm-assets --force"
```

Browser cache temizle. Public path:

```text
public/vendor/crm
```

## Quote PDF Hata Veriyor

Kontrol et:

- `dompdf/dompdf` kurulu mu?
- Logo dosyasi guvenli image mi?
- Storage symlink var mi?
- Turkce karakterler PDF template icinde dogru render ediliyor mu?

Komut:

```bash
make artisan CMD="storage:link"
```

## Import Hata Veriyor

Kontrol et:

- Dosya CSV veya XLSX mi?
- Header template ile uyumlu mu?
- Owner email kayitli user'a ait mi?
- Company/contact relation alanlari mevcut mu?
- Import error report indirilebiliyor mu?

Template indirme ekranini kullanarak dogru kolonlari al.

## Buyuk Import Calismiyor

Buyuk importlar queue'ya alinabilir:

```env
CRM_IMPORT_QUEUE_THRESHOLD=500
QUEUE_CONNECTION=redis
```

Queue worker calisiyor mu:

```bash
make queue
```

Production'da Supervisor worker kontrol edilir.

## Task Reminder Gitmiyor

Kontrol et:

- Task incomplete mi?
- `reminder_at` gecmis mi?
- `reminder_notified_at` bos mu?
- Assignee var mi?
- Queue worker calisiyor mu?
- Scheduler calisiyor mu?

Manuel komut:

```bash
make artisan CMD="crm:tasks:send-reminders"
```

## AI Cevap Vermiyor

AI kapaliysa bu beklenen davranistir:

```env
CRM_AI_ENABLED=false
CRM_AI_DRIVER=null
```

AI aciksa:

```env
CRM_AI_ENABLED=true
CRM_AI_DRIVER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

Kontrol et:

- Driver `openai`, `claude`, `gemini` veya `null` mi?
- Provider API key var mi?
- Rate limit doldu mu?
- Network/API provider hatasi log'a dustu mu?

## API 401 veya 403 Donuyor

`401`: Bearer token yok veya gecersiz.

```http
Authorization: Bearer crm_live_xxx
Accept: application/json
```

`403`: Token kullanicisinin ilgili CRM permission'i yok.

Token uretimi icin [API dokumanina](api.md) bak.

## Kanban Cok Az Kart Gosteriyor

Kanban stage basina limitlidir:

```env
CRM_KANBAN_PER_STAGE_LIMIT=50
CRM_KANBAN_PER_STAGE_MAX_LIMIT=100
```

Daha fazla kayit icin filtre kullan veya list view'a gec.

## Dashboard Yavas

Kontrol et:

- Performance index migration calismis mi?
- Query log ile N+1 var mi?
- `CrmPerformanceModuleTest` geciyor mu?
- Buyuk veri icin Kanban limitleri uygun mu?

Detay: [performance.md](performance.md)


## Hata İzleme (Opsiyonel Sentry)

Production'da uygulama hatalarını merkezi izlemek için:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

`.env`:

```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.1
```

Paket eklenmediği sürece hiçbir etkisi yoktur; çekirdek ürün bilinçli olarak bağımlılıksız bırakılmıştır. Alternatif: Flare (`spatie/laravel-ignition` zaten kurulu, yalnızca FLARE_KEY gerekir).
