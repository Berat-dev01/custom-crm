# CRM Test Suite

Bu dokuman Adim 27 sonrasi CRM test paketinin hangi riskleri kapattigini ozetler.

## Calistirma

Tum testler Docker icinde calisir:

```bash
make test
```

Komut `docker compose run --rm --no-deps app php artisan test` kullanir. Test ortami `phpunit.xml` uzerinden sqlite memory database, array cache/session, sync queue ve array mailer ile izole edilir.

## Unit Coverage

- Quote total calculation: fixed/percentage discount, item-level tax, rounding.
- Deal stage transition: open, won, lost, reopen davranislari.
- AI driver manager: OpenAI, Claude, Gemini, null driver secimi ve availability.
- AI assistant prompt: provider mock ile temizlenmis bounded context dogrulamasi.
- Formatter/config services: para, tarih, branding ve feature flag davranisi.

## Feature Coverage

- Contacts CRUD, duplicate validation, notes, import/export, bulk actions.
- Companies CRUD, duplicate validation, related-record delete guard.
- Deals CRUD, kanban/list, move, stage close, detail workflow.
- Deal stages CRUD, reorder ve stage delete guard.
- Tasks CRUD, my/today/overdue filters, complete, reminder notification command.
- Quotes CRUD, totals, status actions, duplicate, PDF preview/download.
- Import/export preview, templates, queue threshold, error report.
- Dashboard metrics and owner scoping.
- Settings, branding, default tax/currency/AI overrides.
- API auth, policy, validation, resource response and action endpoints.
- Audit/security events, redaction, upload validation, AI/API rate limits.
- Tags, saved filters, global search and UX empty-state hooks.

## Minimum UI Smoke

Automated smoke checks verify that critical HTML/JS integration hooks exist:

- Kanban board exposes `data-crm-kanban-board`, `data-crm-kanban-list`, stage metadata and deal move URLs.
- Quote form exposes `data-crm-quote-form`, line item container, add-line action and line item input names.

Manual QA remains useful for real drag/drop behavior across browsers; see `docs/qa/deals-kanban.md`.

## Regression Rule

Yeni musteri ozellestirmesi eklenirken en az bir test su kategorilerden birini kapsamalidir:

- domain action/unit behavior
- policy/authorization behavior
- validation behavior
- API/resource response behavior
- UI smoke hook
- import/export or queue behavior
- audit/security behavior
