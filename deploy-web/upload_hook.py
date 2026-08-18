#!/usr/bin/env python3
"""Upload deploy-hook.php next to live index.php and drop stale Laravel caches."""

from __future__ import annotations

import os
import sys
from ftplib import FTP, FTP_TLS, error_perm

HOST = os.environ["FTP_SERVER"].strip()
USER = os.environ["FTP_USERNAME"]
PASSWORD = os.environ["FTP_PASSWORD"]
LOCAL_FILE = os.path.join("deploy-web", "deploy-hook.php")

for prefix in ("ftp://", "ftps://"):
    if HOST.lower().startswith(prefix):
        HOST = HOST[len(prefix) :]

PORT = 21
if ":" in HOST and HOST.rsplit(":", 1)[-1].isdigit():
    HOST, port_s = HOST.rsplit(":", 1)
    PORT = int(port_s)

SEARCH_DIRS = [
    "",
    "paceboard",
    "public_html",
    "public_html/paceboard",
    "public_html/dashboard",
    "dashboard",
    "www",
    "www/paceboard",
    "paceboard/public",
]

CACHE_DIRS = [
    "paceboard/bootstrap/cache",
    "bootstrap/cache",
    "public_html/paceboard/bootstrap/cache",
]
CACHE_FILES = ["config.php", "routes-v7.php", "routes.php", "events.php"]


def connect():
    errors = []
    for cls, label, tls in ((FTP_TLS, "FTP_TLS", True), (FTP, "FTP", False)):
        try:
            ftp = cls()
            ftp.connect(HOST, PORT, timeout=30)
            ftp.login(USER, PASSWORD)
            if tls:
                try:
                    ftp.prot_p()
                except Exception:
                    pass
            ftp.set_pasv(True)
            print(f"Connected via {label}")
            return ftp
        except Exception as exc:
            errors.append(f"{label}: {exc}")
    raise SystemExit("FTP connect failed: " + " | ".join(errors))


def names(ftp: FTP) -> list[str]:
    try:
        entries = ftp.nlst()
    except Exception as exc:
        print(f"  nlst failed: {exc}")
        return []
    cleaned = []
    for item in entries:
        cleaned.append(item.replace("\\", "/").rstrip("/").split("/")[-1])
    return cleaned


def cwd(ftp: FTP, home: str, rel: str) -> bool:
    ftp.cwd(home)
    try:
        for part in rel.split("/"):
            if part:
                ftp.cwd(part)
        return True
    except error_perm:
        return False


def upload_here(ftp: FTP, label: str) -> None:
    with open(LOCAL_FILE, "rb") as handle:
        ftp.storbinary("STOR deploy-hook.php", handle)
    print(f"Uploaded deploy-hook.php into {label or '.'}")


def main() -> int:
    if not os.path.isfile(LOCAL_FILE):
        print(f"Missing {LOCAL_FILE}")
        return 1

    ftp = connect()
    home = ftp.pwd()
    print(f"FTP home: {home}")
    print("Home listing:", ", ".join(names(ftp)) or "(empty)")

    uploaded = 0
    for rel in SEARCH_DIRS:
        if not cwd(ftp, home, rel):
            continue
        listing = names(ftp)
        label = rel or "."
        print(f"Listing {label}: {', '.join(listing[:40])}")
        is_app_root = "artisan" in listing or "composer.json" in listing
        if "index.php" in listing and not is_app_root:
            try:
                upload_here(ftp, label)
                uploaded += 1
            except Exception as exc:
                print(f"Upload failed in {label}: {exc}")
        if is_app_root and "public" in listing:
            if cwd(ftp, home, f"{rel}/public" if rel else "public") and "index.php" in names(ftp):
                try:
                    upload_here(ftp, f"{label}/public")
                    uploaded += 1
                except Exception as exc:
                    print(f"Upload failed in {label}/public: {exc}")

    for cache_dir in CACHE_DIRS:
        if not cwd(ftp, home, cache_dir):
            continue
        print(f"Cache dir {cache_dir}: {', '.join(names(ftp))}")
        for cache_file in CACHE_FILES:
            try:
                ftp.delete(cache_file)
                print(f"Deleted {cache_dir}/{cache_file}")
            except Exception as exc:
                print(f"Could not delete {cache_dir}/{cache_file}: {exc}")

    ftp.quit()
    print(f"Hook uploads: {uploaded}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
