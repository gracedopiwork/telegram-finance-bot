"""Proteksi tab Transaksi/Dashboard: hanya service account yang bisa edit."""

from __future__ import annotations

import json
import logging
import os
from typing import Any, Dict, List, Optional

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


def _sheets_get(spreadsheet_id: str, json_path: str, fields: str) -> Dict[str, Any]:
    token = _access_token(json_path)
    url = f"https://sheets.googleapis.com/v4/spreadsheets/{spreadsheet_id}"
    resp = requests.get(url, params={"fields": fields}, headers={"Authorization": f"Bearer {token}"}, timeout=60)
    resp.raise_for_status()
    return resp.json()


def _batch_update(spreadsheet_id: str, json_path: str, requests_body: List[Dict[str, Any]]) -> None:
    if not requests_body:
        return
    token = _access_token(json_path)
    url = f"https://sheets.googleapis.com/v4/spreadsheets/{spreadsheet_id}:batchUpdate"
    resp = requests.post(
        url,
        json={"requests": requests_body},
        headers={"Authorization": f"Bearer {token}"},
        timeout=120,
    )
    resp.raise_for_status()


def clear_sheet_protections(spreadsheet_id: str, json_path: str) -> None:
    meta = _sheets_get(spreadsheet_id, json_path, "sheets(protectedRanges(protectedRangeId),properties(sheetId))")
    deletes: List[Dict[str, Any]] = []
    for sheet in meta.get("sheets", []):
        for pr in sheet.get("protectedRanges", []) or []:
            pr_id = pr.get("protectedRangeId")
            if pr_id is not None:
                deletes.append({"deleteProtectedRange": {"protectedRangeId": pr_id}})
    if deletes:
        _batch_update(spreadsheet_id, json_path, deletes)


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


def share_sheet_with_customer_email(spreadsheet_id: str, json_path: str, email: str) -> bool:
    """Bagikan sheet ke email checkout (writer dulu), fallback link jika gagal."""
    email = (email or "").strip().lower()
    if not email:
        return False
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

    clear_sheet_protections(spreadsheet_id, json_path)

    meta = _sheets_get(spreadsheet_id, json_path, "sheets.properties(sheetId,title)")
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

    _batch_update(spreadsheet_id, json_path, requests_body)
    logger.debug("sheet_privacy: proteksi diterapkan pada %s", spreadsheet_id)
