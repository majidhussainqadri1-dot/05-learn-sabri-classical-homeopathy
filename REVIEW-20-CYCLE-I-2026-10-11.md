# File 05 — Ninth Fresh Twenty-Focus Source Audit — Cycle I (2026-10-11)

## Frozen source and governing evidence

- **Immutable review baseline:** Draft PR #4 HEAD `b82f044b25b0de376f12eb0dfc18254f74cdedb5`. `main` at freeze `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. Branch: `codex/file-05-complete-v3.0.0`; source plugin declared version 4.0.0. **None of these are evidence of deployment.**
- All **19 native File05 PHP class files** fetched from GitHub at the immutable baseline; method map and failure paths studied (native curriculum, REST, state, analytics, privacy, source adapters, migration, Future18).
- Attached original `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx` consulted; relevant clauses include private progress/notes, course completion and certificate readiness, request-time reconciling, outbox reliability, data rights, migration, backup/restore and stage-only acceptance.
- Attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx` consulted; it strictly separates planned/source-tested/staging/live states and requires owner/permissions/retention/migration/test/rollback traceability. Attached File06 knowledge and File12 secure PDF plans provide foreign owner boundaries; other File00/16/17/19/20/22/24/25/26 integration contracts were source-inspected within File05, but their current deployed implementations were **not** verified.
- **20 checks below are distinct scoped source inspections, not 20 full WordPress end-to-end runs or 20 complete re-executions of all requirements.** All code fixes began after collecting source findings.

## All twenty fresh inspection results

| Round | Focus | Evidence / finding |
|---:|---|---|
| 1 | Curriculum File05 ownership, global free tier and roles / File00 | Native roles and claims integration found; actual File00 version pending |
| 2 | Four levels, sixteen topics and curricula | Source vocabulary seeding found; actual published content pending |
| 3 | Founder learning books and canonical File06 knowledge | Native File05 book slots found; File06 external owner not overwritten |
| 4 | Native public queries and File25/26 discovery | Public visibility guards found; live cache/index pending |
| 5 | Parent/child access and case-consent revocation / File24 | Prior ancestor and case-access protections present |
| 6 | File00 member verification and course enrollment | Enrollment checks/idempotency found; live integration pending |
| 7 | Curriculum prerequisite eligibility | Strict invalid input rejection from prior audit found |
| 8 | Private note reading / File24 encryption | **I01:** failed DB `get_row` returned empty user note indistinguishable from truly absent note |
| 9 | Assessment attempt allocation / File19 evidence | **I02:** after successful attempt write, failed post-write `get_row` returned unverified result and could falsely confirm started attempt |
| 10 | Assessment grade persistence and File19 event | **I03:** after successful graded write, failed result reread was dereferenced for AssessmentSubmitted event |
| 11 | File12 attachment validation and assessor appeal governance | Canonical attachment validation hooks, grading/appeal methods found |
| 12 | File22 editorial correction queue/reviewer authorization | **I04:** failed `get_results` entered `array_filter` for nonmanager reviewer (TypeError), or returned null as a legitimate empty queue |
| 13 | Course completion evidence / current lesson versions | **I05:** failed progress-row `get_results` was cast to empty and silently skipped completion/reconciliation |
| 14 | Certificate readiness current exam version/legal wording | Prior current-version and legal gates found |
| 15 | File16 Future18 AI learning / sensitive clinical boundaries | Source-grounded educational tutor source exists; external AI not verified |
| 16 | File17 Community / File19 outbox event delivery | Typed source hooks and outbox workers exist; remote consumer parity pending |
| 17 | File20/25 frontend private dashboard and adaptive layout | Private/noindex surfaces found; actual UI/cache pending |
| 18 | File24 privacy export, erasure, legal hold and encrypted notes | Core/Future18 source privacy lifecycles present; real WordPress rights flow pending |
| 19 | Daily DB reconciliation, File19 retries and File24 retention | **I06:** SQL cleanup/query failures not checked; last successful reconciliation timestamp could still be set |
| 20 | File26 search projections, schema, rollback and migration | Owner-scoped code found; actual installed schema/migration/live checksum unknown |

**Outcome:** 20 source inspections completed. Six confirmed defect classes I01–I06, discovered in rounds 8, 9, 10, 12, 13, 19; fourteen inspected focus areas without an additional proven source defect. Not a declaration that all other code is defect-free.

## Code corrections made only after review

**I01 — private note DB outage is not an empty note:** `LSCH_Services::get_note` checks WordPress `$wpdb->last_error` when `get_row` returns no result. A failed query now yields `lsch_note_read_failed` (503), while a genuinely missing note still yields `note:''` with version 0. This prevents misleading the learner into replacing an existing encrypted note after a temporary DB outage.

**I02 — assessment start post-write verification:** `LSCH_Services::start_assessment` verifies the reread result is an array with required saved-attempt keys. Missing data after insertion yields audited `lsch_assessment_read_failed` (503) rather than a fabricated started state. Existing lock/finally and idempotency remain.

**I03 — graded submission post-write verification:** `LSCH_Services::submit_assessment` checks reread `public_id`, `attempt_number` and `status='graded'` before publishing `AssessmentSubmitted.v1`. On uncertainty, an audit and retryable 503 instruct operator reconciliation; no false submitted event is published. A persisted attempt may still exist: user must not blindly create another attempt.

**I04 — correction list read error:** `LSCH_State::rest_corrections` explicitly rejects nonarray SQL results with `lsch_corrections_read_failed` (503) before staff authorization filters or response serialization.

**I05 — course completion evidence query:** `LSCH_Services::recalculate_completion` rejects nonarray SQL results with `lsch_completion_evidence_unverified` (503), and audits the bounded error rather than treating failed reads as “zero completed lessons.” Real current completion state must be reconciled upon retry.

**I06 — daily reconcile completion marker truth:** `LSCH_Operations::daily_reconcile` now checks every owner-local maintenance `$wpdb->query` before marking `lsch_last_reconcile` successful. It preserves all historical patient consent and withdrawal evidence. `reconcile_corrections` rejects failed queue reads and unconfirmed row update results, propagating failure. Reconcile is retriable; **no transactional atomicity or live rollback has been established** and partial maintenance writes must be audited in staging before approval.

## Executable tests and exact-source QA

- `tests/ninth-private-note-assessment-read.php`: failed/private-note read vs truly absent record; post-insert attempt reread error; post-grade reread failure and no false submitted event.
- `tests/ninth-correction-completion-db.php`: reviewer inbox DB outage, course progress-evidence outage.
- `tests/ninth-reconcile-failclosed.php`: failing direct maintenance SQL prevents `lsch_last_reconcile` marker; failing correction scan also prevents marker; valid successful run marks completion; no automatic patient consent deletion.
- All registered under mandatory `tests/source-invariants.sh`. Legacy `tests/queue-and-consent-retention.php` gained a missing WordPress `is_wp_error` test stub after first complete CI failure.
- Initial final-code run **38131186073** completed **success** on `33ebe0243125ce5a6111b32e47a8cacd787911c9`, all four jobs: `source-integrity`, `php-syntax (7.4)`, `php-syntax (8.3)`, `reproducible-package`. CI must be rechecked on **final documentation HEAD** before closing.

## Outstanding requirements and live prerequisites

- **Full patient re-consent** append-only audit/history and consent withdrawal/renewal workflow remains awaiting Founder-approved scope/data schema, migration, rollback and real patient-case staging privacy evidence; do not build an invented DB contract.
- Atomic DB completion transition/outbox and high-volume race/failure compensation need real SQL engine and WordPress integration tests. Source PHP stubs prove only tested branches.
- Live-first freeze mandatory before diagnosing any actual live symptom: active plugin/package/checksum, exact deployed files, wp_options, real core/aux/Future18 schema, rows, migration logs, runtime errors, File00/06/12/16/17/19/20/22/24/25/26 deployed contract versions, and parity.
- Staging E2E: simulate failed note query, failed attempt insert reread, failed grade reread, failed corrections list, failed earned-evidence query, failed each maintenance/correction step; replay idempotently, verify outbox/reconciliation/deletion/backup/restore and no cross-owner privacy leakage. After successful deploy, live re-test and checksum parity are required.

## Mandatory reality registry

| Truth dimension | Evidence |
|---|---|
| Frozen repository audit HEAD | `b82f044b25b0de376f12eb0dfc18254f74cdedb5` |
| Final repository HEAD & CI | See exact post-documentation workflow |
| Repository main | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` at freeze |
| PR #4 | DRAFT / OPEN / UNMERGED |
| Source plugin version | 4.0.0 |
| Source schema expectations | Core 18, Auxiliary 3, Future18 2 |
| Deployed version and checksum | **Unverified** |
| Actual live DB schema, options and rows | **Unverified** |
| Migration state | **Unverified** |
| Staging and Live verification status | **Not tested** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
