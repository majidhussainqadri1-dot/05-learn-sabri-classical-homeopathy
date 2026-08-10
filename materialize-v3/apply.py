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


def validate_archive(raw):
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
    return members


def decode_payload(payload):
    padded = payload + '=' * (-len(payload) % 4)
    raw = base64.b64decode(padded.encode('ascii'), validate=True)
    validate_archive(raw)
    return raw


def recover_one_missing_base64_char(parts):
    """Exhaustively recover one lost base64 character, verified by gzip+tar.

    The checked-in transport has length 3 mod 4 while the original materializer
    expected strict base64. Instead of guessing a boundary, search every possible
    insertion position that can precede the first zlib failure. Work by base64
    group so each 4-position/64-character family shares one decoded suffix. A
    repair is accepted only when gzip reaches EOF with CRC/length validation and
    the tar archive passes path/link safety checks. More than one valid repair is
    treated as corruption and aborts.
    """
    whole = ''.join(chunk for _, chunk in parts)
    if len(whole) % 4 != 3:
        raise ValueError('Transport length does not match a one-character-loss pattern.')

    candidates = []
    inflater = zlib.decompressobj(16 + zlib.MAX_WBITS)
    group_count = (len(whole) - 3) // 4

    for group_index in range(group_count + 1):
        group_start = group_index * 4
        three = whole[group_start:group_start + 3]
        if len(three) == 3:
            suffix_text = whole[group_start + 3:]
            try:
                suffix_raw = base64.b64decode(suffix_text.encode('ascii'), validate=True)
            except binascii.Error:
                suffix_raw = None

            if suffix_raw is not None:
                for relative_pos in range(4):
                    absolute_pos = group_start + relative_pos
                    if absolute_pos > len(whole):
                        continue
                    for char in BASE64_ALPHABET:
                        group_text = three[:relative_pos] + char + three[relative_pos:]
                        if len(group_text) != 4:
                            continue
                        try:
                            group_raw = base64.b64decode(group_text.encode('ascii'), validate=True)
                            trial = inflater.copy()
                            trial.decompress(group_raw)
                            trial.decompress(suffix_raw)
                            trial.flush()
                        except (binascii.Error, zlib.error):
                            continue
                        if not trial.eof:
                            continue

                        repaired = whole[:absolute_pos] + char + whole[absolute_pos:]
                        try:
                            candidate_raw = decode_payload(repaired)
                        except DECODE_ERRORS:
                            continue
                        candidates.append((absolute_pos, char, candidate_raw))
                        if len(candidates) > 1:
                            raise ValueError('More than one valid payload repair candidate exists.')

        if group_index >= group_count:
            break

        # If the corrupted stream fails while consuming this group, any missing
        # character located after this group cannot repair bytes already parsed;
        # therefore the exhaustive search can safely stop here.
        current = whole[group_start:group_start + 4]
        if len(current) != 4:
            break
        try:
            current_raw = base64.b64decode(current.encode('ascii'), validate=True)
            inflater.decompress(current_raw)
        except (binascii.Error, zlib.error):
            break

    if len(candidates) != 1:
        raise ValueError(f'Expected one valid payload repair candidate, found {len(candidates)}.')

    absolute_pos, char, raw = candidates[0]

    # Map the recovered absolute offset back to its transport part for a useful
    # audit receipt without exposing or persisting a mutated payload file.
    running = 0
    part_name = 'unknown'
    part_offset = absolute_pos
    for path, chunk in parts:
        if absolute_pos <= running + len(chunk):
            part_name = path.name
            part_offset = absolute_pos - running
            break
        running += len(chunk)

    return raw, part_name, part_offset


parts = normalized_parts()
whole = ''.join(chunk for _, chunk in parts)

try:
    raw = decode_payload(whole)
    recovery_note = 'payload validated without repair'
except DECODE_ERRORS as direct_error:
    try:
        raw, part_name, relative_pos = recover_one_missing_base64_char(parts)
    except DECODE_ERRORS as recovery_error:
        raise SystemExit(
            'Materialization payload is corrupt and deterministic recovery failed: '
            f'{recovery_error} (direct error: {direct_error}).'
        ) from recovery_error
    recovery_note = (
        f'uniquely repaired one missing base64 character in {part_name} '
        f'at offset {relative_pos}'
    )

# Destructive replacement starts only after the complete candidate has passed
# gzip CRC/length, tar integrity and archive path/link safety validation.
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
