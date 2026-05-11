import argparse

from app.bot.service import main as run_bot
from app.web.service import run as run_web


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Telegram finance bot monorepo runner")
    parser.add_argument(
        "service",
        choices=["bot", "web", "all"],
        help="Pilih service yang mau dijalankan",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    if args.service == "bot":
        run_bot()
        return
    if args.service == "web":
        run_web()
        return

    raise SystemExit(
        "Mode 'all' butuh process manager (PM2/Supervisor). "
        "Jalankan bot dan web sebagai 2 process terpisah."
    )


if __name__ == "__main__":
    main()
