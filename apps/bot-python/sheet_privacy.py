"""Proteksi tab Transaksi/Dashboard: hanya service account yang bisa edit."""

from __future__ import annotations

import json
import logging
import os
from typing import Any, Dict, List, Optional
from urllib.parse import quote

import requests
from oauth2client.service_account import ServiceAccountCredentials

logger = logging.getLogger(__name__)


def get_env(name: str, default: str = "") -> str:
    return os.getenv(name, default).strip() or default


def transaction_tab_title() -> str:
    return get_env("GOOGLE_SHEET_TRANSACTION_TITLE", "Transaksi")


def dashboard_tab_title() -> str:
    return get_env("DASHBOARD_MASTER_SHEET_TITLE", "Dashboard")


def service_account_email(json_path: str) -> str:
    with open(json_path, encoding="utf-8") as fh:
        data = json.load(fh)
    return str(data.get("client_email", "")).strip()


def _oauth_configured() -> bool:
    return bool(
        get_env("GOOGLE_OAUTH_REFRESH_TOKEN")
        and get_env("GOOGLE_OAUTH_CLIENT_ID")
        and get_env("GOOGLE_OAUTH_CLIENT_SECRET")
    )


def _oauth_access_token() -> str:
    resp = requests.post(
        "https://oauth2.googleapis.com/token",
        data={
            "client_id": get_env("GOOGLE_OAUTH_CLIENT_ID"),
            "client_secret": get_env("GOOGLE_OAUTH_CLIENT_SECRET"),
            "refresh_token": get_env("GOOGLE_OAUTH_REFRESH_TOKEN"),
            "grant_type": "refresh_token",
        },
        timeout=30,
    )
    resp.raise_for_status()
    token = resp.json().get("access_token", "")
    if not token:
        raise RuntimeError("OAuth: access_token kosong")
    return str(token)


def _service_account_access_token(json_path: str) -> str:
    scope = [
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive",
    ]
    creds = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
    token_info = creds.get_access_token()
    return getattr(token_info, "access_token", token_info)


def _drive_access_token(json_path: str) -> str:
    """Pakai OAuth (pemilik file salinan) jika ada; fallback service account."""
    if _oauth_configured():
        try:
            return _oauth_access_token()
        except Exception as exc:
            logger.warning("sheet_privacy: OAuth gagal, fallback SA: %s", exc)
    return _service_account_access_token(json_path)


def _access_token(json_path: str) -> str:
    return _service_account_access_token(json_path)


def _sheets_get_with_token(spreadsheet_id: str, fields: str, token: str) -> Dict[str, Any]:
    url = f"https://sheets.googleapis.com/v4/spreadsheets/{spreadsheet_id}"
    resp = requests.get(url, params={"fields": fields}, headers={"Authorization": f"Bearer {token}"}, timeout=60)
    resp.raise_for_status()
    return resp.json()


def _sheets_get(spreadsheet_id: str, json_path: str, fields: str) -> Dict[str, Any]:
    return _sheets_get_with_token(spreadsheet_id, fields, _access_token(json_path))


def _batch_update_with_token(spreadsheet_id: str, token: str, requests_body: List[Dict[str, Any]]) -> None:
    if not requests_body:
        return
    url = f"https://sheets.googleapis.com/v4/spreadsheets/{spreadsheet_id}:batchUpdate"
    resp = requests.post(
        url,
        json={"requests": requests_body},
        headers={"Authorization": f"Bearer {token}"},
        timeout=120,
    )
    resp.raise_for_status()


def _batch_update(spreadsheet_id: str, json_path: str, requests_body: List[Dict[str, Any]]) -> None:
    _batch_update_with_token(spreadsheet_id, _access_token(json_path), requests_body)


def _clear_sheet_protections_with_token(
    spreadsheet_id: str, token: str, meta: Optional[Dict[str, Any]] = None
) -> None:
    if meta is None:
        meta = _sheets_get_with_token(
            spreadsheet_id,
            "sheets(protectedRanges(protectedRangeId),properties(sheetId))",
            token,
        )
    deletes: List[Dict[str, Any]] = []
    for sheet in meta.get("sheets", []):
        for pr in sheet.get("protectedRanges", []) or []:
            pr_id = pr.get("protectedRangeId")
            if pr_id is not None:
                deletes.append({"deleteProtectedRange": {"protectedRangeId": pr_id}})
    if deletes:
        _batch_update_with_token(spreadsheet_id, token, deletes)


def clear_sheet_protections(spreadsheet_id: str, json_path: str) -> None:
    token = _drive_access_token(json_path)
    meta = _sheets_get_with_token(
        spreadsheet_id,
        "sheets(protectedRanges(protectedRangeId),properties(sheetId))",
        token,
    )
    _clear_sheet_protections_with_token(spreadsheet_id, token, meta)


def _grant_drive_role(spreadsheet_id: str, json_path: str, email: str, role: str) -> bool:
    email = (email or "").strip().lower()
    if not email:
        return False
    token = _drive_access_token(json_path)
    url = f"https://www.googleapis.com/drive/v3/files/{spreadsheet_id}/permissions"
    resp = requests.post(
        url,
        params={"supportsAllDrives": "true", "sendNotificationEmail": "false"},
        json={"type": "user", "role": role, "emailAddress": email},
        headers={"Authorization": f"Bearer {token}"},
        timeout=30,
    )
    if resp.status_code in (200, 201, 409):
        return True
    logger.warning(
        "sheet_privacy: grant %s gagal untuk %s pada %s: %s",
        role,
        email,
        spreadsheet_id,
        resp.text[:400],
    )
    return False


def _customer_has_drive_access(spreadsheet_id: str, json_path: str, email: str) -> bool:
    email = (email or "").strip().lower()
    if not email:
        return False
    token = _drive_access_token(json_path)
    url = f"https://www.googleapis.com/drive/v3/files/{spreadsheet_id}/permissions"
    resp = requests.get(
        url,
        params={"supportsAllDrives": "true", "fields": "permissions(emailAddress,deleted)"},
        headers={"Authorization": f"Bearer {token}"},
        timeout=30,
    )
    if resp.status_code != 200:
        return False
    for perm in resp.json().get("permissions", []):
        if perm.get("deleted"):
            continue
        if str(perm.get("emailAddress", "")).lower() == email:
            return True
    return False


def _grant_anyone_with_link(spreadsheet_id: str, json_path: str) -> bool:
    token = _drive_access_token(json_path)
    url = f"https://www.googleapis.com/drive/v3/files/{spreadsheet_id}/permissions"
    resp = requests.post(
        url,
        params={"supportsAllDrives": "true", "sendNotificationEmail": "false"},
        json={"type": "anyone", "role": "reader"},
        headers={"Authorization": f"Bearer {token}"},
        timeout=30,
    )
    return resp.status_code in (200, 201, 409)


def ensure_service_account_writer(spreadsheet_id: str, json_path: str) -> bool:
    """Bot menulis pakai service account — wajib punya role writer di file Drive."""
    sa_email = service_account_email(json_path)
    if not sa_email:
        logger.warning("sheet_privacy: client_email kosong, tidak bisa grant writer SA")
        return False
    return _grant_drive_role(spreadsheet_id, json_path, sa_email, "writer")


def prepare_sheet_for_bot_write(spreadsheet_id: str, json_path: str) -> bool:
    """
    Pastikan service account punya writer di file Drive (butuh OAuth di .env bot).
    Proteksi tab sudah diatur Laravel saat provision — tidak diulang di sini.
    """
    if not _oauth_configured():
        logger.error(
            "sheet_privacy: GOOGLE_OAUTH_CLIENT_ID/SECRET/REFRESH_TOKEN wajib di .env bot "
            "(sama dengan Laravel) agar service account bisa ditambahkan sebagai editor."
        )
        return False
    if not ensure_service_account_writer(spreadsheet_id, json_path):
        logger.error(
            "sheet_privacy: gagal grant writer ke %s pada %s",
            service_account_email(json_path),
            spreadsheet_id,
        )
        return False
    try:
        apply_sheet_protections(spreadsheet_id, json_path)
    except Exception as exc:
        logger.warning("sheet_privacy: apply protections gagal: %s", exc)
    return True


def _transaction_worksheet_title(spreadsheet_id: str, json_path: str) -> str:
    tab = transaction_tab_title()
    try:
        token = _drive_access_token(json_path)
        meta = _sheets_get_with_token(spreadsheet_id, "sheets.properties(title)", token)
        titles = [str(s.get("properties", {}).get("title", "")) for s in meta.get("sheets", [])]
        if tab in titles:
            return tab
        if titles:
            return titles[0]
    except Exception as exc:
        logger.warning("sheet_privacy: tidak bisa baca daftar tab: %s", exc)
    return tab


def _append_row_gspread_sa(spreadsheet_id: str, json_path: str, row: List[Any]) -> bool:
    import gspread
    from oauth2client.service_account import ServiceAccountCredentials

    scope = [
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive",
    ]
    try:
        creds = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
        client = gspread.authorize(creds)
        sh = client.open_by_key(spreadsheet_id)
        tab = _transaction_worksheet_title(spreadsheet_id, json_path)
        try:
            ws = sh.worksheet(tab)
        except gspread.WorksheetNotFound:
            ws = sh.sheet1
        ws.append_row(row, value_input_option="USER_ENTERED")
        return True
    except Exception as exc:
        logger.warning("sheet_privacy: gspread append gagal: %s", exc)
        return False


def _append_row_oauth_api(spreadsheet_id: str, json_path: str, row: List[Any]) -> bool:
    """Fallback: tulis sebagai pemilik file (OAuth) — mengatasi proteksi/izin SA."""
    if not _oauth_configured():
        return False
    tab = _transaction_worksheet_title(spreadsheet_id, json_path)
    range_a1 = f"'{tab}'!A:J" if " " in tab else f"{tab}!A:J"
    url = (
        f"https://sheets.googleapis.com/v4/spreadsheets/{spreadsheet_id}/values/"
        f"{quote(range_a1, safe='')}:append"
    )
    try:
        token = _oauth_access_token()
        resp = requests.post(
            url,
            params={"valueInputOption": "USER_ENTERED", "insertDataOption": "INSERT_ROWS"},
            headers={"Authorization": f"Bearer {token}"},
            json={"values": [row]},
            timeout=60,
        )
        if resp.status_code in (200, 201):
            logger.info("sheet_privacy: append via OAuth OK pada tab %s", tab)
            return True
        logger.warning("sheet_privacy: OAuth append gagal %s: %s", resp.status_code, resp.text[:500])
    except Exception as exc:
        logger.warning("sheet_privacy: OAuth append exception: %s", exc)
    return False


def append_transaction_row(spreadsheet_id: str, json_path: str, row: List[Any]) -> None:
    """Tulis baris transaksi: coba service account, lalu OAuth (pemilik file)."""
    if _append_row_gspread_sa(spreadsheet_id, json_path, row):
        return
    if _append_row_oauth_api(spreadsheet_id, json_path, row):
        return
    raise RuntimeError(
        "Tidak bisa menulis ke sheet (service account & OAuth gagal). "
        "Jalankan: php artisan google:sheet-setup --reshare=KODE_ORDER"
    )


def verify_service_account_can_open(spreadsheet_id: str, json_path: str) -> bool:
    """Cek gspread bisa buka spreadsheet sebagai service account."""
    import gspread
    from oauth2client.service_account import ServiceAccountCredentials

    sa_email = service_account_email(json_path)
    if not sa_email:
        return False
    scope = [
        "https://www.googleapis.com/auth/spreadsheets",
        "https://www.googleapis.com/auth/drive",
    ]
    try:
        creds = ServiceAccountCredentials.from_json_keyfile_name(json_path, scope)
        client = gspread.authorize(creds)
        sh = client.open_by_key(spreadsheet_id)
        tab = transaction_tab_title()
        try:
            sh.worksheet(tab)
        except gspread.WorksheetNotFound:
            sh.sheet1
        logger.info("sheet_privacy: SA %s bisa akses %s", sa_email, spreadsheet_id)
        return True
    except Exception as exc:
        logger.error("sheet_privacy: SA tidak bisa buka %s: %s", spreadsheet_id, exc)
        return False


def share_sheet_with_customer_email(spreadsheet_id: str, json_path: str, email: str) -> bool:
    """Bagikan sheet ke email checkout (writer dulu), fallback link jika gagal."""
    email = (email or "").strip().lower()
    if not email:
        return False
    ensure_service_account_writer(spreadsheet_id, json_path)
    _grant_drive_role(spreadsheet_id, json_path, email, "writer")
    if not _customer_has_drive_access(spreadsheet_id, json_path, email):
        _grant_drive_role(spreadsheet_id, json_path, email, "reader")
    if _customer_has_drive_access(spreadsheet_id, json_path, email):
        return True
    fallback = get_env("GOOGLE_SHEET_FALLBACK_LINK_READER", "true").lower() in ("1", "true", "yes")
    if fallback:
        _grant_anyone_with_link(spreadsheet_id, json_path)
        return True
    return False


def grant_drive_reader(spreadsheet_id: str, json_path: str, email: str) -> None:
    share_sheet_with_customer_email(spreadsheet_id, json_path, email)


def grant_anyone_with_link_reader(spreadsheet_id: str, json_path: str) -> None:
    """Siapa pun yang punya link bisa melihat (akun Google mana pun)."""
    token = _access_token(json_path)
    url = f"https://www.googleapis.com/drive/v3/files/{spreadsheet_id}/permissions"
    resp = requests.post(
        url,
        params={"supportsAllDrives": "true", "sendNotificationEmail": "false"},
        json={"type": "anyone", "role": "reader"},
        headers={"Authorization": f"Bearer {token}"},
        timeout=30,
    )
    if resp.status_code not in (200, 201, 409):
        logger.warning(
            "sheet_privacy: grant anyone-with-link gagal pada %s: %s",
            spreadsheet_id,
            resp.text[:400],
        )


def apply_sheet_protections(spreadsheet_id: str, json_path: str) -> None:
    sa_email = service_account_email(json_path)
    if not sa_email:
        logger.warning("sheet_privacy: client_email kosong di service account JSON")
        return

    transaction_tab = transaction_tab_title()
    dashboard_tab = dashboard_tab_title()

    token = _drive_access_token(json_path)
    _clear_sheet_protections_with_token(spreadsheet_id, token)
    meta = _sheets_get_with_token(spreadsheet_id, "sheets.properties(sheetId,title)", token)
    requests_body: List[Dict[str, Any]] = []

    for sheet in meta.get("sheets", []):
        props = sheet.get("properties", {})
        title = str(props.get("title", ""))
        if title not in (transaction_tab, dashboard_tab):
            continue
        sheet_id = props.get("sheetId")
        if sheet_id is None:
            continue
        desc = (
            "Dashboard — hanya service account (sync admin)"
            if title == dashboard_tab
            else "Transaksi — hanya service account (bot)"
        )
        requests_body.append(
            {
                "addProtectedRange": {
                    "protectedRange": {
                        "range": {"sheetId": sheet_id},
                        "description": desc,
                        "warningOnly": False,
                        "editors": {"users": [sa_email]},
                    },
                },
            }
        )

    if not requests_body:
        logger.warning(
            "sheet_privacy: tab %s / %s tidak ditemukan di %s",
            transaction_tab,
            dashboard_tab,
            spreadsheet_id,
        )
        return

    _batch_update_with_token(spreadsheet_id, token, requests_body)
    logger.debug("sheet_privacy: proteksi diterapkan pada %s", spreadsheet_id)
