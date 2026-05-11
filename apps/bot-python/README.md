# Bot Python Service

Service ini berisi:
- `bot.py` untuk Telegram bot
- `sync_dashboard.py` untuk update dashboard massal dari master sheet

## Setup

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
```

## Run Bot

```bash
python bot.py
```

## Run Dashboard Sync

```bash
python sync_dashboard.py --version v1.0 --dry-run
python sync_dashboard.py --version v1.0
```
