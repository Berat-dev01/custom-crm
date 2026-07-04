# CRM Modules

Bu dokuman CRM Engine icindeki modullerin sorumluluklarini ozetler.

## Dashboard

Dashboard satis ekibinin gunluk bakis ekranidir.

- Contact ve company sayilari
- Open pipeline value
- Weighted pipeline value
- Won deal value
- Overdue task sayisi
- Pipeline by stage
- Monthly won/lost trend
- Upcoming tasks
- Recent activities
- Top open deals
- Quote status distribution

Manager ve owner rolleri ekip verisini gorebilir. Sales rolleri kendi sahip oldugu kayitlarla sinirlanir.

## Contacts

Contacts kisi kayitlarini yonetir.

- CRUD
- Search ve filtreler
- Company baglantisi
- Owner assignment
- Lifecycle stage
- Tags
- Bulk tag
- Import/export
- 360 derece show ekrani

Email duplicate kontrolu vardir.

## Companies

Companies firma kayitlarini yonetir.

- CRUD
- Vergi ve adres bilgileri
- Contact, deal, quote ve task iliskileri
- Sector/city/owner/tag filtreleri
- Company 360 derece gorunum

Iliskili kayitlari olan company guvenli sekilde silinmez; once baglantilarin tasinmasi gerekir.

## Deals

Deals satis firsatlarini yonetir.

- Kanban pipeline
- List view
- Stage drag-drop
- Kolon ici siralama
- Expected close date ve value filtreleri
- Won/lost status akislari
- Lost reason
- Deal show satis workspace
- Task, quote ve activity baglantilari

Kalici stage/status kurallari frontend'de degil backend action katmaninda uygulanir.

## Deal Stages

Deal stages pipeline kolonlarini belirler.

- Ordered stages
- Probability
- Won/lost stage isaretleri
- Stage reorder
- Stage delete icin replacement zorunlulugu

Varsayilan stage'ler `CrmDealStageSeeder` ile gelir.

## Tasks

Tasks satis ekibinin takip islerini yonetir.

- CRUD
- Assignee
- Priority
- Due date
- Reminder date
- My/today/overdue scope'lari
- Complete action
- Database/mail notification

Reminder secimi scheduler ve queue tarafindan islenir.

## Quotes

Quotes teklif surecini yonetir.

- Quote CRUD
- Quote item satirlari
- KDV ve iskonto hesaplama
- Quote number
- PDF preview/download
- Duplicate
- Status flow: draft, sent, accepted, rejected, expired
- Deal/contact/company baglantisi

Hesaplamalar backend servisinde yapilir. Frontend sadece anlik onizleme sunabilir.

## Activities

Activities timeline kayitlarini tutar.

- Note
- Call
- Email
- Meeting
- System activity

Contact, company, deal ve quote gibi kayitlara morph relation ile baglanir. Kritik olaylar event/listener ile otomatik timeline'a yazilir.

## Tags ve Saved Filters

Tags CRM kayitlarini segmentlemek icindir.

- Contact tag
- Company tag
- Deal tag
- Quote tag
- Bulk attach/remove
- Filtreleme

Saved filters kullanicinin tekrar kullandigi filtreleri saklar. Private filtreler diger kullanicilara gorunmez.

## Import/Export

Data transfer modulu CSV/XLSX veri giris-cikisini yonetir.

- Contacts import/export
- Companies import/export
- Deals import/export
- Quotes export
- Template download
- Preview
- Error report
- Buyuk importlarda queue

Import audit log'a yazilir ve validation hatalari indirilebilir raporla sunulur.

## AI

AI modulu driver tabanlidir.

- Timeline summary
- Email draft
- Quote follow-up draft
- Lost deal analysis

Desteklenen driver'lar:

- `openai`
- `claude`
- `gemini`
- `null`

AI otomatik kayit degistirmez; kullaniciya taslak veya ozet verir.

## Settings

Settings CRM konfigurasyonunu admin panelden yonetir.

- Firma profili
- Logo
- Default currency
- Default tax rate
- Quote prefix
- Quote terms
- Notification preferences
- AI driver/model ayarlari

Settings config defaultlarini override eder.

## API

`/api/crm` altinda token korumali API vardir.

- Contacts
- Companies
- Deals
- Tasks
- Quotes
- Deal move
- Task complete

API, admin panel controller'larindan bagimsizdir ama ayni service/action katmanini kullanir.
