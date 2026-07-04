# CRM — Eksikler & Güvenlik Bulguları

> İlk denetim: 2026-06-02 · Son güncelleme: 2026-07-03
> Durum: **Tüm maddeler kapatıldı.** Üretim hazırlık listesi için `/Users/zyix/Desktop/repo/CRM_PRODUCTION_TODO.md` kullanılıyor.

## Kapatılan Maddeler

| # | Bulgu | Çözüm |
|---|-------|-------|
| 1 | Deal alt-kaynaklarında yetki açığı | `DealsController::storeTask/storeQuote/storeActivity` artık `create` policy'lerini de doğruluyor |
| 2 | `ImportCrmRecordsRequest` herkese açık | Modül bazlı `crm.{module}.import` yetkisi FormRequest'te zorunlu |
| 3 | API oturum cookie fallback (CSRF riski) | `AuthenticateCrmApi` yalnızca Bearer token kabul ediyor |
| 4 | `bucketExpression` string birleştirme | Kolon + bucket whitelist'i eklendi, geçersiz değer exception |
| 5 | Logo içerik doğrulaması | `getimagesize` doğrulaması + GD ile yeniden kodlama (EXIF payload temizliği); webp kaldırıldı |
| 6 | Bulk delete limitsiz | `chunkById(200)` + `record_ids` için `max:500` |
| 7 | Legacy `ContactImportService` | Ölü kod kaldırıldı; tüm import akışı `CrmDataTransferService` üzerinde |
| 8 | Export row limiti yok | 10.000 satır limiti |
| 9 | Web rotalarında throttle yok | Admin grubunda `throttle:240,1`; login 2FA challenge `throttle:10,1` |
| 10 | Kanban stage başına sorgu | `ROW_NUMBER()` window function ile 2 sorguya indirildi |
| 11 | SavedFilter sahiplik kontrolü | `destroy` sahip veya `settings.manage` yetkisi istiyor |
| 12 | Settings cache org-scoped değil | Cache anahtarı organization parametresiyle üretiliyor (tenant-ready) |
| 13 | AI hataları loglanmıyor | `AiAssistant::run` hataları `Log::error` ile kaydediyor |
| 14 | Token `last_used_at` her istekte yazıyor | 60 saniyelik throttle ile güncelleniyor |
| 15 | Yetki sınırı test eksikleri | `CrmSecurityBoundaryTest` viewer yazma denemeleri + cross-user senaryolarını kapsıyor |
