# Yedekleme Stratejisi

## Ne Yedeklenir

| Kaynak | İçerik | Sıklık |
|---|---|---|
| MySQL veritabanı | Tüm CRM verisi | Günlük (yoğun kullanımda 6 saatte bir) |
| `storage/app/public` | Logo, import/export dosyaları | Günlük |
| `.env` | Ortam yapılandırması (APP_KEY dahil!) | Değişiklikte |

> **APP_KEY kaybedilirse** şifreli alanlar (2FA secret'ları, kurtarma kodları) geri getirilemez. `.env` yedeğini mutlaka güvenli bir kasada tutun.

## Basit Cron Tabanlı Yedek

```bash
#!/usr/bin/env bash
# /usr/local/bin/crm-backup.sh
set -euo pipefail

STAMP=$(date +%Y%m%d-%H%M)
BACKUP_DIR=/var/backups/crm
APP_DIR=/var/www/crm/current

mkdir -p "$BACKUP_DIR"

# 1) Veritabanı
mysqldump --single-transaction --quick crm_production | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

# 2) Yüklenen dosyalar
tar -czf "$BACKUP_DIR/storage-$STAMP.tar.gz" -C "$APP_DIR" storage/app/public

# 3) 14 günden eski yedekleri temizle
find "$BACKUP_DIR" -name "*.gz" -mtime +14 -delete
```

Cron:

```cron
30 03 * * * /usr/local/bin/crm-backup.sh >> /var/log/crm-backup.log 2>&1
```

Yedekleri sunucu dışına da kopyalayın (rclone ile S3/B2 önerilir).

## Restore Prosedürü (test edilmeden yayına çıkmayın)

```bash
# Veritabanı
gunzip < db-YYYYMMDD-HHMM.sql.gz | mysql crm_production

# Dosyalar
tar -xzf storage-YYYYMMDD-HHMM.tar.gz -C /var/www/crm/current

php artisan config:clear && php artisan cache:clear
php artisan crm:doctor
```

## Alternatif: spatie/laravel-backup

Daha gelişmiş ihtiyaçlar (bildirimli yedek, otomatik S3, sağlık kontrolü) için `spatie/laravel-backup` paketi eklenebilir; bu üründe çekirdeği sade tutmak için varsayılan olarak dahil edilmemiştir.
