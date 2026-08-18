#!/usr/bin/env python3
"""Put deploy-hook.php in /public_html/paceboard (the subdomain document root)."""

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


def connect() -> FTP:
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


def listing(ftp: FTP) -> list[str]:
    lines: list[str] = []
    try:
        ftp.retrlines("LIST -a", lines.append)
    except Exception:
        try:
            ftp.retrlines("LIST", lines.append)
        except Exception as exc:
            print(f"LIST failed at {ftp.pwd()}: {exc}")
            return []
    print(f"LIST {ftp.pwd()}:")
    for line in lines:
        print(" ", line)
    names = []
    for line in lines:
        names.append(line.split()[-1].replace("\\", "/").split("/")[-1])
    return names


def cwd_parts(ftp: FTP, home: str, rel: str) -> bool:
    ftp.cwd(home)
    try:
        for part in rel.split("/"):
            if part:
                ftp.cwd(part)
        return True
    except error_perm as exc:
        print(f"Cannot enter {rel} from {home}: {exc}")
        return False


def looks_like_web_root(names: list[str]) -> bool:
    return "index.php" in names and "artisan" not in names and "composer.json" not in names


def upload(ftp: FTP) -> None:
    with open(LOCAL_FILE, "rb") as handle:
        ftp.storbinary("STOR deploy-hook.php", handle)
    print(f"STORed deploy-hook.php into {ftp.pwd()}")


def delete_caches(ftp: FTP, home: str) -> None:
    for rel in ("paceboard/bootstrap/cache", "bootstrap/cache"):
        if not cwd_parts(ftp, home, rel):
            continue
        names = listing(ftp)
        for cache_file in ("config.php", "routes-v7.php", "routes.php", "events.php"):
            if cache_file not in names:
                continue
            try:
                ftp.delete(cache_file)
                print(f"Deleted {rel}/{cache_file}")
            except Exception as exc:
                print(f"Could not delete {rel}/{cache_file}: {exc}")


def main() -> int:
    if not os.path.isfile(LOCAL_FILE):
        print(f"Missing {LOCAL_FILE}")
        return 1

    ftp = connect()
    home = ftp.pwd()
    print(f"FTP home: {home}")
    home_names = listing(ftp)

    # File Manager path: /public_html/paceboard
    candidates = []
    if home.rstrip("/").endswith("public_html"):
        candidates.append("paceboard")
    if "public_html" in home_names:
        candidates.append("public_html/paceboard")
    candidates.extend(["public_html/paceboard", "paceboard"])

    seen: set[str] = set()
    uploaded_ok = False
    for rel in candidates:
        if rel in seen:
            continue
        seen.add(rel)
        if not cwd_parts(ftp, home, rel):
            continue
        names = listing(ftp)
        if not looks_like_web_root(names):
            print(f"Skipping {rel} (not the public document root)")
            continue
        try:
            upload(ftp)
        except Exception as exc:
            print(f"STOR failed in {rel}: {exc}")
            continue
        names_after = listing(ftp)
        if "deploy-hook.php" not in names_after:
            print(f"deploy-hook.php missing after upload into {rel}")
            continue
        uploaded_ok = True
        break

    delete_caches(ftp, home)
    ftp.quit()

    if not uploaded_ok:
        print(
            "Could not write deploy-hook.php into public_html/paceboard.\n"
            "The GitHub FTP account is not landing in the folder you see in File Manager.\n"
            "In cPanel → FTP Accounts, the account Directory must be /home/zhenhlkl "
            "(not a subfolder). Then GitHub can see public_html/paceboard."
        )
        return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())
