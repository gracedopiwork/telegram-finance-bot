# API Contract (Current)

## Landing / Checkout

### `POST /checkout`
Create order and request Midtrans payment link.

Form fields:
- `full_name` (required)
- `email` (required)
- `telegram_username` (optional)
- `plan` (`lite|pro|ecosystem`)

Result:
- Redirect to Midtrans payment page (if success)
- Redirect back with message (if Midtrans config fails)

## Payment Webhook

### `POST /webhooks/midtrans`
Midtrans callback endpoint.

Behavior:
- Verify signature (`signature_key`)
- Find order by `order_id`
- Store payload in `payment_events`
- If paid (`capture/settlement`): set order `paid` and create `licenses` record
- If failed (`deny/cancel/expire`): set order `failed`

## Admin

### `GET /admin?token=...`
Simple admin dashboard page.

### `POST /admin/user-sheets?token=...`
Upsert user sheet registry.

Fields:
- `telegram_user_id`
- `spreadsheet_id`
- `spreadsheet_url` (optional)

### `POST /admin/dashboard-sync?token=...`
Trigger python `sync_dashboard.py` script.

Fields:
- `version` (example `v1.0`)
