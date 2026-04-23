# CRM API

Base path: `/api/crm`

The API is independent from the admin controllers. Controllers use Laravel API Resources for JSON output and call the same action/service layer used by the admin panel.

## Authentication

Protected endpoints require a Bearer token:

```http
Authorization: Bearer crm_live_xxx
Accept: application/json
Content-Type: application/json
```

Tokens are stored hashed in `crm_api_tokens`. A token authenticates the related user only; it does not bypass CRM roles or policies.

To issue a token from a trusted console:

```bash
docker compose exec app php artisan tinker
```

```php
$user = App\Models\User::query()->where('email', 'owner@example.com')->firstOrFail();
Sanalkopru\Crm\Models\CrmApiToken::issueFor($user, 'mobile-app');
```

Store the returned `plain_text_token` securely. It is shown only once.

## Rate Limit

Protected API routes use the `crm-api` rate limiter. Configure it with:

```env
CRM_API_RATE_LIMIT_PER_MINUTE=120
CRM_API_DEFAULT_PER_PAGE=20
CRM_API_MAX_PER_PAGE=100
```

## Response Shape

Single records:

```json
{
  "data": {
    "id": 1,
    "public_id": "uuid",
    "created_at": "2026-04-23T12:00:00.000000Z"
  }
}
```

Mutations include a message:

```json
{
  "message": "Contact created.",
  "data": {}
}
```

Validation errors return HTTP `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

Unauthenticated requests return `401`; authenticated users without the required CRM permission return `403`.

## Endpoints

### Health

`GET /api/crm/health`

### Contacts

- `GET /api/crm/contacts`
- `POST /api/crm/contacts`
- `GET /api/crm/contacts/{contact}`
- `PUT/PATCH /api/crm/contacts/{contact}`

Filters: `search`, `lifecycle_stage`, `source`, `company_id`, `owner_id`, `tag_id`, `sort`, `direction`, `per_page`.

Required create/update fields follow the admin validation contract. Main fields: `first_name`, `last_name`, `full_name`, `email`, `phone`, `title`, `company_id`, `lifecycle_stage`, `source`, `owner_id`, `tag_ids`, `custom_fields_json`.

### Companies

- `GET /api/crm/companies`
- `POST /api/crm/companies`
- `GET /api/crm/companies/{company}`
- `PUT/PATCH /api/crm/companies/{company}`

Filters: `search`, `sector`, `city`, `owner_id`, `tag_id`, `sort`, `direction`, `per_page`.

Main fields: `name`, `email`, `phone`, `website`, `tax_number`, `tax_office`, `sector`, address fields, `owner_id`, `tag_ids`, `custom_fields_json`.

### Deals

- `GET /api/crm/deals`
- `POST /api/crm/deals`
- `GET /api/crm/deals/{deal}`
- `PUT/PATCH /api/crm/deals/{deal}`
- `POST /api/crm/deals/{deal}/move`

Filters: `search`, `owner_id`, `tag_id`, `status`, `expected_from`, `expected_to`, `value_min`, `value_max`, `per_page`.

Main fields: `title`, `contact_id`, `company_id`, `stage_id`, `value`, `currency`, `probability`, `expected_close_date`, `status`, `lost_reason`, `owner_id`, `tag_ids`, `custom_fields_json`.

Move payload:

```json
{
  "stage_id": 2,
  "position": 1,
  "lost_reason": null
}
```

### Tasks

- `GET /api/crm/tasks`
- `POST /api/crm/tasks`
- `GET /api/crm/tasks/{task}`
- `PUT/PATCH /api/crm/tasks/{task}`
- `POST /api/crm/tasks/{task}/complete`

Filters: `scope`, `search`, `assigned_to`, `priority`, `status`, `due_from`, `due_to`, `per_page`.

Allowed `scope`: `all`, `my`, `today`, `overdue`.

Main fields: `title`, `description`, `taskable_type`, `taskable_id`, `assigned_to`, `due_at`, `reminder_at`, `priority`, `status`.

### Quotes

- `GET /api/crm/quotes`
- `POST /api/crm/quotes`
- `GET /api/crm/quotes/{quote}`
- `PUT/PATCH /api/crm/quotes/{quote}`

Filters: `search`, `status`, `owner_id`, `tag_id`, `valid_from`, `valid_to`, `per_page`.

Main fields: `contact_id`, `company_id`, `deal_id`, `status`, `currency`, `discount_type`, `discount_value`, `valid_until`, `notes`, `terms`, `owner_id`, `tag_ids`, `items`.

Quote item fields: `name`, `description`, `quantity`, `unit_price`, `discount_type`, `discount_value`, `tax_rate`, `position`.
