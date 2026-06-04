# CRM Engine

A production-ready, white-label Laravel CRM built for rapid client delivery. Drop it into any Laravel application, configure it once, and hand off a fully functional CRM without writing it from scratch.

Designed for single-tenant deployments by default — each client gets their own installation, database, and domain. The codebase is kept tenant-ready so a public portal, mobile app, or SaaS surface can be layered on top without rewriting business logic.

---

## Features

| Area | Capabilities |
|---|---|
| **Contacts & Companies** | Full management, lifecycle stages, tagging, import/export |
| **Deals** | Kanban pipeline, drag-and-drop, won/lost flows |
| **Tasks** | Reminders, queue-backed scheduling, calendar view |
| **Quotes** | VAT/discount calculation, PDF preview and download |
| **Activities** | Timeline, automated system activities |
| **Dashboard** | Reporting, performance-optimised aggregate queries |
| **Settings** | Brand info, quote defaults, logo upload |
| **API** | Token-protected `/api/crm` layer |
| **Security** | Audit log, policy-based permissions, rate limiting |
| **AI** | Driver-based: `openai`, `claude`, `gemini`, `null` |

---

## Quick Start (Docker)

Development runs entirely inside Docker. No local PHP, Composer, or Node required.

```bash
cp .env.example .env
make up
make composer CMD="install"
make artisan CMD="key:generate"
make fresh
```

App: **http://localhost:8081**

### Demo Accounts

| Role | Email | Password |
|---|---|---|
| Owner | `crm.owner@example.com` | `password` |
| Manager | `crm.manager@example.com` | `password` |
| Sales | `crm.sales@example.com` | `password` |
| Support | `crm.support@example.com` | `password` |
| Viewer | `crm.viewer@example.com` | `password` |

### Seed Data

```bash
# Demo dataset
make artisan CMD="crm:seed-demo"

# Large performance dataset
make artisan CMD="crm:seed-performance"
```

---

## Admin Panel Integration

CRM views are built on the embedded `admin-panel` layout and admin guard. The package source lives under `app/AdminPanel/` — no external package dependency at runtime.

CRM screens run isolated under `/admin/crm`. Admin authentication is handled by the `admin` guard configured in `config/admin-panel.php`.

---

## Publish Commands

```bash
make artisan CMD="vendor:publish --tag=crm-config"
make artisan CMD="vendor:publish --tag=crm-views"
make artisan CMD="vendor:publish --tag=crm-migrations"
make artisan CMD="vendor:publish --tag=crm-assets"
make artisan CMD="migrate"
```

---

## AI Provider

Select a provider in `.env`:

```env
CRM_AI_ENABLED=false
CRM_AI_DRIVER=openai   # openai | claude | gemini | null
```

The AI layer generates drafts and summaries. It does not modify CRM records without explicit user confirmation.

---

## Testing & QA

```bash
make test
make artisan CMD="migrate:fresh --seed --force"
make composer CMD="validate --strict"
```

---

## Demo Reset

Set `APP_ENV=demo` in `.env` to unlock the `demo:reset` command. The command runs `migrate:fresh --seed --force` and refuses to execute in any other environment.

```bash
# Manual reset
php artisan demo:reset

# Or via Docker
make artisan CMD="demo:reset"
```

To schedule an automatic reset (e.g. every night at 02:00), add the Laravel scheduler to the server crontab and configure the schedule in `routes/console.php`:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('demo:reset')->dailyAt('02:00');
```

Then register the scheduler cron on the server (once per server):

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Production

Production does not use Docker. Target stack: **Nginx · PHP-FPM · MySQL · Redis · Supervisor (queue) · Cron (scheduler) · SSL/TLS**.

---

## Project Structure

```
app/Crm/               Business logic, models, actions, policies
app/AdminPanel/        Embedded admin panel (layout, middleware, facade)
config/crm.php         CRM configuration
config/admin-panel.php Admin panel configuration
routes/crm.php         CRM route definitions
resources/views/crm/   Blade views
docs/                  Internal development documentation (git-ignored)
```

---

## License

Proprietary. All rights reserved. Not for public distribution.
