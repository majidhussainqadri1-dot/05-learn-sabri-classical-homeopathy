# File 05 — Learn Sabri Classical Homeopathy

Canonical repository for the Sabri Social Homeopathy Platform learning domain.

## Current corrective candidate

- Runtime: `4.0.0`
- Core schema marker: `18`
- File 05 auxiliary state schema: `3`
- Future-18 learning-intelligence schema: `2`
- Plan: `SSH-F05-PLAN-2026-v1.1-future18-current-central-2026-08-10` under the current consolidated central governing corpus
- REST contract: `learn-sabri-classical-homeopathy/v2` (kept versioned/backward-compatible)
- Canonical package root: `05-learn-sabri-classical-homeopathy/`
- WordPress slug/text domain: `learn-sabri-classical-homeopathy`
- PHP prefix: `LSCH_`
- Required identity contract: File 00 `SMC_CONTRACT_VERSION >= 1.2.2`
- Access model: `single-free-tier-v2`; no learning paywall or donor advantage
- Primary visual fallback: Sabri Green `#087A4E`; File 25 remains visual-token owner
- Global search/discovery/ranking owner: File 26; File 05 exposes only a scoped learning projection

## Architectural boundary

File 05 owns curriculum, programs, courses, Founder learning-book catalog, lessons, assessments, assignments, enrollment, private progress/bookmarks/notes, teacher/assessor scope, completion/certificate-readiness records, learning-local saved searches, correction governance and privacy-minimized learning-value events. Identity is owned by File 00; shell by File 20; PDF by File 12; encyclopedia by File 06; video/live by File 10; AI answers by File 16; communication by File 17; notifications by File 19; feed/publishing truth by File 21; visual system by File 25; global search/discovery/ranking by File 26.

All protected learning actions consume File 00 public/versioned assertions and the current File 00 policy contract. File 05 does not read File 00 private meta/tables.

Private notes use AES-256-GCM with an independent deployment/key-manager keyring. New note encryption never derives keys from WordPress authentication salts. Historical v1 notes have a read-only compatibility decrypt path so controlled migration can re-encrypt them to the current key generation.

## Future Superset 18

Runtime 4.0.0 materializes F05-FUT-01..18. See `FUTURE-18-ENHANCEMENTS.md` and `REQUIREMENTS-TRACEABILITY.md`.

## Verification

```bash
bash tests/source-invariants.sh
find 05-learn-sabri-classical-homeopathy tests -type f -name '*.php' -print0 | sort -z | xargs -0 -n1 php -l
python3 scripts/build-release.py --root . --output file05-a.zip
python3 scripts/build-release.py --root . --output file05-b.zip
cmp file05-a.zip file05-b.zip
```

The deterministic package embeds `MANIFEST.sha256` and `SBOM.spdx.json`; the build also emits an external archive SHA-256 receipt.

## Truthful status rule

Repository-source completion is not staging/live/operational completion. Hostinger staging fresh-install/upgrade, real companion contracts, real-role journeys, browser/accessibility/load/security, backup/restore and rollback evidence remain mandatory before any production deployment or completion claim.
