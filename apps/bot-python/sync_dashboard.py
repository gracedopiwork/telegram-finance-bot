import argparse
import logging
import os
from typing import Dict, List, Tuple

import gspread
import mysql.connector
from dotenv import load_dotenv
from oauth2client.service_account import ServiceAccountCredentials

load_dotenv()

logging.basicConfig(
    format="%(asctime)s - %(levelname)s - %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)


def get_env(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise RuntimeError(f"Environment variable '{name}' belum diisi.")
    return value


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Sync Dashboard tab from master to all user sheets")
    parser.add_argument("--version", required=True, help="Versi dashboard, contoh: v1.3")
    parser.add_argument("--dry-run", action="store_true", help="Hanya tampilkan target tanpa menulis perubahan")
    parser.add_argument("--limit", type=int, default=0, help="Batasi jumlah sheet untuk testing")
    return parser.parse_args()


def get_db_connection():
    return mysql.connector.connect(
        host=get_env("MYSQL_HOST"),
        port=int(get_env("MYSQL_PORT", required=False) or "3306"),
        user=get_env("MYSQL_USER"),
        password=os.getenv("MYSQL_PASSWORD", ""),
        database=get_env("MYSQL_DATABASE"),
    )


def build_gspread_client() -> gspread.Client:
    scope = [
        "https://spreadsheets.google.com/feeds",
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive.file",
        "https://www.googleapis.com/auth/drive",
    ]
    json_path = get_env("GOOGLE_SERVICE_ACCOUNT_JSON")
    credentials = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
    return gspread.authorize(credentials)


def load_targets(limit: int = 0) -> List[Dict[str, str]]:
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    query = """
        SELECT telegram_user_id, spreadsheet_id
        FROM user_sheets
        WHERE status = 'active'
        ORDER BY id ASC
    """
    if limit > 0:
        query += " LIMIT %s"
        cursor.execute(query, (limit,))
    else:
        cursor.execute(query)
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def upsert_dashboard_tab(
    client: gspread.Client,
    master_spreadsheet_id: str,
    master_sheet_title: str,
    target_spreadsheet_id: str,
) -> Tuple[int, int]:
    source_spreadsheet = client.open_by_key(master_spreadsheet_id)
    source_ws = source_spreadsheet.worksheet(master_sheet_title)
    source_rows = source_ws.row_count
    source_cols = source_ws.col_count

    formulas = source_ws.get(
        f"A1:{gspread.utils.rowcol_to_a1(source_rows, source_cols)}",
        value_render_option="FORMULA",
    )

    target_spreadsheet = client.open_by_key(target_spreadsheet_id)
    existing = None
    try:
        existing = target_spreadsheet.worksheet(master_sheet_title)
    except gspread.WorksheetNotFound:
        existing = None

    if existing:
        existing.clear()
        ws = existing
    else:
        ws = target_spreadsheet.add_worksheet(
            title=master_sheet_title,
            rows=max(source_rows, 100),
            cols=max(source_cols, 20),
        )

    ws.resize(rows=max(source_rows, 1), cols=max(source_cols, 1))
    if formulas:
        ws.update(
            f"A1:{gspread.utils.rowcol_to_a1(source_rows, source_cols)}",
            formulas,
            value_input_option="USER_ENTERED",
        )
    return source_rows, source_cols


def mark_synced(spreadsheet_id: str, version: str) -> None:
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        """
        UPDATE user_sheets
        SET dashboard_version = %s, last_synced_at = NOW()
        WHERE spreadsheet_id = %s
        """,
        (version, spreadsheet_id),
    )
    conn.commit()
    cursor.close()
    conn.close()


def main() -> None:
    args = parse_args()
    master_spreadsheet_id = get_env("DASHBOARD_MASTER_SPREADSHEET_ID")
    master_sheet_title = get_env("DASHBOARD_MASTER_SHEET_TITLE", required=False) or "Dashboard"

    client = build_gspread_client()
    targets = load_targets(args.limit)
    if not targets:
        logger.info("Tidak ada target user_sheets status active.")
        return

    logger.info("Mulai sync dashboard versi %s ke %d spreadsheet.", args.version, len(targets))
    success = 0
    failed = 0

    for row in targets:
        spreadsheet_id = row["spreadsheet_id"]
        user_id = row["telegram_user_id"]
        try:
            if args.dry_run:
                logger.info("[DRY RUN] user=%s spreadsheet=%s", user_id, spreadsheet_id)
                success += 1
                continue

            source_rows, source_cols = upsert_dashboard_tab(
                client=client,
                master_spreadsheet_id=master_spreadsheet_id,
                master_sheet_title=master_sheet_title,
                target_spreadsheet_id=spreadsheet_id,
            )
            mark_synced(spreadsheet_id=spreadsheet_id, version=args.version)
            logger.info(
                "Sukses sync user=%s spreadsheet=%s (%sx%s).",
                user_id,
                spreadsheet_id,
                source_rows,
                source_cols,
            )
            success += 1
        except Exception as exc:  # pragma: no cover
            logger.exception("Gagal sync user=%s spreadsheet=%s: %s", user_id, spreadsheet_id, exc)
            failed += 1

    logger.info("Selesai. sukses=%d gagal=%d", success, failed)


if __name__ == "__main__":
    main()
