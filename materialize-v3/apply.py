#!/usr/bin/env python3
from pathlib import Path
import base64
import binascii
import io
import shutil
import tarfile
import zlib

ROOT = Path.cwd().resolve()
PARTS = ROOT / 'materialize-v3' / 'parts'
BASE64_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
DECODE_ERRORS = (ValueError, binascii.Error, tarfile.TarError, OSError, EOFError, zlib.error)


def normalized_parts():
    result = []
    for path in sorted(PARTS.glob('payload-*.txt')):
        result.append((path, ''.join(path.read_text(encoding='ascii').split())))
    if not result:
        raise SystemExit('No materialization payload parts found.')
    return result


def decode_and_validate(chunks):
    payload = ''.join(chunks)
    payload += '=' * (-len(payload) % 4)
    raw = base64.b64decode(payload.encode('ascii'), validate=True)
    with tarfile.open(fileobj=io.BytesIO(raw), mode='r:gz') as tf:
        members = tf.getmembers()
        if not members:
            raise ValueError('Materialization archive is empty.')
        for member in members:
            target = (ROOT / member.name).resolve()
            if ROOT not in target.parents and target != ROOT:
                raise ValueError(f'Unsafe archive member: {member.name}')
            if member.issym() or member.islnk():
                raise ValueError(f'Links are not allowed: {member.name}')
    return raw


parts = normalized_parts()
chunks = [chunk for _, chunk in parts]

try:
    raw = decode_and_validate(chunks)
    recovery_note = 'payload validated without repair'
except DECODE_ERRORS as direct_error:
    # Historical transport split left exactly one base64 character missing at a
    # chunk boundary. Recover only when the archive itself cryptographically/
    # structurally disambiguates the missing character: exactly one candidate
    # must produce a complete safe gzip/tar archive. Never guess or continue on
    # zero/multiple candidates.
    candidates = []
    for index, (path, chunk) in enumerate(parts[:-1]):
        if len(chunk) % 4 != 3:
            continue
        for char in BASE64_ALPHABET:
            test_chunks = list(chunks)
            test_chunks[index] = chunk + char
            try:
                candidate_raw = decode_and_validate(test_chunks)
            except DECODE_ERRORS:
                continue
            candidates.append((index, path.name, char, candidate_raw))

    if len(candidates) != 1:
        raise SystemExit(
            'Materialization payload is corrupt and cannot be uniquely repaired: '
            f'{len(candidates)} valid boundary candidates (direct error: {direct_error}).'
        )

    index, part_name, recovered_char, raw = candidates[0]
    chunks[index] = chunks[index] + recovered_char
    # Re-validate the chosen candidate once more before any destructive action.
    raw = decode_and_validate(chunks)
    recovery_note = f'uniquely repaired one missing base64 character at end of {part_name}'

# Only after the complete candidate archive has been validated do we replace
# the checked-out transport/materializer tree. This guarantees a corrupt
# payload cannot destructively remove the current source.
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

print(f'Materialized File 05 repository candidate; {recovery_note}.')
