# CRM Manual Test Guide

Bu rehber sistemi tarayicida test ederken hem hata yakalamak hem de CRM Engine'in kabiliyetlerini ogrenmek icindir. Tum adimlari tek oturusta yapmak zorunda degilsin; once hizli smoke testi, sonra ana satis akisini, en son rol/API/import gibi derin kontrolleri yap.

## 0. Baslamadan Once

Servisler su an Docker development ortaminda ayakta olmali:

- CRM: `http://localhost:8081/admin/crm`
- Login: `http://localhost:8081/admin/login`
- Mailpit: `http://localhost:8026`

Demo kullanicilar:

| Rol | Email | Sifre | Ne icin test edilir |
| --- | --- | --- | --- |
| Owner | `crm.owner@example.com` | `password` | Tum yetkiler, settings, stage yonetimi |
| Manager | `crm.manager@example.com` | `password` | Ekip dashboard ve rapor gorunumu |
| Sales | `crm.sales@example.com` | `password` | Kendi satis kayitlari ve gunluk kullanim |
| Support | `crm.support@example.com` | `password` | Task/activity odakli kullanim |
| Viewer | `crm.viewer@example.com` | `password` | Sadece okuma ve yetki kisitlari |

Bir hata bulursan not formati:

```text
Ekran:
Kullanici:
Yaptigim adim:
Beklenen:
Gercek:
Varsa hata mesaji:
```

Kavram notlari:

- Quote modulunu test etmeden once [quotes-tanitim.txt](../quotes-tanitim.txt) dosyasini oku.
- AI aksiyonlarini test etmeden once [ai-sayfalar.txt](../ai-sayfalar.txt) dosyasindaki sayfa/aksiyon listesini kullan.

## 1. Hizli Smoke Test

Bu bolum sistem aciliyor mu, ana ekranlar patliyor mu diye bakar.

1. `http://localhost:8081/admin/crm` adresini ac.
2. Login sayfasina yonlenmelisin.
3. `crm.owner@example.com` / `password` ile giris yap.
4. Dashboard acilmali.
5. Sol menuden su ekranlara tek tek gir:
   - Dashboard
   - Contacts
   - Companies
   - Deals
   - Tasks
   - Quotes
   - Activities
   - Tags
   - Settings
6. Her ekranda bak:
   - Sayfa 500 hata vermiyor mu?
   - Header/action butonlari gorunuyor mu?
   - Liste veya empty state duzgun mu?
   - Sidebar kaybolmuyor mu?
   - Mobil degilse desktop layout kirilmiyor mu?

Basarili kabul: Tum ana ekranlar acilir ve demo veriler gorunur.

## 2. Dashboard Kabiliyetleri

Amac: CRM'in yonetici bakis ekranini anlamak.

1. Dashboard'da stat card'lari kontrol et:
   - Contacts
   - Companies
   - Open Deals
   - Open Pipeline
   - Weighted Pipeline
   - Won Value
   - Overdue Tasks
   - Quotes Sent / Accepted
2. Period filtresini degistir:
   - Today
   - This week
   - This month
   - Custom date
3. Pipeline by stage alaninda stage bazli adet ve tutarlar gorunmeli.
4. Upcoming tasks, recent activities ve top open deals kartlari dolu gelmeli.

Basarili kabul: Filtre degisince dashboard hata vermeden yenilenir; metrikler mantikli kalir.

## 3. Contact ve Company Akisi

Amac: Temel CRM veri girisini test etmek.

1. Contacts ekranina git.
2. New Contact ile yeni kisi olustur:
   - First name
   - Last name
   - Email
   - Phone
   - Lifecycle stage
   - Owner
3. Kayit sonrasi contact show sayfasinda:
   - Profil bilgileri
   - Company baglantisi
   - Deals
   - Quotes
   - Tasks
   - Activities
   alanlarini kontrol et.
4. Contact'a quick note ekle.
5. Companies ekranina git.
6. Yeni company olustur:
   - Name
   - Email
   - Phone
   - Tax number
   - City
   - Sector
7. Company show sayfasinda contact attach etmeyi dene.
8. Ayni email ile ikinci contact olusturmayi dene.

Basarili kabul: Kayit olusturma/edit calisir, duplicate email validation verir, show ekranlari 360 derece baglanti gosterir.

## 4. Deal Kanban ve Satis Pipeline

Amac: CRM'in asil satis motorunu test etmek.

1. Deals ekranina git.
2. Kanban view acilmali.
3. New Deal olustur:
   - Title
   - Contact
   - Company
   - Stage
   - Value
   - Currency
   - Expected close date
   - Owner
4. Deal'i Kanban'da baska stage'e surukle veya move action ile tasi.
5. Sayfayi yenile; stage ve siralama kalici kalmali.
6. Deal show sayfasina gir.
7. Deal icinden:
   - Task olustur
   - Quote olustur
   - Activity/note ekle
8. Deal'i won yap.
9. Baska bir deal'i lost yaparken lost reason gir.

Basarili kabul: Stage move kalici olur, won/lost status dogru islenir, timeline otomatik dolar.

## 5. Task ve Reminder Akisi

Amac: Gorev ve hatirlatma altyapisini anlamak.

1. Tasks ekranina git.
2. New Task olustur:
   - Title
   - Assignee
   - Due date
   - Reminder date
   - Priority
   - Related record varsa bagla
3. My Tasks, Today ve Overdue ekranlarini gez.
4. Bir task'i complete yap.
5. Mailpit'i ac: `http://localhost:8026`
6. Reminder icin due/reminder zamani gelen task varsa bildirim gelip gelmedigine bak.

Basarili kabul: Task filtreleri calisir, complete status kalici olur, reminder altyapisi queue/scheduler ile sorun cikarmadan calisir.

## 6. Quote, Hesaplama ve PDF

Amac: Teklif motorunun satisa hazir olup olmadigini gormek.

1. Quotes ekranina git.
2. New Quote olustur.
3. Quote item ekle:
   - Name
   - Quantity
   - Unit price
   - Discount type/value
   - Tax rate
4. Kaydet.
5. Quote show sayfasinda subtotal, tax, discount ve grand total kontrol et.
6. Preview PDF ac.
7. Download PDF dene.
8. Quote'u sent yap.
9. Quote'u accepted yap.
10. Duplicate quote aksiyonunu dene.

Basarili kabul: Hesaplar backend ile dogru kaydedilir, PDF acilir/iner, status aksiyonlari calisir.

## 7. Import / Export

Amac: Musteri verisi tasima kabiliyetini test etmek.

1. Contacts ekraninda template indir.
2. Contacts export al.
3. Contacts import ekranina gir.
4. Template formatina uygun kucuk bir CSV/XLSX hazirla.
5. Preview yap.
6. Import et.
7. Bilerek hatali satir ekleyip error report indirilebilir mi kontrol et.
8. Companies ve Deals import ekranlarini da acip template/preview akisini kontrol et.
9. Quotes export al.

Basarili kabul: Template iner, preview calisir, basarili import kayit olusturur, hatali import rapor verir.

## 8. Tags ve Saved Filters

Amac: Segmentasyon ve tekrar kullanilan filtreleri test etmek.

1. Tags ekranina git.
2. Yeni tag olustur.
3. Contacts veya Deals listesinde bir kayda tag ata.
4. Tag filtresiyle listeyi daralt.
5. Bir filtreyi saved filter olarak kaydet.
6. Saved filter'i uygula.
7. Private saved filter'in baska kullanicida gorunmedigini viewer veya sales ile kontrol et.

Basarili kabul: Tag ve saved filter listeleri dogru calisir.

## 9. AI Modulu

Amac: AI acik/kapali davranisini anlamak.

Varsayilan development ayarinda AI kapali olabilir.

1. Deal show veya quote show ekraninda AI butonlarini kontrol et.
2. AI kapaliyken buton disabled veya friendly response vermeli.
3. Settings ekraninda AI driver/model alanlarini incele.
4. AI acmak istersen `.env` icinde provider ayarlari gerekir:

```env
CRM_AI_ENABLED=true
CRM_AI_DRIVER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

Desteklenen driver'lar:

- `openai`
- `claude`
- `gemini`
- `null`

Basarili kabul: AI kapaliyken sistem bozulmaz; acikken sadece taslak/ozet uretir, otomatik kayit degistirmez.

## 10. Settings ve Marka Ayarlari

Amac: Musteriye gore markalama ve default degerleri test etmek.

1. Owner ile Settings ekranina gir.
2. Company profile alanlarini doldur:
   - Company name
   - Email
   - Website
   - Tax office
   - Tax number
3. Default currency ve tax rate ayarlarini kontrol et.
4. Quote terms guncelle.
5. Logo upload dene.
6. Yeni quote veya PDF preview'da marka bilgilerinin yansidigina bak.

Basarili kabul: Settings kaydedilir ve quote/PDF tarafina yansir.

## 11. Yetki Rolleri

Amac: Satis urununde kritik olan policy kontrollerini gormek.

1. Logout yap.
2. `crm.viewer@example.com` ile gir.
3. Contacts, Deals, Quotes ekranlarini ac.
4. Delete veya edit gibi aksiyonlari dene.
5. Viewer silme yapamamali.
6. Logout yap.
7. `crm.sales@example.com` ile gir.
8. Settings ekranina gitmeyi dene.
9. Sales settings yonetememeli.
10. `crm.manager@example.com` ile gir.
11. Dashboard'da ekip metriklerini gorebildigini kontrol et.

Basarili kabul: Owner tum sistemi yonetir; viewer okur ama kritik aksiyon yapamaz; sales settings'e giremez; manager raporlari gorebilir.

## 12. API Kabiliyetleri

Amac: Gelecekte mobil/public frontend veya entegrasyon icin API hazir mi gormek.

Tarayicida health endpoint:

```text
http://localhost:8081/api/crm/health
```

Beklenen:

```json
{"status":"ok"}
```

Protected endpointler Bearer token ister. Token trusted console'dan uretilir:

```bash
make artisan CMD="tinker"
```

```php
$user = App\Models\User::query()->where('email', 'crm.owner@example.com')->firstOrFail();
Sanalkopru\Crm\Models\CrmApiToken::issueFor($user, 'manual-test');
```

Token ile test edilecek endpointler:

- `GET /api/crm/contacts`
- `POST /api/crm/contacts`
- `GET /api/crm/deals`
- `POST /api/crm/deals/{deal}/move`
- `GET /api/crm/tasks`
- `POST /api/crm/tasks/{task}/complete`
- `GET /api/crm/quotes`

Basarili kabul: Token yoksa 401, yetki yoksa 403, validation hatalarinda 422 JSON doner.

## 13. Performans Kontrolu

Bu adimi normal manual testten sonra yap. Buyuk seed sistemi doldurur.

```bash
make artisan CMD="crm:seed-performance"
```

Sonra kontrol et:

1. Dashboard hala aciliyor mu?
2. Deals Kanban stage basina limitli kart gosteriyor mu?
3. List view pagination calisiyor mu?
4. Contact/company/deal search kabul edilebilir hizda mi?

Basarili kabul: 10k contact, 2k company, 5k deal ile temel ekranlar kullanilabilir kalir.

## 14. Oncelikli Test Sirasi

Zamanin azsa su sirayla test et:

1. Login ve dashboard
2. Contacts create/edit/show
3. Companies create/show
4. Deals Kanban move
5. Deal show icinden task + quote + activity
6. Quote item hesaplama + PDF download
7. Task complete + reminder/Mailpit
8. Import preview + export
9. Viewer role ile silme engeli
10. Settings marka bilgisi + quote PDF yansimasi

Bu 10 adim gecerse urunun ana satis demolarinda guvenle gezilebilir.

## 15. Ne Raporlamalisin?

Test ederken su tip sorunlari ozellikle bildir:

- 500 hata veya beyaz ekran
- Butona basinca hicbir sey olmamasi
- Kaydin kaybolmasi veya yanlis status'e gecmesi
- PDF'in acilmamasi
- Hesaplama farki
- Yetkisiz kullanicinin silme/edit yapabilmesi
- Mobil veya dar ekranda layout kirilmasi
- Anlamsiz/Ing-Tr karisik metinler
- Yavas acilan ekranlar

Raporlarken ekran adini ve kullanici rolunu mutlaka yaz.
