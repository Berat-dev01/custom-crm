# CRM Security Checklist

Bu dokuman CRM paketinin guvenlik, veri koruma ve audit kontrollerini takip etmek icin kullanilir.

## Kimlik ve Yetki

- Admin CRM route'lari `web` middleware'i ve `crm.access` kontrolu altindadir.
- API route'lari `crm.api.auth` Bearer token middleware'i ile korunur.
- API token'lari yalnizca hash olarak `crm_api_tokens.token_hash` alaninda saklanir.
- Policy/Gate kontrolleri web ve API controller'larinda ayni permission katalogunu kullanir.
- Kritik mutasyonlar icin ilgili permission gerekir: create, update, delete, move, complete, send, accept, reject, import, export, settings.

## Rate Limit

- API endpoint'leri `throttle:crm-api` ile sinirlandirilir.
- AI endpoint'leri `throttle:crm-ai` ile sinirlandirilir.
- Limitler `.env` uzerinden yonetilir:
  - `CRM_API_RATE_LIMIT_PER_MINUTE`
  - `CRM_AI_RATE_LIMIT_PER_MINUTE`

## Request Validation

- CRUD, settings, deal move, quote actions, import/export ve AI istekleri FormRequest veya controller validation ile dogrulanir.
- Validation hatalari web formlarinda session errors, API tarafinda JSON `422` olarak doner.
- Para, KDV, tarih, status, stage, owner ve relation alanlari tip ve existence kurallariyla korunur.

## Upload Guvenligi

- Settings logo upload yalnizca `jpg`, `jpeg`, `png`, `webp` image dosyalarini kabul eder ve boyut siniri vardir.
- Import upload yalnizca `csv`, `txt`, `xlsx` dosyalarini kabul eder ve boyut siniri vardir.
- Import dosyalari configured disk altinda CRM path'lerine kaydedilir.
- Import hata raporlari kontrollu route ve import permission ile indirilir.

## XSS ve HTML Guvenligi

- Blade ekranlari varsayilan escaped output kullanir.
- AI prompt context'i `strip_tags` ve uzunluk limitleriyle temizlenir.
- Kullanici kaynakli not, aciklama, terms ve body alanlari raw HTML olarak render edilmemelidir.

## CSRF

- Web formlari Laravel `web` middleware'i altindadir ve CSRF korumasi kullanir.
- API stateful session'a bagli degildir; Bearer token ile calisir.

## Mass Assignment

- CRM modellerinde `guarded = ['id']` kullanilir.
- Controller'lar dogrudan request payload'i yerine validated payload/action katmani kullanir.
- Audit ve token gibi destek modellerinde id korunur; token plaintext saklanmaz.

## Audit Log

Audit loglar `crm_audit_logs` tablosuna yazilir.

Kaydedilen olaylar:

- `crm.contact.created`
- `crm.contact.updated`
- `crm.contact.deleted`
- `crm.deal.moved`
- `crm.deal.won`
- `crm.deal.lost`
- `crm.quote.sent`
- `crm.quote.accepted`
- `crm.quote.rejected`
- `crm.settings.changed`
- `crm.import.started`
- `crm.export.started`

Audit log alanlari:

- event
- auditable type/id
- user id
- old/new values
- metadata
- ip address
- user agent
- created at

## Hassas Veri Koruma

Audit logger hassas anahtar adlarini otomatik redakte eder:

- password
- token
- secret
- api_key
- authorization
- cookie
- email
- phone
- address
- notes
- reason
- body
- content
- description

Bu nedenle audit loglar is aksiyonlarini izler, fakat musteri iletisim detaylarini, not iceriklerini, token'lari veya gizli provider bilgilerini saklamaz.

## Test Coverage

Guvenlik ve audit kontrolleri `CrmAuditSecurityModuleTest` ile kapsanir:

- contact create/update/delete audit
- deal won/lost audit
- quote sent/accepted/rejected audit
- settings/import/export audit
- upload validation
- AI rate limit
- API auth/rate limit middleware

Ilgili mevcut testler:

- `CrmAuthorizationPolicyTest`
- `CrmApiModuleTest`
- `CrmDataTransferModuleTest`
- `CrmSettingsModuleTest`

## Eklenen Korumalar (2026-07-03)

- Global security header'ları: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` (`App\Http\Middleware\SecurityHeaders`).
- Login brute-force koruması: `throttle:crm-login` (varsayılan 5 deneme/dk, `crm.security.login_attempts_per_minute`).
- Parola politikası: en az 10 karakter, harf + rakam (`crm.security.password_min_length`).
- Opsiyonel TOTP 2FA + tek kullanımlık kurtarma kodları (`/admin/crm/security`).
- Logo yüklemeleri GD ile yeniden kodlanır; EXIF/eklenmiş payload'lar temizlenir.
- API yalnızca Bearer token; token'lar hash'li saklanır, UI'dan iptal edilebilir.
- Webhook teslimatları HMAC-SHA256 imzalı.
- `composer audit` temiz (guzzle, psr7, framework güncellendi — 2026-07-03).
