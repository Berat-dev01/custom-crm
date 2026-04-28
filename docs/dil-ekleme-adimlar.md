# Dil Ekleme Adimlari

## Amac

Bu dokumanin amaci, CRM host uygulamasina ve iki pakete:

- `packages/sanalkopru/admin-panel`
- `packages/sanalkopru/crm`

Turkce (`tr`) ve Ingilizce (`en`) localization destegini dogru, bakimi kolay ve performansli sekilde eklemek icin net bir yol haritasi vermektir.

Bu repo icin hedef sadece "ceviri eklemek" degil; locale secimi, paket translation loading, validation, notification, admin UI ve CRM domain metinlerinin tek bir sistem icinde tutarli calismasidir.

## Kisa Sonuc

Mevcut sistemde localization altyapisi kismen var, ama tamamlanmis degil.

Var olanlar:

- Laravel uygulama seviyesinde `locale` ve `fallback_locale` ayarlari var: `config/app.php`
- Admin panelde `admin_trans()` helper'i var: `app/Support/admin_helpers.php`
- `admin-panel` view/component katmaninda `admin_trans()` ve `__()` kullanimi baslamis
- Admin layout icinde dil secici UI mevcut
- `admin.locale.update` route'u tanimli

Eksikler:

- Root uygulamada `lang/` klasoru yok
- Paketlerde translation dosyasi yok
- `AdminPanelServiceProvider` translation load etmiyor
- `CrmServiceProvider` translation load etmiyor
- `updateLocale()` su an bos donuyor, locale kaydetmiyor
- Request basinda locale set eden middleware yok
- CRM paketinde view, request, notification, controller ve service katmanlarinda cok sayida sert yazilmis Ingilizce metin var

Kisacasi: temel isaretler var ama sistem su an production-grade localization seviyesinde degil.

## Mevcut Durum Tespiti

### 1. Host app locale config hazir

`config/app.php` icinde su alanlar zaten var:

- `locale`
- `fallback_locale`
- `faker_locale`

Bu iyi bir baslangic. Laravel cekirdegi locale degistirmeye hazir.

### 2. Admin panelde ceviri dostu bir desen baslamis

`app/Support/admin_helpers.php` icindeki helper:

```php
function admin_trans(string $key, array $replace = [], ?string $locale = null): string
{
    return __($key, $replace, $locale);
}
```

Bu sayede admin panelin bircok yerinde `admin_trans('Dashboard')` gibi kullanimlar var. Bu desen korunabilir.

Ek olarak admin-panel komponentlerinde label ve placeholder cevirisi otomatik dusunulmus:

- `packages/sanalkopru/admin-panel/resources/views/components/input.blade.php`
- `packages/sanalkopru/admin-panel/resources/views/components/select.blade.php`

Bu cok degerli cunku CRM view'lari raw string verse bile komponent katmani bunlari ceviri sistemine sokabiliyor.

### 3. Dil secici UI var ama calismiyor

Dil secici admin layout icinde mevcut:

- `packages/sanalkopru/admin-panel/resources/views/layouts/app.blade.php`

Route da var:

- `routes/web.php`

Ama controller implementasyonu eksik:

- `app/Http/Controllers/AdminAuthController.php`

`updateLocale()` su an sadece `back()` donuyor. Session'a locale yazmiyor, whitelist kontrolu yapmiyor, `app()->setLocale()` akisini beslemiyor.

### 4. Locale uygulayan middleware yok

`bootstrap/app.php` icinde locale ile ilgili middleware kaydi yok. Yani kullanici locale secse bile sonraki request'lerde bunu uygulayan bir katman bulunmuyor.

### 5. Paket translation loading yok

Su iki provider su an translation klasoru yuklemiyor:

- `packages/sanalkopru/admin-panel/src/AdminPanelServiceProvider.php`
- `packages/sanalkopru/crm/src/CrmServiceProvider.php`

Bu cok kritik. Cunku paket icinde translation dosyasi olustursak bile Laravel bunlari otomatik kullanamaz.

### 6. Paketlerde `lang` dosyalari henuz yok

Tarama sonucunda:

- host app altinda `lang/` yok
- `admin-panel` paketinde translation dosyasi yok
- `crm` paketinde translation dosyasi yok

### 7. CRM katmaninda cok sayida hard-coded metin var

Ozellikle su alanlarda ceviri ihtiyaci yuksek:

- Blade view basliklari, butonlar, empty-state metinleri
- Form placeholder ve help metinleri
- Validation custom error mesajlari
- Controller `withErrors()` mesajlari
- Notification subject/body metinleri
- Navigation group ve item labellari
- API error mesajlari
- Status / priority / type gibi domain label'lari

Ornek dosyalar:

- `packages/sanalkopru/crm/resources/views/admin/deals/index.blade.php`
- `packages/sanalkopru/crm/src/Services/Navigation/CrmNavigation.php`
- `packages/sanalkopru/crm/src/Notifications/TaskReminderNotification.php`
- `packages/sanalkopru/crm/src/Notifications/TaskAssignmentNotification.php`
- `packages/sanalkopru/crm/src/Http/Requests/Deals/StoreDealRequest.php`
- `app/Http/Controllers/AdminAuthController.php`

## Onerilen Mimari

En dogru ve performansli yapi icin hibrit bir strateji onerilir.

### 1. UI metinlerinde JSON translation kullan

Neden:

- Mevcut kodda cok sayida `__('Dashboard')`, `admin_trans('Settings')`, `admin_trans('Search')` gibi kullanim var
- Bu kullanimlari minimum refactor ile ceviriye sokmanin en temiz yolu JSON translation
- Ozellikle `admin-panel` paketinde bu yontem en hizli ve en az riskli yol

Kullanilacak dosyalar:

- `lang/en.json`
- `lang/tr.json`
- paket icin de package-local JSON dosyalari

Bu sayede:

```php
admin_trans('Dashboard')
__('Notifications')
```

gibi mevcut kullanimlar bozulmadan calisir.

### 2. Domain ve backend mesajlarinda keyed translation kullan

Neden:

- Validation mesaji, notification subject'i, status label'i, enum benzeri metinler uzun vadede sabit anahtarlarla daha bakimli olur
- Ayni metni birden fazla yerde kontrollu sekilde kullanmak kolaylasir
- Refactor sirasinda Ingilizce cumleyi key olarak tasima zorunlulugu olmaz

Onerilen key alanlari:

- `crm::messages.*`
- `crm::validation.*`
- `crm::statuses.*`
- `crm::notifications.*`
- `crm::navigation.*`
- `admin-panel::messages.*`

### 3. Statik sistem cevirilerini dosyada tut

Statik admin/CRM metinleri icin database tabanli translation kullanma.

Sebep:

- Gereksiz query maliyeti olusur
- Cache ve deployment karmasikligi artar
- Paket yapisi icin dosya tabanli translation daha dogrudur

Dogru secim:

- kodla gelen sabit UI/domain metinleri: dosya bazli translation
- kullanicinin girdigi veriler: oldugu gibi kalir, cevrilmez

## Onerilen Dizin Yapisi

### Host app

```text
lang/
  en/
    auth.php
    pagination.php
    passwords.php
    validation.php
  tr/
    auth.php
    pagination.php
    passwords.php
    validation.php
  en.json
  tr.json
```

### Admin panel paketi

```text
packages/sanalkopru/admin-panel/resources/lang/
  en.json
  tr.json
  en/
    messages.php
  tr/
    messages.php
```

### CRM paketi

```text
packages/sanalkopru/crm/resources/lang/
  en.json
  tr.json
  en/
    messages.php
    validation.php
    navigation.php
    statuses.php
    notifications.php
  tr/
    messages.php
    validation.php
    navigation.php
    statuses.php
    notifications.php
```

## Uygulama Plani

## Faz 1: Host app locale omurgasini tamamla

### Faz 1 Uygulama Ozeti

Durum: tamamlandi.

- `config/localization.php` eklendi ve desteklenen locale listesi `tr` / `en` olarak tanimlandi
- `app/Http/Middleware/SetLocale.php` eklendi
- Middleware `bootstrap/app.php` icinde `web` grubuna session sonrasinda calisacak sekilde baglandi
- `AdminAuthController::updateLocale()` implement edildi ve locale session'a yazilir hale geldi
- Docker icinde `php artisan about`, `route:list --name=admin.locale.update` ve tum test suiti basarili dogrulandi

Bu fazda bilerek sadece host app locale omurgasi tamamlandi. Paket translation loading ve paket `lang` dosyalari Faz 3'te ele alinacak.

### 1. Locale whitelist tanimla

Uygulamada desteklenen dilleri tek yerde tanimla.

Oneri:

- `config/localization.php` olustur
- `supported_locales => ['tr', 'en']`
- `default_locale => 'tr'`
- `fallback_locale => 'en'`

Not:

`config/app.php` yine Laravel'in ana kaynagi olacak. `config/localization.php` sadece uygulama seviyesinde net bir referans noktasi saglar.

### 2. `updateLocale()` implement et

`app/Http/Controllers/AdminAuthController.php` icinde:

- `locale` inputunu validate et
- sadece `tr` ve `en` kabul et
- secilen locale'i session'a yaz
- sonra `back()` don

Ornek mantik:

```php
$locale = $request->validate([
    'locale' => ['required', 'string', Rule::in(config('localization.supported_locales'))],
])['locale'];

$request->session()->put('locale', $locale);

return back();
```

### 3. Locale middleware ekle

Yeni middleware:

- `app/Http/Middleware/SetLocale.php`

Gorevi:

- session'dan locale oku
- yoksa `config('app.locale')` kullan
- locale whitelist disindaysa fallback'e don
- `app()->setLocale($locale)` cagir

Bu middleware `web` akisinda calismali. Laravel 12 yapiniza gore `bootstrap/app.php` icinde web middleware stack'ine eklenmeli.

### 4. Admin locale secimini view'a tasima

Aslinda layout su an `app()->getLocale()` kullandigi icin ekstra paylasim zorunlu degil.

Yine de ihtiyac olursa:

- `View::share('adminCurrentLocale', app()->getLocale())`

kullanilabilir. Ama ilk asamada buna gerek yok.

## Faz 2: Laravel core dil dosyalarini yayinla

### Faz 2 Uygulama Ozeti

Durum: tamamlandi.

- Docker icinde `php artisan lang:publish` calistirildi ve host app `lang/` klasoru olusturuldu
- Laravel'in varsayilan `en` dosyalari projeye alindi
- `lang/tr/auth.php`, `lang/tr/pagination.php`, `lang/tr/passwords.php` ve `lang/tr/validation.php` eklendi
- `validation.php` icinde temel alan adlari icin Turkce `attributes` map'i tanimlandi
- Docker icinde Turkce translation dogrulamasi yapildi ve tum test suiti basarili gecti

Bu faz sadece host app'in Laravel core dil dosyalarini kapsadi. Paket cevirileri ve package-level translation loading sonraki fazlarda ele alinacak.

Bu proje Docker icinde calistigi icin komutlari container icinde calistir.

Ilk adim:

```bash
cd crm
make artisan CMD="lang:publish"
```

Bu komut Laravel'in temel dil dosyalarini host app altindaki `lang/` klasorune cikarir.

Sonra:

- `lang/tr/validation.php`
- `lang/tr/auth.php`
- `lang/tr/pagination.php`
- `lang/tr/passwords.php`

dosyalari doldurulmali.

Not:

Ingilizce dosyalar referans olarak kalabilir. Turkce tarafini tamlamak yeterlidir.

## Faz 3: Paket provider'larina translation loading ekle

### Faz 3 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/admin-panel/src/AdminPanelServiceProvider.php` icine package translation loading eklendi
- `packages/sanalkopru/crm/src/CrmServiceProvider.php` icine package translation loading eklendi
- `admin-panel-lang` ve `crm-lang` publish tag'leri tanimlandi
- Iki pakette de `resources/lang` iskeleti olusturuldu

Bu fazda sadece package-level translation loading ve publish altyapisi tamamlandi. Gercek package ceviri icerikleri sonraki fazlarda doldurulacak.

### 1. Admin panel provider

`packages/sanalkopru/admin-panel/src/AdminPanelServiceProvider.php` icinde:

- `loadJsonTranslationsFrom(__DIR__.'/../resources/lang');`
- `loadTranslationsFrom(__DIR__.'/../resources/lang', 'admin-panel');`

eklenmeli.

Ayrica publish tag eklenmeli:

- `admin-panel-lang`

### 2. CRM provider

`packages/sanalkopru/crm/src/CrmServiceProvider.php` icinde:

- `loadJsonTranslationsFrom(__DIR__.'/../resources/lang');`
- `loadTranslationsFrom(__DIR__.'/../resources/lang', 'crm');`

eklenmeli.

Ayrica publish tag eklenmeli:

- `crm-lang`

Not:

`loadJsonTranslationsFrom()` mevcut `__('English text')` desenini calistirir.

`loadTranslationsFrom()` ise `trans('crm::validation.lost_reason_required')` gibi keyed kullanimlari destekler.

## Faz 4: Admin panel paketini ceviri dostu hale getir

### Faz 4 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/admin-panel/resources/lang/tr.json` eklendi ve admin panelin ortak UI metinleri icin Turkce JSON translation haritasi olusturuldu
- `packages/sanalkopru/admin-panel/resources/lang/en.json` eklendi
- Login, 2FA, layout, export modal ve skin indicator icindeki gorunur raw stringler ceviri sistemine baglandi
- Layout icindeki fallback admin kullanici label'i de ceviri akisina alindi
- Locale'e bagli gorunen admin panel metinleri icin iki feature test locale-aware hale getirildi
- Docker icinde package JSON translation yuklemesi dogrulandi ve tum test suiti basarili gecti

Bu faz sonunda admin panel ortak UI katmani artik gercek anlamda `tr` locale ile ceviri verebilir durumda. CRM paketinin kendi ekran metinleri ise sonraki fazlarda cevrilecek.

Bu faz once yapilmali cunku admin layout ve ortak komponentler tum CRM ekranlarini etkiliyor.

### Yapilacaklar

- `admin-panel` icindeki tum ortak UI metinlerini JSON translation'a bagla
- Zaten `admin_trans()` kullanan yerler korunacak
- Hard-coded role label ve dropdown label'lari da ceviri altina alinacak

Ozellikle kontrol edilecek dosyalar:

- `packages/sanalkopru/admin-panel/resources/views/layouts/app.blade.php`
- `packages/sanalkopru/admin-panel/resources/views/auth/login.blade.php`
- `packages/sanalkopru/admin-panel/resources/views/auth/2fa.blade.php`
- `packages/sanalkopru/admin-panel/resources/views/components/*`

Not:

Admin panel komponentleri label/placeholder cevirisi icin zaten iyi bir temel sunuyor. Bu avantaj korunmali.

## Faz 5: CRM view katmanini cevir

### Faz 5 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/crm/resources/lang/en.json` ve `packages/sanalkopru/crm/resources/lang/tr.json` eklendi
- CRM dashboard, liste ekranlari ve temel form ekranlarindaki gorunur raw stringler `__()` akisina baglandi
- Ozellikle su moduller translation-ready hale getirildi:
  - dashboard
  - activities
  - contacts
  - companies
  - deals
  - tasks
  - quotes
  - tags
  - users
  - settings
  - deal stages
- Empty-state metinleri, bulk delete onaylari, table header'lari, kart basliklari, dashboard panel basliklari ve form aksiyonlari locale-aware oldu
- CRM dashboard icindeki stat-card label'lari da dogrudan ceviri akisina alindi
- CRM liste ekranlari `admin-panel` icindeki ortak `filter-shell` komponentini kullandigi icin, bu komponentteki `Apply` ve `Reset` metinleri de translation'a baglandi
- Locale'e bagli bozulan feature testler locale-aware hale getirildi
- Docker icinde `php artisan view:cache` basarili calisti
- Docker icinde package translation yuklemesi `Satis Panosu`, `Uygula` ve `Secili kisiler silinsin mi?` ciktilariyla dogrulandi
- Docker icinde tum test suiti tekrar kosuldu ve basarili gecti: `162 passed`

Bu faz sonunda CRM paketinin ana admin workflow ekranlari artik `tr` locale ile gercek ceviri uretiyor. Show/detail ekranlarinda kalan daginik raw stringler varsa bunlar sonraki fazlarda domain-level merkezilestirme ile birlikte temizlenebilir.

Bu fazda tum admin CRM ekranlari taranir ve sabit metinler translation sistemine tasinir.

### Donusum kurallari

- Sayfa basliklari: `__('Deals')`
- Empty-state title/body: `__('No deals found.')`
- Button metinleri: `__('New Deal')`
- Confirm mesajlari: `__('Delete this deal?')`
- Input label/placeholder: mevcut komponent akisi korunarak stringler translation'a birakilir

### Oncelik sirasi

1. Navigation ve layout ile gorunen ust seviye metinler
2. Liste sayfalari
3. Form sayfalari
4. Show/detail sayfalari
5. Partial ve modal'lar

Yuksek oncelikli dosyalar:

- `packages/sanalkopru/crm/resources/views/admin/*`
- `packages/sanalkopru/crm/resources/views/admin/partials/*`
- `packages/sanalkopru/crm/resources/views/dashboard/index.blade.php`

## Faz 6: Navigation ve option labellarini merkezilestir

### Faz 6 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/crm/src/Support/CrmLabelCatalog.php` eklendi ve CRM tarafindaki navigation, status, priority, activity type, related record type, discount type, visibility ve role label'lari tek merkezde toplandi
- `CrmNavigation`, `CrmFormatter` ve `DashboardReport` bu merkezi label katalogunu kullanacak sekilde guncellendi
- `ContactsController`, `DealsController`, `QuotesController`, `TasksController`, `ActivitiesController` ve `UsersController` icindeki daginik option/label map'leri kaldirildi; bunlar `CrmLabelCatalog` uzerinden resolve edilmeye baslandi
- CRM timeline ve saved filter partial'lari da merkezi label akisina baglandi; activity type ve filter visibility metinleri artik locale-aware hale geldi
- `packages/sanalkopru/crm/resources/lang/tr.json` dosyasi Faz 5 kapsamindaki gorunur metinlere ek olarak Faz 6'da merkezilestirilen domain label'larini da kapsayacak sekilde genisletildi
- Docker icinde `php artisan view:cache` basarili calisti
- Docker icinde package translation yuklemesi `Genel Bakis`, `Potansiyel Musteri`, `Devam Ediyor` ve `Mevcut Filtreyi Kaydet` ciktilariyla dogrulandi
- Docker icinde tum test suiti tekrar kosuldu ve basarili gecti: `162 passed`

Bu faz sonunda CRM paketindeki navigation ve secim listesi labellari artik controller bazli daginik map'ler yerine tek bir merkezi katalogdan geliyor. Bu da Faz 7'de request, validation, notification ve backend mesajlarini cevirirken ana domain terminolojisini tekrar tekrar elle yonetme ihtiyacini azaltir.

`packages/sanalkopru/crm/src/Services/Navigation/CrmNavigation.php` icinde label'lar su an Ingilizce raw string.

Oneri:

- route ve permission bilgisi ayni kalsin
- label'lar translation key'e donsun

Ornek:

```php
['label' => __('crm::navigation.dashboard'), 'route' => 'crm.dashboard', ...]
```

Alternatif:

- class sadece key dondursun
- Blade tarafi bu key'i cevirsin

Tercih:

Controller/service katmaninda degil, gosterime yakin yerde ceviriye gitmek daha temizdir. Ama navigation gibi saf presentation metadata siniflarinda `__()` kullanmak da kabul edilebilir.

## Faz 7: Validation ve backend mesajlarini cevir

### Faz 7 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/crm/resources/lang/en/messages.php` ve `tr/messages.php` dosyalari dolduruldu; admin flash mesajlari, API response mesajlari, AI hata durumlari, import durumlari ve erisim/guard mesajlari `crm::messages.*` altinda merkezilestirildi
- `packages/sanalkopru/crm/resources/lang/en/validation.php` ve `tr/validation.php` eklendi; custom request validator akislari artik `crm::validation.*` key'leri uzerinden mesaj donuyor
- `AdminAuthController`, `EnsureCrmAccess`, `AuthenticateCrmApi`, `AiAssistant`, `AiResult`, `DeleteDealStage` ve `CrmDataTransferService` icindeki kullaniciya gorunen backend stringleri translation sistemine baglandi
- CRM admin controller'larindaki `crm_status`, `withErrors()` ve JSON `message` alanlari dogrudan translation key kullaniyor hale getirildi
- CRM API controller'larindaki create/update/move/complete response mesajlari da ayni translation katmanina tasindi
- Host uygulamanin `lang/en/validation.php` ve `lang/tr/validation.php` dosyalarindaki `attributes` listesi genisletildi; boylece standart Laravel validation mesajlari da CRM alanlarini daha okunur isimlerle gosteriyor
- Docker icinde `php artisan view:cache` basarili calisti
- Docker icinde package translation yuklemesi `Firsat kazanildi olarak isaretlendi.`, `Iliskili bir kayit secin.` ve `Kimlik dogrulamasi gerekiyor.` ciktilariyla dogrulandi
- Docker icinde tum test suiti tekrar kosuldu ve basarili gecti: `162 passed`

Bu faz sonunda validation, flash status, API response ve guard/AI hata mesajlari artik locale-aware. Sonraki notification fazinda ayni terminoloji tekrar kullanilabilecegi icin backend metinleri icin ikinci bir daginik string katmani olusmuyor.

Bu faz genelde atlanir ama en onemli kalite farkini burada gorursun.

### Yapilacaklar

- `withErrors()` mesajlarini translation'a tasi
- `abort()` metinlerini translation'a tasi
- request custom validation mesajlarini `messages()` ve gerekirse `attributes()` ile ayir
- API error cevaplarinda insan-okunur mesajlari cevir

Ornek riskli alanlar:

- `app/Http/Controllers/AdminAuthController.php`
- `packages/sanalkopru/crm/src/Http/Middleware/AuthenticateCrmApi.php`
- `packages/sanalkopru/crm/src/Http/Middleware/EnsureCrmAccess.php`
- `packages/sanalkopru/crm/src/Http/Requests/*`

Oneri:

Custom validation stringlerini dogrudan `withValidator()` icinde yazmak yerine translation key kullan.

Ornek:

```php
$validator->errors()->add(
    'lost_reason',
    trans('crm::validation.lost_reason_required')
);
```

## Faz 8: Notification ve mail metinlerini cevir

### Faz 8 Uygulama Ozeti

Durum: tamamlandi.

- `packages/sanalkopru/crm/resources/lang/en/notifications.php` ve `tr/notifications.php` eklendi; mail subject/action metinleri, database notification title/body kaliplari ve notification center fallback metinleri `crm::notifications.*` altinda toplandi
- `TaskReminderNotification` artik mail subject, intro, due-date line ve action label icin translation kullaniyor; database notification title'i da locale-aware hale geldi
- `TaskAssignmentNotification`, `QuoteStatusChangedNotification` ve `ImportStatusNotification` icindeki title/body metinleri translation key'lerine tasindi
- `QuoteStatusChangedNotification` icinde quote status label'i `CrmLabelCatalog` uzerinden locale-aware uretiliyor; `ImportStatusNotification` da modul adini merkezi label katalogundan aliyor
- `NotificationCenter` fallback title/body uretimini translation sistemine baglayacak sekilde guncellendi; due date ve priority fallback'lari artik locale-aware
- Notification testleri sabit Ingilizce string yerine translation sonucunu dogrulayacak sekilde guncellendi; import status notification title/body beklentileri de test kapsaminda
- Docker icinde package notification translation yuklemesi `Gorev hatirlatmasi`, `Sirketler ice aktarma tamamlandi` ve `En son CRM guncellemesini incelemek icin acin.` ciktilariyla dogrulandi
- Docker icinde hedefli notification testleri basarili gecti:
  - `CrmNotificationsModuleTest`
  - `CrmDataTransferModuleTest`
- Docker icinde tum test suiti tekrar kosuldu ve basarili gecti: `162 passed`

Bu faz sonunda notification title/body, task reminder mail metinleri ve notification center fallback mesajlari da locale-aware oldu. Boylece kullanicinin dogrudan gordugu bildirim akislari backend localization sisteminin disinda kalan son buyuk alanlardan biri olmaktan cikti.

Notification'lar kullanicinin dogrudan gordugu alanlar oldugu icin localization kapsaminda mutlaka tamamlanmali.

Kontrol listesi:

- mail subject
- `MailMessage` line/action metinleri
- database notification `title`
- timeline/system activity metinleri

Oncelikli dosyalar:

- `packages/sanalkopru/crm/src/Notifications/TaskReminderNotification.php`
- `packages/sanalkopru/crm/src/Notifications/TaskAssignmentNotification.php`
- `packages/sanalkopru/crm/src/Notifications/QuoteStatusChangedNotification.php`

## Faz 9: Domain label ve enum benzeri degerleri cevir

### Faz 9 Uygulama Ozeti

Durum: tamamlandi.

- `CrmLabelCatalog` ve `CrmFormatter` genisletildi; modul label ve model sinifindan related-record label uretimi merkezi hale getirildi
- CRM admin ekranlarindaki kalan raw enum gosterimleri temizlendi:
  - contact lifecycle/source
  - deal/quote/task status badge'leri
  - task priority badge'leri
  - quote item discount type label'lari
  - import/export modal icindeki modul label'lari
- `contacts.show`, `companies.show`, `deals.show`, `quotes.show`, `quotes.index`, `tasks.show`, `quotes.pdf`, `contacts.index` ve benzeri detail/list ekranlari artik `ucfirst(...)` yerine merkezi label akisindan besleniyor
- Eski varyant map'lerde kalan hatali `medium` kontrolu temizlendi; task priority varyantlari mevcut domain degerleriyle (`normal`, `high`, `urgent`) uyumlu hale getirildi
- API resource katmanina backward-compatible label alanlari eklendi:
  - `lifecycle_stage_label`
  - `source_label`
  - `status_label`
  - `priority_label`
  - `discount_type_label`
  - taskable icin `type_key` ve `type_label`
- Boylece raw veritabani degerleri korunurken UI ve API tuketicileri ayni enum alanlarin locale-aware label karsiligini alabiliyor
- Docker icinde API/resource ve modul label dogrulamasi `Potansiyel Musteri`, `Acil` ve `Teklifler` ciktilariyla yapildi
- Docker icinde hedefli testler basarili gecti:
  - `CrmApiModuleTest`
  - `CrmQuotesModuleTest`
  - `CrmContactsModuleTest`
- Docker icinde tum test suiti tekrar kosuldu ve basarili gecti: `162 passed`

Bu faz sonunda enum benzeri domain degerleri icin veritabani degerleri degismeden, gosterim label'i merkezi ve locale-aware hale geldi. Sonraki fazlarda frontend data-attribute veya JS uzerinden bu alanlar kullanilsa bile ayni label katmani tekrar kullanilabilir.

Asagidaki alanlar UI'da label olarak cikiyorsa translation altina alinmali:

- status: `open`, `won`, `lost`
- quote status: `draft`, `sent`, `accepted`, `rejected`, `expired`
- task priority: `low`, `normal`, `high`, `urgent`
- activity type: `note`, `call`, `email`, `meeting`

Dogru yontem:

- veritabani degeri degismesin
- sadece gosterim label'i cevrilsin

Yani:

- DB: `won`
- UI: `__('crm::statuses.deal.won')`

Bu hem performansli hem de veritabanini temiz tutan yaklasimdir.

## Faz 10: JavaScript ve data-attribute mesajlarini cevir

### Faz 10 Uygulama Ozeti

Durum: tamamlandi.

- `admin-panel` ve `crm` tarafina ayri translation payload partial'lari eklendi; `admin.js`, `listing.js` ve `crm.js` artik sabit UI fallback stringlerini Blade'den gelen locale verisiyle okuyor
- JS tarafinda confirm modal, custom select, notification widget, export/listing hata mesajlari, kanban bos durumlari, AJAX toast mesajlari, AI sonuc label'i ve dashboard pager `aria-label` metinleri locale-aware hale getirildi
- `admin-panel` auth sayfalari ve layout icine translation payload enjekte edildi; login email/password ve 2FA placeholder'lari ile pagination `title` / `aria-label` alanlari da ceviri sistemine baglandi
- CRM detail ekranlarinda kalan ham `data-crm-confirm`, `data-admin-select-placeholder`, AI button `title`, `data-crm-ai-label` ve ilgili placeholder stringleri `__()` / `trans()` uzerinden locale-aware hale getirildi
- Bu faz icin gereken yeni `tr.json` anahtarlari `admin-panel` ve `crm` paketlerine eklendi; bir feature test locale-aware beklentiye gore guncellendi
- Docker icinde `vendor:publish --tag=admin-panel-assets --force`, `vendor:publish --tag=crm-assets --force`, `php artisan view:cache`, translation kontrolu ve tam test suiti basarili dogrulandi

Bu faz sonunda kullanicinin tarayicida gordugu JS kaynakli ve attribute tabanli sistem metinleri de host locale ile tutarli calisiyor. Boylece localization sadece Blade ve backend response seviyesinde degil, front-end interaction katmaninda da tamamlanmis oldu.

Blade icindeki `data-*` attribute'larda sabit metinler var. Bunlar da localization kapsaminda.

Ornekler:

- `data-crm-confirm="Delete this deal?"`
- `aria-label="Select all deals"`

Bunlar da Blade tarafinda translation ile yazilmali.

Not:

JS dosyasinin icinde sabit UI metni varsa:

- ya Blade'den data attribute ile ver
- ya da sayfaya global bir translation objesi enjekte et

Bu projede ilk secenek daha hafif ve daha kolay.

## Performans Notlari

### 1. Dosya bazli translation yeterli

Bu sistem icin JSON/PHP translation dosyalari yeterlidir. Redis/database tabanli ozel translation sistemi gerekmiyor.

### 2. `config:cache` ve OPcache ile hizli kalir

Production'da Laravel config cache ve PHP OPcache ile bu translation sistemi ek maliyet yaratmaz.

### 3. Kullanici verisini cevirme

Asagidaki alanlar translation'a sokulmaz:

- company adlari
- contact adlari
- user input notlari
- quote item aciklamalari

Sadece sistem metinleri cevrilir.

### 4. Translation key standardi belirle

Karisikligi azaltmak icin kural belirle:

- shared/admin UI: JSON veya `admin-panel::messages.*`
- CRM domain: `crm::...`
- host app auth/validation: `lang/tr/*.php`

### 5. Gereksiz runtime ceviri katmani ekleme

Static label'lar icin API'den translation cekme, DB query ile label uretme veya custom translation repository yazma. Laravel'in native translation sistemi burada yeterli ve daha hizlidir.

## Docker Uyumlu Komutlar

Bu repo icin host makinede `php artisan` calistirilmaz. Asagidaki akisa sadik kal:

```bash
cd crm
make artisan CMD="lang:publish"
make test
make artisan CMD="optimize:clear"
make artisan CMD="view:cache"
make artisan CMD="route:cache"
make artisan CMD="config:cache"
```

Paket lang publish tag'leri eklendikten sonra:

```bash
cd crm
make artisan CMD="vendor:publish --tag=admin-panel-lang"
make artisan CMD="vendor:publish --tag=crm-lang"
```

Development'ta ceviri dosyalari degistikce:

```bash
cd crm
make artisan CMD="optimize:clear"
```

genelde yeterlidir.

## Onerilen Uygulama Sirasi

En risksiz ilerleme sirasi:

1. Host app locale middleware + session locale kaydi
2. `lang:publish` ile root Laravel dil dosyalari
3. `admin-panel` package translation loading + dosyalar
4. `crm` package translation loading + dosyalar
5. Admin panel ortak UI metinleri
6. CRM navigation ve ortak partial'lar
7. CRM liste/form/show view'lari
8. Validation, controller ve middleware mesajlari
9. Notification/mail/system activity metinleri
10. QA ve cache/test dogrulamasi

## QA Kontrol Listesi

Localization tamamlandiginda en az su testler yapilmali:

- Admin login sayfasi `tr` ve `en` arasinda degisiyor mu
- Admin topbar dil secici secimi session'da kaliyor mu
- CRM sidebar ve topbar metinleri locale ile degisiyor mu
- Liste/form/show ekranlari iki dilde de dogru mu
- Validation mesajlari iki dilde dogru mu
- Empty-state, bulk action, modal ve confirm metinleri iki dilde dogru mu
- Notification `title/body` cevirileri dogru mu
- Mail subject ve body cevirileri dogru mu
- `make test` basarili mi
- `view:cache`, `route:cache`, `config:cache` basarili mi

## Son Tavsiye

Bu proje icin en saglikli yol:

- mevcut `admin_trans()` ve `__()` desenini korumak
- UI tarafinda JSON translation kullanmak
- backend/domain tarafinda keyed translation kullanmak
- locale secimini session tabanli yapmak
- translation dosyalarini package seviyesinde tutmak

Bu yaklasim:

- en az refactor ile ilerler
- package mimarisini bozmaz
- performanslidir
- bakimi kolaydir
- yeni diller eklenince tekrar kullanilabilir bir temel olusturur

## Bu Tespitlere Gore Nihai Durum

Sistemde localization icin bir baslangic var ama gercek anlamda altyapi tamam degil.

En kritik eksikler:

- locale persistence yok
- locale middleware yok
- package translation loading yok
- package lang dosyalari yok
- CRM domain metinleri hala buyuk oranda hard-coded

Yani bu ise sifirdan degil ama yari hazir bir sistemden basliyoruz.
