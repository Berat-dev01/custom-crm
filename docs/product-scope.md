# CRM Engine Product Scope

## Urun Konumu

CRM Engine, Sanal Kopru'nun hazir satilabilir CRM altyapisidir. Amac, musteri CRM istediginde sifirdan proje yazmak yerine, tamamlanmis ve customize edilebilir bir cekirdek uzerinden hizli teslimat yapmaktir.

Bu urun MVP olarak dusunulmez. Hedef, ilk kurulumda gercek bir isletmenin satis operasyonunu yonetebilecegi tam kapasiteli bir temel sunmaktir.

## Ilk Faz Kapsami

Ilk faz iki parcadan olusur:

- CRM cekirdegi
- Admin CRM frontend'i

Admin CRM frontend'i sirket calisanlari icindir. Sales, manager, support veya owner rolleri bu panelden contact, company, deal, task, quote ve aktiviteleri yonetir.

## Tenancy Kapsami

Ilk fazda urun single-tenant deploy edilir: her musteriye ayri kurulum yapilir. Bu karar satis hizini artirir ve CRM cekirdeginin gereksiz SaaS karmasasi ile agirlasmasini engeller.

Ancak urun tenant-ready tasarlanir:

- Veriler ileride organization/workspace bazli ayrilabilecek sekilde modellenir.
- Servisler ve policy'ler sahiplik context'i alabilecek sekilde dusunulur.
- Dashboard ve rapor sorgulari ileride organization scope ile filtrelenebilir kalir.
- Public/customer frontend veya SaaS modeline gecis icin API ve servis katmani hazir tutulur.

Full multi-tenant SaaS ozellikleri bu fazda yoktur. Subdomain, tenant database provisioning, abonelik, super admin tenant paneli ve billing akisi ayri urun/faz konusudur.

## Ilk Fazda Olacak Moduller

### Dashboard

- Stat card metrikleri
- Pipeline degeri
- Weighted pipeline
- Bu ay kazanilan deal tutari
- Overdue task sayisi
- Son aktiviteler
- Yaklasan gorevler

### Contacts

- CRUD
- Data-table
- Arama, filtre, siralama
- Tag destegi
- Import/export
- Contact show 360 derece gorunum

### Companies

- CRUD
- Contact/deal/quote/task iliskileri
- Vergi ve adres bilgileri
- Firma bazli pipeline ozeti

### Deals

- Kanban pipeline
- SortableJS drag-drop
- Stage ve kolon ici siralama
- Won/lost akis mantigi
- Deal show satis merkezi
- Activity timeline

### Tasks

- CRUD
- Due date
- Reminder date
- Priority ve status
- Assignment
- Database/mail notification
- Queue ve scheduler destegi

### Quotes

- Quote ve quote item CRUD
- Otomatik quote number
- KDV ve iskonto hesaplama
- PDF export
- Status flow: draft, sent, accepted, rejected, expired
- Deal/contact/company baglantisi

### Activities

- Note, call, email, meeting, system activity
- Morph relation ile contact/company/deal/quote baglantisi
- Event tabanli otomatik timeline kayitlari

### Tags

- Contact, company, deal ve quote etiketleme
- Filtreleme
- Bulk assign/remove

### AI

- Not ozetleme
- Deal timeline ozetleme
- Email taslagi
- Teklif takip mesaji
- Kaybedilen deal analizi

AI otomatik aksiyon almaz; sadece kullaniciya taslak veya ozet uretir.
AI provider'i driver mantigi ile degistirilebilir olur. Ilk destek hedefleri OpenAI, Claude ve Gemini'dir; `.env` icindeki `CRM_AI_DRIVER` secimi hangi provider'in kullanilacagini belirler.

### Settings

- Firma bilgileri
- Logo
- Vergi bilgileri
- Default para birimi
- Default KDV
- Quote prefix ve terms
- Notification tercihleri
- AI ayarlari

## Ilk Fazda Olmayacak Ama Hazirlik Yapilacak Alanlar

Asagidaki frontendler ilk fazin teslim kapsami degildir:

- Public quote approval page
- Customer portal
- Musteri self-service login
- SaaS landing/onboarding
- Ayri React/Vue admin panel
- Mobil uygulama

Bu bir eksiklik olarak degerlendirilmez. Ilk ticari satis icin oncelik, sirket ic operasyonunun kullanacagi admin CRM panelidir.

Ancak mimari bu yuzeylere hazir olmalidir. Is mantigi admin panel icine gomulmeyecegi icin, sonraki fazda public/customer frontend eklemek yonetilebilir kalir.

## Cekirdek Kurallari

- Quote hesaplari backend servisinde yapilir.
- Deal stage degisimi action sinifinda uygulanir.
- Task reminder secimi servis/job katmaninda olur.
- AI prompt orkestrasyonu servis katmaninda ve provider bagimsiz driver contract'i arkasinda tutulur.
- Audit log ve activity event/listener ile yazilir.
- Controller'lar is kurali icermez.
- Blade view'lar sadece sunum yapar.
- JavaScript sadece etkilesim ve anlik onizleme icindir.

## Satilabilirlik Kriterleri

Urun satilabilir sayilmak icin:

- Demo seed ile dolu ve etkileyici bir CRM gorunumu sunmali.
- Admin panelden temel satis operasyonu yonetilebilmeli.
- Yetki sistemi kritik islemleri korumali.
- Import/export musteri verisi tasimayi desteklemeli.
- PDF teklif sablonu profesyonel gorunmeli.
- Docker development kurulumu kolay olmali.
- Production deploy Docker'siz dokumante edilmeli.
- Test suite kritik is kurallarini korumali.

## Bitti Kabul Kriterleri

Ilk faz tamamlandiginda:

- `/admin/crm` altinda kullanilabilir CRM paneli olur.
- Contacts, companies, deals, tasks, quotes, activities ve dashboard calisir.
- Kanban drag-drop kalici ve yetki kontrolludur.
- Quote PDF Turkce karakterlerle sorunsuz uretilir.
- Reminder notification queue/scheduler ile calisir.
- AI kapaliyken sistem bozulmaz, acikken taslak/ozet uretebilir.
- API katmani ileride yeni frontendler icin hazirdir.
- README ve dokumanlar yeni developer'in projeyi devralmasina yeter.
