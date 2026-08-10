#!/usr/bin/env python3
from pathlib import Path
import base64, binascii, io, shutil, tarfile

ROOT = Path.cwd().resolve()
PARTS = ROOT / 'materialize-v3' / 'parts'

# Payload chunks are ASCII base64. Normalize whitespace and restore only the
# syntactically required terminal padding; corruption still fails strict decode
# or gzip/tar validation below.
payload = ''.join(
    ''.join(p.read_text(encoding='ascii').split())
    for p in sorted(PARTS.glob('payload-*.txt'))
)
if not payload:
    raise SystemExit('No materialization payload parts found.')
payload += '=' * (-len(payload) % 4)
try:
    raw = base64.b64decode(payload.encode('ascii'), validate=True)
except (ValueError, binascii.Error) as exc:
    raise SystemExit(f'Invalid materialization payload: {exc}') from exc

# Validate the complete archive before deleting any checked-out source. This
# makes a broken/truncated materialization payload fail non-destructively.
try:
    with tarfile.open(fileobj=io.BytesIO(raw), mode='r:gz') as tf:
        members = tf.getmembers()
        if not members:
            raise SystemExit('Materialization archive is empty.')
        for member in members:
            target = (ROOT / member.name).resolve()
            if ROOT not in target.parents and target != ROOT:
                raise SystemExit(f'Unsafe archive member: {member.name}')
            if member.issym() or member.islnk():
                raise SystemExit(f'Links are not allowed: {member.name}')
except (tarfile.TarError, OSError, EOFError) as exc:
    raise SystemExit(f'Invalid materialization archive: {exc}') from exc

for item in list(ROOT.iterdir()):
    if item.name == '.git':
        continue
    if item.is_dir() and not item.is_symlink():
        shutil.rmtree(item)
    else:
        item.unlink()

with tarfile.open(fileobj=io.BytesIO(raw), mode='r:gz') as tf:
    tf.extractall(ROOT)

for rel in [
    'tests/source-invariants.sh',
    'tests/repository-scan.py',
    'tests/deep-invariants.py',
    'tests/static-invariants.py',
    'scripts/build-release.py',
    'scripts/source-tree-hash.py',
]:
    path = ROOT / rel
    if path.exists():
        path.chmod(0o755)

print('Materialized File 05 v3.0.0 repository candidate.')
