# File 05 — Learn Sabri Classical Homeopathy

This repository preserves the immutable File 05 `0.1.0` baseline and develops the governed `1.0.0` correction separately.

## Branches

- `baseline/file-05-original-import` — exact supplied source and custody evidence; never modify or merge as a release.
- `audit/file-05-source-review` — corrective implementation for the 20 recorded blockers.
- `main` — remains protected from unaccepted baseline or corrective code.

## Corrected architecture

File 00 is the authoritative membership and verification boundary. File 01 owns the Learn page. File 20 owns the global application shell. File 05 owns learning books, lessons, classifications, moderation, private progress, bookmarks, quizzes, patient-case consent records, privacy integration, and release/staging evidence.

## Verification

```bash
bash tests/source-invariants.sh
php tests/security-invariants.php
python3 scripts/source-tree-hash.py sabri-learning
python3 scripts/build-release.py --output file-05-1.0.0-rc1.zip
```

See `CORRECTIVE-REVIEW.md`, `CORRECTIVE-MANIFEST.md`, `RELEASE-LOCK.md`, and `STAGING-ACCEPTANCE.md`.
