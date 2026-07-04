# Go-Live Checklist

Müşteri sunucusuna kurulumdan yayına kadar sırayla uygulanır. Detaylar için ilgili dokümanlara bakın: `installation.md`, `production-deploy-no-docker.md`, `production-scheduler.md`, `security-checklist.md`, `backup.md`.

## 1. Sunucu ve Ortam

- [ ] PHP 8.3+, MySQL 8+, Redis, Nginx + PHP-FPM hazır
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `.env` üretildi (`.env.example` üzerinden); `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` doğru
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## 2. Servisler

- [ ] Queue worker Supervisor altında (`production-scheduler.md` örneği)
- [ ] Cron: `* * * * * php artisan schedule:run`
- [ ] SMTP ayarları girildi; test maili gönderildi (`installation.md` e-posta bölümü)
- [ ] HTTPS zorunlu (Nginx redirect + HSTS önerilir)

## 3. Uygulama Ayarları

- [ ] İlk owner kullanıcısı oluşturuldu, güçlü parola + 2FA etkin
- [ ] CRM Settings: firma bilgileri, logo, para birimi, KDV, teklif prefix'i
- [ ] Bildirim anahtarları kontrol edildi (e-posta anahtarı SMTP hazır olmadan açılmamalı)
- [ ] Deal stage'leri müşteriye göre düzenlendi
- [ ] Demo verisi YOK (production'da `crm:seed-demo` çalıştırmayın)

## 4. Doğrulama

- [ ] `php artisan crm:doctor` → tüm kontroller OK
- [ ] Login + 2FA akışı test edildi
- [ ] Teklif oluştur → gönder → müşteri maili ve public link doğrulandı
- [ ] API health: `GET /api/crm/v1/health` 200
- [ ] Webhook kullanılacaksa test teslimatı yapıldı

## 5. Yedekleme ve İzleme

- [ ] Günlük DB + storage yedeği kuruldu ve restore test edildi (`backup.md`)
- [ ] Log rotasyonu aktif (`logrotate` veya Laravel daily driver)
- [ ] Hata izleme (opsiyonel: Sentry/Flare) yapılandırıldı

## 6. Teslim

- [ ] Kullanıcı kılavuzu paylaşıldı (`user-guide.tr.md` / `user-guide.en.md`)
- [ ] API kullanılacaksa `api.md` + `openapi.yaml` geliştiriciye iletildi
- [ ] Sürüm etiketi: `git tag vX.Y.Z` — CHANGELOG güncel
