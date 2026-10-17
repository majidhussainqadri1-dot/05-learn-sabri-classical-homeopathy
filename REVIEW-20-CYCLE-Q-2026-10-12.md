# File 05 — Seventeenth Fresh Twenty-Focus Source Audit — Cycle Q (2026-10-12)

## Frozen sources, document authority and evidence limits
- Exact independently reviewed source PR #4 HEAD: `5d912ca81107c7d3b7ff615b4e6a37c01135577c`. Draft branch `codex/file-05-complete-v3.0.0`, actual declared source plugin version `4.0.0`; GitHub main/base HEAD `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. PR open/unmerged, **not installed by GitHub review**.
- Read all **19 native PHP class sources** from that exact frozen source revision, including endpoints, authorizations, schema, event/outbox, jobs, operations and Future18. Reviewed the actual provided `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx`, and consolidated `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`. Relevant plan laws: canonical owner only, File19 event/queue status with bounded retries and dead-letter, 1:1 source evidence to tests/rollbacks, authorization at object scope, privacy, staging approval and actual repository/deployment separation.
- Cross-file master plans actually consulted from provided attachments: File06 encyclopedia, File08 worldwide clinics, File09 doctor verification, File12 PDF reading; owner bridges to File00 authentication, File16 educational AI, File17 community, File19 durable delivery, File20 application shell, File22 corrections, File24 privacy, File25 public UX, File26 public search were inspected only at the **File05 source interface**. Actual packages/consumer deployments and live DB migration remain **unverified**.
- This document records **20 separate focus inspections** at a frozen source state. It is NOT 20 complete repeated audit executions, NOT real WordPress/MySQL test evidence and NOT proof all planned features are coded.

## Twenty focus inspections, before corrections
| # | Source focus / linked plan | Result |
|---:|---|---|
| 1 | Education levels, sixteen-topic curriculum, native content ownership; `LSCH_Content`, File05/06 | Owner source present; full published lesson/book corpus unverified |
| 2 | File06 Knowledge canonical change and File05 secondary learning references | Adapter source present; File06 live consumer not checked |
| 3 | File08 genuine clinical data versus File05 simulations and lesson consent | Educational isolation present in source; patient workflow E2E pending |
| 4 | File09 doctor status/mentor role; `LSCH_Capabilities` | Versioned trust-policy source present; installed contract unknown |
| 5 | File00 approval/guardian/access validity; `LSCH_Dependencies`, `LSCH_Policy` | Protected source claims present, real claims runtime unverified |
| 6 | File12 approved PDF attachment and owner-held artifact references | Source validator/interface present, partner adapter staging pending |
| 7 | File20/25 public and private student UI and caching; `LSCH_Frontend` | Source projections found, browser/RTL/accessibility acceptance missing |
| 8 | File26 discoverability and source-only public search; `LSCH_REST` | Public visibility/canonical link source found, index parity missing |
| 9 | Enrollment/prerequisite/current student permissions; `LSCH_Services` | Prior fail-closed versioned checks found; real MySQL races pending |
| 10 | Academic assessments, assignments and grader/appeal rights | Source version and score invariants present, real end-to-end tests missing |
| 11 | Certificate issuance and revocation/identity state | Prior source checks found; state/outbox transaction atomicity not proved |
| 12 | Future18 graded simulated practice, mastery and spaced reviews | Earlier source audit partial-state errors guarded; operator reconciliation and true transactions pending |
| 13 | Mentor/CPD academic evidence and File09 eligibility | Existing source checks present, live professional verifier contract unverified |
| 14 | File22 correction decision and File06 knowledge-change propagation | Source path and retryable impact jobs found; actual consumer retry still not E2E tested |
| 15 | File19 job queue initial pending/retry discovery; `LSCH_Operations::process_jobs` | Existing DB get_results failure guard found |
| 16 | **File19 claim then detailed running-job SELECT** | **Q01 new defect:** already-claimed job with unreadable row silently left running until separate stale-job sweep |
| 17 | **File19 job payload decoding/consumer input** | **Q02 new defect:** malformed JSON passed null instead of array to consumer, risking TypeError or cron termination |
| 18 | **File19 cross-module handler exception** | **Q03 new defect:** uncaught `Throwable` could abort worker rather than mark bounded retry/dead-letter |
| 19 | **File19 final completed/retry/dead SQL update** | **Q04 new defect:** terminal update failures ignored, returning apparent normal worker completion with unknown persisted status |
| 20 | Core/aux/Future18 migration, File24 legal hold, operations/rollback/CI, exact staged vs live | Repository schema source and CI exist; actual deployed DB, migration, backups, true staging/live parity unverified |

**Result:** 20 focused source areas reviewed; four newly confirmed worker reliability defect classes **Q01–Q04**, corrected after all source inspections. Sixteen other inspected scopes yielded no further *proven new defect in those inspected routes*; not a statement that full plan implementation is complete.

## Corrections (File05 owns job ledger, File19 owns delivery contract)
- **Q01 claimed row unreadable:** On successful SQL claim (pending/retry→running) but failed subsequent `SELECT`, the worker now performs a single conditional SQL recovery of that *same running row*, increments attempts, chooses retry or dead according to max_attempts, clears lock, schedules bounded retry, sets `claim_read_failed`, and emits a bounded reliability audit. If recovery SQL fails, the worker returns `lsch_job_recovery_unverified` 503; no false success assertion. Daily stale-job reconciliation remains an additional recovery guard; an actual outage can still prevent immediate requeue.
- **Q02 malformed job payload:** Decode JSON to a PHP array before dispatch. Invalid/non-array payload is never delivered to a typed File19 consumer; it receives `invalid_payload` and bounded retry/dead-letter treatment.
- **Q03 handler thrown exception:** Wrap `lsch_run_job` execution in `try/catch(Throwable)`, emit a bounded `job_handler_exception` audit and use `handler_exception` code with the existing retry/dead-letter policy. Do not log exception text because it may contain private academic/clinical material.
- **Q04 terminal status write failure:** Verify that SQL update of `completed`, `retry` or `dead` affects exactly one claimed running row. Any failure/mismatch logs `job_terminal_state_unverified` and returns `lsch_job_terminal_state_unverified` / 503 for operator reconciliation. This does not prove compensating transactions or exactly-once side effects after a failed final status persistence.

## Evidence and testing
- New `tests/seventeenth-job-worker-recovery.php`, registered in mandatory `tests/source-invariants.sh`, injects unreadable claimed row, failed recovery SQL, malformed JSON, throwing owner handler, failed terminal status write and healthy completion. Prior 16 audit cycles' regression cases remain in the same CI runner.
- Exact post-fix code/test SHA `2b7fa65b68686462f6f3cff739794604a7e1d424` passed all four GitHub CI jobs in run `38279836013` (source integrity, PHP 7.4 syntax, PHP 8.3 syntax, reproducible package). **Final post-documentation SHA must be matched to its own four-job successful workflow.**
- The negative tests simulate WordPress database and external handler behavior. They **do not replace** real MySQL locking, delivery, cross-plugin consumer, concurrency, backup/restore, migration or staging acceptance tests.

## Outstanding canonical acceptance requirements
1. Complete **governed append-only patient case re-consent** after withdrawal, including dated evidence, real role/jurisdiction state, File24 retention/legal hold, approved schema/migration/rollback and real staging. Silent consent revival remains prohibited.
2. Demonstrate true **state+outbox atomicity**, correct bounded retries/dead-letter after partial worker execution and source-versus-consumer idempotency under real WordPress/MySQL and File19 integration. Manual reconciliation may still be needed after an unverified terminal write; no impossible exactly-once guarantee is claimed.
3. Test File00/06/08/09/12/16/17/19/20/22/24/25/26 exact installed versions/contracts, academic/clinical owner separation, File26 cache/index invalidation, File12 rights, WordPress privacy exporter/eraser, accessibility/RTL, API roles and real staging backup/restore.
4. No merge/deploy/live retest or deployed code checksum parity was performed. Before diagnosing a live symptom: freeze exact plugin runtime/deployed package/checksum, actual schema versions/tables/rows, wp_options, migrations and error logs, then compare exact GitHub artifact before code diagnosis; after approved deploy retest live.

## Mandatory separate reality registry
| Dimension | Evidence |
|---|---|
| Repository audited HEAD | `5d912ca81107c7d3b7ff615b4e6a37c01135577c` |
| Tested correction HEAD | `2b7fa65b68686462f6f3cff739794604a7e1d424` |
| Final documented PR HEAD / CI | See exact final workflow after documentation commits |
| Repository main at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR #4 | OPEN / DRAFT / UNMERGED |
| Declared source plugin version | 4.0.0 |
| Source-expected schema | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed plugin version/checksum | UNVERIFIED |
| Live DB version and actual migrations | UNVERIFIED |
| Staging verification | NOT DONE |
| Live verification | NOT DONE |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
