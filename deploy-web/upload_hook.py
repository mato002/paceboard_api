#!/usr/bin/env python3
"""Upload deploy-hook.php to the live web document root when FTP can reach it."""

from __future__ import annotations

import os
import sys
from ftplib import FTP, FTP_TLS, error_perm

HOST = os.environ["FTP_SERVER"].strip()
USER = os.environ["FTP_USERNAME"]
PASSWORD = os.environ["FTP_PASSWORD"]
LOCAL_FILES = (
    ("deploy-hook.php", os.path.join("deploy-web", "deploy-hook.php")),
    ("serve-media.php", os.path.join("deploy-web", "serve-media.php")),
)

for prefix in ("ftp://", "ftps://"):
    if HOST.lower().startswith(prefix):
        HOST = HOST[len(prefix) :]

PORT = 21
if ":" in HOST and HOST.rsplit(":", 1)[-1].isdigit():
    HOST, port_s = HOST.rsplit(":", 1)
    PORT = int(port_s)


def connect() -> FTP:
    errors = []
    for cls, label, _tls in ((FTP_TLS, "FTP_TLS", True), (FTP, "FTP", False)):
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
            if part == "..":
                ftp.cwd("..")
            elif part:
                ftp.cwd(part)
        return True
    except error_perm as exc:
        print(f"Cannot enter {rel} from {home}: {exc}")
        return False


def looks_like_web_root(names: list[str]) -> bool:
    return "index.php" in names and "artisan" not in names and "composer.json" not in names


def looks_like_laravel_app(names: list[str]) -> bool:
    return "artisan" in names and "public" in names


def upload(ftp: FTP, remote_name: str, local_file: str) -> None:
    with open(local_file, "rb") as handle:
        ftp.storbinary(f"STOR {remote_name}", handle)
    print(f"STORed {remote_name} into {ftp.pwd()}")


def delete_caches(ftp: FTP, home: str) -> None:
    for rel in ("bootstrap/cache", "paceboard/bootstrap/cache"):
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


def candidate_paths(home: str, home_names: list[str]) -> list[str]:
    paths: list[str] = []

    custom = os.environ.get("FTP_PUBLIC_DIR", "").strip().strip("/")
    if custom:
        paths.append(custom)

    if looks_like_laravel_app(home_names):
        paths.append("public")

    if home.rstrip("/").endswith("public_html"):
        paths.append("paceboard")

    for rel in (
        "public_html/paceboard",
        "../public_html/paceboard",
        "../../public_html/paceboard",
        "../../../public_html/paceboard",
        "paceboard/public",
        "../paceboard/public",
        "public",
        "paceboard",
    ):
        if rel not in paths:
            paths.append(rel)

    if "public_html" in home_names:
        paths.insert(0, "public_html/paceboard")

    return paths


def main() -> int:
    missing = [local for _, local in LOCAL_FILES if not os.path.isfile(local)]
    if missing:
        print("Missing " + ", ".join(missing))
        return 1

    ftp = connect()
    home = ftp.pwd()
    print(f"FTP home: {home}")
    home_names = listing(ftp)

    uploaded_ok = False
    for rel in candidate_paths(home, home_names):
        if not cwd_parts(ftp, home, rel):
            continue
        names = listing(ftp)
        if not looks_like_web_root(names):
            print(f"Skipping {rel} (not the public document root)")
            continue
        try:
            for remote_name, local_file in LOCAL_FILES:
                upload(ftp, remote_name, local_file)
        except Exception as exc:
            print(f"STOR failed in {rel}: {exc}")
            continue
        names_after = listing(ftp)
        if "serve-media.php" not in names_after:
            print(f"serve-media.php missing after upload into {rel}")
            continue
        uploaded_ok = True
        print(f"media + deploy hooks uploaded to {ftp.pwd()}")
        break

    delete_caches(ftp, home)
    ftp.quit()

    if not uploaded_ok:
        print(
            "Could not upload deploy-hook.php to a public web root over FTP.\n"
            "This is OK when migrations run via https://your-domain/internal/deploy instead.\n"
            "If you need deploy-hook.php in public_html, point the FTP account to /home/zhenhlkl "
            "or set FTP_PUBLIC_DIR in GitHub secrets."
        )
        custom = os.environ.get("FTP_PUBLIC_DIR", "").strip()
        if custom:
            print(f"FTP_PUBLIC_DIR was set to {custom!r} but upload still failed.")
            return 1
        return 0

    return 0


if __name__ == "__main__":
    sys.exit(main())
