# Installation

Bu dokuman CRM Engine'i development ortaminda calistirmak ve baska bir Laravel projesine paket olarak kurmak icin kullanilir.

## Gereksinimler

Development icin host makinede sadece sunlar gerekir:

- Docker
- Docker Compose
- Git
- Make

PHP, Composer, npm, Artisan, queue ve scheduler Docker container icinde calisir.

## Development Kurulumu

Repo dizinine gir:

```bash
cd crm
cp .env.example .env
```

Container'lari baslat:

```bash
make up
```

Dependency ve app hazirligi:

```bash
make composer CMD="install"
make artisan CMD="key:generate"
make fresh
```

Uygulama adresi:

```text
http://localhost:8081
```

Yardimci servisler:

- MySQL host portu: `3307`
- Redis host portu: `6380`
- Mailpit: `http://localhost:8026`

## Demo Veri

`make fresh` root `DatabaseSeeder` uzerinden CRM permission, stage ve demo veriyi yukler. Sadece demo CRM verisini tekrar basmak icin:

```bash
make artisan CMD="crm:seed-demo"
```

Demo kullanicilari:

| Rol | Email | Sifre |
| --- | --- | --- |
| Owner | `crm.owner@example.com` | `password` |
| Manager | `crm.manager@example.com` | `password` |
| Sales | `crm.sales@example.com` | `password` |
| Support | `crm.support@example.com` | `password` |
| Viewer | `crm.viewer@example.com` | `password` |

Demo pipeline, companies, contacts, deals, tasks, quotes, quote items, tags ve activities kayitlari olusturur.

## Performans Veri Seti

Buyuk veriyle ekranlari denemek icin:

```bash
make artisan CMD="crm:seed-performance"
```

Bu seeder 2.000 company, 10.000 contact ve 5.000 deal olusturur.

## Private Admin Panel Erisimi

CRM, `sanalkopru/admin-panel` paketini kullanir. Root `composer.json` icinde hem lokal path repository hem de private GitHub repository tanimlidir.

GitHub token gerekiyorsa:

```bash
COMPOSER_AUTH='{"github-oauth":{"github.com":"GITHUB_TOKEN"}}' make composer CMD="install"
```

Token `.env`, `composer.json` veya dokumanlara yazilmaz.

## Paket Olarak Kurulum

Bir musteri Laravel projesinde hedef kullanim:

```bash
composer require sanalkopru/crm
php artisan vendor:publish --tag=crm-config
php artisan vendor:publish --tag=crm-migrations
php artisan vendor:publish --tag=crm-assets
php artisan migrate
```

Admin panel paketi de proje dependency'lerinde bulunmalidir:

```bash
composer require sanalkopru/admin-panel:^1.0
```

Private repository kullaniliyorsa musteri projesinin `composer.json` dosyasina VCS repository eklenir:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/ZyixQQ/admin-panel"
    }
  ]
}
```

## Temel Env Ayarlari

```env
CRM_ROUTE_PREFIX=admin/crm
CRM_ROUTE_MIDDLEWARE=web
CRM_CURRENCY=TRY
CRM_SUPPORTED_CURRENCIES=TRY,USD,EUR
CRM_TAX_RATE=20
CRM_PERMISSIONS_ENABLED=true
QUEUE_CONNECTION=redis
```

AI kapali baslatmak icin:

```env
CRM_AI_ENABLED=false
CRM_AI_DRIVER=null
```

AI acilacaksa:

```env
CRM_AI_ENABLED=true
CRM_AI_DRIVER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

`CRM_AI_DRIVER` degeri `openai`, `claude`, `gemini` veya `null` olabilir.

## Opsiyonel Ozellikler (Feature Flags)

2FA, webhook ve takvim aboneligi cekirdek kurulumda kapalidir; musteri istedikce `.env` uzerinden acilir:

```env
CRM_FEATURE_2FA=true            # TOTP iki faktorlu dogrulama + Security ekrani
CRM_FEATURE_WEBHOOKS=true       # HMAC imzali webhook'lar + yonetim ekrani
CRM_FEATURE_CALENDAR_FEED=true  # Gorevler icin ICS takvim aboneligi
```

Kapaliyken ilgili menu ogeleri gizlenir, ekranlar ve public endpoint'ler 404 doner, webhook dispatcher hicbir teslimat uretmez.

## E-posta (SMTP) Ayarlari

Bildirim e-postalari (gorev atama/hatirlatma, teklif durumu, import sonucu) icin SMTP yapilandirin:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ornek.com
MAIL_PORT=587
MAIL_USERNAME=crm@ornek.com
MAIL_PASSWORD=gizli
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="crm@ornek.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Notlar:

- E-postalar queue uzerinden gonderilir; production'da queue worker sart (`docs/production-scheduler.md`).
- Genel e-posta anahtari CRM Settings > Notifications altindaki "Send notifications by email" secenegidir. SMTP hazir degilse bu anahtari kapatin.
- Her kullanici Bildirimler sayfasindan olay bazli e-posta tercihlerini yonetebilir; uygulama ici bildirimler her zaman acik kalir.
- Development ortaminda mailpit kullanilir: gonderilen mailler http://localhost:8026 adresinde gorunur.

## Kurulum Sonrasi Kontrol

Tek komutla saglik kontrolu:

```bash
php artisan crm:doctor
```

DB baglantisi, migration durumu, cache, storage link, queue/mail konfigurasyonu, izin seed'i, aktif kullanici, route kaydi ve APP_KEY kontrol edilir; hata varsa komut sifir olmayan exit code doner (deploy pipeline'ina eklenebilir).


```bash
make artisan CMD="route:list --path=admin/crm"
make artisan CMD="route:list --path=api/crm"
make test
```

Admin panelden `/admin/crm` acildiginda dashboard, navigation ve demo data gorunmelidir.
