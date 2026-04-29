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

## [ ] Faz 2 — View'lar
`packages/sanalkopru/crm/resources/views/` → `resources/views/crm/`

- [ ] View'ları taşı
- [ ] ServiceProvider'da `loadViewsFrom` güncelle (artık `resource_path`)
- [ ] Tüm `crm::` prefix'li view referanslarını güncelle (include, extends, components)
- [ ] Layout `crm::layouts.app` → `crm.layouts.app` (noktalı notation)
- [ ] Testler

## [ ] Faz 3 — Config & Routes
- [ ] `packages/sanalkopru/crm/config/crm.php` → `config/crm.php`
- [ ] `mergeConfigFrom` kaldır, direkt `config/crm.php` kullanılır
- [ ] Routes → `routes/crm-web.php` + `routes/crm-api.php`
- [ ] `RouteServiceProvider` veya `AppServiceProvider`'dan include

## [ ] Faz 4 — Assets & Lang
- [ ] `resources/css/crm.css` + `resources/js/crm.js` (public/vendor/crm olarak kalabilir)
- [ ] `lang/tr/crm/` (veya JSON)
- [ ] `loadTranslationsFrom` kaldır, Laravel'in standart lang yükleme mekanizması

## [ ] Faz 5 — Testler & Temizlik
- [ ] Test namespace import'larını güncelle (`Sanalkopru\Crm\` → `App\Crm\`)
- [ ] Tüm testler geçmeli
- [ ] `packages/sanalkopru/crm/` dizinini sil
- [ ] `composer.json`'dan crm path repo + `sanalkopru/crm` require kaldır
- [ ] `composer update` + `php artisan test` final kontrol

---

## Notlar
- Her faz sonunda `php artisan test` çalıştır
- Faz bittikten sonra commit at, sonra sonraki faz
- `packages/sanalkopru/crm/` Faz 5'e kadar silinmez (referans için kalır)
