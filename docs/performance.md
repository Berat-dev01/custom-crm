# CRM Performance Notes

Bu dokuman Adim 28 sonrasi CRM'in buyuyen veri setlerinde nasil davranmasi gerektigini ve hangi kontrollerin uygulandigini ozetler.

## Hedef Veri Seti

Performans kabul senaryosu:

- 10.000 contact
- 2.000 company
- 5.000 deal

Bu veri seti Docker development ortaminda su seeder ile uretilebilir:

```bash
docker compose run --rm --no-deps app php artisan db:seed --class="Sanalkopru\\Crm\\Database\\Seeders\\CrmPerformanceSeeder"
```

Seeder siniflari:

- `Sanalkopru\Crm\Database\Seeders\CrmPerformanceSeeder`
- `Sanalkopru\Crm\Database\Seeders\CrmDealStageSeeder`

## Kanban Limit Stratejisi

Kanban ekrani artik her stage icin tum deal'leri tek seferde cekmez.

Varsayilanlar:

- `CRM_KANBAN_PER_STAGE_LIMIT=50`
- `CRM_KANBAN_PER_STAGE_MAX_LIMIT=100`

Kullanici `per_stage` query parametresi ile limit isteyebilir; kod bu degeri max limite kadar kabul eder. Stage toplam deal sayisi ve pipeline value ise ayri aggregate sorgudan gelir, bu nedenle kart listesi limitli olsa bile stage metrikleri dogru kalir.

Kanban daha fazla kayit oldugunda ekranda "Showing X of Y deals" uyarisi gosterir. Tam liste icin filtre veya list view kullanilir.

## Dashboard Optimizasyonu

Dashboard bolumleri aggregate ve limitli sorgularla calisir:

- Stats: count/sum aggregate sorgulari.
- Pipeline by stage: stage bazli group by.
- Monthly won/lost trend: tek grouped aggregate sorgusu.
- Upcoming tasks: limit 6, eager loading.
- Recent activities: limit 8, eager loading.
- Top open deals: limit 6, eager loading.
- Quote status distribution: status group by.

`CrmPerformanceModuleTest` dashboard query count'unun bounded kaldigini test eder.

## Indexler

Adim 28 ile eklenen composite index migration'i:

`2026_04_22_000015_add_performance_indexes_to_crm_tables.php`

Eklenen index gruplari:

- contacts: owner/lifecycle/created, company/deleted
- companies: owner/sector/created
- deals: stage/status/position, owner/status/expected date, status/closed date, status/value
- tasks: assigned/status/due, status/reminder/notified
- quotes: owner/status/created, status/valid_until, company/status
- activities: user/occurred, activityable/occurred
- tag_relations: taggable/tag

## N+1 Kontrolleri

Mevcut query servislerinde eager loading kullanilir:

- Contacts: company, owner, tags
- Companies: owner, tags
- Deals: stage, company, contact, owner, tags, open task count
- Tasks: assignee, taskable
- Quotes: company, contact, deal, owner, tags, items
- Dashboard cards: ilgili relation'lar limitli eager load edilir

## Import/Export Memory

Import islemleri:

- Preview sadece ornek satirlari okur.
- Buyuk importlar `CRM_IMPORT_QUEUE_THRESHOLD` uzerinden queue'ya alinir.
- Import hata raporu disk uzerine yazilir.

Export islemleri su an collection bazli CSV stream eder. 10k civari CRM veri setleri icin yeterlidir; daha buyuk musteri kurulumlarinda chunked export veya queued export eklenmelidir.

## Test Coverage

Performans regresyon testleri:

- `CrmPerformanceModuleTest::test_kanban_pipeline_limits_deals_per_stage_but_keeps_aggregates`
- `CrmPerformanceModuleTest::test_dashboard_report_keeps_query_count_bounded`

Ilgili mevcut testler:

- `CrmDealsPipelineModuleTest`
- `CrmDashboardModuleTest`
- `CrmDataTransferModuleTest`
- `CrmGlobalSearchUxTest`
