# CRM Engine

`sanalkopru/crm`, Sanal Kopru projelerine kurulabilen tekrar satilabilir Laravel CRM paketidir. Amac, musteri CRM istediginde sifirdan proje yazmak yerine, admin paneli hazir, testleri olan, customize edilebilir ve production deploy'a hazir bir cekirdegi hizla teslim etmektir.

Urun varsayilan olarak single-tenant kurulur. Her musteri kendi kurulumu, veritabani ve domainiyle calisir. Kod yapisi tenant-ready tutulur; ileride public portal, mobil uygulama veya SaaS yuzeyi eklenirken is mantigi yeniden yazilmaz.

## Ozellikler

- Admin CRM paneli: `/admin/crm`
- Contacts ve companies yonetimi
- Deals Kanban pipeline, drag-drop ve won/lost akislari
- Tasks, reminder, queue ve scheduler destegi
- Quotes, KDV/iskonto hesaplama, PDF preview/download
- Activities timeline ve otomatik sistem aktiviteleri
- Tags, saved filters, import/export
- Dashboard, raporlama ve performans odakli aggregate sorgular
- Settings, marka bilgileri, quote varsayilanlari ve logo upload
- Token korumali `/api/crm` API katmani
- Audit log, policy tabanli yetki sistemi ve rate limitler
- Driver tabanli AI: `openai`, `claude`, `gemini`, `null`

## Hizli Development Kurulumu

Development tamamen Docker ile calisir. Host makinede `php`, `composer`, `npm` veya `php artisan` calistirilmaz.

```bash
cp .env.example .env
make up
make composer CMD="install"
make artisan CMD="key:generate"
make fresh
```

Uygulama: `http://localhost:8081`

Demo kullanicilari:

- `crm.owner@example.com` / `password`
- `crm.manager@example.com` / `password`
- `crm.sales@example.com` / `password`
- `crm.support@example.com` / `password`
- `crm.viewer@example.com` / `password`

Demo verisi tek komutla gelir:

```bash
make artisan CMD="db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmDemoSeeder"
```

Performans veri seti:

```bash
make artisan CMD="db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmPerformanceSeeder"
```

Detayli kurulum: [docs/installation.md](docs/installation.md)

## Admin Panel Entegrasyonu

CRM view katmani `sanalkopru/admin-panel` paketinin layout ve admin guard yapisini kullanir. Root `composer.json` icinde admin panel private repository tanimlidir:

```json
{
  "type": "vcs",
  "url": "https://github.com/ZyixQQ/admin-panel"
}
```

Private GitHub erisimi gerekiyorsa token repoya yazilmaz; lokal ortamdan `COMPOSER_AUTH` ile verilir.

CRM ekranlari admin panelden izole sekilde `/admin/crm` altinda calisir. Public frontend'e CRM assetleri yuklenmez.

## Paket Publish Komutlari

```bash
make artisan CMD="vendor:publish --tag=crm-config"
make artisan CMD="vendor:publish --tag=crm-views"
make artisan CMD="vendor:publish --tag=crm-migrations"
make artisan CMD="vendor:publish --tag=crm-assets"
make artisan CMD="migrate"
```

Kurulum ve paket entegrasyonu icin: [docs/installation.md](docs/installation.md)

## Test ve QA

```bash
make test
make artisan CMD="migrate:fresh --seed --force"
make composer CMD="validate --strict"
```

Test kapsami: [docs/qa/test-suite.md](docs/qa/test-suite.md)

Manual QA checklist: [docs/qa-checklist.md](docs/qa-checklist.md)

## AI Provider Secimi

`.env` icinden provider secilir:

```env
CRM_AI_ENABLED=false
CRM_AI_DRIVER=openai
```

Desteklenen driver'lar: `openai`, `claude`, `gemini`, `null`. AI katmani taslak/ozet uretir; CRM kayitlarini kullanici onayi olmadan degistirmez.

## Production

Production'da Docker kullanilmaz. Hedef mimari Nginx, PHP-FPM, MySQL, Redis, Supervisor queue worker, Cron scheduler ve SSL/TLS uzerinedir.

Production rehberi: [docs/production-deploy-no-docker.md](docs/production-deploy-no-docker.md)

## Dokumanlar

- [Installation](docs/installation.md)
- [Development Docker](docs/development-docker.md)
- [Production Deploy No Docker](docs/production-deploy-no-docker.md)
- [Modules](docs/modules.md)
- [Customization](docs/customization.md)
- [API](docs/api.md)
- [Performance](docs/performance.md)
- [Security Checklist](docs/security-checklist.md)
- [Manual Test Guide](docs/manual-test-guide.md)
- [QA Checklist](docs/qa-checklist.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Architecture](docs/architecture.md)
- [Product Scope](docs/product-scope.md)

## Roadmap

Tam uygulama plani ve adim durumlari [roadmap.md](roadmap.md) dosyasindadir.
