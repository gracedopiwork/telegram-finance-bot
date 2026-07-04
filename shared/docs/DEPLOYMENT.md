# Deployment Guide (VPS)

## 1) Prasyarat

- Ubuntu 22.04
- Domain aktif (contoh: `financebot.domain.com`)
- MySQL 8
- PHP 8.2+, Composer, Nginx
- Python 3.10+

## 2) Clone Project

```bash
git clone <repo-url> telegram-finance-bot
cd telegram-finance-bot
```

## 3) Setup Database

```bash
mysql -u root -p -e "CREATE DATABASE telegram_finance_bot"
mysql -u root -p telegram_finance_bot < shared/database/schema.sql
```

## 4) Setup Laravel (Landing + Admin + Checkout)

```bash
cd apps/admin-laravel
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Isi `.env` penting:
- `DB_*` (MySQL)
- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_CLIENT_KEY`
- `MIDTRANS_IS_PRODUCTION=true/false`
- `ADMIN_DASHBOARD_TOKEN`

Jalankan worker:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## 5) Setup Bot Python

```bash
cd ../bot-python
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
python bot.py
```

Isi `.env` bot:
- Telegram + Anthropic (Claude) API key
- MySQL credentials
- `LICENSE_REQUIRED=true`
- Dashboard master config untuk sync script

## 6) Nginx Reverse Proxy

Gunakan domain ke Laravel:

```nginx
server {
    listen 80;
    server_name financebot.domain.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

Aktifkan SSL (certbot) sebelum go-live.

## 7) Process Manager

- Laravel: systemd untuk `php artisan serve` atau lebih baik PHP-FPM + Nginx.
- Bot Python: systemd/supervisor untuk `python bot.py`.
- Dashboard sync: jalankan manual dari admin dashboard atau cron.

## 8) Endpoint Penting

- Landing: `/`
- Checkout: `POST /checkout`
- Midtrans webhook: `POST /webhooks/midtrans`
- Admin panel: `/admin?token=...`

## 9) Go-live Checklist

- [ ] `MIDTRANS_IS_PRODUCTION=true` dan key production sudah benar
- [ ] webhook URL Midtrans mengarah ke domain HTTPS final
- [ ] `ADMIN_DASHBOARD_TOKEN` tidak kosong
- [ ] bot jalan 24/7 dan auto-restart aktif
- [ ] backup database harian aktif
