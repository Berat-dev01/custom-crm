# Customization

CRM Engine musteri projelerinde config, publish ve servis katmanlariyla customize edilir. Kritik is mantigi Blade veya JavaScript icine gomulmez.

## Config Publish

```bash
php artisan vendor:publish --tag=crm-config
```

Publish sonrasi musteri projesinde:

```text
config/crm.php
```

## Route ve Middleware

```env
CRM_ROUTE_PREFIX=admin/crm
CRM_ROUTE_MIDDLEWARE=web
```

Varsayilan admin path:

```text
/admin/crm
```

Admin guard ve layout `sanalkopru/admin-panel` tarafindan saglanir.

## Modul Ac/Kapat

```env
CRM_MODULE_CONTACTS=true
CRM_MODULE_COMPANIES=true
CRM_MODULE_DEALS=true
CRM_MODULE_TASKS=true
CRM_MODULE_QUOTES=true
CRM_MODULE_ACTIVITIES=true
CRM_MODULE_AI=true
```

Modul flag'leri navigation ve erisim kararlarinda kullanilir. Bir modulu kapatmadan once musteri akisinda hangi ekranlarin etkilenecegi kontrol edilmelidir.

## Para Birimi ve Vergi

```env
CRM_CURRENCY=TRY
CRM_SUPPORTED_CURRENCIES=TRY,USD,EUR
CRM_TAX_RATE=20
```

Quote item hesaplamalari backend servisinde yapilir. Miktar, iskonto, vergi ve toplamlar frontend'e guvenmeden tekrar hesaplanir.

## Quote Marka Bilgileri

```env
CRM_QUOTE_NUMBER_PREFIX=CRM-
CRM_QUOTE_NUMBER_PADDING=6
CRM_QUOTE_DEFAULT_TERMS=
CRM_QUOTE_COMPANY_NAME="Sanal Kopru"
CRM_QUOTE_COMPANY_EMAIL="sales@example.com"
CRM_QUOTE_COMPANY_WEBSITE="https://example.com"
CRM_QUOTE_COMPANY_TAX_OFFICE=
CRM_QUOTE_COMPANY_TAX_NUMBER=
```

Admin settings ekrani bu varsayilanlari musteri bazli override edebilir.

## View Publish

```bash
php artisan vendor:publish --tag=crm-views
```

View'lar publish edildikten sonra:

```text
resources/views/vendor/crm
```

Kurallar:

- View sadece sunum yapar.
- Deal move, quote calculation, reminder secimi gibi kalici kurallar action/service katmaninda kalir.
- Custom view guncellemelerinde paket upgrade diff'i takip edilir.

## Asset Publish

```bash
php artisan vendor:publish --tag=crm-assets
```

Asset path'leri:

```text
public/vendor/crm/css
public/vendor/crm/js
```

CRM assetleri public siteye global olarak eklenmez; admin CRM ekranlarinda yuklenir.

## Yetki Rolleri

Varsayilan roller:

- `crm_owner`
- `crm_manager`
- `crm_sales`
- `crm_support`
- `crm_viewer`

Permission listesi `PermissionCatalog` tarafindan uretilir ve `CrmPermissionSeeder` ile Spatie Permission tablolarina yazilir.

```bash
php artisan db:seed --class=Sanalkopru\\Crm\\Database\\Seeders\\CrmPermissionSeeder
```

## AI Provider Customization

```env
CRM_AI_ENABLED=true
CRM_AI_DRIVER=openai
CRM_AI_MODEL=
CRM_AI_MAX_TOKENS=1200
CRM_AI_TEMPERATURE=0.3
CRM_AI_RATE_LIMIT_PER_MINUTE=30
```

Provider env bloklari:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini

CLAUDE_API_KEY=
CLAUDE_MODEL=

GEMINI_API_KEY=
GEMINI_MODEL=
```

Yeni provider eklemek icin provider class'i `AiProviderContract` uygular ve `AiDriverManager` icinden secilebilir hale getirilir.

## Import/Export Limitleri

```env
CRM_IMPORT_QUEUE_THRESHOLD=500
CRM_DATA_TRANSFER_DISK=local
```

Buyuk importlar queue'ya alinmalidir. Daha buyuk enterprise kurulumlarda export icin queued/chunked export stratejisi eklenebilir.

## Performance Ayarlari

```env
CRM_KANBAN_PER_STAGE_LIMIT=50
CRM_KANBAN_PER_STAGE_MAX_LIMIT=100
CRM_API_DEFAULT_PER_PAGE=20
CRM_API_MAX_PER_PAGE=100
```

Kanban stage aggregate bilgileri dogru kalir; ancak kart listesi stage basina limitlenir.

## Tenant-Ready Notu

Urun varsayilan olarak single-tenant deploy edilir. Mimaride ileride `organization_id` veya `workspace_id` eklenecek sekilde servis/action katmani korunur.

Musteri custom code yazarken:

- Query scope'lar sahiplik/context alabilecek sekilde yazilir.
- Controller icinde kalici is kurali tutulmaz.
- API ve admin panel ayni servisleri kullanir.
