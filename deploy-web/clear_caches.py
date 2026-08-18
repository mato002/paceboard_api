#!/usr/bin/env python3
"""Delete Laravel bootstrap/cache files over FTP so new routes load after deploy."""

from __future__ import annotations

import os
import sys
from ftplib import FTP, FTP_TLS, error_perm

HOST = os.environ["FTP_SERVER"].strip()
USER = os.environ["FTP_USERNAME"]
PASSWORD = os.environ["FTP_PASSWORD"]

for prefix in ("ftp://", "ftps://"):
    if HOST.lower().startswith(prefix):
        HOST = HOST[len(prefix) :]

PORT = 21
if ":" in HOST and HOST.rsplit(":", 1)[-1].isdigit():
    HOST, port_s = HOST.rsplit(":", 1)
    PORT = int(port_s)

CACHE_FILES = ("config.php", "routes-v7.php", "routes.php", "events.php", "services.php", "packages.php")


def connect() -> FTP:
    for cls, label in ((FTP_TLS, "FTP_TLS"), (FTP, "FTP")):
        try:
            ftp = cls()
            ftp.connect(HOST, PORT, timeout=30)
            ftp.login(USER, PASSWORD)
            if cls is FTP_TLS:
                try:
                    ftp.prot_p()
                except Exception:
                    pass
            ftp.set_pasv(True)
            print(f"Connected via {label}")
            return ftp
        except Exception as exc:
            print(f"{label} failed: {exc}")
    raise SystemExit("FTP connect failed")


def cwd_parts(ftp: FTP, home: str, rel: str) -> bool:
    ftp.cwd(home)
    try:
        for part in rel.split("/"):
            if part == "..":
                ftp.cwd("..")
            elif part:
                ftp.cwd(part)
        return True
    except error_perm as exc:
        print(f"Cannot enter {rel} from {home}: {exc}")
        return False


def listing(ftp: FTP) -> list[str]:
    lines: list[str] = []
    try:
        ftp.retrlines("LIST -a", lines.append)
    except Exception:
        ftp.retrlines("LIST", lines.append)
    names = []
    for line in lines:
        names.append(line.split()[-1].replace("\\", "/").split("/")[-1])
    return names


def main() -> int:
    ftp = connect()
    home = ftp.pwd()
    print(f"FTP home: {home}")

    cleared = False
    for rel in ("bootstrap/cache", "paceboard/bootstrap/cache"):
        if not cwd_parts(ftp, home, rel):
            continue
        names = listing(ftp)
        print(f"Cache dir {ftp.pwd()}: {names}")
        for cache_file in CACHE_FILES:
            if cache_file not in names:
                continue
            try:
                ftp.delete(cache_file)
                print(f"Deleted {rel}/{cache_file}")
                cleared = True
            except Exception as exc:
                print(f"Could not delete {cache_file}: {exc}")

    ftp.quit()

    if not cleared:
        print("No Laravel cache files deleted (may already be clear).")
    else:
        print("Route/config cache cleared — new deploy routes will load.")

    return 0


if __name__ == "__main__":
    sys.exit(main())
