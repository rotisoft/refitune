# -*- coding: utf-8 -*-
"""Smoke tests for upload filename sanitizer."""
import os
import sys
import unicodedata

ALLOWED = {
    "jpg", "jpeg", "png", "gif", "webp", "avif", "svg", "ico", "bmp",
    "pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "odt", "ods", "odp", "rtf", "txt", "csv",
}


def remove_accents(text: str) -> str:
    normalized = unicodedata.normalize("NFKD", text)
    return "".join(ch for ch in normalized if not unicodedata.combining(ch))


def sanitize_upload_filename(filename: str) -> str:
    filename = os.path.basename(filename)
    ext = os.path.splitext(filename)[1].lstrip(".").lower()
    if ext not in ALLOWED:
        return filename
    basename = os.path.splitext(filename)[0]
    basename = remove_accents(basename).lower()
    import re
    basename = re.sub(r"[^a-z0-9]+", "-", basename)
    basename = re.sub(r"-+", "-", basename).strip("-") or "file"
    return f"{basename}.{ext}"


def run_case(name, inp, expected):
    got = sanitize_upload_filename(inp)
    ok = got == expected
    print(("PASS" if ok else "FAIL") + f": {name} -> {got!r} (expected {expected!r})")
    return ok


def main():
    cases = [
        ("hungarian_image", "árvíz tűrő +33.jpg", "arviz-turo-33.jpg"),
        ("plain_image", "photo.jpg", "photo.jpg"),
        ("hungarian_pdf", "Jelentés összefoglaló.pdf", "jelentes-osszefoglalo.pdf"),
        ("video_skip", "árvíz tűrő +33.mp4", "árvíz tűrő +33.mp4"),
        ("double_ext_rename_only_when_allowed", "shell.php.jpg", "shell-php.jpg"),
    ]
    passed = sum(run_case(n, i, e) for n, i, e in cases)
    print(f"Summary: {passed}/{len(cases)} passed")
    return 0 if passed == len(cases) else 1


if __name__ == "__main__":
    sys.exit(main())
