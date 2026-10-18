# File 05 — Thirteenth Fresh Twenty-Focus Source Audit — Cycle M (2026-10-12)

## 1. Frozen exact-HEAD basis, governing documents and reality separation

**Review-frozen GitHub PR HEAD:** `de8ae9a87a743f8ea0c3d5d6b38e8dcb80a3732f` on `codex/file-05-complete-v3.0.0`; Draft/Open/Unmerged PR #4. `main` at freeze: `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. Fetched and read the **19 native PHP classes** (approximately **424,812 characters**) and focused on required owner semantics, replay/history failures and educational state integrity. This is **20 distinct source-focused inspections**, NOT twenty independent WordPress end-to-end trials.

Exact supplied governing source plans: **File 05 Learn Sabri Classical Homeopathy Complete Master Plan 2026 v1.1 Future-18** and **Three Central Plans Consolidated Governing Master Plan Version 3.0**. Both explicitly distinguish specified, coded, staged, and live evidence; govern educational owner truth, enrollment, evidence, privacy, current credential and versioned state, durable event delivery, File19 event idempotency, rollback and release approval. Companion attached **File06 Encyclopedia** and **File12 PDF Library** plans confirm learning references may not take over their canonical knowledge/document owners. Actual installed File00/06/09/12/16/17/19/20/22/24/25/26 plugin revisions were not inspected or proven deployed.

## 2. Twenty independent focus checks — documented results

| # | Plan requirement and File05 source/cross-file relationship | Fresh result |
|---:|---|---|
| 1 | File05 canonical four learning levels, programs/books/courses/lessons; Content | Native owner methods found; published data not verified |
| 2 | File06 reference links and knowledge authority boundary; Content/related links | File05 link projection exists; external owner responder runtime unverified |
| 3 | File12 approved assignment attachment references; Services | External validation hook exists; File12 installed attachment and signing behavior unverified |
| 4 | File00 accounts, minors/guardian, current role and account claims; Dependencies/Capabilities | Versioned source gates found; partner parity unverified |
| 5 | File09 verified doctor status before teacher/assessor access; Capabilities/Future18 | Source status gates found; staging real-role checks pending |
| 6 | File20/25 frontend private learner surfaces and public search; Frontend | Source no-store and authorization surfaces found |
| 7 | File26 global search related links and cache visibility; REST/Policy/Value | Source visibility and external hook checks found; external consumer unknown |
| 8 | File05 course enrollment and prerequisites; Services/Policy | Prior strict authority/DB guards found; live schema unverified |
| 9 | F05 assessed attempt allocation and request replay; Services | **M01: prior attempt SELECT DB failure could be read as absent before a second allocation** |
| 10 | File05 assessment answers/versions/timer and grading; Services | Previously corrected scoring/version safety found; real exam load pending |
| 11 | File05 assignment, appeal and File12 assessor authorization; Services | **M07: grading and appeal SELECT failure misclassified as ordinary conflict** |
| 12 | File05 teacher/assessor object scope and revocation; Services/Capabilities | Conflict-cleared object-scoped guards found |
| 13 | File05 earned completion and enrolled course state; Services | **M05: completion enrollment SELECT DB failure silently returned without reconciliation** |
| 14 | File05 previously revoked completion and certification source truth; Services | **M06: failed award history read could proceed to upsert snapshot and disturb historical version** |
| 15 | File19 inbox event-consumer deduplication and durable delivery; Events | **M02: failed event inbox SELECT could reexecute previously processed event handler** |
| 16 | Future18 mastery evidence and learner competency version; Future18 | **M03: failed mastery prior-row read could be treated as absent and take new INSERT path** |
| 17 | Future18 spaced reviews and source-based educational recommendations; Future18 | **M04: failed scheduled-review due-date read could be replaced by a recomputed due date** |
| 18 | File16 Socratic tutor and File17 community context; Future18/Value | Source-only education contracts found; AI/messaging partners unverified |
| 19 | File24 WordPress privacy exporter, erasure, consent and legal hold; Privacy/Future18 | Historical consent revival already blocked; governed renewal still pending; real WordPress privacy workflows untested |
| 20 | Core/aux/Future18 DB versions, File19 queues, migration and rollback; Database/Operations/Idempotency | Repository schema and maintenance code found; live deployed build, migration state and transactional integration unknown |

**Outcome:** 20 scoped inspections; **7 fresh confirmed source defect classes M01–M07**, covering eight focus points (M07 in one focus, M05/06 in two). Other inspected topics found no new *proven* source omission in the inspected route. This does not establish full functional completeness.

## 3. Root-cause corrections, applied after all reviews

**M01 — Assessment replay:** `LSCH_Services::start_assessment` now validates the initial attempt lookup outcome and its WordPress DB error before allocating a new attempt; 503 `lsch_assessment_replay_read_failed` requires retry with the *same* idempotency key, avoiding spurious duplicated assessed attempts.

**M02 — File19 consumer inbox:** `LSCH_Events::consume` checks failed inbox history read **before** insert/update/handler invocation. An unavailable prior event record yields false, bounded reliability audit and named lock release rather than executing possibly already-processed events a second time.

**M03 — Future18 mastery history:** `LSCH_Future18::record_mastery_evidence` fails with `lsch_future18_mastery_history_read_failed` on failed original mastery SELECT instead of silently treating the prior evidence and score as nonexistent.

**M04 — Existing spaced-review schedule:** When scheduling a competency review, failed due-date read yields `lsch_future18_mastery_schedule_read_failed` rather than overwriting the previously governed review schedule with a newly computed date.

**M05 — Completion enrollment ledger:** `LSCH_Services::recalculate_completion` no longer silently finishes as if no active enrollment existed after a failed enrollment DB SELECT; returns `lsch_completion_enrollment_read_failed` 503.

**M06 — Completion/revocation history:** The same completion process checks whether the current course-version award/revocation history SELECT actually succeeded. Failed read yields `lsch_completion_history_read_failed` and **does not issue** an `INSERT ... ON DUPLICATE KEY UPDATE` that could mutate a prior revoked award snapshot/version.

**M07 — Submission review/appeal:** `grade_submission` and `appeal_submission` now report explicit 503 `lsch_grade_submission_read_failed` / `lsch_appeal_submission_read_failed` on failed academic row reads, preserving real conflict/authorization decisions for verified rows only.

No SQL schema changes, cross-owner write paths, production package installation, merge, or data migration was attempted. Remaining multi-write **atomic state+outbox** and real-MySQL concurrency require explicit staging proof before asserting production-grade exactly-once outcomes.

## 4. Runnable source regression evidence

- Extended `tests/ninth-private-note-assessment-read.php` with failed original idempotent assessment attempt lookup and zero unintended writes.
- Extended `tests/current-competency-evidence.php` to force enrollment and award-history SQL errors during fully satisfied course-completion evidence; asserts zero mutations.
- Added `tests/thirteenth-inbox-consumer-db.php`: failed inbox lookup cannot invoke the event handler, audit and named-lock release; healthy processed-event replay returns true without duplicate handler calls.
- Added `tests/thirteenth-mastery-replay-db.php`: failed prior mastery and existing review due date cannot insert/update competency evidence.
- Added `tests/thirteenth-assessor-appeal-db.php`: failed submission row reads cannot update grading or appeals.
- All three new suites included in required `tests/source-invariants.sh`, alongside previous File05 architecture/security and PHP 7.4/8.3/package controls.
- CI source integrity initially failed for an isolated missing WordPress `DAY_IN_SECONDS` constant in the new Future18 test fixture. Fixed it in test only. After correction, **code-level exact SHA `2a7b54589bc67099e07d29186e7129a894f57815` passed all four jobs** in GitHub workflow run **38256756472**. After this report and documentation commits, **re-check all four jobs on exact final documentation HEAD**.

## 5. Remaining work and mandatory live-state registry

The File05 v1.1 plan and central v3.0 remain the specifications, **not** evidence of execution. Actual source-vs-deployment parity has not been shown. Real acceptance must freeze File05 active ZIP/source checksum, version and plugin dependencies, actual WordPress runtime, `wp_options`, core/aux/Future18 database versions/tables/rows, migration state and logs. Run real WordPress staging tests with File00/06/09/12/16/17/19/20/22/24/25/26 installed and confirm both event-consumer deduplication and database/outbox atomicity under failures and replay. Then backup/restore, rollback, approved deploy, **live retest**, checksum parity. Never label a live incident resolved based only on GitHub green tests.

**Open governed feature:** full append-only patient-case re-consent after withdrawal still needs Founder-approved record schema/history, signed evidence, File24 retention, migration/rollback and actual staging workflow. It must not be fabricated as implemented.

| Reality dimension | Actual evidence |
|---|---|
| Frozen exact repository review HEAD | `de8ae9a87a743f8ea0c3d5d6b38e8dcb80a3732f` |
| Final repository PR HEAD / QA | To be confirmed after final docs updates |
| Repository main HEAD at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Draft PR #4 | OPEN / DRAFT / UNMERGED |
| Declared plugin source version | `4.0.0` |
| Source schema expectations | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed version and package checksum | **UNVERIFIED** |
| Actual live DB/schema version/tables/rows | **UNVERIFIED** |
| Migration state | **UNVERIFIED** |
| Live/staging verification | **NOT PERFORMED** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
