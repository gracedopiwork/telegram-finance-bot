import os
import secrets
from datetime import datetime, timedelta
from typing import Any, Dict

import mysql.connector
from dotenv import load_dotenv
from flask import Flask, jsonify, request

load_dotenv()
app = Flask(__name__)


def get_env(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise RuntimeError(f"Environment variable '{name}' belum diisi.")
    return value


def get_db_connection():
    return mysql.connector.connect(
        host=get_env("MYSQL_HOST"),
        port=int(get_env("MYSQL_PORT", required=False) or "3306"),
        user=get_env("MYSQL_USER"),
        password=os.getenv("MYSQL_PASSWORD", ""),
        database=get_env("MYSQL_DATABASE"),
    )


def serialize_license(row: Dict[str, Any]) -> Dict[str, Any]:
    return {
        "license_key": row["license_key"],
        "plan": row["plan"],
        "status": row["status"],
        "expires_at": row["expires_at"].isoformat() if row["expires_at"] else None,
        "max_accounts": row["max_accounts"],
        "assigned_user_id": row["assigned_user_id"],
        "assigned_username": row["assigned_username"],
        "activated_at": row["activated_at"].isoformat() if row["activated_at"] else None,
        "created_at": row["created_at"].isoformat() if row["created_at"] else None,
    }


def require_admin_token() -> None:
    header = request.headers.get("Authorization", "")
    token = header.replace("Bearer ", "").strip()
    if not token or token != get_env("ADMIN_API_TOKEN"):
        raise PermissionError("Unauthorized")


def generate_license_key(prefix: str = "TFB") -> str:
    return f"{prefix}-{secrets.token_hex(2).upper()}-{secrets.token_hex(2).upper()}-{secrets.token_hex(2).upper()}"


@app.get("/health")
def health_check():
    return jsonify({"ok": True})


@app.post("/admin/licenses")
def create_license():
    try:
        require_admin_token()
    except PermissionError:
        return jsonify({"error": "unauthorized"}), 401

    payload = request.get_json(silent=True) or {}
    plan = str(payload.get("plan", "basic")).strip().lower()
    duration_days = int(payload.get("duration_days", 30))
    max_accounts = int(payload.get("max_accounts", 1))
    custom_key = str(payload.get("license_key", "")).strip().upper()

    if duration_days <= 0:
        return jsonify({"error": "duration_days harus > 0"}), 400
    if max_accounts <= 0:
        return jsonify({"error": "max_accounts harus > 0"}), 400

    license_key = custom_key or generate_license_key()
    expires_at = datetime.utcnow() + timedelta(days=duration_days)

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            INSERT INTO licenses (license_key, plan, status, expires_at, max_accounts)
            VALUES (%s, %s, 'active', %s, %s)
            """,
            (license_key, plan, expires_at, max_accounts),
        )
        conn.commit()
    except mysql.connector.Error as exc:
        return jsonify({"error": f"gagal membuat lisensi: {exc.msg}"}), 400
    finally:
        cursor.close()
        conn.close()

    return jsonify(
        {
            "license_key": license_key,
            "plan": plan,
            "status": "active",
            "expires_at": expires_at.isoformat(),
            "max_accounts": max_accounts,
        }
    ), 201


@app.get("/admin/licenses/<license_key>")
def get_license(license_key: str):
    try:
        require_admin_token()
    except PermissionError:
        return jsonify({"error": "unauthorized"}), 401

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM licenses WHERE license_key = %s", (license_key.upper(),))
    row = cursor.fetchone()
    cursor.close()
    conn.close()

    if not row:
        return jsonify({"error": "license tidak ditemukan"}), 404
    return jsonify(serialize_license(row))


@app.patch("/admin/licenses/<license_key>")
def update_license(license_key: str):
    try:
        require_admin_token()
    except PermissionError:
        return jsonify({"error": "unauthorized"}), 401

    payload = request.get_json(silent=True) or {}
    allowed_fields = {"status", "plan", "max_accounts", "expires_at"}
    updates = []
    values = []

    for key, value in payload.items():
        if key not in allowed_fields:
            continue
        updates.append(f"{key} = %s")
        values.append(value)

    if not updates:
        return jsonify({"error": "tidak ada field valid untuk update"}), 400

    values.append(license_key.upper())
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(f"UPDATE licenses SET {', '.join(updates)} WHERE license_key = %s", tuple(values))
    conn.commit()
    affected = cursor.rowcount
    cursor.close()
    conn.close()

    if affected == 0:
        return jsonify({"error": "license tidak ditemukan"}), 404
    return jsonify({"ok": True})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.getenv("ADMIN_PORT", "8080")))
