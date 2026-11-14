# File05 V12.3 — Defect ledger and two scoped source review passes

**Date:** 2026-11-14. **Basis:** Draft PR #8 and V12.3 execution plan. **Production parity:** UNVERIFIED.

## Exact meaning of review evidence

- **Review A (internal/source):** requirements/logic, File06/26 owner contracts, authorization/privacy and errors.
- **Review B (internal/adversarial):** retraction, version drift, revoked reviewer, failed WordPress DB/taxonomy, malformed response, safe-mode and disposable activation/repetition.
- Both are **scoped code/test passes conducted in this assistant-led workflow**, not a claim of two independent human reviewers. V12.3 G4's *two independent acceptance reviews* remain a release blocker.
- A successful test is a source result only. A deploy/staging/live defect cannot be marked fixed without exact deployed artifact evidence and live retesting.

## Unique source findings (12 cumulative: 5 earlier and 7 added this run)

| ID | Proven candidate-source issue | Class | Source correction and regression | Test |
| --- | --- | --- | --- | --- |
| F05-S01 | File06 approval accepted owner assertion without explicit version equality | Source logic / stale reference | Source guarded; exact version test | `tests/v12-file06-approved-links.php` |
| F05-S02 | File26 can_view did not compare indexed revision and canonical destination with current owner | Search freshness/privacy | Source guarded; stale URL/version tests | `tests/v123-file26-public-version.php` |
| F05-S03 | File26 readiness did not require exact current File00/01/20 and central policy | Owner availability / fail closed | Source gated; dependency failure test | `tests/v123-file26-public-version.php` |
| F05-S04 | File06 mutation recheck omitted fresh native reviewer capability | Authorization TOCTOU | Source guarded; revoked native cap test | `tests/v12-file06-approved-links.php` |
| F05-S05 | File26 can_view did not explicitly reject nonpublished indexed state | Indexed state | Source guarded; withdrawn state test | `tests/v123-file26-public-version.php` |
| F05-S06 | File06 already approved related link did not recheck current File06 publication/version | Retraction/freshness | Source guarded; absent/changed/throwing owner tests | `tests/v12-file06-approved-links.php` |
| F05-S07 | File06 existing link only checked reviewer WordPress capability, not current File00 eligibility | Reviewer suspension | Source guarded; lost current eligibility test | `tests/v12-file06-approved-links.php` |
| F05-S08 | File06 approval route and handler did not check learning safe mode | Incident safety | Source guarded; safe-mode negative test | `tests/v12-file06-approved-links.php` |
| F05-S09 | File26 source batch silently treated taxonomy provider errors as absent items | Snapshot completeness | Source returns retryable WP_Error; fault-injection test | `tests/v123-file26-public-version.php` |
| F05-S10 | File26 source batch or object projection could advance through DB read errors | Source/DB integrity | Source aborts inconsistent reads; DB-failure test | `tests/v123-file26-public-version.php` |
| F05-S11 | Failed private-note GET left an editable empty field and no accessible failure feedback | UX/privacy/data-loss confusion | Read-only until revision GET; error status; JS test | `tests/v123-private-note-read-ux.test.js` |
| F05-S12 | Successful but malformed note payload lacking revision unlocked editing after first fix | Review-B revision validation | Strict canonical revision proof; malformed-success test | `tests/v123-private-note-read-ux.test.js` |

**Status for F05-S01..S12:** corrected in the Draft source branch and corresponding isolated regression checks passed on its prior tested commits. *Current exact head CI must always be rechecked after further commits.* **Live verified: 0**. Do not mark production Resolved.

## Other non-duplicated work and remaining questions

| ID | Category | Evidence/decision | Status |
| --- | --- | --- | --- |
| F05-I01 | PR integration | PR #6 and #7 both altered the PR #5 bootstrap; now joined under PR #8 candidate | Draft source complete, not merged |
| F05-D01 | Documentation | STATUS/CHANGELOG/ARCHITECTURE historical 4.0.0 vs current plugin header 4.1.0; dated header/clarification added | Draft docs corrected |
| F05-D02 | Release identity | `LSCH_PLAN_VERSION` writes legacy v12 marker to `lsch_plan_version` at activation; changing it could be misread as achieved V12.3 compliance | **Open governance/version decision** |
| F05-A01 | Academic | Four-level/16-topic 64-outcome blueprint and eight unapproved book slots written; no source text or rights invented | Draft only, Founder/editorial signoff blocked |
| F05-O01 | P00 | WordPress.com/Jetpack disconnected, WPVibe HTTP 530, public fetch unavailable | **Blocked; root cause unknown** |
| F05-O02 | P06 | File16 Socratic provider `lsch_file16_socratic_tutor` and actual File19/File24/26 companion lifecycle not attested against deployed builds | **Blocked** |
| F05-O03 | P11 | WordPress 7.0.1 + MariaDB 10.11 disposable CI with File00/01/20 mocks — *not site staging* | Provisional WP compatibility only |
| F05-O04 | P07 | Eight source books, lesson claims, edition/copyright, reviewed translations and approved quizzes not populated | **Blocked / pending approval** |
| F05-O05 | P12 | Exact deployed ZIP/version, DB options/schema, migration/restore and Live re-test unknown | **Blocked** |

## Test/evidence inventory

| Mechanism | Proven scope | Not proven |
| --- | --- | --- |
| PHP 7.4 + PHP 8.3 | Full source lint, existing fixtures, File06 17 and File26 17 isolated assertions | Real peer contracts or Live |
| Node.js | Original learning/Future18 and new private-note failure/malformed-revision regression | Browser NVDA/TalkBack/VoiceOver |
| Deterministic ZIP | A/B identical archive plus manifest verification | Actual deployed artifact |
| WordPress 7.0.1 disposable with MariaDB 10.11 | Plugin activation, installed schema, REST routes, guest/private authorization, owner proposed status and reactivation | File00/01/20 *real* versions, WordPress on Hostinger, upgrade from actual predecessor |
| P00 runbook | Exact read-only options/tables/roles/parity checklist | No actual Live DB evidence |

## Defect accounting — unique, no stacking

- **Source findings S01..S12:** 12 cumulative; all 12 corrected on the Draft candidate with regression evidence. Of these, **7 findings (S06..S12)** were added and corrected during the present continuation.
- **Branch integration conflict:** 1 (I01) reconciled in Draft.
- **Documentation mismatch:** D01 corrected; D02 intentionally open and tracked.
- **Test failures being concealed:** 0 known in the recorded passing exact-head CI. **Blocked mandatory tests remain** and must never be counted as Passed.
- **Staging-confirmed corrections:** 0. **Live-confirmed corrections:** 0.
- **Found Live root causes:** 0 — no deployed code, DB/migration parity or runtime logs were accessible.

## Mandatory remaining G0–G7 gates

1. G0 real deployed version/source checksum, DB/migration/state and backup restore evidence.
2. G1 signoff on 48 direct + 5 supplemental source IDs, twelve CV paths and UX/AJ/QA checklist.
3. G2 owner versions, File06/16/19/24/26 actual contract integration, no proposed-to-active index cutover.
4. G3 Founder/academic acceptance of approved lessons, source rights, safe simulations and credentials wording.
5. G4 two **independent** reviews (not merely two self-checks), all exact-head tests and zero active defects.
6. G5 real Hostinger/WordPress staging, roles, UX/RTL/a11y/load, upgrade, migration/rollback and restore.
7. G6 controlled Founder-authorized production release with verified ZIP, monitor and rollback.
8. G7 **same deployed build** re-test, source/DB/manifest parity and no known active defects.

**Five truth fields at this source-review stage:** Repository `main` historical observed `e2667d69d43d655e3d86b0a358dec5f28b61dd96` (refresh at release); **Deployed Version UNVERIFIED / DB Version UNVERIFIED / Migration State UNVERIFIED / Live Verification NOT PERFORMED**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
