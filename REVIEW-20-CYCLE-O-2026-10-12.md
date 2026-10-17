# File 05 — Fifteenth Fresh Twenty-Focus Source Audit — Cycle O (2026-10-12)

## 1. Live-first evidence freeze, immutable repository scope and plans

- Source audit **frozen at exact Draft PR #4 HEAD `cf71b23db7c5287d7a669c1d1650a65a723eb16e`**. At freeze GitHub main was `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. PR `codex/file-05-complete-v3.0.0` is Draft, open and unmerged. **GitHub is repository truth only, not staging or live truth.**
- Read all **19 native File05 PHP class sources** (426,950 source characters) from that exact HEAD. Completed **20 separately scoped source inspections** before altering code. Twenty focus scopes are not 20 complete manual WordPress executions, and no claim of full implementation coverage follows from a source scan.
- Actual documents read: `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx` and `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`. The plans separate specified, coded, packaged, automated QA, staging, live and operational facts; assign File05 ownership of education, mastery, spaced review and *simulated* clinical-learning practice.
- Cross-file source/plan boundary: File06 canonical knowledge and change/version provenance; File08 owner of real clinics, patient, appointment and clinical truth (File05 simulations are **not** patient records); File12 approved reading assets and file rights; File00 membership and identity; File09 doctor verification; File16 education-only AI; File17 communications; File19 event delivery/jobs; File20 shell; File22 editorial review; File24 security/privacy; File25 UI; File26 public-safe search. Companion **deployed** packages, endpoints and DB contracts were **not** verified. See the actual attached File08 2026-08-10 Future24 plan for its canonical clinical boundary and separate evidence-state law.
- No schema/table/owner contract change was authorized or attempted. All fixes below operate on File05's existing source error paths; actual File05 and File08 deployed parity remains unverified.

## 2. Twenty fresh focus inspections and results

| Round | File05 owned or cross-file scope inspected at frozen HEAD | Evidence/result |
|---:|---|---|
| 1 | Four curriculum levels, topic/lesson/book source registration (File05 / File06) | Native content registration and parent records present; published corpus and functional UI not validated |
| 2 | File05 learn/read public routing and File20/25 shell visual policy | REST/frontend source projections present; browser and true CDN behavior unverified |
| 3 | File00 actor/guardian claims, File09 professional verification | Dependency and capability gates present; real installed owners unverified |
| 4 | File05 strict free-tier, prerequisites, consent and visibility policies | Existing policy checks present; no new source defect proven here |
| 5 | File12 PDF owner links/lesson-related references | External verified reference callback found; real File12 response/version unknown |
| 6 | File08 real patient/clinic domain versus File05 simulation/case education | Distinct source owner domains maintained; clinical data integration remains staged-only |
| 7 | File05 enrollment state, course conditions and lesson progress | Prior fail-closed checks found; real DB concurrency still pending |
| 8 | Current assessments, idempotent academic attempts and scoring | Prior source tests and guards found; real WordPress grading unverified |
| 9 | Assignment assessor, appeal, versioning and File09 clearance | Source object-aware policies found; no new defect proven |
| 10 | File05 consent history, File24 retention and native WordPress privacy | Source safeguards present; complete governed append-only patient re-consent **pending** |
| 11 | Future18 competency mastery and source-based evidence (File16/06) | **O01** review-item helper read/insert errors previously ignored following mastery writes |
| 12 | Future18 spaced review action and stale version/evidence | **O02** old review-row SQL read failed but appeared as 409 version conflict |
| 13 | Future18 supervised clinical *learning practice*, File08 clinical boundary | **O03** failed practice-row read appeared as stale/invalid grading record |
| 14 | File06 correction event and File19 job's eligible learner page scan | **O04** failed `get_col` could enter `foreach` and never correctly retry; added array validation |
| 15 | Future18 File06 knowledge-change impact writes and review-item creation | **O04** failed impact INSERT or targeted review lookup/INSERT could be silently followed by false success event |
| 16 | Future18 File06 restudy/impact acknowledgement eligibility | **O05** failed pending-impact row read appeared as conflict; failed completed-restudy SQL query appeared as unfulfilled requirement |
| 17 | File19 outbox/job handler retry and completion semantics | Changed owner worker `run_job` now returns false on failed evidence and no fabricated completion event; true provider-level delivery still unverified |
| 18 | File22 correction governance, File24 legal hold and learner privacy | Prior source protections found; native WP privacy execution still pending |
| 19 | File26 public search freshness, noindex/low-data and source trust | Source projection boundaries found; real index invalidation and cache not tested |
| 20 | Core/aux/Future18 migrations, package, rollback/DR, security and source CI | Source schemas, test runner, reproducible packaging found; deployed version/real migration/restore and entire release acceptance unverified |

**20/20 source-focus inspections completed.** Five independent defect classes **O01–O05**, involving several failure subpaths, were corrected. In the other focus areas no further **proven** defect was found on the inspected routes; this is not an assertion that the entire File05 master specification is fully coded.

## 3. Root causes and source code corrections (performed after inspection)

**O01 — Mastery review dependency was unverified.** `LSCH_Future18::record_mastery_evidence()` previously ignored `ensure_competency_review_item()` read and INSERT results after a mastery write, then could publish an apparently completed mastery event. It now requires a confirmed existing or newly inserted review item; on uncertainty returns `lsch_future18_mastery_review_unverified` 503 and **does not publish** `LearningMasteryUpdated.v1`. The mastery row may already have been persisted: caller must reconcile prior evidence before replay. The helper explicitly returns true for an existing item and checks failed SQL reads and INSERTs.

**O02 — Review-grade previous row SQL outage obscured.** `record_review_result()` differentiates a failed prior review SELECT from genuine not-found/version mismatch, using `lsch_future18_review_history_read_failed` / 503; makes no new change on failed read.

**O03 — Educational practice grade previous row SQL outage obscured.** `grade_practice()` differentiates failed prior practice SELECT from real state/scope/version conflict, using `lsch_future18_practice_history_read_failed` / 503; the assessor cannot grade absent verified academic source evidence. This is File05 simulation, not File08 clinical patient-chart authority.

**O04 — File06 learning-correction propagation could falsely complete on DB failure.** In `run_job(future18_impact_batch)`, invalid/false `get_col` now emits bounded reliability audit and returns false for File19 retry. `INSERT IGNORE` impact writes are checked against false, and required targeted-review creation must succeed (including its prior SELECT). Any failure yields false with audit and **does not publish** `LearningRestudyRequired.v1` as success. Previously a failed learner scan could trigger warnings and a false empty set; write/lookup failures could silently pass. On retry, owner unique keys/ignore allow replay of partially completed bounded batches; actual MySQL restore/atomic consistency still requires staging proof.

**O05 — Inaccessible knowledge-impact/restudy evidence was misleading.** `resolve_impact()` explicitly returns `lsch_future18_impact_history_read_failed` or `lsch_future18_impact_review_read_failed` 503 when the relevant DB query failed; only a truly absent or unqualified state receives a 409. This avoids falsely blaming a learner for not completing required re-study during an outage.

## 4. Source regression execution

- New `tests/fifteenth-future18-impact-batch.php`: failed user enumeration, failed impact INSERT, failed existing targeted-review query, failed review INSERT: all retriable false, no false summary event; verified healthy batch succeeds. Compares File05 / File06 change evidence and File19 job status.
- New `tests/fifteenth-future18-evidence-reads.php`: prior spaced-review, scored simulated-practice, pending impact and re-study-completion query DB outages return typed errors; confirmed missing re-study remains a real 409. Verifies no mastery-completed event when scheduled review is unverified.
- Both suites registered in mandatory `tests/source-invariants.sh`. Initial CI found missing `LSCH_Policy::sanitize_json` test-double stub and an incorrect mock Impact table name; **test fixtures** were corrected. Real PHP source behavior was not waived.
- Four exact-head `source-integrity`, `php-syntax (7.4)`, `php-syntax (8.3)`, `reproducible-package` jobs require re-check **on the final post-documentation HEAD**. Passing PHP-only source stubs and reproducible build are not real WordPress/MySQL+File19 owner integration.

## 5. Unresolved governing requirements and hard deployment gates

- **Full append-only, governed patient consent re-grant after withdrawal** is still incomplete. The existing source prevents unauthorized revival but does not provide Founder-approved renewal schema, dated proof, case/publication-level permissions, File24 retention/legal hold, migration rollback or staging patient-role journeys. Must not invent a new clinical owner schema while File08 and File24 contracts are unverified.
- **Partial mastery write before review-item failure** and **partial knowledge-change batch write before failure** remain possible by construction; current correction prevents false success/events and makes retries available, but **strict transaction/outbox atomicity and compensation are NOT demonstrated**. A real MySQL WordPress staging exercise is required before this is production-complete.
- File05, File00, 06, 08, 09, 12, 16, 17, 19, 20, 22, 24, 25, 26 true current installed package/version/interface parity, patient case separation, File06 index invalidation, staging restoration, WordPress privacy callbacks and live smoke tests are unknown.
- Exact deployed files/package/checksum, `wp_options`, DB core/aux/Future18 actual schemas/versions/tables/rows, migration state, logs, active dependencies and deployed parity must be frozen **before any actual live-incident root cause or patch**. No live/staging access was used here; no merge, deployment or live re-test done.

## 6. Mandatory separate reality report

| Dimension | Evidenced status |
|---|---|
| Audited source PR HEAD | `cf71b23db7c5287d7a669c1d1650a65a723eb16e` |
| Amended source+documentation PR HEAD | See final verified GitHub run |
| Repository `main` at review freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR #4 | OPEN, DRAFT, UNMERGED |
| Declared source plugin version | 4.0.0 |
| Source schema expectations | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed version/package checksum | **UNVERIFIED** |
| Live DB version/schema/rows | **UNVERIFIED** |
| Migration state | **UNVERIFIED** |
| Staging/live verification | **NOT PERFORMED** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
