# Changelog

All notable changes to CRM Engine are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/) and the project uses semantic versioning.

## [1.0.0] - 2026-07-04

First production-ready, sellable release.

### Added
- **Email notifications** for task assignment/reminders, quote status changes, deal won/lost, import results — with a global switch, per-user per-event opt-outs and queued delivery.
- **Customer quote email**: sending a quote emails the customer a PDF plus a **public approval link** (`/quote/{token}`) where they can accept, decline with a reason, or download the PDF without logging in.
- **Weekly digest email** to owners/managers (`crm:digest:send-weekly`, Mondays 08:00).
- **Webhooks**: HMAC-SHA256 signed deliveries with retries and a delivery log for contact/company/deal created, deal won/lost, quote sent/accepted/rejected, task completed.
- **API v1** (`/api/crm/v1`) with 308 redirects from legacy paths; DELETE endpoints on all resources; new activities, tags and deal-stages endpoints; OpenAPI spec at `docs/openapi.yaml`.
- **API token management screen** (create/revoke, one-time plaintext display).
- **Audit log viewer** with event/user/date filters and field-level diffs.
- **Trash screen**: restore or permanently delete soft-deleted contacts, companies, deals, quotes.
- **Two-factor authentication** (TOTP) with QR setup, recovery codes and a login challenge.
- **Private ICS calendar feed** of assigned tasks for Google Calendar/Outlook.
- **crm:doctor** post-installation health check command.
- GitHub Actions CI (Pint, PHPUnit on PHP 8.3/8.4, composer audit).

### Changed
- Quote status transitions are enforced by a state machine; accepted/rejected quotes are locked (duplicate as draft to change).
- Kanban pipeline loads in two queries via window functions; dashboard aggregates cached (configurable TTL).
- Product documentation now ships with the repository (`docs/`); internal notes live in `docs/internal/` (ignored).

### Security
- Global security headers (X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy).
- Login brute-force lockout (5/min per email+IP) and a minimum password policy (10+ chars, letters and numbers).
- Logo uploads re-encoded through GD; EXIF/appended payloads stripped.
- API is bearer-token only; dependencies updated past all known advisories (`composer audit` clean).
