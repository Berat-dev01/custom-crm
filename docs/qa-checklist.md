# QA Checklist

Bu checklist release oncesi manual kabul testi icindir.

## Hazirlik

- `.env` development icin hazir.
- `make up` calisti.
- `make fresh` ile demo veri geldi.
- `make test` basarili.
- Mailpit aciliyor: `http://localhost:8026`
- Queue worker veya queue container calisiyor.

## Login ve Yetki

- `crm.owner@example.com` ile login olunur.
- `/admin/crm` dashboard acilir.
- `crm.viewer@example.com` ile silme aksiyonlari reddedilir.
- `crm.sales@example.com` settings ekranina giremez.
- Manager dashboard ekip metriklerini gorur.
- Sales dashboard sadece kendi kayitlariyla sinirlidir.

## Contacts

- Yeni contact olusturulur.
- Contact'a company baglanir.
- Duplicate email validation kontrol edilir.
- Contact show 360 derece ekrani acilir.
- Tag ekleme/cikarma denenir.
- Contact export alinir.
- Contact import preview ve import denenir.
- Import hata raporu indirilebilir.

## Companies

- Yeni company olusturulur.
- Vergi ve adres alanlari kaydedilir.
- Company show ekraninda contacts/deals/quotes/tasks gorulur.
- Iliskili kaydi olan company silme korumasi kontrol edilir.

## Deals

- Yeni deal olusturulur.
- Deal Kanban'da stage'ler arasinda tasinir.
- Kolon ici siralama kalici kalir.
- Won stage'e tasiyinca status/probability/closed date kontrol edilir.
- Lost stage'e tasiyinca lost reason akisi kontrol edilir.
- Deal show ekranindan task, quote ve note olusturulur.
- Activity timeline otomatik guncellenir.

## Tasks ve Reminder

- Deal icin task olusturulur.
- Due date ve reminder date ayarlanir.
- Task complete action denenir.
- `crm:tasks:send-reminders` komutu calistirilir.
- Notification tekrarsiz gider.
- Mailpit veya database notification kaydi kontrol edilir.

## Quotes

- Deal icin quote olusturulur.
- Quote item eklenir.
- Quantity, unit price, discount ve KDV hesaplari kontrol edilir.
- PDF preview acilir.
- PDF download denenir.
- Quote sent yapilir.
- Quote accepted yapilir.
- Accepted quote'un bagli deal etkisi kontrol edilir.
- Rejected/expired akislarinda yetki ve audit kontrol edilir.

## Activities

- Manuel note olusturulur.
- Call/email/meeting activity filtreleri denenir.
- Deal move, quote sent ve task complete sonrasi system activity olusur.
- Timeline sadece ilgili kayitlari gosterir.

## AI

- AI kapaliyken ekranlar bozulmaz ve aksiyonlar friendly response verir.
- AI aciksa provider key ile summary/draft uretilir.
- AI ciktisi sadece taslak olarak gosterilir.
- Hassas contact alanlari prompt context'ine gereksiz tasinmaz.
- Rate limit kontrol edilir.

## API

- Token uretilir.
- Bearer token olmadan protected endpoint `401` doner.
- Yetkisiz role `403` doner.
- Contacts list/create denenir.
- Deals move endpoint denenir.
- Tasks complete endpoint denenir.
- Validation error JSON sekli kontrol edilir.

## Performance

- `CrmPerformanceSeeder` ile buyuk veri basilir.
- Dashboard kullanilabilir kalir.
- Kanban stage basina limitli yuklenir.
- List view pagination calisir.
- Import/export memory davranisi izlenir.

## Production Hazirlik

- `composer validate --strict` basarili.
- `php artisan config:cache` basarili.
- `php artisan route:cache` basarili.
- `php artisan view:cache` basarili.
- `php artisan migrate --force` dry-run plani kontrol edildi.
- Queue worker supervisor config'i hazir.
- Cron scheduler satiri hazir.
- Backup ve rollback plani yazili.
