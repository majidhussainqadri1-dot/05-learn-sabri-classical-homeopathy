#!/usr/bin/env python3
from pathlib import Path
import base64, io, shutil, tarfile
ROOT=Path.cwd().resolve()
parts=ROOT/'materialize-v3'/'parts'
payload=''.join(p.read_text(encoding='ascii') for p in sorted(parts.glob('payload-*.txt')))
raw=base64.b64decode(payload.encode('ascii'),validate=True)
for item in list(ROOT.iterdir()):
    if item.name=='.git': continue
    if item.is_dir() and not item.is_symlink(): shutil.rmtree(item)
    else: item.unlink()
with tarfile.open(fileobj=io.BytesIO(raw),mode='r:gz') as tf:
    for member in tf.getmembers():
        target=(ROOT/member.name).resolve()
        if ROOT not in target.parents and target!=ROOT: raise SystemExit(f'Unsafe archive member: {member.name}')
        if member.issym() or member.islnk(): raise SystemExit(f'Links are not allowed: {member.name}')
    tf.extractall(ROOT)
for rel in ['tests/source-invariants.sh','tests/repository-scan.py','tests/deep-invariants.py','tests/static-invariants.py','scripts/build-release.py','scripts/source-tree-hash.py']:
    path=ROOT/rel
    if path.exists(): path.chmod(0o755)
print('Materialized File 05 v3.0.0 repository candidate.')
