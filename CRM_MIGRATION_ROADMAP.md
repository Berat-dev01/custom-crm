# CRM Package → Ana App Taşıma Roadmap

Karar: CRM sadece bu projede kullanılıyor, paket overhead'i anlamsız.
admin-panel paketi kalıyor (gerçekten multi-project). CRM ana app'e taşınıyor.

---

## [x] Faz 1 — PHP Sınıfları (Namespace)
`packages/sanalkopru/crm/src/` → `app/Crm/`
`Sanalkopru\Crm\` → `App\Crm\`

- [x] `app/Crm/` dizin yapısı oluştur
- [x] Tüm sınıfları kopyala, namespace güncelle
- [x] `composer.json` autoload güncelle
- [x] `CrmServiceProvider` → `app/Providers/CrmServiceProvider.php`
- [x] `bootstrap/providers.php` güncelle
- [x] `composer dump-autoload` + testler → 162 passed

## [x] Faz 2 — View'lar
`packages/sanalkopru/crm/resources/views/` → `resources/views/crm/`

- [x] View'ları taşı
- [x] ServiceProvider'da `loadViewsFrom` güncelle → `resource_path('views/crm')`
- [x] `crm-views` publish tag kaldırıldı (views artık main app'te)
- [x] Testler → 162 passed

## [x] Faz 3 — Config & Routes
- [x] `config/crm.php` zaten ana app'teydi (aynı içerik)
- [x] `mergeConfigFrom` kaldırıldı, Laravel otomatik yükler
- [x] Routes → `routes/crm-web.php` + `routes/crm-api.php`
- [x] Testler → 162 passed

## [x] Faz 4 — Assets & Lang
- [x] `resources/css/crm.css` + `resources/js/crm.js` → Vite pipeline'a eklendi
- [x] Tüm view'lardaki `asset('vendor/crm/...')` → `@vite()` ile değiştirildi
- [x] CSS `@push` bloğu `crm::layouts.app`'e taşındı (32 view'dan kaldırıldı)
- [x] `lang/en.json` + `lang/tr.json` kopyalandı (otomatik yüklenir)
- [x] PHP translations → `lang/crm/tr/` + `lang/crm/en/`
- [x] `loadTranslationsFrom(lang_path('crm'), 'crm')` — `crm::` namespace korundu
- [x] `crm-lang` ve `crm-assets` publish tag'ları kaldırıldı
- [x] Testler → 162 passed

## [x] Faz 5 — Testler & Temizlik
- [x] `packages/sanalkopru/crm/` dizini silindi
- [x] `composer.json`'dan crm path repo kaldırıldı
- [x] `registerPublishables()` kaldırıldı (artık paket yok)
- [x] Migrations → `database/migrations/` (auto-discovered)
- [x] `composer dump-autoload` → 7726 classes
- [x] `php artisan test` → 161 passed

---

## Notlar
- Her faz sonunda `php artisan test` çalıştır
- Faz bittikten sonra commit at, sonra sonraki faz
- `packages/sanalkopru/crm/` Faz 5'e kadar silinmez (referans için kalır)
