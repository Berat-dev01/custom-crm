# Dashboard ve Notification Analizi

## 1. Dashboard period problemi

### Mevcut durum
- Dashboard ekranı su an klasik `GET` form ile calisiyor; AJAX yok.
- Period filtresi [DashboardController.php](/Users/zyix/Desktop/repo/crm/packages/sanalkopru/crm/src/Http/Controllers/DashboardController.php) ve [DashboardReport.php](/Users/zyix/Desktop/repo/crm/packages/sanalkopru/crm/src/Services/Dashboard/DashboardReport.php) icinde isleniyor.
- Ama period tum dashboard'u etkilemiyor. Bilerek statik birakilan alanlar var:
  - `Contacts`
  - `Companies`
  - `Open Deals`
  - `Open Pipeline`
  - `Weighted Pipeline`
  - `Overdue Tasks`
  - `Pipeline by Stage`
  - `Upcoming Tasks`
  - `Highest Value Open Deals`
- Period sadece zaman bazli alanlari etkiliyor:
  - `Won Value`
  - `Quotes Sent / Accepted`
  - `Recent Activities`
  - `Quote Status Distribution`
  - `Monthly Won/Lost Trend`
- Bu davranis ekranda metinle anlatiliyor, ama UX olarak yeterince net degil. Kullanici tum dashboard degisecek diye bekliyor.

### Tespit
- Teknik olarak "period calisiyor ama kullaniciya guclu sekilde hissettirmiyor" durumu var.
- UX olarak iki sorun ust uste binmis:
  1. period degisimi tam sayfa yenileme ile calisiyor
  2. dashboard'un buyuk kismi intentionally sabit kaldigi icin kullanici "hicbir sey degismedi" hissine kapiliyor

### Karar
- Evet, dashboard period degisimini AJAX ile yapmak dogru.
- Hatta burada en dogru model:
  - filtre degisiminde dashboard ana region'unu AJAX ile yenilemek
  - URL query string'i de guncellemek
  - perioda bagli kartlari ve tablolari loading skeleton ile yenilemek
- Ama sadece AJAX yetmez; hangi bolumlerin perioda bagli oldugu daha net ayrilmalı.

### Dashboard icin yapilacak adimlar
1. Dashboard ekranini tek bir `data-crm-dashboard-region` altinda topla.
2. Period/filter formunu `data-crm-dashboard-filter` haline getir; submit ve select degisimlerinde AJAX calistir.
3. Response tarafinda dashboard icin partial/fragment render destegi ekle.
4. AJAX response geldiginde sadece dashboard region degissin; tam sayfa reload olmasin.
5. `history.replaceState` ile `period`, `date_from`, `date_to` URL'de korunsun.
6. Perioda bagli bolumlerin basligina kucuk badge/aciklama ekle:
   - `Period-based`
   - `Current snapshot`
7. Period degisiminde en az su alanlarin gorunur sekilde degistigi test edilsin:
   - won value
   - recent activities
   - quote distribution
   - monthly trend
8. Dashboard feature testlerine AJAX davranisi eklensin.
9. Demo seed verileri, `today / this_week / this_month / custom` farkini daha net gosterecek sekilde zenginlestirilsin.

## 2. Notification sistemi mevcut durum analizi

### Elde zaten olanlar
- `notifications` tablosu var.
- Laravel notification altyapisi kullanilabiliyor.
- `TaskReminderNotification` mevcut ve `database + mail` kanalina yaziyor.
- `crm:tasks:send-reminders` komutu reminder notification uretiyor.
- Navbar'da notification bell ve dropdown UI placeholder'i var.

### Eksik olanlar
- Dropdown'a veri baglayan backend endpoint yok.
- Unread count yok.
- Mark as read / mark all as read yok.
- Polling yok.
- Bell UI su an statik bos state.
- Task reminder disinda sistematik notification matrisi yok.
- Settings ekraninda iki toggle var:
  - `notify_task_reminders`
  - `notify_quote_status_changes`
  ama bu tercihlerin tum akis boyunca uygulandigi net bir orchestration yok.

### Sonuc
- Notification sistemi yari tamamlanmis.
- Data modeli ve ilk notification sinifi var.
- Fakat urun davranisi olarak "tam notification experience" henuz yok.

## 3. Notification icin kim, ne zaman, neden bildirim almali

Burada asiri gurultu uretmeden, satin alinabilir urun mantigiyla ilerlemek gerekiyor.

### Faz 1 zorunlu notification olaylari
- `Task assigned`
  - Alici: gorevi atanan user
  - Tetik: yeni task ataninca
- `Task reassigned`
  - Alici: yeni assignee
  - Tetik: `assigned_to` degisince
- `Task reminder`
  - Alici: assignee
  - Tetik: `reminder_at`
- `Quote sent`
  - Alici: quote owner
  - Ek alici: bagli deal owner farkliysa o da
- `Quote accepted`
  - Alici: quote owner
  - Ek alici: deal owner farkliysa o da
- `Quote rejected`
  - Alici: quote owner
  - Ek alici: deal owner farkliysa o da
- `Import completed with errors`
  - Alici: importu baslatan user
- `Import queued/completed`
  - Alici: importu baslatan user

### Faz 2 guclu ama kontrollu notification olaylari
- `Deal ownership changed`
  - Alici: yeni deal owner
- `Deal moved to won/lost`
  - Alici: deal owner
  - Ek alici: manager/owner rolunde isteyenler, ama bu global toggle ile gelmeli
- `New contact assigned`
  - Alici: contact owner
- `New company assigned`
  - Alici: company owner

### Simdilik notification yapmamak gerekenler
- her activity create
- her field update
- her saved filter aksiyonu
- her AI aksiyonu
- her liste bulk degisikligi

Bu tip olaylar notification center'i cok hizli cope cevirir.

## 4. Notification veri modeli ve UI hedefi

### Dropdown hedef davranis
- Bell icon uzerinde unread badge olacak.
- Tiklayinca dropdown dolu gelecek.
- Icinde su alanlar olacak:
  - baslik
  - kisa aciklama
  - zaman
  - okunmamis/okunmus durumu
  - tiklanabilir hedef URL
- Dropdown aksiyonlari:
  - tek notification'a tiklayinca ilgili sayfaya git + read yap
  - `Mark all as read`
  - `View all`

### Polling
- Evet, 3 saniyede bir polling bu asamada dogru.
- Sebep:
  - websocket zorunlulugu yok
  - local ve self-hosted kurulum icin sade
  - admin panel UX icin yeterli
- Ama polling sadece sayfa gorunur ve tab aktifken calismali.
- Dropdown aciksa hemen refresh yapilmali.

## 5. Notification teknik mimari

### Backend adimlari
1. CRM notification service katmani ekle.
2. Notification payload standardi belirle:
   - `kind`
   - `title`
   - `body`
   - `url`
   - `entity_type`
   - `entity_id`
   - `meta`
3. Task reminder payload'i bu standarda cek.
4. Quote status change notification class'lari ekle.
5. Task assignment/reassignment notification class'lari ekle.
6. Import queued/completed notification class'lari ekle.
7. Global helper/service ile recipients tek yerde hesaplanir hale getirilsin.
8. Settings toggle'lari bu dispatcher icinde uygulanir hale getirilsin.

### API/endpoint adimlari
1. `GET /admin/crm/notifications` veya admin panel uyumlu benzeri endpoint
2. `POST /admin/crm/notifications/{id}/read`
3. `POST /admin/crm/notifications/read-all`
4. JSON response:
   - `items`
   - `unread_count`
   - `has_more`
   - `server_time`

### Frontend adimlari
1. Navbar bell component'i statik placeholder olmaktan cikarilsin.
2. Alpine veya mevcut admin-panel JS ile dropdown state yonetilsin.
3. Page load'da ilk fetch yapilsin.
4. Her 3 saniyede polling calissin.
5. Sekme aktif degilse polling dursun.
6. Yeni unread varsa badge aninda guncellensin.
7. Dropdown empty/loading/error state'leri profesyonel hale getirilsin.

## 6. Notification icin uygulanacak sirali fazlar

### Faz A - Dashboard UX duzeltmesi
1. Dashboard period degisimini AJAX yap.
2. URL senkronizasyonu ekle.
3. Snapshot vs period-based ayrimini daha gorunur yap.
4. Dashboard AJAX testlerini yaz.

### Faz B - Notification foundation
1. Notification payload standardini tanimla.
2. Bell dropdown icin endpointleri ekle.
3. Read / read-all endpointlerini ekle.
4. Bell badge ve dropdown fetch UI'sini bagla.
5. 3 saniyelik polling ekle.

### Faz C - Ilk business notification seti
1. Task assigned
2. Task reassigned
3. Task reminder
4. Quote sent
5. Quote accepted
6. Quote rejected
7. Import queued/completed/error

### Faz D - Preferences ve gurultu kontrolu
1. Settings toggle'larini gercek dispatch flow'una bagla.
2. Faz 2 eventleri icin ek toggle ihtiyacini degerlendir.
3. Duplicate notification korumasi ekle.

### Faz E - UI polish ve guven
1. Notification dropdown tasarimini profesyonellestir.
2. Relative time goster.
3. Empty/loading/error durumlarini duzelt.
4. Feature test + polling smoke test + permission testlerini tamamla.

## 7. Net onerim

Siradaki uygulama sirasi boyle olmali:

1. once dashboard period UX/AJAX duzeltmesi
2. sonra notification foundation
3. sonra ilk notification seti
4. sonra preferences ve polish

Bu sira dogru cunku dashboard problemi su an dogrudan kullaniciya batiyor; notification ise daha genis ama kontrollu sekilde kurulmasi gereken bir is.
