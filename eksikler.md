# CRM — Eksikler & Güvenlik Bulguları

> Audit tarihi: 2026-06-02  
> Kaynak: Otomatik kod incelemesi + manuel denetim

---

## KRİTİK (Satış öncesi mutlaka kapatılmalı)

### 1. Deal alt-kaynaklarında yetki açığı — storeTask / storeQuote / storeActivity

`DealsController::storeTask`, `storeQuote` ve `storeActivity` metodları `Gate::authorize('view', $deal)` çağırıyor — ama `create` yetkisini kontrol etmiyor. `crm_viewer` rolündeki bir kullanıcı görebildiği her deal'e task, quote ve activity ekleyebilir.

**Dosya:** `app/Crm/Http/Controllers/Admin/DealsController.php:294,309,320`

**Düzeltme:** Her metodda `Gate::authorize('crm.tasks.create')` / `crm.quotes.create` / `crm.activities.create` ekle.

---

### 2. ImportCrmRecordsRequest — her oturum açmış kullanıcıya izin veriyor

`ImportCrmRecordsRequest::authorize()` sadece `(bool) $this->user()` döndürüyor. Modül bazlı `crm.{module}.import` yetkisi FormRequest seviyesinde hiç kontrol edilmiyor.

**Dosya:** `app/Crm/Http/Requests/DataTransfer/ImportCrmRecordsRequest.php:10`

**Düzeltme:** `Gate::allows("crm.{$this->module}.import")` kontrolü FormRequest'e eklenecek.

---

### 3. API oturum cookie fallback — CSRF riski

`AuthenticateCrmApi` middleware, token yoksa `$request->user('web')` ile oturum cookie'sinden kimlik doğruluyor. API rotaları CSRF middleware'ini atladığından, aktif admin oturumu olan bir kullanıcıya yönelik CSRF saldırısı `POST /api/crm/contacts` gibi endpoint'leri çalıştırabilir.

**Dosya:** `app/Crm/Http/Middleware/AuthenticateCrmApi.php:15`

**Düzeltme:** Session fallback kaldırılacak veya session-based isteklere CSRF token zorunluluğu eklenecek.

---

## MAJOR (Yayına çıkmadan önce düzeltilmeli)

### 4. selectRaw ile string birleştirme — injection riski taşıyan tasarım

`DashboardReport::bucketExpression()` içinde kolon adı `selectRaw` string'ine birleştiriliyor. Şu an hardcoded olduğu için güvenli, ancak parametre ileride request'ten gelirse doğrudan SQL injection'a açık hale gelir.

**Dosya:** `app/Crm/Services/Dashboard/DashboardReport.php:346-361`

**Düzeltme:** Whitelist kontrolü ekle veya kolon adını PDO parametresi değil, güvenli bir enum ile belirle.

---

### 5. Logo yükleme — dosya içeriği doğrulanmıyor

`mimes` kuralı uzantı + finfo ile MIME tipini kontrol ediyor ama görüntünün gerçekten geçerli bir görüntü dosyası olduğunu doğrulamıyor. EXIF alanına gömülü PHP payload riski mevcut.

**Dosya:** `app/Crm/Http/Requests/Settings/UpdateCrmSettingsRequest.php:24`

**Düzeltme:** Yüklenen görüntüyü GD veya Intervention/Image ile yeniden kodla, sonra kaydet.

---

### 6. Bulk delete — tüm kayıtları belleğe yüklüyor, limit yok

Beş controller'daki `bulkDelete` metotları `->get()->each(fn => delete())` yapıyor. Binlerce kayıt için bu OOM'a yol açar. `record_ids` için sadece `min:1` var, `max` yok.

**Dosyalar:** `app/Crm/Http/Controllers/Admin/ContactsController.php:122`, `DealsController.php:161`, `QuotesController.php:113`, `TasksController.php:106`, `ActivitiesController.php:114`

**Düzeltme:** `->chunkById(200, fn => ...)` kullan ve `record_ids` için `max:500` gibi bir limit ekle.

---

### 7. ContactImportService — eski yol, audit kaydı yok

`ContactsController::import()` eski `ContactImportService`'i çağırıyor. Bu yol audit log yazmıyor, queue kullanmıyor, XLSX desteklemiyor. Yeni `CrmDataTransferService` yolu tüm bunları yapıyor ama contacts'ta aktif değil.

**Dosyalar:** `app/Crm/Http/Controllers/Admin/ContactsController.php:161`, `app/Crm/Services/Contacts/ContactImportService.php`

**Düzeltme:** Contacts controller'da import işlemini `DataTransferController` üzerinden `CrmDataTransferService`'e yönlendir, eski `ContactImportService`'i kaldır.

---

### 8. Export — row limiti yok

`CrmDataTransferService::exportRows()` `$ids` boş ve filtre uygulandığında `->get()` ile limitsiz sorgu çalıştırıyor. Büyük tablolarda tüm kayıtlar belleğe alınır.

**Dosya:** `app/Crm/Services/DataTransfer/CrmDataTransferService.php:607`

**Düzeltme:** Cursor pagination veya en fazla 10.000 satır sınırı + "too many records" uyarısı ekle.

---

### 9. Web rotalarında throttle middleware yok

`crm-web.php` altındaki admin rotalarında hiçbir `throttle` middleware yok. Brute-force koruması tamamen `sanalkopru/admin-panel` paketine bırakılmış.

**Dosya:** `routes/crm-web.php`

**Düzeltme:** Admin prefix grubuna `throttle:120,1` gibi makul bir limit ekle. Login endpoint'ine özel daha sıkı bir limit uygula.

---

## MİNÖR (Sonraki iterasyonda)

### 10. Kanban — her stage için ayrı sorgu

`DealQuery::pipeline()` her stage için ayrı bir `baseQuery()` çalıştırıyor. 5 stage = 6 sorgu. Stage sayısı artarsa sorun büyür.

**Dosya:** `app/Crm/Services/Deals/DealQuery.php:28`

---

### 11. SavedFilter silinmesinde sahiplik kontrolü yok

`SavedFiltersController::destroy()` hiçbir `Gate::authorize` veya `owner` kontrolü yapmıyor. Herhangi bir CRM kullanıcısı başkasının kayıtlı filtresini silebilir.

**Dosya:** `routes/crm-web.php:95`

---

### 12. Settings cache — organization-scoped değil

`CrmSettingsManager` ayarları `rememberForever` ile ve statik `crm_settings_default` anahtarıyla cache'liyor. Multi-tenant'a geçildiğinde farklı organizasyonlar aynı cache'i okur.

**Dosya:** `app/Crm/Services/Settings/CrmSettingsManager.php:155`

---

### 13. AI hataları sessizce yutulmuş — log yok

`AiAssistant::run()` tüm `Throwable`'ları yakalıyor ama `Log::error` çağırmıyor. Production'da AI arızaları izlenemiyor.

**Dosya:** `app/Crm/Services/Ai/AiAssistant.php:126`

---

### 14. API token last_used_at — her istekte yazıyor

`AuthenticateCrmApi` her authentication'da token satırını güncelliyor. Yoğun API kullanımında gereksiz write amplification.

**Dosya:** `app/Crm/Http/Middleware/AuthenticateCrmApi.php:48`

---

### 15. Test suite — yetki sınırı senaryoları eksik

`CrmAuthorizationPolicyTest` temel rol kontrollerini test ediyor ama viewer-creates-task açığını ve cross-user data erişimini test etmiyor.

**Dosya:** `tests/Feature/CrmAuthorizationPolicyTest.php`

---

## Öncelik Sırası

| Öncelik | Madde |
|---------|-------|
| 1 | #1 — Deal alt-kaynak yetki açığı |
| 2 | #3 — API CSRF riski |
| 3 | #2 — ImportCrmRecordsRequest |
| 4 | #6 — Bulk delete limitsiz |
| 5 | #9 — Web throttle eksik |
| 6 | #7 — Legacy import yolu |
| 7 | #8 — Export limitsiz |
| 8 | #5 — Logo yükleme |
| 9 | #11 — SavedFilter sahiplik |
| 10 | Minörler (#10–#15) |
