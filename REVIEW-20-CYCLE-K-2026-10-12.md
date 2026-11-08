# File 05 — Eleventh Fresh Twenty-Focus Source Audit — Cycle K (2026-10-12)

## Evidence freeze and governing plans
- Frozen exact repository PR HEAD: `d08f28949d73ecf54077d0cc8622ac7c08eeb4bc` in Draft PR #4, branch `codex/file-05-complete-v3.0.0`; GitHub `main` at freeze: `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. Draft PR was open/unmerged.
- All **19 native PHP class source files**, **417,285 characters**, read at the frozen source commit. Read the actual attached File05 v1.1 Future18 Master Plan and the governing Three Central Plans v3.0, plus attached File06 and File12 plan owners. File05 master: private notes/progress, validated assessment/learning evidence, source-owner File12 attachment linkage, idempotent durable changes, privacy and staging acceptance. Central plan: separate canonical owners, exact build and migration parity, staged QA, Founder-approved release.
- These are twenty **separate focused source inspections** at a single immutable baseline. They do NOT represent twenty WordPress E2E executions or twenty complete plugin rereads. Source and CI do not prove live deployment.

## Twenty source focus review register

| Round | Focus and owner cross-check | Outcome |
|---:|---|---|
| 1 | File05 level/topic/course vocab and native post types; Content | Found source curriculum owners; actual published corpus pending |
| 2 | Founder book slots versus File06 canonical encyclopedic content | Source link contracts present, external live owner unverified |
| 3 | Central free-tier and File00 account access; Policy/Dependencies | Versioned central policy gates present |
| 4 | Doctor/guardian role claims and staff scope; Capabilities | Source role and guardian gates found |
| 5 | Public REST/WordPress query visibility, Files20/25/26 | Prior parent/case/public fail-closed guards present |
| 6 | Prerequisite completion and academic exemption validity | Previous query and exception safety checks present |
| 7 | Enrollment state persistence versus File19 event delivery | **K01**: transition emitted event and returned unchecked DB reread, which could be null/mismatched |
| 8 | Private lesson progress, File24 privacy/version concurrency | **K02**: failed pre-update progress row read could masquerade as missing row |
| 9 | Owned learning bookmark toggle and File25 private UX | **K03**: failed bookmark existence query could lead to unintended INSERT/toggle |
| 10 | AES-GCM private note write, File24 privacy and keyring | **K04**: failed prior note read could lead to new INSERT path with unknown previous state |
| 11 | Assessment attempts, version/time controls and File19 outbox | Previous score and idempotent write guards present |
| 12 | Submission grading, assessor case scope, education-only credential | Current assessor scope checks found; staging concurrency pending |
| 13 | File12 canonical assignment attachment and post-insert verification | **K05**: successful assignment insert followed by failed reread returned null with success audit |
| 14 | Case consent, withdrawal and File24 retention | No new fix; history-preserving patient re-consent remains governed schema/workflow gap |
| 15 | File16/17 learning/community contextual adapters | Native guest authorization and source scopes present; counterpart implementations unverified |
| 16 | File19 reminder preferences and user notifications | **K06**: learning reminder change event emitted before DB reread, which might fail |
| 17 | Certificate-readiness current test evidence and File00 identity | **K07**: failed `MAX(score)` read silently cast to zero instead of actionable error |
| 18 | File22 correction lifecycle, File24 erasure/legal hold | Prior source controls found; staging and native WP privacy rights pending |
| 19 | File19 outbox, daily reconcile, jobs and transaction buffer | Prior error handling and source operational hooks found |
| 20 | File26 public index, schema/versioning, staging rollback | Source migrations and projections present; actual installed build, DB and partner parity unverified |

**Round outcome:** 20 focus checks; seven new source defects K01-K07 in rounds 7, 8, 9, 10, 13, 16, 17, corrected together after the review. The 13 other focus checks found no new proven defect in the inspected routes; this is not evidence of complete specification delivery.

## Root causes and corrective code

- **K01** `LSCH_Services::change_enrollment_state()`: post-update enrollment row must be a typed array with matching new status and expected next version *before* publishing `LearningEnrollmentActivated.v1` / state-changed event. On read failure/mismatch returns `lsch_enrollment_transition_unverified` 503 and bounded reliability audit; no false event.
- **K02** `LSCH_Services::progress()`: if existing version row lookup returns empty while `$wpdb->last_error` reports DB failure, explicitly return `lsch_progress_read_failed` 503 rather than inserting version-1 progress.
- **K03** `LSCH_Services::toggle_bookmark()`: protect bookmark existence lookup against false or null-with-DB-error; returns `lsch_bookmark_read_failed` 503, performs no toggle.
- **K04** `LSCH_Services::save_note()`: protect encrypted-note prior version read against failed SQL query, explicitly return `lsch_note_read_failed` 503 rather than treating existing ciphertext as absent or attempting duplicate INSERT.
- **K05** `LSCH_Services::submit_assignment()`: reread/verify persisted submission id/status/owner/assignment after INSERT *before* emitting the success audit or returning. On error `lsch_assignment_read_failed` 503 and reliability audit; operator must reconcile saved row before trying new submission.
- **K06** `LSCH_Services::set_reminder()`: verify saved row with expected enabled flag and cadence after upsert and before publishing the preference-change outbox event. On mismatch/failed read returns `lsch_reminder_read_failed` 503, avoiding fabricated result/event.
- **K07** `LSCH_Services::certificate_readiness()`: verifies per-current-assessment `MAX(score)` returned a numeric, nonfailed DB value before constructing competency floor. On false/null/non-number returns `lsch_certificate_assessment_read_failed` 503 rather than presenting an incorrect zero/failed-evidence conclusion.

## Regression and CI
- New executable `tests/eleventh-postwrite-verified.php`: failure and healthy postwrite cases for enrollment transition, submission and reminder; asserts no premature events or success audit.
- New `tests/eleventh-private-read-guards.php`: failures in note version read, progress version read, bookmark existence and current certificate score; requires no writes when unverified.
- Both in mandatory `tests/source-invariants.sh`; previous tests preserved. Initial new-test GitHub runs failed due to missing/isolation differences in fixture: assignment enrollment active state and `sanitize_key` WordPress stub, corrected in the test fixtures only. **Final exact documentation SHA-matched CI must be reconfirmed.**
- Source-only test harnesses do not establish a real MySQL transaction, File19 durable outbox delivery, File12 attachment plugin runtime, deployed File24 privacy state or integration acceptance.

## Remaining gaps and deploy-parity law
- Full governed **patient re-consent after withdrawal** with append-only signed evidence/versioned schema, Founder-approved File24 retention/migration/reversal and real-role acceptance remains pending; no unapproved schema patch was made.
- Source SQL migration and live DB/version parity are unverified. Never infer deployed code from GitHub main, draft PR, ZIP, CI or historical reports.
- Stage: real File00/06/12/16/17/19/20/22/24/25/26 owner contract tests, error injection at each row fetch, private note decryption/rotation, multi-device CAS, outbox/transaction rollback, privacy data requests, migration backup and restore. Deployment requires approval, exact checksum parity and live retest.

## Separate reality register (evidence, not assumptions)
| Dimension | Status |
|---|---|
| Frozen audited PR HEAD | `d08f28949d73ecf54077d0cc8622ac7c08eeb4bc` |
| Final amended PR branch HEAD | See last documentation commit and exact-matched CI |
| GitHub main HEAD at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR state | Draft/Open/Unmerged |
| Declared source plugin version | `4.0.0` |
| Source-expected schema versions | Core 18 / Auxiliary 3 / Future18 2 |
| Exact deployed version/package checksum | Unverified |
| Actual DB version/schema/rows | Unverified |
| Live migration state | Unverified |
| Staging / Live verification | Not performed |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
