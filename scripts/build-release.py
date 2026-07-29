#!/usr/bin/env python3
"""Create a byte-reproducible WordPress release ZIP with a fixed timestamp."""
from __future__ import annotations
import argparse
import hashlib
from pathlib import Path
import zipfile

FIXED_TIME = (2026, 7, 29, 0, 0, 0)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--source', default='sabri-learning')
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    source = Path(args.source).resolve()
    output = Path(args.output).resolve()
    if not source.is_dir():
        raise SystemExit(f'Missing source directory: {source}')
    files = sorted((p for p in source.rglob('*') if p.is_file()), key=lambda p: p.relative_to(source).as_posix())
    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in files:
            relative = Path(source.name) / path.relative_to(source)
            info = zipfile.ZipInfo(relative.as_posix(), FIXED_TIME)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = (0o100644 & 0xFFFF) << 16
            archive.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    output.with_suffix(output.suffix + '.sha256').write_text(f'{digest}  {output.name}\n', encoding='utf-8')
    print(f'{output.name}: {digest}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
