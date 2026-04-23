# Yapilacak Guncellemeler

Bu dosya `test-notlari.txt` icindeki manuel test sonuclari ve alinan kararlar uzerinden hazirlandi.

Amaç:

- CRM paket mimarisini korumak.
- Admin-panel paketini genel UI altyapisi olarak guclendirmek.
- CRM tarafinda is kurallarini ve CRM'e ozel ekran davranislarini iyilestirmek.
- Manuel testte yakalanan buglari kapatmak.
- UX'i modernlestirmek ama sistemi gereksiz SPA karmasasina sokmamak.

## Alinan Kararlar

1. CRM paket olarak devam edecek.
2. Genel UI componentleri `sanalkopru/admin-panel` paketine yazilacak.
3. Hibrit UX uygulanacak; kritik hizli aksiyonlar AJAX/dinamik olacak, buyuk formlar klasik Laravel form submit kalabilecek.
4. Liste filtreleri compact filter bar + advanced filters yapisina gecirilecek.
5. Bulk quick action sistemi global admin-panel componenti olacak.
6. Sidebar gruplu, nested active state destekli ve permission-aware olacak.
7. Kanban drag-drop sonrasi stage count/value anlik guncellenecek.
8. Show/edit ekranlarina profesyonel UI polish yapilacak.
9. `/admin/users` gercek admin user/role management modulune donusecek.
10. Delete davranisi tum CRUD'larda standart, permission-aware ve confirm modal'li olacak.
11. Import preview structured table + validation summary olacak.
12. `quotes-tanitim.txt` yazilacak ve quote ekranlarina kisa urun ici yardim eklenecek.
13. `ai-sayfalar.txt` liste + beklenen cikti + manuel test adimi icerecek.
14. `crm:seed-demo` ve `crm:seed-performance` artisan komutlari eklenecek; package/host mimarisi netlestirilecek.
15. Global search navbar command palette olarak admin-panel seviyesinde yapilacak.
16. Modern custom select component admin-panel package seviyesinde yazilacak.
17. Filter ve pagination progressive AJAX olacak; URL state korunacak.
18. Profil badge oncelikli ana CRM rolunu gosterecek.
19. Dashboard period sadece zaman bazli metrikleri etkileyecek; UI bunu net anlatacak ve demo tarihleri iyilestirilecek.
20. Admin-panel CSS reset sade olacak; katmanli CSS sirasi kurulacak.

## Kapsam Ayirimi

### Admin-panel Paketinde Yapilacaklar

Genel UI ve layout altyapisi burada cozulur:

- Modern custom select component.
- Compact filter bar ve advanced filters componenti.
- Progressive AJAX list/pagination altyapisi.
- Bulk quick action componenti.
- Confirm modal ve toast/loading state componentleri.
- Navbar command palette search altyapisi.
- Sidebar grouping, nested active matching ve permission-aware link gorunurlugu.
- Profil badge role label destegi.
- Katmanli CSS mimarisi ve reset duzeltmesi.
- Global component padding/margin sorunlarini cozen CSS sirasi.

### CRM Paketinde Yapilacaklar

CRM'e ozel is akislari ve domain ekranlari burada kalir:

- Deals Kanban aggregate guncelleme.
- CRM dashboard metrik ve period davranisi.
- CRM import/export logic ve preview.
- CRM saved filter entegrasyonunun global filter componentine baglanmasi.
- Quote item editor ve quote hesaplama yardimlari.
- AI aksiyon yuzeyleri.
- CRM user/role management policy entegrasyonu.
- CRM show/edit ekranlarinin domain yerlesimleri.
- Seed komutlari.
- CRM dokumanlari.

### Root Laravel Uygulamasi

Root uygulama development/demo host olarak kalir:

- `routes/web.php`: host app route'lari ve admin login route'lari.
- `database/seeders`: demo host icin seeder wiring.
- `.env.example`, Docker ve Makefile: development deneyimi.
- Asil urun kodu `packages/sanalkopru/crm` icinde kalir.

## Onceliklendirme

## Uygulama Durumu

### 2026-04-23 - Faz 1 ve Faz 2 Ilk Gecis

Tamamlananlar:

- Import preview structured table + validation summary haline getirildi.
- Eksik optional import kolonlari notice/error uretmeden guvenli defaultlara baglandi.
- `crm:seed-demo` ve `crm:seed-performance` komutlari eklendi.
- Demo seeder tarihleri dashboard period testine uygun hale getirildi.
- Dashboard snapshot/time-based metric ayrimi UI'da netlestirildi.
- Quote create select option bos label sorunu duzeltildi.
- Contacts, companies, deals, tasks ve quotes delete aksiyonlari index/show ekranlarinda permission-aware ve confirm'li hale getirildi.
- `quotes-tanitim.txt` ve `ai-sayfalar.txt` yazildi.
- Quote create/show ekranlarina kisa urun ici yardim eklendi.
- Package/host/admin-panel mimari ayrimi `docs/architecture.md` icinde netlestirildi.

Dogrulama:

- Pint formatter basarili.
- Route/config/view cache basarili; ardindan dev ortaminda cache temizlendi.
- Full test suite basarili: `131 passed (1041 assertions)`.

### 2026-04-23 - Faz 2 Kapanis

Durum:

- Faz 2 tamamlandi.
- Quote ve AI kavram dosyalari README ve manuel test rehberi icinden bulunabilir hale getirildi.
- Quote create/show kisa yardim metinleri feature test ile korumaya alindi.
- Package/host/admin-panel ayrimi icinde path repository/dirty subrepo gorunumunun mimari hata olmadigi net yazildi.

Dogrulama:

- Pint formatter basarili.
- `CrmQuotesModuleTest` basarili: `6 passed (66 assertions)`.
- `git diff --check` temiz.

### 2026-04-23 - Faz 3 Kapanis

Durum:

- Faz 3 tamamlandi.
- Admin-panel CSS import sirasi cascade layer yapisina alindi: tokens, reset, layout, components, utilities, package-overrides.
- Agresif global reset azaltildi; universal reset artik margin/padding sifirlamiyor.
- `x-admin-panel::select` package seviyesinde modern custom select altyapisina baglandi. Native select form submit uyumlulugu korunuyor; single/multiple, search, chips, clear, disabled/error/help state destekleri eklendi.
- Admin-panel JS icine reusable `AdminPanel.confirm`, `AdminPanel.toast`, form loading state ve command palette altyapisi eklendi.
- CRM delete confirm akisi yeni admin-panel confirm modalini kullanacak sekilde guncellendi.
- Navbar command palette eklendi; `Cmd+K`, `Ctrl+K` ve `/` ile aciliyor, CRM navigation ve CRM search submit destekliyor.
- CRM sidebar navigation gruplandi: Overview, Sales, Customers, Operations, System.
- Sidebar dropdown nested active state ile aktif grup acik geliyor ve permission-aware item rendering korunuyor.
- Profil badge oncelikli CRM rol siralamasina baglandi: Owner, Manager, Sales, Support, Viewer, sonra Staff.
- Admin-panel ve CRM public assetleri publish edildi.

Dogrulama:

- Pint formatter basarili.
- `CrmUiSmokeTest` basarili: `2 passed (16 assertions)`.
- `CrmGlobalSearchUxTest` basarili: `3 passed (18 assertions)`.
- `CrmAdminRoutingTest` basarili: `7 passed (125 assertions)`.
- Route/config/view cache basarili; ardindan dev cache temizlendi.
- Full test suite basarili: `131 passed (1050 assertions)`.
- `git diff --check` temiz.

### 2026-04-23 - Faz 4 Kapanis

Durum:

- Faz 4 tamamlandi.
- `x-admin-panel::filter-shell` ve `x-admin-panel::bulk-actions` package seviyesinde liste UX standardi olarak eklendi.
- Contacts, companies, deals, tasks, quotes ve activities index ekranlari compact filter bar + advanced filters yapisina tasindi.
- Active filter count, clear filters ve saved filters akisi tum uygun CRM listelerinde standartlastirildi.
- Progressive AJAX filtre/pagination altyapisi admin-panel JS tarafinda list region bazli calisacak sekilde baglandi.
- Header disindaki scope/view switch aksiyonlari icin `data-admin-ajax-target` destegi eklendi; tasks scope butonlari ve deals kanban/list switch tam sayfa yenilemeden ayni liste regionunu guncelleyebiliyor.
- Contacts listesinde global bulk quick action bar devreye alindi; secili kayit sayisi, select-all ve custom aksiyon slotlari package componentiyle yonetiliyor.
- Saved filter modulleri `tasks`, `quotes` ve `activities` icin de store/apply seviyesinde tamamlandi.

Dogrulama:

- Admin-panel ve CRM asset publish islemleri basarili.
- Pint formatter basarili.
- Hedefli test paketi basarili: `26 passed (188 assertions)`.
- `php artisan route:cache`, `php artisan config:cache`, `php artisan view:cache` basarili; ardindan dev ortaminda `php artisan optimize:clear` calistirildi.
- Full test suite basarili: `133 passed (1076 assertions)`.

### Faz 1 - Kritik Bugfix ve Test Edilebilirlik

Bu faz once yapilmali. Amac kullanicinin sistemi rahat test edebilmesi.

1. Import bug'larini duzelt.
   - `Undefined array key "lifecycle_stage"` hatasini merkezi olarak coz.
   - Contacts, companies ve deals import akisini ayni validator/normalizer mantigiyla guvenli hale getir.
   - Eksik kolonlarda notice/error yerine validation mesajlari uret.

2. Performance seeder komutlarini ekle.
   - `php artisan crm:seed-demo`
   - `php artisan crm:seed-performance`
   - Make kullanimini dokumana ekle:
     - `make artisan CMD="crm:seed-demo"`
     - `make artisan CMD="crm:seed-performance"`

3. Admin login ve route cache davranisini koru.
   - `admin.login` ve `admin.login.post` route'lari test edilmeye devam edecek.
   - Closure route birakilmadigi dogrulanacak.
   - `route:cache`, `config:cache`, `view:cache` test edilecek.

4. Dashboard period davranisini netlestir.
   - Time-based metrikler perioddan etkilenecek.
   - Snapshot metrikler perioddan etkilenmeyecek.
   - UI'da kisa aciklama veya section ayrimi eklenecek.
   - Demo seed tarihleri today/week/month/custom farkini gosterecek sekilde iyilestirilecek.

5. Quote select option bosluklarini duzelt.
   - `/admin/quotes/create` contact ve deal selectlerinde bos string option gorunmeyecek.
   - Option label fallback'leri merkezi hale getirilecek.

6. Delete aksiyonlarini gorunur ve standart yap.
   - Contact delete bulunabilir olacak.
   - Index/show delete aksiyonlari permission-aware olacak.
   - Confirm modal sonraki admin-panel component fazina kadar basit confirm ile korunabilir.

### Faz 2 - Dokuman ve Kavram Netlestirme

Bu faz kullanicinin urunu anlamasini kolaylastirir.

1. `quotes-tanitim.txt` yaz.
   - Quote nedir?
   - Quote item nedir?
   - Quote ve quote item fiyat/iskonto farki nedir?
   - Duplicate quote neden gerekir?
   - Status'ler ne anlama gelir?
   - Deal/contact/company iliskisi nedir?
   - PDF ne icin kullanilir?

2. Quote ekranlarina kisa urun ici yardim ekle.
   - Create/show ekranlarinda ekran bozmayan info hint veya tooltip kullan.
   - Uzun egitim metni ekleme; detay `quotes-tanitim.txt` icinde kalsin.

3. `ai-sayfalar.txt` yaz.
   - AI olan sayfa.
   - Aksiyon.
   - Kullanilan context.
   - Beklenen cikti.
   - AI kapaliyken davranis.
   - AI acikken manuel test adimi.
   - Kayit degistirip degistirmedigi.

4. Package/host mimari dokumanini netlestir.
   - `packages/sanalkopru/crm` asil urun paketidir.
   - Root app development/demo host'tur.
   - `packages/sanalkopru/admin-panel` genel admin UI paketidir.
   - Root ve package klasorlerinin neden paralel bulundugu aciklanir.
   - Dirty subrepo/path repo durumunun mimari hata olmadigi, repo yonetimi konusu oldugu yazilir.

### Faz 3 - Admin-panel UI Temeli

Bu faz CRM disinda admin-panel package seviyesinde yapilacak ana UI altyapisidir.

1. CSS reset ve katmanli mimariyi kur.
   - Agresif global resetleri kaldir veya sinirla.
   - CSS sirasi:
     1. tokens/variables
     2. reset/base
     3. layout
     4. components
     5. utilities
     6. package overrides
   - Gerekirse CSS cascade layer kullan.

2. Modern custom select component ekle.
   - Single select.
   - Multiple select.
   - Searchable select.
   - Selected chips.
   - Clear button.
   - Disabled/error/help state.
   - Form submit uyumlulugu.
   - CRM selectleri bu componente tasinacak.

3. Confirm modal, toast ve loading state componentlerini standartlastir.
   - Delete ve kritik aksiyonlar icin confirm modal.
   - AJAX islemler icin loading.
   - Basarili/hata durumlari icin toast/status.

4. Navbar command palette search altyapisini ekle.
   - Navbar'da search trigger.
   - Overlay/command palette.
   - `/` veya `Cmd+K` kisayolu.
   - Grouped result rendering.
   - Mobile full-screen overlay davranisi.

5. Sidebar componentini guclendir.
   - Group heading destegi.
   - Nested active matching.
   - Permission-aware link rendering.
   - CRM navigation gruplari:
     - Overview
     - Sales
     - Customers
     - Operations
     - System

6. Profil badge role label destegi ekle.
   - Oncelik sirasi:
     1. Owner
     2. Manager
     3. Sales
     4. Support
     5. Viewer
   - CRM rolu yoksa `Staff`.

### Faz 4 - Liste UX Sistemi

Bu faz admin-panel altyapisini CRM liste ekranlarina uygular.

1. Compact filter bar + advanced filters.
   - Listeyi asagi itmeyen kompakt bar.
   - Advanced filters collapsible alan.
   - Active filter count badge.
   - Clear filters.
   - Saved filters entegrasyonu.
   - Mobilde dar/drawer benzeri davranis.

2. Progressive AJAX filter/pagination.
   - URL query state korunur.
   - JS varsa partial list update.
   - JS yoksa klasik form submit/pagination.
   - Browser history guncellenir.
   - Loading ve hata state'leri olur.

3. Global bulk quick action component.
   - Table checkbox selection.
   - En az 1 secim yapilinca floating/compact action bar.
   - Default `Delete selected`.
   - Moduller custom action ekleyebilir.
   - Confirm modal ve AJAX update.

4. Saved filters tum uygun CRM listelerine standart uygulanir.
   - Contacts.
   - Companies.
   - Deals.
   - Tasks.
   - Quotes.
   - Activities.
   - Gerekliyse Tags.

### 2026-04-23 - Faz 5 Kapanis

Durum:

- Faz 5 tamamlandi.
- Kanban drag-drop sonrasi `DealsController::move()` endpoint etkilenen stage'lerin anlık `deals_count` ve `pipeline_value` verilerini JSON olarak donuyor; `CrmFormatter` ile PHP tarafinda formatlaniyor.
- `crm.js`'e `updateKanbanAggregates()` eklendi; basarili move sonrasi stage kolon basliklarindaki sayac ve tutar anlık guncelleniyor.
- Kanban move basarili/hatali durumlar icin `AdminPanel.toast()` mesajlari eklendi; `window.alert` kaldirildi.
- `data-crm-ajax-form` attr'li formlar `crm.js`'teki `initializeAjaxForms()` ile AJAX olarak gondeiliyor: `Accept: application/json` header, JSON yanit, toast, AI result inline display, region reload, redirect ve form reset destekleri.
- Deal show sayfasinda stage degistirme, close won/lost, task ekleme, activity ekleme ve AI draft/summary formlari AJAX'a alindi.
- Task ekleme formu basarili submit sonrasi `crm-deal-tasks-list` regionunu; activity ekleme formu `crm-deal-timeline` regionunu sayfayi yenilemeden guncellıyor.
- AI sonuclari (`data-crm-ai-result`) deal show ve quote show sayfalarinda inline gosteriliyor; sayfayi yenilemeden taslagin ustunde cikiyor.
- Quote show sayfasinda send/accept/reject/expire/AI follow-up formlari AJAX'a alindi; basarili submit sonrasi toast + ayni sayfaya redirect yapiliyor.
- `DataTransferController::preview()` AJAX isteginde `_preview.blade.php` partialini HTML olarak dondüruyor; `initializeImportPreviewForms()` dosya yukleyince once preview endpoint'e AJAX POST yapiyor, gelen HTML'i sayfa yenilemeden preview bolumune injekte ediyor.
- `DealsController`, `TasksController` ve `QuotesController` status aksiyonlari `$request->expectsJson()` kontrolu ile geri donusluluk korunarak JSON/redirect destekli hale getirildi.

Dogrulama:

- Pint formatter basarili.
- Full test suite basarili: `133 passed (1076 assertions)`.
- Route/config/view cache basarili; ardindan dev cache temizlendi.
- `git diff --check` temiz.

### 2026-04-24 - Faz 6 Kapanis

Durum:

- Faz 6 tamamlandi.
- `/admin/users` artik CRM user management modulune yonleniyor; users index, create ve edit ekranlari aktif.
- CRM rol atama akisi `crm_owner`, `crm_manager`, `crm_sales`, `crm_support` ve `crm_viewer` rollerini destekliyor.
- Kullanici olusturma ve guncellemede password set/change akisi aktif.
- Kullanici aktif/pasif durumu `is_active` alani ve toggle aksiyonu ile yonetiliyor.
- Son owner rolu kaldirma, son owner'i pasiflestirme ve kullanicinin kendi hesabini pasiflestirme engelleri uygulandi.
- Pasif kullanicilar `EnsureCrmAccess` middleware'i ile CRM'den bloke edilip login ekranina yonlendiriliyor.
- Sidebar Users linki permission-aware navigation icinde gosteriliyor.
- Feature test kapsaminda user management ve inactive account senaryolari koruma altina alindi.

Dogrulama:

- `CrmUsersModuleTest` dahil full test suite basarili: `145 passed (1104 assertions)`.
- Root `composer.json` ve package `packages/sanalkopru/crm/composer.json` strict validate basarili.
- `php artisan route:cache`, `php artisan config:cache`, `php artisan view:cache` basarili; ardindan dev cache temizlendi.
- `git diff --check` temiz.

### 2026-04-24 - Faz 7 Kapanis

Durum:

- Faz 7 tamamlandi.
- Contact, company, deal, task ve activity show ekranlari daha okunabilir badge/link/timeline yapisina tasindi.
- Ortak `_timeline.blade.php` partial'i ile activity type badge, system activity ayrimi ve daha okunabilir meta satiri standartlastirildi.
- Create/edit ekranlarinda action ayrimi, select/multiple select stilleri ve genel spacing polish guncellendi.
- Empty state kullanimi liste ve detay ekranlarinda ortak partial uzerinden korunuyor.
- Deal stage reorder ekraninda ve saved filter panelinde UX follow-up duzeltmeleri yapildi.
- Admin-panel custom select icin panel/portal fixleri son iki submodule commit ile toparlandi.

Dogrulama:

- Kullanici manuel smoke testleri basarili bildirildi.
- Full test suite basarili: `145 passed (1104 assertions)`.
- Root ve package composer validate basarili.
- Route/config/view cache basarili; ardindan dev cache temizlendi.
- `git diff --check` temiz.

### Faz 5 - CRM Dinamik Aksiyonlar

Bu faz hibrit AJAX kararini CRM'e uygular.

1. Kanban drag-drop aggregate guncelleme.
   - Move endpoint JSON response doner.
   - Stage count/value guncellenir.
   - `has_more_deals` ve limit metni guncellenir.
   - Hata olursa kart eski yerine doner.
   - Toast mesajlari eklenir.

2. Show sayfasi quick action AJAX entegrasyonu.
   - Task complete.
   - Quick note add.
   - Quote send/accept/reject/expire.
   - Deal won/lost/move.
   - AI draft/summary.
   - Kayit silme confirm modal ile korunur.

3. Import preview AJAX/structured hale getirilir.
   - Preview tablo guncellenir.
   - Validation summary gosterilir.
   - Hata satirlari/hatalı hucreler isaretlenir.

### Faz 6 - Admin User/Role Management

Bu faz `/admin/users` beklentisini gercek module cevirir.

1. `/admin/users` redirect olmaktan cikar.
2. Kullanici listesi eklenir.
3. Kullanici create/edit formlari eklenir.
4. Roller atanabilir:
   - `crm_owner`
   - `crm_manager`
   - `crm_sales`
   - `crm_support`
   - `crm_viewer`
5. Password set/change akisi olur.
6. Kullanici aktif/pasif yapilabilir.
7. Guvenlik kurallari:
   - Son owner rolu kaldirilamaz.
   - Kullanici kendi hesabini pasif yapamaz.
   - Yetkisiz roller users ekranini gormez.
8. Sidebar Users linki permission-aware olur.
9. Testler eklenir.

### Faz 7 - UI Polish

Bu faz satilabilir urun hissini guclendirir.

1. Show ekranlari polish.
   - Contact show.
   - Company show.
   - Deal show.
   - Task show.
   - Quote show.
   - Activity show.

2. Edit/create ekranlari polish.
   - Daha net form section'lari.
   - Primary/secondary action ayrimi.
   - Daha okunabilir validation state'leri.

3. Timeline polish.
   - Activity type badge.
   - Daha okunabilir tarih/kullanici bilgisi.
   - System activity ayrimi.

4. Badge ve status standardi.
   - Deal status.
   - Quote status.
   - Task priority/status.
   - Activity type.

5. Empty state standardi.
   - Bos listelerde anlamli aksiyonlar.
   - Gereksiz uzun metin yok.

## Bugfix Listesi

Bu maddeler karar beklemez; uygulanacak.

1. `/admin/users` owner icin redirect olmamalı; user management modulune donusmeli.
2. Sag ust profil badge `staff` yerine ana CRM rolunu gostermeli.
3. Yetkisiz roller sidebar'da girememeleri gereken linkleri gormemeli.
4. Multiple select ve normal select stilleri eksik; admin-panel custom select ile cozulmeli.
5. Dashboard period filtresinin hangi metrikleri etkiledigi net degil; UI ve demo data duzeltilmeli.
6. Delete butonlari/aksiyonlari tum CRUD'larda bulunabilir ve standart olmali.
7. Inner sayfalarda sidebar parent link active kalmali.
8. Kanban stage count/value drag-drop sonrasi anlik guncellenmeli.
9. Quote create contact/deal selectlerinde bos option label'lari duzeltilmeli.
10. Import `Undefined array key` bug'lari merkezi duzeltilmeli.
11. Import preview okunabilir tabloya cevrilmeli.
12. Performance seeder namespace komutu yerine artisan command eklenmeli.
13. Sayfa yenileyen quick actionlar kritik yerlerde AJAX'a alinmali.
14. Search sayfa icinden navbar command palette'e tasinmali.
15. Filtre panelleri fazla yer kaplamayacak compact sisteme gecmeli.
16. Contacts'e ozel bulk action yapisi global hale getirilmeli.
17. Global CSS reset component paddinglerini bozmamali.
18. Sidebar link hiyerarsisi gruplara ayrilmali.
19. Show/edit ekranlari profesyonel polish almali.

## Test Plani

Her faz sonunda su kontroller yapilacak:

```bash
docker compose exec -T app ./vendor/bin/pint --ansi
docker compose exec -T app php artisan test
docker compose exec -T app composer validate --strict
docker compose exec -T app composer validate --strict packages/sanalkopru/crm/composer.json
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan optimize:clear
git diff --check
```

Manual smoke:

1. Login.
2. Dashboard period filtreleri.
3. Contacts CRUD/delete/import/export.
4. Companies CRUD/delete korumasi.
5. Deals Kanban drag-drop.
6. Deal show quick actions.
7. Tasks reminder.
8. Quotes item/PDF/status/duplicate.
9. User management.
10. Role visibility.
11. API health.

## Uygulama Sirasi Onerisi

1. Faz 1: Kritik bugfix ve test edilebilirlik.
2. Faz 2: Dokuman ve kavram netlestirme.
3. Faz 3: Admin-panel UI temeli.
4. Faz 4: Liste UX sistemi.
5. Faz 5: CRM dinamik aksiyonlar.
6. Faz 6: Admin user/role management.
7. Faz 7: UI polish.

Bu sira, once sistemi bozulan noktalardan temizler, sonra genel UI altyapisini guclendirir, en son polisaj ve demo etkisini artirir.
