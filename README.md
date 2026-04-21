# CRM Engine

`sanalkopru/crm`, Sanal Kopru'nun tekrar satilabilir CRM altyapisidir. Hedef; bir musterinin "isyerim icin CRM modulune ihtiyacim var" talebinde, hazir ve production seviyesine yakin bir cekirdegi alip 1-2 gun icinde kurabilmek, markalayabilmek ve gerekirse musteriye ozel gelistirmeler ekleyebilmektir.

## Kapsam

CRM Engine ilk fazda sirket calisanlarinin kullanacagi admin CRM arayuzunu ve onun altindaki is motorunu sunar:

- Contacts ve Companies yonetimi
- Deals Kanban pipeline
- Tasks, reminder ve notification sistemi
- Quotes, KDV/iskonto hesaplama ve PDF export
- Activities timeline
- Tags, import/export, dashboard ve settings
- Driver tabanli AI ile not ozetleme, email taslagi ve teklif takip metni

Public/customer frontend bu fazin ana kapsami degildir. Teklif onay linki, musteri portali, SaaS onboarding veya ayri React/Vue panel gibi yuzeyler, cekirdek tamamlandiktan sonra ayni servis katmanlari uzerine eklenebilir.

## Tenancy Karari

CRM Engine varsayilan olarak her musteriye ayri kurulan single-tenant bir urundur. Bu, ilk satislar icin daha hizli, daha sade ve desteklemesi daha kolay bir modeldir.

Kod mimarisi yine de tenant-ready tasarlanir. Yani ileride SaaS modeline gecmek istersek veriyi ayirmak icin `organization_id` / `workspace_id` benzeri bir sahiplik katmanina hazir olunur. Full SaaS multi-tenancy, subdomain, tenant provisioning, abonelik ve tenant database yonetimi bu urunun ilk kapsamina alinmaz; bunlar ileride `saas-starter` urunuyle birlestirilebilir.

## Mimari Prensip

Admin panel bir arayuzdur; CRM'in asil degeri cekirdektedir.

Bu nedenle kritik is mantigi controller, Blade veya JavaScript icine gomulmez. Controller'lar ince kalir: request validate eder, yetki kontrolu yapar, ilgili Action/Service sinifini cagirir ve response dondurur. Hesaplama, durum gecisi, bildirim, audit log ve AI orkestrasyonu Services, Actions, Events, Jobs ve Policies katmanlarinda tutulur.

Bu karar sayesinde ayni CRM motoru daha sonra su yuzeylere kolayca acilabilir:

- Admin panel
- Public quote approval page
- Customer portal
- Mobil uygulama
- React/Vue tabanli ozel panel
- Ucuncu parti entegrasyon API'lari

## Development

Development Docker ile yapilir. PHP, Composer, npm, Artisan, queue worker ve scheduler container icinde calisir.

Host makinede su komutlar calistirilmaz:

```bash
php
composer
npm
php artisan
```

Docker development stack'i bu dizindeki `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf` ve `Makefile` ile yonetilir.

Laravel 12 hatti kullanilir. Bunun nedeni `sanalkopru/admin-panel` paketinin mevcut surumunun Laravel 10/11/12 desteklemesi ve CRM'in bu admin altyapisi uzerine kurulacak olmasidir.

Baslangic:

```bash
cp .env.example .env
make up
```

Kullanilacak komutlar:

```bash
make bash
make composer CMD="install"
make artisan CMD="about"
make npm CMD="install"
make migrate
make test
make logs
```

Private GitHub paketleri icin token repoya yazilmaz. `sanalkopru/admin-panel` kurulacagi zaman Composer auth bilgisi shell uzerinden veya lokal `.env` dosyasindan verilir:

```bash
COMPOSER_AUTH='{"github-oauth":{"github.com":"GITHUB_TOKEN"}}' make composer CMD="require sanalkopru/admin-panel"
```

AI saglayicisi driver mantigi ile secilir. `.env` icinde `CRM_AI_DRIVER=openai`, `CRM_AI_DRIVER=claude`, `CRM_AI_DRIVER=gemini` veya `CRM_AI_DRIVER=null` kullanilabilir. Provider anahtarlari ve modelleri kendi env bloklarinda tutulur; is mantigi belirli bir provider SDK'sina gomulmez.

Development portlari:

- Uygulama: `http://localhost:8081`
- MySQL host portu: `3307`
- Redis host portu: `6380`
- Mailpit: `http://localhost:8026`

## Production

Production'da Docker kullanilmaz. Hedef deploy mimarisi:

- Nginx
- PHP-FPM
- MySQL
- Redis
- Supervisor queue worker
- Cron ile Laravel scheduler
- SSL/TLS
- Log rotation ve backup stratejisi

Production dokumani `docs/production-deploy-no-docker.md` olarak ayri fazda yazilacaktir.

## Admin Panel

CRM admin ekranlari `/admin/crm` altinda izole calisir ve `sanalkopru/admin-panel` paketini kullanir. CRM assetleri public frontend'e karismaz; sadece admin CRM ekranlarinda yuklenir.

## Roadmap

Tam uygulama plani `roadmap.md` dosyasindadir. Ilk hedef once cekirdegi ve admin CRM frontend'ini bitirmek, ardindan ihtiyaca gore public/customer frontend fazini eklemektir.
