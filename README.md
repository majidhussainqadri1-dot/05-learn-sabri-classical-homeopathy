# File 05 — Learn Sabri Classical Homeopathy

Canonical repository for the Sabri Social Homeopathy Platform learning domain.

## Corrective release

- Runtime: `2.0.0`
- Schema: `6`
- Plan: `SSH-F05-PLAN-2026-v1.0`
- Canonical package root: `05-learn-sabri-classical-homeopathy/`
- WordPress slug/text domain: `learn-sabri-classical-homeopathy`
- PHP namespace prefix: `LSCH_`
- Business amendment: one complete free education tier; no PKR 400 gate
- Visual amendment: green primary identity with contextual secondary colors and icons

This branch implements the complete source architecture and automated evidence required by the approved File 05 plan. It does not claim Hostinger staging acceptance, Founder acceptance, live deployment, or operational acceptance without their separate real-environment evidence.

## Local verification

```bash
python3 tests/static-invariants.py
php tests/unit-policy.php
find 05-learn-sabri-classical-homeopathy tests -name '*.php' -print0 | xargs -0 -n1 php -l
python3 scripts/build-release.py --output file05-a.zip
python3 scripts/build-release.py --output file05-b.zip
cmp file05-a.zip file05-b.zip
```
