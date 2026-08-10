# File 05 Status — 3.3.0 Current-Plans Corrective Source Candidate

This status distinguishes repository truth from staging/live truth.

| Status | Evidence / truth |
|---|---|
| Specified | Current consolidated central plan + current File 05 plan mapped |
| Coded | 18 FR + 10 NFR + current central amendments mapped to the 3.3.0 candidate |
| Reviewed | Source review/fix pass 1: version/materialization/File00/private-storage/crypto/search ownership defects corrected; pass 2 required on final exact HEAD |
| Packaged | Deterministic build workflow present; exact-head CI result required before claiming Green |
| Automated QA | Current-plan invariant suite + policy/crypto unit + JS syntax + PHP 7.4/8.3 + deterministic double build configured |
| Staging accepted | **No** — real WordPress/Hostinger and companion-runtime evidence pending |
| Live deployed | **No claim / not changed by this PR** |
| Operationally accepted | **No** |

## Current release identity

- Runtime: `3.3.0`
- Core database/schema marker: `17`
- File05 auxiliary state schema: `3`
- REST namespace: `learn-sabri-classical-homeopathy/v2`
- Canonical package root: `05-learn-sabri-classical-homeopathy/`
- Text domain: `learn-sabri-classical-homeopathy`
- PHP prefix: `LSCH_`
- Access model: `single-free-tier-v2`
- File00 public assertion contract minimum: `1.2.2`
- Global Search/Discovery/Ranking owner: `File 26`
- Current File05 color fallback: Sabri Green `#087A4E`; File25 retains design-token ownership

## Root-cause correction in this cycle

The prior branch had a PR narrative claiming a 3.x candidate while the materialized source was still runtime 2.0.0/schema 7. The historical `materialize-v3` transport was also corrupt and could not be deterministically reconstructed from its checked-in base64 payload. Patch-stacking on that transport has been stopped. The corrective release now advances the actual source tree directly and removes the failed materializer/workflow.

The release is not production-complete until the same immutable exact HEAD passes CI, deterministic package verification, then real Hostinger staging fresh-install/upgrade, File00/01/06/10/12/15/16/17/19/20/22/24/25/26 integration, browser/accessibility, concurrency/failure, backup/restore and rollback acceptance.
