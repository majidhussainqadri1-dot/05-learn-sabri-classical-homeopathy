#!/usr/bin/env python3
"""Print the canonical File 05 source-tree hash and manifest rows."""
from __future__ import annotations
import hashlib
from pathlib import Path
import sys

root = Path(sys.argv[1] if len(sys.argv) > 1 else 'sabri-learning').resolve()
rows = []
for path in sorted((p for p in root.rglob('*') if p.is_file()), key=lambda p: p.relative_to(root).as_posix()):
    rel = (Path(root.name) / path.relative_to(root)).as_posix()
    data = path.read_bytes()
    rows.append((rel, len(data), hashlib.sha256(data).hexdigest()))
canonical = ''.join(f'{digest}  {rel}\n' for rel, _, digest in rows).encode('utf-8')
print(hashlib.sha256(canonical).hexdigest())
for rel, size, digest in rows:
    print(f'{digest}  {size:>8}  {rel}')
