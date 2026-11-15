# File05 V12.3 — Defect ledger and two scoped source review passes

**Updated:** 2026-11-15; previous findings retain their original 2026-11-14 provenance. **Basis:** Draft PR #8 and V12.3 execution plan. **Production parity:** UNVERIFIED.

## Exact meaning of review evidence

- **Review A (internal/source):** requirements/logic, File06/26 owner contracts, authorization/privacy and errors.
- **Review B (internal/adversarial):** retraction, version drift, revoked reviewer, failed WordPress DB/taxonomy, malformed response, safe-mode and disposable activation/repetition.
- Both are **scoped code/test passes conducted in this assistant-led workflow**, not a claim of two independent human reviewers. V12.3 G4's *two independent acceptance reviews* remain a release blocker.
- A successful test is a source result only. A deploy/staging/live defect cannot be marked fixed without exact deployed artifact evidence and live retesting.

## Unique source findings (26 cumulative: 17 prior + 5 previous 2026-11-15 + 4 scoped UI findings)

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
| F05-S13 | File16 tutor citation filter could upgrade an unrelated/non-approved source to approved | Source-trust escalation | Filters may only veto the native approved bibliography match | `tests/v123-file16-socratic-owner.php` |
| F05-S14 | File16 external tutor/reviewer callback could throw through the File05 REST handler | Availability / error containment | Provider and review exceptions converted to safe error states | `tests/v123-file16-socratic-owner.php` |
| F05-S15 | File16 answer and citation fields were not strictly typed/bounded | Payload safety / resource budget | Require bounded string response, scalar citation fields and 20-citation cap | `tests/v123-file16-socratic-owner.php` |
| F05-S16 | File16 matching object ID could authorize an unrelated URL (or matching URL an unrelated object ID) | Citation identity/link forgery | When both fields supplied, require both to match one approved source | `tests/v123-file16-socratic-owner.php` |
| F05-S17 | External File16 WP_Error could expose an untrusted provider error message | Provider error disclosure | Convert all provider errors to bounded safe 503, do not relay the raw error | `tests/v123-file16-socratic-owner.php` |
| F05-S18 | File16 callback/third-party citation reviewer could revoke learner access or change the lesson/source while answer request was in flight | Runtime authorization and evidence TOCTOU | Revalidate the current learner, lesson version, objectives and approved sources after provider and citation-review filters; four new negative cases | `tests/v123-file16-socratic-owner.php` |
| F05-S19 | File26 `list_batch()` accepted nonempty scope filters (except `object_id`) but silently returned the full unfiltered snapshot | Incomplete/ambiguous owner contract | Reject all nonempty unsupported scopes; explicit negative test | `tests/v123-file26-public-version.php` |
| F05-S20 | File26 untrusted document IDs/types could cast booleans/overflowing integers or malformed fields before canonical owner revalidation | Identity normalization / malformed DTO | Reject alternate, out-of-range IDs and non-scalar indexed metadata | `tests/v123-file26-public-version.php` |
| F05-S21 | Exact 4.0.0 → 4.1.0 code replacement upgraded schema but left `lsch_version=4.0.0`, misleading release identity | Version/migration identity | After all schema markers and physical mentor table validate, persist `lsch_version` and source-plan provenance with readback; abort on persistence failure | `tests/v123-upgrade-seed-v400.php`, `tests/v123-upgrade-verify-v410.php` |
| F05-S22 | File06 approved link metadata was written before relation index; index persistence failure could leave a partially approved, inconsistent relation | Cross-object approval ordering | Persist unprivileged index first, independently approved decision last; inject each meta-write failure | `tests/v12-file06-approved-links.php` |
| F05-S23 | Media-only course card links had no accessible link name when no featured image was supplied | WCAG 2.4.4 link purpose / UI | Accessible localized aria-label using actual card title; real WP markup regression | `tests/v123-accessible-lesson-ui-wp.php` |
| F05-S24 | Lesson progress/bookmark controls had no local live-status destination, leaving successful or failed actions visually and semantically silent | Accessible feedback / user trust | Separate role=status region inside learning tools; Node mutation + real WP markup regression | `tests/v123-learning-action-status.test.js`, `tests/v123-accessible-lesson-ui-wp.php` |
| F05-S25 | Once verified CSS/JS assets loaded, 320px Chromium measured `.lsch-hero` first grid child at 335px, pushing page width to 396px | Responsive reflow / genuine File05 mobile overflow | Set hero grid minmax(0,1fr), constrain grid children and permit kicker line wrapping | `tests/v123-disposable-chromium-landing.test.js` |
| F05-S26 | 200% root text resizing on a 320px viewport made four learning level cards 312.5px wide and total document 405px due unbreakable min-content level names | Text resize and reflow WCAG 1.4.4/1.4.10 | Constrain card min-width, allow title wrapping and mobile minmax(0,1fr) | `tests/v123-disposable-chromium-landing.test.js` |

**Status for F05-S01..S25:** candidate source corrected; exact-head browser reflow retest pending in the Draft source branch and corresponding isolated regression checks passed on its prior tested commits. *Current exact head CI must always be rechecked after further commits.* **Live verified: 0**. Do not mark production Resolved.

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
| F05-O06 | P06 real peer CI | File00 1.2.44 (DB 1.4.5, `ready` after deferred admin bootstrap), File01 2.0.1, File20 1.4.17, File05 4.1.0: pinned source snapshots and guest/private REST smoke passed | Disposable source integration; Hostinger staging and exact deployed peers unverified |
| F05-O07 | File00 owner bootstrap log | First authorized CLI activation observed 31 missing-table SQL errors before deferred File00 admin bootstrap completed; upstream issue #62 records reproducible owner finding | Open File00-owned source concern; not a File05/live fix |
| F05-O08 | File00 readiness contract | Before completion, File00 bootstrap was `queued` while File05's `missing()` returned `[]`; a public owner migration/ready contract should be supplied rather than direct File00 private-table coupling | Pending versioned File00 owner decision |
| F05-O09 | Actual File06 source | Pinned File06 `2.4.20` candidate at `4f5b7b1714bd1dc502026ca6509f7cab35b43cd5`; real owner `HE_V2_Schema::runtime_status()=active`, 11 negative canonical claim checks passed | Missing real approved current concepts/lessons, source rights and peer signoff; source candidate is NOT File06 main |
| F05-O10 | Actual File26 source | Pinned File26 main `1.3.0` at `1b064c6ea9f81195611ed001d5457e8ced65460a`; proposed File05 adapter registered in real WP and remained not index-eligible; 15 checks passed | No owner tombstone/event lifecycle proof, activation governance, populated live index or real peer staging |
| F05-O11 | Historical versioned migration | Exact File05 `4.0.0` commit `1fa37ec81df5ef9da4b794cdb44283a7204d1dcf` upgraded to `4.1.0`; 14 tests, historical 101 feedback and bookmark retained | Not actual site's predecessor, not real Hostinger backup/rollback |
| F05-O12 | Database corruption safety | On disposable WP, physically dropped mentor table while schema marker stayed `1`; next process recorded `lsch_runtime_failure` and paused | Hostinger missing-table/read-only incident still unverified |
| F05-O13 | Real MariaDB mentor boundary | 0/99/100/101/500/1000 legacy rows, 56 checks, tampered middle entry and partial normalization fail-closed | Real site historical data unknown |
| F05-O04 | P07 | Eight source books, lesson claims, edition/copyright, reviewed translations and approved quizzes not populated | **Blocked / pending approval** |
| F05-O05 | P12 | Exact deployed ZIP/version, DB options/schema, migration/restore and Live re-test unknown | **Blocked** |

## Corrected diagnostic attribution (2026-11-15)

- **F05-T01 — Disposable browser harness bug, NOT File05 code defect.** An initial headless axe `target-size` failure on 1440px RTL reflected missing stylesheet presentation; a subsequent browser run confirmed the File05 CSS `link` was present in HTML but computed `.lsch-grid-four` remained `display:block`. The disposable PHP development server had been started with `index.php` as an explicit router, which routed static asset requests through WordPress instead of serving actual CSS/JS files. The server setup was corrected to use only the static document root. A speculative source `is_learning_context()` modification was **reverted**. Only if a subsequent exact-head browser run with verified loaded CSS still fails should a File05 layout defect be recorded.
- This correction replaces the provisional source-root-cause claim; do not count F05-T01 among File05 source findings or present it as live/staging evidence.

## Test/evidence inventory

| Mechanism | Proven scope | Not proven |
| --- | --- | --- |
| PHP 7.4 + PHP 8.3 | Full source lint, existing fixtures, File06 source assertions (new count to verify), File26 25 and File16 19 isolated assertions; V12.3 matrix 53/CV12 integrity | Hostinger/Live and actual provider suites |
| Node.js | Original learning/Future18 and new private-note failure/malformed-revision regression | Browser NVDA/TalkBack/VoiceOver |
| Deterministic ZIP | A/B identical archive plus manifest verification | Actual deployed artifact |
| WordPress 7.0.1 disposable with MariaDB 10.11 | Plugin activation, installed schema, REST routes, guest/private authorization, owner proposed status and reactivation | WordPress on Hostinger, upgrade from actual predecessor |
| Pinned real File00/01/20 peers in disposable CI | File00 protected admin bootstrap to `ready` / DB 1.4.5, File05 preflight, guest public catalog and native private REST denial succeeded, CI #43664653956 | Real site migration/parity, approved users/teachers, independent security acceptance |
| Real File06 and File26 peer contract CI | File06 11 canonical negative checks in disposable WordPress and File26 15 proposed/no-index checks; peer source SHAs pinned | Real approved links, event/tombstone retry/retract, File16 provider and actual Hostinger staging |
| P00 runbook | Exact read-only options/tables/roles/parity checklist | No actual Live DB evidence |

## Defect accounting — unique, no stacking

- **Source findings S01..S24:** 24 cumulative; 17 inherited from 2026-11-14, 5 fixed on the prior 2026-11-15 Draft PR #8 (S18–S22), and **4 candidate UI repairs** on the stacked access branch (S23–S26). Verify the exact branch HEAD and independent real WP + Node regression before marking them source-tested. **No staging/Live resolution is claimed.**
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
