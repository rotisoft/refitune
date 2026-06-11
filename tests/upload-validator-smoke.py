# -*- coding: utf-8 -*-
"""Smoke tests for upload validator rules (Python port for environments without PHP CLI)."""
import os
import sys
import tempfile

DANGEROUS = {
    "php", "php3", "php4", "php5", "php7", "php8", "phtml", "phar", "pht", "phps",
    "cgi", "pl", "py", "asp", "aspx", "jsp", "sh", "exe", "dll", "htaccess", "suspected",
}
IMAGE_EXT = {"jpg", "jpeg", "png", "gif", "webp", "avif", "svg", "ico", "bmp"}
SIGNATURES = [
    "<?php", "<?=", "<?", "<script", "javascript:", "eval(", "base64_decode(",
    "shell_exec", "system(", "passthru(",
]


def validate_filename(filename: str):
    basename = os.path.basename(filename)
    parts = basename.lower().split(".")
    if len(parts) < 2:
        return True
    for i, part in enumerate(parts):
        if i == 0:
            continue
        if part in DANGEROUS:
            return "dangerous_extension"
    import re
    if re.search(r"\.(" + "|".join(map(re.escape, DANGEROUS)) + r")(\.|$)", basename, re.I):
        return "double_extension"
    return True


def detect_magic(header: bytes) -> str:
    if header.startswith(b"\xff\xd8\xff"):
        return "jpeg"
    if header.startswith(b"\x89PNG\r\n\x1a\n"):
        return "png"
    if header[:6] in (b"GIF87a", b"GIF89a"):
        return "gif"
    if header.startswith(b"%PDF"):
        return "pdf"
    sample = header[:512]
    if b"\x00" not in sample and any(32 <= b <= 126 for b in sample[:20]):
        try:
            sample.decode("ascii")
            if b"<?" in sample or b"<script" in sample.lower():
                return "text"
        except UnicodeDecodeError:
            pass
    return "binary"


VERIFIED_IMAGE_MAGIC = {"jpeg", "png", "gif", "webp", "avif"}
BINARY_IMAGE_MAGIC = {"jpeg", "png", "webp", "avif"}


def validate_magic(filename: str, content: bytes):
    ext = os.path.splitext(filename)[1].lstrip(".").lower()
    magic = detect_magic(content[:512])
    expected = {"jpg": "jpeg", "jpeg": "jpeg", "png": "png", "gif": "gif", "webp": "webp", "pdf": "pdf"}.get(ext)
    if ext in IMAGE_EXT and magic == "text":
        return "text_disguised_as_image"
    if expected and magic != expected:
        allowed = (
            (ext == "avif" and magic == "binary")
            or magic in VERIFIED_IMAGE_MAGIC
        )
        if not allowed:
            return "magic_mismatch"
    return True


def scan_scripts(filename: str, content: bytes):
    if os.path.splitext(filename)[1].lstrip(".").lower() == "svg":
        return True
    magic = detect_magic(content[:512])
    if magic in BINARY_IMAGE_MAGIC:
        return True
    lowered = content.decode("latin-1", errors="ignore").lower()
    for sig in SIGNATURES:
        if sig in lowered:
            return "script_marker"
    return True


def run_case(name, filename, content, expect):
    filename_result = validate_filename(filename)
    if filename_result is not True:
        got = filename_result
    else:
        magic_result = validate_magic(filename, content)
        if magic_result is not True:
            got = magic_result
        else:
            got = scan_scripts(filename, content)
            if got is not True:
                pass
            else:
                got = True
    ok = got == expect
    print(("PASS" if ok else "FAIL") + f": {name} (expected {expect}, got {got})")
    return ok


def main():
    cases = [
        ("valid_jpg_name", "photo.jpg", b"\xff\xd8\xff\xe0\x00\x10JFIF", True),
        ("double_ext", "shell.php.jpg", b"test", "dangerous_extension"),
        ("php_ext", "image.jpg.php", b"test", "dangerous_extension"),
        ("polyglot_gif", "logo.gif", b"GIF89a<?php echo 1;", "script_marker"),
        ("jpeg_as_png_ext", "screenshot.png", b"\xff\xd8\xff\xe0\x00\x10JFIF", True),
        ("magic_mismatch", "fake.jpg", b"\x00\x01\x02\x03\x04\x05\x06\x07\x08", "magic_mismatch"),
    ]
    passed = sum(run_case(*c) for c in cases)
    print(f"Summary: {passed}/{len(cases)} passed")
    return 0 if passed == len(cases) else 1


if __name__ == "__main__":
    sys.exit(main())
