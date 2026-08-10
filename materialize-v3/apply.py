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
    """Recover exactly one missing base64 character without guessing.

    The historical transport has one non-final chunk of length 18,999 while the
    surrounding fixed-size chunks are 19,000 bytes. Search every possible
    insertion position in that short chunk. Work at base64-group granularity:
    all four insertion positions in one group share the same decoded suffix, so
    the expensive suffix decode is done once per group rather than once per
    candidate. A candidate is accepted only when zlib reaches a valid gzip EOF
    (which verifies gzip CRC/length) and the resulting tar passes the archive
    safety checks. Exactly one full candidate must exist.
    """
    short = [
        (index, path, chunk)
        for index, (path, chunk) in enumerate(parts[:-1])
        if len(chunk) % 4 == 3
    ]
    if len(short) != 1:
        raise ValueError(f'Expected exactly one short transport chunk, found {len(short)}.')

    short_index, short_path, short_chunk = short[0]
    whole = ''.join(chunk for _, chunk in parts)
    short_start = sum(len(chunk) for _, chunk in parts[:short_index])
    short_end = short_start + len(short_chunk)

    if len(whole) % 4 != 3:
        raise ValueError('Transport length does not match a one-character-loss pattern.')

    # The missing character is known to be inside the uniquely short chunk.
    first_group = short_start // 4
    last_group = short_end // 4
    candidates = []

    # Prefix state for gzip decompression. Before the true missing character,
    # the corrupted transport prefix is byte-for-byte correct. We advance one
    # base64 group at a time and stop once the corrupted stream itself becomes
    # undecodable by zlib; the true insertion cannot lie after that point.
    inflater = zlib.decompressobj(16 + zlib.MAX_WBITS)
    group_count = (len(whole) - 3) // 4

    for group_index in range(group_count + 1):
        group_start = group_index * 4

        if first_group <= group_index <= last_group:
            prefix = whole[:group_start]
            three = whole[group_start:group_start + 3]
            if len(three) == 3:
                # After inserting one character into these three bytes, the
                # remaining source starts at group_start+3 and is 4-aligned.
                suffix_text = whole[group_start + 3:]
                try:
                    suffix_raw = base64.b64decode(suffix_text.encode('ascii'), validate=True)
                except binascii.Error:
                    suffix_raw = None

                if suffix_raw is not None:
                    # Positions 0..3 within this candidate group. Restrict the
                    # absolute insertion point to the short chunk itself.
                    for relative_pos in range(4):
                        absolute_pos = group_start + relative_pos
                        if absolute_pos < short_start or absolute_pos > short_end:
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

        # Advance the corrupted prefix state to the next group. Once zlib fails,
        # no later insertion can restore bytes already consumed before it.
        if group_index >= group_count:
            break
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
    return raw, short_path.name, absolute_pos - short_start


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
