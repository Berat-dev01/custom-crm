# URUN 2 — CRM Engine — Tam Roadmap

**Paket:** `sanalkopru/crm`  
**Hedef:** Musteriye 1-2 gun icinde kurulup ozellestirilebilecek, admin panel icinde izole calisan, production ready ve satilabilir CRM altyapisi.  
**Stack:** Laravel · MySQL · Redis · Queue/Scheduler · PDF export · Driver tabanli AI modulu · SortableJS Kanban · Docker sadece development  
**Admin Panel:** `sanalkopru/admin-panel` paketi zorunlu. Tum yonetim sayfalari `/admin` altinda, `admin-panel::layouts.app` ile izole calisir. Frontend layout'una CRM CSS/JS eklenmez.  

## Frontend Stratejisi

- Urun 2 kapsaminda ilk frontend, sirket calisanlarinin kullanacagi admin CRM arayuzudur: dashboard, contacts, companies, deals Kanban, tasks, quotes, activities, settings ve AI ekranlari.
- Public/customer frontend ilk cekirdek kapsam degildir. Musteri portal, teklif onay linki, SaaS onboarding veya React/Vue panel gibi yuzeyler cekirdek tamamlandiktan sonra ayri faz olarak eklenebilir.
- Bu nedenle is mantigi Blade view, JavaScript veya controller icine gomulmez. Controller sadece request alir, authorize eder, servis/action cagirir ve response dondurur.
- CRM'in asil motoru Models, Services, Actions, Policies, Events, Jobs, Notifications ve API Resources katmanlarinda yasamalidir.
- Yeni frontend eklendiginde ayni cekirdek servisleri ve policy'leri kullanmali; mevcut admin panel kodunu kopyalamak veya is mantigini tekrar yazmak yasaktir.

## Tenancy Stratejisi

- CRM Engine varsayilan olarak single-tenant deploy edilir: her musteri kendi kurulumu, kendi veritabani ve kendi ortamiyla calisir.
- Tam SaaS multi-tenancy bu urunun ilk fazina dahil degildir; subdomain tenant routing, tenant database provisioning, abonelik ve super admin SaaS operasyonu `saas-starter` urununun konusudur.
- Buna ragmen CRM tenant-ready tasarlanir. Veri modeli, policy'ler, dashboard sorgulari, import/export, notification ve AI servisleri ileride `organization_id` veya `workspace_id` context'i alabilecek sekilde dusunulur.
- Tek musterili kurulumda default organization/workspace kullanilabilir; ancak full SaaS karmasasi simdiden urune gomulmez.

## Degismez Kurallar

- Development ortaminda PHP, Composer, npm, Artisan ve queue islemleri Docker container icinden calisir.
- Host makinede `php`, `composer`, `npm`, `artisan` calistirma.
- Docker production'da kullanilmaz. Production deploy klasik Nginx + PHP-FPM + Supervisor + MySQL + Redis mimarisiyle yapilir.
- CRM paketi tekrar satilabilir ve customize edilebilir olacak sekilde tasarlanir.
- Her modulde yetki, validasyon, test, bos state, hata state, loglama ve dokumantasyon tamamlanmadan adim bitmis sayilmaz.
- UI admin panelin tasarim dilini takip eder; yeni bir design system icat edilmez.
- Veri modeli musteri projelerinde kolay genisletilebilir olmali: migration'lar temiz, config'ler publish edilebilir, event'ler ve policy'ler hazir olmali.
- Is kurallari controller, Blade veya JS icine gomulmez; servis/action katmani disinda kritik hesaplama ve state transition yazilmaz.
- Urun single-tenant deploy odakli kalir ama tenant-ready mimari kararlar korunur.

## Bitmis Urun Tanimi

Bu roadmap tamamlandiginda ortaya su ozelliklerde bir CRM cikmali:

- Contacts, Companies, Deals, Deal Stages, Tasks, Quotes, Quote Items, Activities, Tags modulleri tamam.
- Contacts ve Companies icin data-table, filtre, arama, import/export, modal/form CRUD hazir.
- Deals icin pipeline Kanban, drag-drop stage degisimi, siralama, kazanildi/kaybedildi akisi hazir.
- Tasks icin due date, reminder, assignment, notification, queue/scheduler hazir.
- Quotes icin KDV, iskonto, para birimi, PDF export, durum akisi ve deal/contact/company baglantisi hazir.
- Dashboard icin stat-card metrikleri, pipeline toplam tutarlari, yaklasan gorevler, son aktiviteler hazir.
- Driver tabanli AI ile not ozetleme, email taslagi ve teklif takip mesaji uretimi hazir.
- Admin CRM frontend'i tamam; public/customer frontend sonradan ayni cekirdegin uzerine eklenebilecek mimari hazir.
- Paket olarak kurulabilir, config/view/migration publish edilebilir, yeni projelere hizlica entegre edilebilir.
- Test suite, demo seed, README, kurulum dokumani, production deploy dokumani ve QA checklist tamam.

---

## ADIM 1 — Urun Sinirlarini ve Teknik Kararlari Sabitle

```prompt
Sen senior Laravel urun mimarisinden sorumlusun. Bu projede "CRM Engine" adinda satilabilir ve tekrar kullanilabilir bir CRM altyapisi gelistireceksin.

Once mevcut repo kokunu incele:
- projeler.md
- varsa chatbot projesindeki Docker, admin panel ve roadmap yaklasimi

Ardindan crm urunu icin teknik karar dokumanini olustur:
- Laravel uygulamasi + sanalkopru/crm paketi mimarisi nasil olacak?
- Admin panel entegrasyonu nasil izole kalacak?
- Development Docker ile nasil calisacak?
- Production neden Docker kullanmayacak ve hangi mimariyle deploy edilecek?
- Hangi moduller MVP degil, satilabilir tam urun kapsamina dahil?
- Admin frontend ile CRM cekirdegi nasil ayrilacak?
- Is mantigi controller, Blade veya JS icine gomulmeden hangi servis/action katmanlarinda tutulacak?
- Cekirdek bittikten sonra public/customer frontend eklemek icin hangi hazirliklar yapilacak?

Olusturulacak dosyalar:
- README.md: urunun kisa tanimi
- docs/architecture.md: mimari kararlar
- docs/product-scope.md: modul kapsami ve bitis kriterleri

Kabul kriterleri:
- CRM'in musteriye satilabilir paket oldugu net yazilsin.
- Docker'in sadece development icin oldugu acik yazilsin.
- Production hedefinin Nginx + PHP-FPM + Supervisor + MySQL + Redis oldugu net yazilsin.
- Admin panelin /admin altinda izole calisacagi vurgulansin.
- Admin frontend'in ilk arayuz oldugu, public/customer frontend'in sonraki faz oldugu yazilsin.
- Is mantiginin Blade/controller/JS icine gomulmeyecegi mimari kural olarak yazilsin.
- Multi-tenant karari net olsun: single-tenant deploy by default, tenant-ready by design.
```

---

## ADIM 2 — Docker Development Ortamini Kur

```prompt
Mevcut crm dizininde Laravel CRM gelistirmesi icin tam Docker development ortami kur.

Olustur:
- docker-compose.yml
- docker/php/Dockerfile
- docker/nginx/default.conf
- Makefile
- .env.example

Servisler:
- app: PHP-FPM, PHP 8.3+, Composer 2, Node.js 20+, npm
- nginx: Laravel public klasorunu servis eder
- mysql: MySQL 8, database `crm`
- redis: cache, queue, session, reminder jobs
- queue: app image ile `php artisan queue:work`
- scheduler: app image ile Laravel scheduler loop
- mailpit: development email testleri icin

PHP extension'lari:
- pdo_mysql
- mbstring
- intl
- bcmath
- gd
- zip
- pcntl
- redis
- opcache

Makefile hedefleri:
- make up
- make down
- make restart
- make bash
- make composer CMD="..."
- make artisan CMD="..."
- make npm CMD="..."
- make migrate
- make fresh
- make test
- make queue
- make logs

.env.example icinde:
- APP_PORT=8081
- DB_HOST=mysql
- DB_DATABASE=crm
- DB_USERNAME=crm
- DB_PASSWORD=secret
- REDIS_HOST=redis
- QUEUE_CONNECTION=redis
- CACHE_STORE=redis
- SESSION_DRIVER=redis
- MAIL_MAILER=smtp
- MAIL_HOST=mailpit
- MAIL_PORT=1025
- CRM_CURRENCY=TRY
- CRM_TAX_RATE=20
- CRM_AI_ENABLED=false
- CRM_AI_DRIVER=openai
- CRM_AI_MODEL=
- OPENAI_API_KEY=
- OPENAI_MODEL=gpt-4o-mini
- CLAUDE_API_KEY=
- CLAUDE_MODEL=
- GEMINI_API_KEY=
- GEMINI_MODEL=

Kurallar:
- Host makinede php/composer/npm calistirma.
- Tum komutlari Docker icinden calistir.
- Production icin Docker deploy dokumani yazma; production ayri adimda Docker'siz anlatilacak.

Kabul kriterleri:
- `make up` development stack'i ayaga kaldirir.
- `make bash` app container icine girer.
- `make composer CMD="install"` container icinde calisir.
- Nginx Laravel public root'a yonlenir.
```

---

## ADIM 3 — Laravel Projesini Baslat ve Temel Paketleri Kur

```prompt
Docker app container icinde yeni Laravel projesini baslat ve CRM icin temel paketleri kur.

Yapilacaklar:
1. Laravel uygulamasini crm dizinine kur ve Laravel 12 hattina sabitle. Not: `sanalkopru/admin-panel` mevcut paketi Laravel 10/11/12 destekledigi icin Laravel 13'e gecme.
2. composer.json'a private admin panel repo bilgisini ekle:
   - type: vcs
   - url: https://github.com/ZyixQQ/admin-panel
   - Not: Repository kaydi projeler.md'deki gibi yalnizca bu VCS URL'ini icersin; ekstra repository filtreleri ekleme.
3. `sanalkopru/admin-panel` paketini kur. Private repo token'i repoya yazma; gerekirse `COMPOSER_AUTH` lokal environment uzerinden Docker container'a aktar.
4. CRM icin gerekli paketleri kur:
   - PDF uretimi icin guvenilir bir Laravel PDF paketi
   - Excel/CSV import-export icin Laravel Excel veya esdeger paket
   - AI driver altyapisi icin provider bagimsiz config; OpenAI/Claude/Gemini provider'lari ayni contract arkasindan calismali
   - permission/role ihtiyaci varsa spatie/laravel-permission
5. Admin panel view publish gerekiyorsa yap.
6. Laravel auth ihtiyacini belirle ve admin girisi icin temiz bir auth akisi kur.

Kurallar:
- Tum Composer ve Artisan komutlari Docker icinden calisacak.
- Admin panel `/admin` altinda izole olacak.
- Frontend public tarafina CRM assetleri eklenmeyecek.

Kabul kriterleri:
- Laravel acilis sayfasi container ustunden calisir.
- `/admin` rotasi admin panel mimarisine hazir hale gelir.
- `composer.json` icinde admin-panel repo kaydi vardir.
- `.env.example` CRM ve AI ayarlarini icerir.
```

---

## ADIM 4 — CRM Paket Iskeletini Olustur

```prompt
CRM'i sadece uygulama icine gomulu kod olarak degil, tekrar satilabilir Laravel paketi olarak tasarla.

Kritik mimari kural:
- Contacts, deals, tasks, quotes, AI ve notification is kurallari controller veya Blade icine yazilmaz.
- Controller katmani ince kalir: validate/authorize eder, action/service cagirir, response dondurur.
- Blade ve JS sadece sunum ve kullanici etkilesimi icindir.
- Gelecekte public quote page, customer portal, mobil uygulama veya React/Vue panel ayni servisleri kullanabilmelidir.

Olusturulacak paket yapisi:
- packages/sanalkopru/crm/composer.json
- packages/sanalkopru/crm/src/CrmServiceProvider.php
- packages/sanalkopru/crm/src/Models
- packages/sanalkopru/crm/src/Http/Controllers
- packages/sanalkopru/crm/src/Http/Requests
- packages/sanalkopru/crm/src/Policies
- packages/sanalkopru/crm/src/Services
- packages/sanalkopru/crm/src/Actions
- packages/sanalkopru/crm/src/Events
- packages/sanalkopru/crm/src/Listeners
- packages/sanalkopru/crm/src/Jobs
- packages/sanalkopru/crm/src/Notifications
- packages/sanalkopru/crm/src/routes/web.php
- packages/sanalkopru/crm/src/routes/api.php
- packages/sanalkopru/crm/database/migrations
- packages/sanalkopru/crm/database/seeders
- packages/sanalkopru/crm/resources/views
- packages/sanalkopru/crm/resources/js
- packages/sanalkopru/crm/resources/css
- packages/sanalkopru/crm/config/crm.php

ServiceProvider gorevleri:
- config merge
- migration load/publish
- view load/publish
- route load
- policy registration
- event/listener registration
- command registration

composer path repository ekle:
- Uygulama composer.json icinde `sanalkopru/crm` path repository ile gelistirilebilir olsun.

Kabul kriterleri:
- `composer require sanalkopru/crm:*` veya path repository ile paket uygulamada calisir.
- Paket config'i `php artisan vendor:publish --tag=crm-config` ile publish edilebilir.
- Paket view'lari `php artisan vendor:publish --tag=crm-views` ile publish edilebilir.
- Paket migration'lari `php artisan vendor:publish --tag=crm-migrations` ile publish edilebilir.
```

---

## ADIM 5 — Config, Feature Flags ve Customization Katmanini Yaz

```prompt
CRM'in musteriye gore hizla ozellestirilebilmesi icin config tabanli customization katmani olustur.

config/crm.php icinde sunlari tanimla:
- route_prefix: admin/crm
- middleware: web, auth
- default_currency: TRY
- supported_currencies: TRY, USD, EUR
- default_tax_rate: 20
- quote_number_prefix
- quote_number_padding
- enabled_modules:
  - contacts
  - companies
  - deals
  - tasks
  - quotes
  - activities
  - ai
- ai:
  - enabled
  - provider
  - model
  - max_tokens
  - temperature
- notifications:
  - task_reminders
  - quote_status_changes
- permissions:
  - enabled
  - roles
- ui:
  - app_name
  - primary_color

Kurallar:
- Config publish edilince musteri projesinde rahat degistirilebilir olmali.
- Kod icinde magic string yerine config kullan.
- Feature disabled ise ilgili route/menu/action gorunmemeli.

Kabul kriterleri:
- Tum moduller config ile acilip kapatilabilir.
- Para birimi ve KDV orani config'ten gelir.
- AI kapaliysa AI butonlari ve endpoint'leri calismaz/gorunmez.
```

---

## ADIM 6 — Veritabani Semasini Production Kalitesinde Kur

```prompt
CRM icin migration'lari production kalitesinde tasarla ve yaz.

Tablolar:
- contacts
- companies
- deals
- deal_stages
- tasks
- quotes
- quote_items
- activities
- tags
- tag_relations

Ek destek tablolari gerekiyorsa ekle:
- crm_settings
- crm_imports
- crm_exports
- crm_saved_filters
- crm_audit_logs veya Laravel activity log entegrasyonu

Genel kolon standartlari:
- id
- UUID gerekiyorsa public_id
- tenant/company ozellestirmesine hazir olacak sekilde nullable owner/company alanlari
- created_by
- updated_by
- timestamps
- softDeletes

contacts:
- first_name
- last_name
- full_name generated/manuel senkronize edilebilir
- email nullable indexed
- phone nullable indexed
- title
- company_id nullable
- lifecycle_stage
- source
- owner_id nullable
- last_contacted_at
- custom_fields json

companies:
- name indexed
- email
- phone
- website
- tax_number
- tax_office
- address fields
- sector
- owner_id
- custom_fields json

deal_stages:
- name
- slug
- color
- position
- probability
- is_won
- is_lost

deals:
- title
- contact_id nullable
- company_id nullable
- stage_id
- value decimal
- currency
- probability
- expected_close_date
- closed_at
- status: open, won, lost
- lost_reason
- owner_id
- position
- custom_fields json

tasks:
- title
- description
- taskable morphs
- assigned_to
- due_at
- reminder_at
- completed_at
- priority
- status

quotes:
- quote_number unique
- contact_id nullable
- company_id nullable
- deal_id nullable
- status: draft, sent, accepted, rejected, expired
- currency
- subtotal
- discount_type
- discount_value
- discount_total
- tax_rate
- tax_total
- grand_total
- valid_until
- notes
- terms
- sent_at
- accepted_at
- rejected_at

quote_items:
- quote_id
- name
- description
- quantity
- unit_price
- discount_type
- discount_value
- tax_rate
- line_total
- position

activities:
- subject
- body
- type: note, call, email, meeting, system
- activityable morphs
- user_id
- occurred_at
- metadata json

tags:
- name
- slug
- color

tag_relations:
- tag_id
- taggable morphs

Indexler:
- foreign key alanlari
- status alanlari
- owner_id
- due_at/reminder_at
- expected_close_date
- morph alanlari
- sik aranan email/phone/name alanlari

Kabul kriterleri:
- Migration'lar temiz rollback olur.
- Foreign key ve cascade davranislari bilincli secilir.
- Decimal para alanlari float kullanmaz.
- Soft delete gereken tum is nesnelerinde vardir.
- MySQL 8 ile sorunsuz calisir.
```

---

## ADIM 7 — Model, Relationship, Factory ve Seeder Katmanini Yaz

```prompt
Migration'lara uygun Eloquent model katmanini olustur.

Modeller:
- Contact
- Company
- Deal
- DealStage
- Task
- Quote
- QuoteItem
- Activity
- Tag

Her model icin:
- fillable/guarded stratejisini belirle
- casts tanimla
- relationship'leri yaz
- query scope'lari ekle
- factory olustur
- soft delete davranisini test et

Gerekli scope ornekleri:
- Contact::search($term)
- Contact::ownedBy($userId)
- Deal::open()
- Deal::won()
- Deal::lost()
- Deal::forStage($stageId)
- Task::dueSoon()
- Task::overdue()
- Quote::active()
- Quote::accepted()

Seeder:
- default deal stages
- demo companies
- demo contacts
- demo deals
- demo tasks
- demo quotes
- demo activities
- demo tags

Kabul kriterleri:
- `php artisan migrate:fresh --seed` sonrasi dolu ve anlamli demo CRM verisi gelir.
- Dashboard ve Kanban test etmek icin yeterli veri olusur.
- Factory'ler testlerde kullanilabilir.
```

---

## ADIM 8 — Yetki, Roller ve Policy Sistemini Kur

```prompt
CRM icin profesyonel yetki sistemi kur.

Roller:
- crm_owner
- crm_manager
- crm_sales
- crm_support
- crm_viewer

Permission ornekleri:
- crm.dashboard.view
- crm.contacts.view/create/update/delete/export/import
- crm.companies.view/create/update/delete/export/import
- crm.deals.view/create/update/delete/move/close
- crm.tasks.view/create/update/delete/assign/complete
- crm.quotes.view/create/update/delete/send/export/accept/reject
- crm.activities.view/create/update/delete
- crm.ai.use
- crm.settings.manage

Yapilacaklar:
- Policy siniflarini olustur.
- Controller action'larinda authorize kullan.
- Menu/action butonlarini yetkiye gore goster.
- Seeder ile roller ve permission'lari olustur.

Kurallar:
- Yetki kapaliysa config ile basit auth kontrolune dusulebilsin.
- Yetki ihlali 403 donsun.

Kabul kriterleri:
- Viewer kayit silemez.
- Sales deal tasiyabilir ama settings yonetemez.
- Manager raporlari ve tum pipeline'i gorebilir.
- Policy testleri yazilidir.
```

---

## ADIM 9 — Admin Routing, Layout ve Navigasyon Iskeletini Kur

```prompt
CRM admin ekranlarini `/admin/crm` altinda izole calisacak sekilde route ve layout yapisina bagla.

Route gruplari:
- GET /admin/crm
- resource /admin/crm/contacts
- resource /admin/crm/companies
- resource /admin/crm/deals
- GET/POST/PATCH endpoints for Kanban move
- resource /admin/crm/tasks
- resource /admin/crm/quotes
- resource /admin/crm/activities
- resource /admin/crm/tags
- POST /admin/crm/ai/summarize-note
- POST /admin/crm/ai/draft-email

Layout:
- Tum view'lar `admin-panel::layouts.app` extend eder.
- Admin panel komponentleri kullanilir: stat-card, data-table, modal, form, button, badge.
- CRM menusu admin panel navigasyonuna entegre olur.
- Aktif menu state dogru calisir.
- View'larda is kurali yazilmaz; hesaplama, durum gecisi ve kayit olusturma servis/action katmaninda kalir.
- Admin frontend ilk resmi arayuzdur, fakat CRM cekirdeginin tek kullanicisi degildir.

Kabul kriterleri:
- `/admin/crm` dashboard'a gider.
- Her modul route'u auth middleware altindadir.
- Frontend public layout etkilenmez.
- CRM assetleri sadece CRM admin sayfalarinda yuklenir.
```

---

## ADIM 10 — Contacts Modulunu Tamamla

```prompt
Contacts modulunu satilabilir urun kalitesinde tamamla.

Ekranlar:
- Contacts index: data-table
- Contact create/edit: modal veya sayfa formu
- Contact show: profil, company, deals, tasks, quotes, activities timeline

Data-table ozellikleri:
- arama: ad, soyad, email, telefon
- filtre: lifecycle_stage, source, owner, company, tag
- siralama
- pagination
- bulk delete
- bulk tag assign
- CSV/Excel export
- CSV/Excel import

Form alanlari:
- first_name
- last_name
- email
- phone
- title
- company
- lifecycle_stage
- source
- owner
- tags
- notes/activity quick add
- custom_fields icin JSON veya dinamik alan altyapisi

Validasyon:
- email format
- telefon max length
- duplicate email uyari davranisi

Kabul kriterleri:
- Contact CRUD eksiksiz calisir.
- Index buyuk veride performansli sorgu kullanir.
- Contact show sayfasi satis ekibinin ihtiyaci olan 360 derece gorunumu verir.
- Import hatali satirlari raporlar.
- Feature ve request testleri yazilir.
```

---

## ADIM 11 — Companies Modulunu Tamamla

```prompt
Companies modulunu tamamla.

Ekranlar:
- Companies index: data-table
- Company create/edit
- Company show: firma bilgisi, bagli contacts, deals, quotes, tasks, activities

Alanlar:
- name
- email
- phone
- website
- tax_number
- tax_office
- sector
- address
- owner
- tags
- custom_fields

Ozellikler:
- firma arama ve filtreleme
- duplicate company name/tax_number uyari sistemi
- contacts ile hizli iliskilendirme
- company show'da toplam deal degeri ve acik firsatlar

Kabul kriterleri:
- Company CRUD eksiksiz calisir.
- Contact formunda company secilebilir veya hizlica olusturulabilir.
- Company silinince iliskili kayitlar icin guvenli davranis vardir.
- Testler yazilir.
```

---

## ADIM 12 — Deal Stages ve Pipeline Temelini Kur

```prompt
Deal pipeline icin stage yonetimini kur.

Default stage'ler:
- Yeni
- Iletisim Kuruldu
- Teklif Hazirlaniyor
- Teklif Gonderildi
- Pazarlik
- Kazanildi
- Kaybedildi

Stage ozellikleri:
- name
- slug
- color
- position
- probability
- is_won
- is_lost

Ekran:
- Stage settings sayfasi
- stage create/update/delete/reorder

Kurallar:
- Won ve lost stage'leri sistem davranisini etkiler.
- Icinde deal olan stage silinmek istenirse once tasima istenir.
- Stage sirasi Kanban kolon sirasini belirler.

Kabul kriterleri:
- Stage seed calisir.
- Stage reorder sonrasi Kanban sirasi degisir.
- Won/lost stage davranisi test edilir.
```

---

## ADIM 13 — Deals Kanban Pipeline Modulunu Tamamla

```prompt
Deals modulunu Kanban pipeline olarak tamamla. Kanban icin SortableJS kullan.

Ekranlar:
- Deals Kanban: kolonlar stage'lere gore
- Deals list: data-table alternatifi
- Deal create/edit modal
- Deal show: detay, contact/company, tasks, quotes, activities

Kanban ozellikleri:
- drag-drop ile stage degistirme
- kolon ici siralama
- optimistic UI
- hata durumunda geri alma
- stage toplam tutar gosterimi
- kart uzerinde title, company/contact, value, expected_close_date, owner, task badge
- filtre: owner, tag, expected close date, value range, status

Backend:
- PATCH /admin/crm/deals/{deal}/move
- transaction kullan
- position hesaplama saglam olsun
- stage won ise deal status won yap, closed_at set et
- stage lost ise lost reason iste veya modal ac

Kabul kriterleri:
- Drag-drop sonrasi sayfa yenilense bile stage ve sira korunur.
- Ayni anda iki tasima istegi veri bozmaz.
- Won/lost akisinda status dogru guncellenir.
- Kanban mobilde yatay scroll ile kullanilabilir.
- Feature test ve JS davranisi icin minimum browser test veya dokumante manual QA vardir.
```

---

## ADIM 14 — Deal Detay ve Satis Aktivite Akisini Tamamla

```prompt
Deal show sayfasini satis ekibi icin merkez ekran haline getir.

Bolumler:
- deal summary
- value, probability, weighted value
- contact/company bilgisi
- stage degistirme
- next task
- quote listesi
- activities timeline
- note ekleme
- call/email/meeting activity ekleme
- AI email draft butonu

Ozellikler:
- Deal uzerinden task olusturma
- Deal uzerinden quote olusturma
- Deal uzerinden activity ekleme
- Deal kazanildi/kaybedildi aksiyonlari

Kabul kriterleri:
- Satis temsilcisi tek ekrandan deal yonetebilir.
- Timeline kronolojik ve filtrelenebilir calisir.
- Tum aksiyonlar policy ile korunur.
```

---

## ADIM 15 — Tasks, Hatirlatma ve Bildirim Sistemini Kur

```prompt
Tasks modulunu reminder ve notification sistemiyle tamamla.

Task ozellikleri:
- title
- description
- taskable morph: contact/company/deal/quote
- assigned_to
- due_at
- reminder_at
- priority: low, normal, high, urgent
- status: open, in_progress, completed, cancelled
- completed_at

Ekranlar:
- My Tasks
- All Tasks
- Overdue Tasks
- Today Tasks
- Task create/edit modal
- Contact/Company/Deal/Quote detaylarinda related tasks

Reminder:
- Laravel scheduler her dakika/5 dakika reminder kontrol eder.
- Reminder due oldugunda Notification gonderir.
- Notification kanallari: database + mail
- Reminder tekrar gonderilmesin diye notified_at alanini veya log tablosunu kullan.

Kabul kriterleri:
- Task CRUD calisir.
- Overdue ve today filtreleri dogru calisir.
- Queue worker ile notification gonderilir.
- Scheduler development'ta Docker scheduler servisiyle calisir.
- Production icin cron/scheduler dokumani vardir.
- Notification testleri yazilir.
```

---

## ADIM 16 — Activities Timeline Modulunu Tamamla

```prompt
CRM activity sistemini tamamla.

Activity tipleri:
- note
- call
- email
- meeting
- task_completed
- quote_sent
- deal_moved
- system

Ozellikler:
- Morph relation ile contact/company/deal/quote uzerine baglanabilir.
- Kullanici manuel activity ekleyebilir.
- Sistem olaylari otomatik activity olusturur.
- Timeline filtrelenebilir.
- Markdown veya guvenli rich text destegi varsa XSS korumasi uygulanir.

Event entegrasyonlari:
- DealMoved -> activity
- QuoteSent -> activity
- TaskCompleted -> activity
- ContactCreated -> activity

Kabul kriterleri:
- Her ana detay sayfasinda activity timeline gorunur.
- Sistem activity'leri otomatik yazilir.
- HTML/XSS guvenligi saglanir.
- Testler yazilir.
```

---

## ADIM 17 — Tags ve Saved Filters Katmanini Kur

```prompt
CRM icin etiketleme ve kaydedilmis filtre altyapisini kur.

Tags:
- Contact, Company, Deal, Quote uzerine morph olarak baglanabilir.
- Renk secimi vardir.
- Slug unique olur.
- Bulk tag assign/remove desteklenir.

Saved filters:
- Kullanici data-table filtre kombinasyonunu kaydedebilir.
- Saved filter modul bazli olur.
- Private/public secenegi olabilir.

Kabul kriterleri:
- Tag CRUD calisir.
- Contact/Company/Deal listelerinde tag filtreleme calisir.
- Bulk tag action calisir.
- Saved filter tekrar acildiginda ayni sonucu getirir.
```

---

## ADIM 18 — Quotes ve Quote Items Modulunu Tamamla

```prompt
Quotes modulunu production ready teklif sistemi olarak tamamla.

Ekranlar:
- Quotes index
- Quote create/edit
- Quote show
- Quote PDF preview/download
- Deal/Contact/Company detaylarinda related quotes

Quote ozellikleri:
- quote_number otomatik uretilir
- status: draft, sent, accepted, rejected, expired
- contact/company/deal baglantisi
- para birimi
- valid_until
- notes
- terms
- line items
- KDV
- iskonto: percentage veya fixed
- subtotal, discount_total, tax_total, grand_total

Line item ozellikleri:
- name
- description
- quantity
- unit_price
- discount
- tax_rate
- line_total
- reorder

Hesaplama kurallari:
- Para hesaplari decimal ile yapilir.
- Toplamlar backend servisinde hesaplanir, frontend'e guvenilmez.
- Quote update edildiginde toplamlar yeniden hesaplanir.

Durum aksiyonlari:
- send
- accept
- reject
- expire
- duplicate

Kabul kriterleri:
- Quote formu satir ekleme/silme/reorder destekler.
- KDV ve iskonto hesaplari dogrudur.
- Accepted quote ilgili deal'i won yapabilir veya opsiyon sunar.
- PDF export profesyonel gorunur.
- Hesaplama unit testleri yazilir.
```

---

## ADIM 19 — PDF Export ve Teklif Sablonu Tasarla

```prompt
Teklif PDF export sistemini kur ve profesyonel teklif sablonu tasarla.

Yapilacaklar:
- PDF service sinifi olustur.
- Quote PDF Blade template olustur.
- Firma logo/adres/vergi bilgileri config veya settings'ten gelsin.
- Musteri bilgileri net gorunsun.
- Kalemler tablo halinde gorunsun.
- Ara toplam, iskonto, KDV, genel toplam net gorunsun.
- Notes ve terms alanlari PDF'te yer alsin.
- PDF dosya adi anlamli olsun: teklif-{quote_number}.pdf

Sablon:
- Kurumsal, temiz, yazdirilabilir.
- Asiri renkli veya ajans isi abarti tasarim olmasin.
- A4 uyumlu.
- Turkce karakter sorunu olmasin.

Kabul kriterleri:
- PDF indirilebilir.
- PDF Turkce karakterleri dogru basar.
- Bos/uzun aciklama alanlari layout'u bozmaz.
- PDF test veya snapshot/manual QA notu vardir.
```

---

## ADIM 20 — Dashboard ve Raporlama Ekranini Tamamla

```prompt
CRM dashboard'u karar verdiren bir satis paneli olarak tamamla.

Stat card'lar:
- toplam contact
- toplam company
- acik deal sayisi
- acik pipeline degeri
- weighted pipeline degeri
- bu ay kazanilan deal degeri
- overdue task sayisi
- gonderilen/accepted quote sayisi

Grafik/bolumler:
- pipeline stage bazli deal degeri
- aylik won/lost trend
- yaklasan gorevler
- son aktiviteler
- en yuksek degerli acik deal'ler
- quote status dagilimi

Kurallar:
- Sorgular performansli olmali.
- Gereksiz N+1 olmamali.
- Tarih filtreleri desteklenmeli: today, this_week, this_month, custom range

Kabul kriterleri:
- Dashboard demo seed ile anlamli gorunur.
- Manager tum veriyi, sales sadece kendi verisini gorebilir.
- Stat hesaplari test edilir.
```

---

## ADIM 21 — AI: Not Ozetleme, Email Taslagi ve Takip Metni

**Durum:** Tamamlandi. AI katmani OpenAI/Claude/Gemini/null driver'lariyla provider bagimsiz contract arkasinda calisir; `.env`/config secimine gore aktif olur, API key yoksa UI aksiyonlari disabled kalir ve tum ciktilar sadece taslak olarak gosterilir.

```prompt
CRM icine AI yardimci ozelliklerini ekle.

AI ozellikleri:
1. Activity/note ozetleme
2. Deal timeline ozetleme
3. Musteriye email taslagi uretme
4. Teklif takip mesaji uretme
5. Kaybedilen deal icin kisa analiz onerisi

Mimari:
- AiProviderContract tanimla.
- AiDriverManager veya esdeger resolver olustur.
- OpenAI, Claude ve Gemini driver'larini ayni contract arkasinda calistir.
- `.env` icindeki `CRM_AI_DRIVER` degeri hangi provider'in kullanilacagini belirlesin.
- Provider'a ozel API key, base URL, model ve timeout ayarlari `config/crm.php` altindan gelsin.
- AI config'ten acilip kapatilabilir.
- Prompt template'leri merkezi dosyalarda tutulur.
- API key yoksa UI'da AI butonlari disabled olur.
- CRM servisleri OpenAI/Claude/Gemini siniflarina direkt baglanmasin; sadece provider bagimsiz servis/contract kullansin.

Guvenlik:
- AI'ya gereksiz kisisel veri gonderme.
- Prompt'a kullanici girdisi eklerken injection riskini azaltan net sinirlar koy.
- AI ciktisini direkt mail olarak gonderme; once kullaniciya taslak olarak goster.
- Loglarda hassas veri tutma.

Endpoint'ler:
- POST /admin/crm/ai/summarize
- POST /admin/crm/ai/draft-email
- POST /admin/crm/ai/follow-up

Kabul kriterleri:
- AI kapaliyken sistem tamamen calismaya devam eder.
- AI cevaplari taslak olarak gelir, otomatik aksiyon almaz.
- Timeout/hata durumunda kullanici dostu hata mesaji doner.
- AI servisleri mock'lanarak test edilir.
```

---

## ADIM 22 — Import, Export ve Veri Tasima Ozelliklerini Tamamla

**Durum:** Tamamlandi. Contacts, companies ve deals icin CSV/XLSX import; contacts, companies, deals ve quotes icin filtreli CSV export; preview, template, import log, indirilebilir hata raporu ve buyuk importlarda queue akisi eklendi.

```prompt
CRM icin profesyonel import/export ozelliklerini ekle.

Import:
- contacts CSV/XLSX
- companies CSV/XLSX
- deals CSV/XLSX
- field mapping ekrani veya basit kolon standardi
- preview
- validation errors
- duplicate detection
- import result report

Export:
- contacts
- companies
- deals
- quotes
- filtrelenmis sonuc export
- yetkiye bagli export

Teknik:
- Buyuk import islemleri queue ile calissin.
- Import log tablosu veya crm_imports kullan.
- Hatali satirlar indirilebilir rapor olsun.

Kabul kriterleri:
- Ornek CSV template dosyalari olusturulur.
- Import hatali satirda tum islemi sessizce bozmaz.
- Export policy ile korunur.
- Import/export testleri yazilir.
```

---

## ADIM 23 — Global Search, Filtreleme ve UX Detaylarini Tamamla

```prompt
CRM genelinde kullanici deneyimini profesyonel hale getir.

Ozellikler:
- Global search: contact, company, deal, quote arar
- Data-table empty state
- Loading state
- Error state
- Confirm modal
- Toast notifications
- Keyboard-friendly form navigation
- Date/time formatlari
- Currency formatlari
- Badge renkleri
- Responsive admin ekranlari

Arama:
- Basit MySQL LIKE/fulltext ile basla.
- Kod mimarisi ileride Meilisearch/Scout'a gecmeye uygun olsun.

Kabul kriterleri:
- Kullanici kaybolmadan CRM'i kullanabilir.
- Bos listelerde anlamli aksiyon butonu vardir.
- Form hatalari alan bazli gorunur.
- Para ve tarih formatlari Turkiye satis is akisi icin dogru gorunur.
```

---

## ADIM 24 — Settings Modulunu ve Marka Ozellestirmesini Ekle

```prompt
CRM settings ekranini ekle.

Settings alanlari:
- company_name
- company_logo
- company_email
- company_phone
- company_address
- tax_number
- tax_office
- default_currency
- default_tax_rate
- quote_prefix
- quote_terms
- notification preferences
- AI enabled/driver/model ayarlari

Kurallar:
- Settings config default'larini override edebilir.
- Logo upload guvenli ve validate edilmis olmali.
- Settings sadece yetkili kullanici tarafindan guncellenebilir.

Kabul kriterleri:
- Quote PDF firma bilgilerini settings'ten alir.
- Default KDV/para birimi settings'ten gelir.
- Policy testleri yazilir.
```

---

## ADIM 25 — API Katmanini Hazirla

```prompt
CRM icin opsiyonel API katmanini kur. Bu API ileride mobil uygulama, entegrasyon veya musteri ozel istekleri icin kullanilacak.

API route prefix:
- /api/crm

Endpoint'ler:
- contacts index/show/create/update
- companies index/show/create/update
- deals index/show/create/update/move
- tasks index/show/create/update/complete
- quotes index/show/create/update

Teknik:
- Laravel API Resource kullan.
- Token auth icin Sanctum veya mevcut auth mimarisine uygun cozum sec.
- Rate limiting ekle.
- Policy kontrolleri API'da da calissin.
- API response formatlari tutarli olsun.
- API, ileride public/customer frontend veya mobil uygulama eklenebilmesi icin admin controller'lardan bagimsiz tasarlanir.
- API controller'lari da ayni service/action katmanini kullanir; is mantigi ikinci kez yazilmaz.

Kabul kriterleri:
- API dokumani docs/api.md icinde vardir.
- Yetkisiz istekler 401/403 doner.
- Validation hatalari tutarli JSON doner.
- API feature testleri yazilir.
```

---

## ADIM 26 — Audit Log, Guvenlik ve Veri Koruma Katmanini Tamamla

```prompt
CRM'i kurumsal musteriye satilabilir guvenlik seviyesine getir.

Yapilacaklar:
- kritik olaylar icin audit log:
  - contact created/updated/deleted
  - deal moved/won/lost
  - quote sent/accepted/rejected
  - settings changed
  - import/export started
- request validation tum formlarda tam olsun
- authorization tum controller action'larinda olsun
- mass assignment riski kontrol edilsin
- file upload guvenligi saglansin
- XSS ve unsafe HTML kontrol edilsin
- CSRF web formlarinda aktif olsun
- rate limit AI ve API endpoint'lerinde olsun
- hassas veriler loglanmasin

Kabul kriterleri:
- Audit log ekrani veya en azindan servis/test altyapisi vardir.
- Guvenlik checklist dokumani yazilir.
- Policy coverage kontrol edilir.
- Import/upload dosyalari validate edilir.
```

---

## ADIM 27 — Test Suite'i Production Seviyesine Cikar

```prompt
CRM icin kapsamli test suite yaz.

Test kategorileri:
- Unit:
  - quote total calculation
  - deal stage status transition
  - task reminder selection
  - AI prompt service mock
- Feature:
  - contacts CRUD
  - companies CRUD
  - deals CRUD and move
  - tasks CRUD and complete
  - quotes CRUD and PDF
  - import/export
  - dashboard metrics
  - permissions/policies
- Browser veya minimum UI smoke:
  - Kanban drag-drop manual/browser test
  - quote form line item behavior

Kurallar:
- Test verisi factory ile uretilsin.
- AI ve mail servisleri fake/mock kullanilsin.
- Queue/Notification testleri fake ile dogrulansin.

Kabul kriterleri:
- `make test` container icinde calisir.
- Kritik is kurallari testlidir.
- Yeni musteri ozellestirmeleri yaparken regresyon riski dusuktur.
```

---

## ADIM 28 — Performance, Index ve Query Optimizasyonu Yap

```prompt
CRM'i buyuyen musteri verisine hazir hale getir.

Kontroller:
- data-table sorgularinda N+1 var mi?
- dashboard sorgulari gereksiz agir mi?
- Kanban tum deal'leri tek seferde cekip sisiyor mu?
- import/export memory kullanimlari guvenli mi?
- indexler yeterli mi?

Yapilacaklar:
- eager loading ekle
- pagination/limit uygula
- dashboard aggregate sorgularini optimize et
- Kanban filtre ve limit stratejisi belirle
- sik sorgulara index ekle
- queue kullanilmasi gereken agir islemleri queue'ya al

Kabul kriterleri:
- 10.000 contact, 2.000 company, 5.000 deal demo/perf seed ile temel ekranlar kullanilabilir kalir.
- Debugbar veya query log ile N+1 kontrolu yapilir.
- Performance notlari docs/performance.md icine yazilir.
```

---

## ADIM 29 — Dokumantasyon ve Demo Paketini Hazirla

```prompt
CRM'i satilabilir urun gibi dokumante et.

Dokumanlar:
- README.md
- docs/installation.md
- docs/development-docker.md
- docs/production-deploy-no-docker.md
- docs/modules.md
- docs/customization.md
- docs/api.md
- docs/qa-checklist.md
- docs/troubleshooting.md

README icinde:
- urun tanimi
- ozellik listesi
- development kurulum
- admin panel entegrasyonu
- paket publish komutlari
- test komutlari
- production deploy linki

Demo:
- demo seed komutu
- demo kullanici bilgileri
- demo pipeline verisi

Kabul kriterleri:
- Yeni bir developer sadece README ile development ortamini acabilir.
- Satis/demo icin dolu veri tek komutla gelir.
- Production deploy dokumani Docker kullanmaz.
```

---

## ADIM 30 — Docker'siz Production Deploy Rehberini Yaz ve Hazirla

```prompt
Production'da Docker kullanmadan CRM deploy rehberini hazirla.

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

Dokumanda anlat:
- server requirements
- PHP extension listesi
- env ayarlari
- composer install --no-dev
- npm build gerekiyorsa build stratejisi
- php artisan key:generate
- php artisan migrate --force
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache
- storage symlink
- file permissions
- Nginx server block
- PHP-FPM pool notlari
- Supervisor queue config
- cron scheduler satiri
- log rotation
- backup stratejisi
- rollback stratejisi

Kurallar:
- Production deploy icin Docker veya docker compose onermeyeceksin.
- Development Docker kullanimiyla production mimarisini karistirmayacaksin.

Kabul kriterleri:
- docs/production-deploy-no-docker.md tamamlanir.
- Bir musteri sunucusuna Docker'siz kurulum icin yeterli netlik vardir.
- Queue ve scheduler production'da nasil calisacak acik yazilir.
```

---

## ADIM 31 — Paketleme, Versiyonlama ve Release Hazirligi

```prompt
CRM paketini private repo olarak yayinlanmaya hazir hale getir.

Yapilacaklar:
- package composer.json metadata tamamla
- semantic versioning stratejisi yaz
- CHANGELOG.md olustur
- LICENSE veya private license notu ekle
- release checklist olustur
- config/view/migration publish tag'lerini netlestir
- minimum PHP/Laravel version constraint'lerini belirle
- admin-panel dependency versiyonunu sabitle: ^1.0 gibi

Composer kullanim hedefi:
- composer require sanalkopru/crm
- php artisan vendor:publish --tag=crm-config
- php artisan vendor:publish --tag=crm-migrations
- php artisan migrate

Kabul kriterleri:
- Paket baska Laravel projesine kurulabilir.
- Versiyonlama musteri projelerini bozmayacak sekilde planlidir.
- Release checklist docs/release-checklist.md icinde vardir.
```

---

## ADIM 32 — Son QA, Satisa Hazirlik ve Kabul Testi

```prompt
CRM'i satisa hazir kabul testinden gecir.

Manual QA senaryolari:
1. Yeni contact olustur.
2. Contact'a company bagla.
3. Deal olustur.
4. Deal'i Kanban'da stage'ler arasinda tasi.
5. Deal icin task olustur ve reminder ayarla.
6. Scheduler/queue ile reminder notification test et.
7. Deal icin quote olustur.
8. Quote'a item ekle, KDV/iskonto hesaplarini kontrol et.
9. PDF indir.
10. Quote'u accepted yap ve deal durumunu kontrol et.
11. Activity timeline'in otomatik doldugunu kontrol et.
12. AI ile email taslagi olustur.
13. Contact export al.
14. Contact import dene.
15. Viewer rolunde silme yapilamadigini kontrol et.
16. Manager rolunde dashboard metriklerini kontrol et.

Otomatik kontroller:
- make test
- composer validate
- php artisan route:list
- php artisan config:cache
- php artisan migrate:fresh --seed
- npm build veya ilgili asset build komutu

Son dokuman:
- docs/final-acceptance.md olustur.
- Hangi senaryolar gecti, hangi riskler kaldi yaz.

Kabul kriterleri:
- Kritik hata kalmaz.
- Testler gecer.
- Demo veriyle urun sunulabilir gorunur.
- Yeni musteriye kurulum icin README ve production dokumani yeterlidir.
```

---

## Onerilen Uygulama Sirasi

```prompt
Bu CRM roadmap'ini uygulamaya basla. Adimlari sirasiyla ilerlet:

1. Once Docker development ortamini ve Laravel temelini kur.
2. Sonra paket iskeleti, config ve migration'lari tamamla.
3. Ardindan model/factory/seeder ve admin routing katmanini kur.
4. Contacts, Companies, Deals, Tasks, Quotes modullerini sirayla bitir.
5. Dashboard, AI, import/export, settings ve API katmanini ekle.
6. Test, performance, dokumantasyon ve Docker'siz production deploy rehberini tamamla.
7. En sonunda QA checklist'i calistir ve final acceptance dokumani yaz.

Her adimdan sonra:
- Degisen dosyalari ozetle.
- Calistirilan komutlari yaz.
- Test sonucunu belirt.
- Eksik veya risk varsa saklama, acikca yaz.

Unutma:
- Profesyoneliz.
- Amac hizli satilabilir ama kalitesiz olmayan bir altyapi cikarmak.
- Docker development icindir; production'da Docker yoktur.
```
