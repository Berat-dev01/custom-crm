# CRM Engine Architecture

## Amac

CRM Engine, musteri projelerine hizla kurulabilen ve tekrar satilabilen bir Laravel CRM paketidir. Urun sadece ekranlardan olusmaz; asil hedef, farkli frontendlerin kullanabilecegi saglam bir CRM cekirdegi kurmaktir.

## Temel Karar

Ilk resmi arayuz admin paneldir. Bu arayuz `/admin/crm` altinda calisir ve `sanalkopru/admin-panel` layout/komponent yapisini kullanir.

Public/customer frontend ilk fazin kapsami degildir. Ancak mimari, sonradan asagidaki yuzeylerin eklenmesine hazir olacak sekilde kurulmalidir:

- Public quote approval page
- Customer portal
- SaaS onboarding ekranlari
- Mobil uygulama
- React/Vue tabanli ozel CRM paneli
- Ucuncu parti entegrasyon API'lari

Bu nedenle is mantigi admin panel koduna gomulmez.

## Tenancy Stratejisi

CRM Engine icin secilen model:

```text
single-tenant deploy by default, tenant-ready by design
```

Varsayilan satis ve kurulum modeli single-tenant'tir. Her musteri kendi kurulumu, kendi veritabani, kendi domaini veya sunucusu ile calisir. Bu model ilk ticari satislar icin daha hizli, daha az riskli ve daha kolay desteklenebilir durumdadir.

Tam SaaS multi-tenancy bu urunun ilk fazina dahil edilmez. Su ozellikler CRM Engine'in ilk kapsamina girmez:

- subdomain bazli tenant routing
- tenant basina ayri database provisioning
- central tenant database
- abonelik ve paket bazli billing
- tenant-aware cache/storage ayrimi
- super admin SaaS operasyon paneli

Bunlar `saas-starter` urununun sorumlulugudur. Ancak CRM Engine, ileride SaaS'a baglanabilmek icin tenant-ready tasarlanir.

Tenant-ready kurallar:

- CRM tablolarinda ileride `organization_id` veya `workspace_id` ile veri ayirma mumkun olmalidir.
- Tek musterili kurulumda sistem default organization/workspace ile calisabilmelidir.
- Query scope, policy ve dashboard metrikleri sahiplik filtresine hazir yazilmalidir.
- Import/export, notifications ve AI islemleri ileride organization context alabilecek sekilde tasarlanmalidir.
- Full SaaS ozellikleri simdi yazilmaz; ama sonradan eklemeyi zorlastiracak kararlar alinmaz.

## Katmanlar

### Package Layer

CRM paketi `packages/sanalkopru/crm` altinda gelistirilir. Paket kendi service provider, config, routes, migrations, views, policies, events, jobs ve notifications yapisina sahip olur.

Paket hedefi:

```bash
composer require sanalkopru/crm
php artisan vendor:publish --tag=crm-config
php artisan vendor:publish --tag=crm-migrations
php artisan migrate
```

### Admin Panel Package Layer

Genel admin arayuz altyapisi `packages/sanalkopru/admin-panel` paketinden gelir.

Bu katman CRM'e ozel is kurali tasimaz. Burada tutulacak seyler genel UI ve layout altyapisidir:

- Layout, navbar ve sidebar komponentleri
- Button, card, table, form inputlari ve badge gibi temel komponentler
- Confirm modal, toast, loading state ve command palette gibi tekrar kullanilabilir UI parcalari
- Custom select, compact filter bar, bulk quick action ve progressive pagination gibi CRM disinda da kullanilabilecek admin patternleri

CRM paketi bu komponentleri kullanir; fakat satis pipeline, quote hesaplama, import validation veya AI context gibi domain kurallarini admin-panel paketine gommez.

### Host App Layer

Root Laravel uygulamasi gelistirme ve demo hostudur. Production musteride de benzer bir host app bulunur, ancak asil urun kodu package icinde kalir.

Root uygulamanin sorumluluklari:

- Docker development ortami
- Demo login ve smoke test route'lari
- Musteri projesine ozel `.env`, cache, queue ve scheduler ayarlari
- Paketlerin Composer uzerinden yuklenmesi
- Paket publish/migration/seed komutlarinin calistirilmasi

`packages/sanalkopru/crm` ile `packages/sanalkopru/admin-panel` klasorlerinin root app yaninda durmasi mimari hata degildir. Bu repo gelistirme monorepo/working copy gibi kullanilir; satis ve kurulumda paketler Composer uzerinden musteri projesine baglanabilir.

`packages/sanalkopru/admin-panel` git status icinde dirty subrepo veya path repository olarak gorunebilir. Bu durum CRM mimarisinin hatali oldugu anlamina gelmez; sadece lokal repo yonetimi ve paket kaynak baglantisi konusudur. Kod sorumlulugu yine ayridir: genel admin UI admin-panel paketinde, CRM domain mantigi CRM paketinde kalir.

### Domain Layer

Domain katmani CRM'in asil motorudur.

Burada bulunacak ana nesneler:

- Contact
- Company
- Deal
- DealStage
- Task
- Quote
- QuoteItem
- Activity
- Tag

Domain katmani migration, model, relationship, scope, event ve policy'lerden olusur.

### Service ve Action Layer

Is kurallari bu katmanda tutulur.

Ornek servis/action siniflari:

- CreateContactAction
- UpdateContactAction
- MoveDealAction
- CloseDealAsWonAction
- CloseDealAsLostAction
- CreateQuoteAction
- RecalculateQuoteTotalsAction
- SendQuoteAction
- CompleteTaskAction
- DispatchTaskReminderAction
- SummarizeActivityTimelineAction
- DraftEmailAction

Kurallar:

- Controller icinde para hesabi yapilmaz.
- Blade icinde status transition yapilmaz.
- JavaScript icinde kalici is kurali bulunmaz.
- Deal stage degisimi, quote total hesaplama, task reminder secimi ve AI prompt orkestrasyonu servis/action katmaninda olur.
- AI entegrasyonu driver/contract mantigi ile yazilir; servisler OpenAI, Claude veya Gemini gibi provider siniflarina dogrudan baglanmaz.

### HTTP Layer

HTTP katmani ikiye ayrilir:

- Web/admin controllers
- API controllers

Her iki controller tipi de ayni service/action katmanini kullanir. Bu sayede admin paneldeki mantik API icin tekrar yazilmaz.

Controller sorumluluklari:

- request validate etmek
- authorize/policy kontrolu yapmak
- service/action cagirarak sonucu almak
- view veya JSON response dondurmek

### Presentation Layer

Presentation layer admin panel view'lari, Blade partial'lari ve gerekli JavaScript kodlarindan olusur.

Kurallar:

- Blade sadece veri gosterir ve form/render sorumlulugu tasir.
- SortableJS sadece drag-drop etkilesimini yonetir.
- Kanban move istegi backend action tarafindan dogrulanir ve uygulanir.
- Quote formundaki frontend hesaplama sadece kullaniciya anlik onizleme verebilir; nihai hesap backend tarafinda yapilir.

### Policy Layer

Tum kritik islemler policy ile korunur:

- create/update/delete
- deal move
- quote send/accept/reject
- import/export
- AI kullanimi
- settings yonetimi

Policy kontrolleri hem web controller'larda hem API controller'larda kullanilir.

### Events, Jobs ve Notifications

Asenkron ve sistem olaylari bu katmanlarda tutulur.

Ornekler:

- DealMoved event'i activity olusturur.
- QuoteSent event'i timeline'a kayit yazar.
- Task reminder scheduler tarafindan job'a aktarilir.
- Notification database/mail kanallariyla gonderilir.
- Import/export buyuk veri icin queue ile calisir.

## Admin Panel Izolasyonu

Admin panel `/admin` altinda izole calisir. CRM route prefix'i varsayilan olarak `/admin/crm` olur.

CRM admin view'lari:

```php
@extends('admin-panel::layouts.app')
```

Frontend public layout'una CRM CSS/JS eklenmez. CRM assetleri sadece ilgili admin sayfalarinda yuklenir.

## Development Docker

Development ortaminda Docker kullanilir:

- PHP-FPM app container
- Nginx
- MySQL
- Redis
- Queue worker
- Scheduler
- Mailpit

PHP, Composer, npm ve Artisan komutlari host makinede degil container icinde calisir.

Laravel surumu 12 hattina sabitlenir. `sanalkopru/admin-panel` paketinin mevcut uyumluluk araligi Laravel 10/11/12 oldugu icin Laravel 13'e gecis, admin panel paketi ayni araligi destekleyene kadar yapilmaz.

Private GitHub paketleri icin Composer token bilgisi repoya eklenmez. Gerekirse `COMPOSER_AUTH` lokal environment uzerinden Docker container'a aktarilir.

AI provider secimi `.env` uzerinden yapilir. `CRM_AI_DRIVER` degeri `openai`, `claude`, `gemini` veya `null` olabilir. Her provider kendi API key, base URL, model ve timeout ayarlarini `config/crm.php` altindan alir. CRM servisleri provider detaylarini bilmez; sadece AI driver contract'ini kullanir.

## Production Docker'siz Deploy

Production ortaminda Docker kullanilmaz.

Hedef mimari:

- Nginx
- PHP-FPM
- MySQL
- Redis
- Supervisor
- Cron scheduler
- SSL/TLS
- Backup ve log rotation

Bu karar musteri sunucularinda daha standart, bakimi kolay ve hosting uyumlu deploy icin alinmistir.

## Frontend Genisleme Stratejisi

Cekirdek tamamlandiktan sonra yeni frontend eklemek basit kalmalidir. Bunun icin:

- Is mantigi service/action katmaninda tutulur.
- API Resource yapisi erken kurulur.
- Policy'ler HTTP yuzeyinden bagimsiz tasarlanir.
- Events ve jobs admin panelden bagimsiz calisir.
- Config ve settings publish edilebilir olur.

Bu kurallar korunursa public quote approval, customer portal veya mobil uygulama eklemek yeni bir cekirdek yazmak anlamina gelmez; sadece mevcut CRM motoruna yeni bir arayuz eklemek anlamina gelir.
