# Telegram Finance Bot Monorepo

Satu repository untuk:
- Bot Telegram (Python)
- Admin + landing + checkout (Laravel)
- Database schema bersama (MySQL)

## Struktur Folder

- `apps/bot-python` -> service bot Telegram
- `apps/admin-laravel` -> landing page, checkout, webhook Midtrans, admin dashboard
- `shared/database/schema.sql` -> schema SQL terpadu
- `shared/docs/PROPOSAL.md` -> proposal paket
- `shared/docs/API_CONTRACT.md` -> kontrak endpoint utama
- `shared/docs/DEPLOYMENT.md` -> panduan deploy VPS

## Quick Start

### 1) Database

```bash
mysql -u root -p -e "CREATE DATABASE telegram_finance_bot"
mysql -u root -p telegram_finance_bot < shared/database/schema.sql
```

### 2) Bot Python

```bash
cd apps/bot-python
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
python bot.py
```

### 3) Admin Laravel

```bash
cd apps/admin-laravel
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Endpoint utama:
- Landing: `http://127.0.0.1:8000/`
- Admin: `http://127.0.0.1:8000/admin?token=ADMIN_DASHBOARD_TOKEN`
- Webhook Midtrans: `POST /webhooks/midtrans`

## Catatan Integrasi

- Bot dan Laravel harus memakai database MySQL yang sama.
- Midtrans webhook akan update `orders` dan membuat `licenses` otomatis saat payment settle.
