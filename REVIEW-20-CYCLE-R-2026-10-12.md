# File 05 — Eighteenth Fresh Twenty-Focus Source Audit — Cycle R (2026-10-12)

## Source truth, exact freeze and governing document review
- Exact frozen source PR #4 HEAD **`869b502662ed11fd325e2880532e69b54018699e`**, branch `codex/file-05-complete-v3.0.0`, repository `main` at freeze `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. PR **Draft / open / unmerged**. Repository SHA does NOT prove deployed state.
- Fetched the canonical GitHub tree and read the relevant source of all **19 native File05 PHP classes**, with detailed focus on `class-lsch-operations.php`, `class-lsch-future18.php`, `class-lsch-services.php`, `class-lsch-state.php`, `class-lsch-policy.php`, `class-lsch-privacy.php`, `class-lsch-events.php`, `class-lsch-rest.php`, and their existing regression tests. This is a register of **20 different focused source/requirement checks**, not twenty complete WordPress E2E executions.
- Read the supplied actual DOCX **File05 Learn Sabri Classical Homeopathy Master Plan 2026 v1.1 Future-18**, and **Sabri Three Central Plans Consolidated Governing Master Plan 2026**, plus pertinent File08 clinic and File12 PDF owner plans. Requirements include File05-owned CPD/mentorship/private learning evidence, File19 at-least-once event outbox/inbox/retry/dead-letter, transaction/outbox coupling, failed-provider/degraded truth, File24 legal/privacy protections, File00 and File09 trust, File06 original medical/encyclopedic source references, File08 real clinic owner isolation, File12 PDF rights, File16 AI provider, File17 communications, File20 shell, File22 corrections, File25 UI and File26 public search. Actual installed counterpart packages were NOT verified.

## Twenty independent source-focus review results

| # | Reviewed plan-to-code area / cross-owner boundary | Result |
|---:|---|---|
| 1 | File05 core curriculum (four levels, lessons, courses, publication), File06 canonical content | Native post type/lesson registration present; authored corpus not verified |
| 2 | File00 authentication, guardian and actor claims; File09 current doctor trust | File05 source gates exist; real counterpart runtime unverified |
| 3 | File12 governed PDF/license/attachments and File06 related learning references | Source validation contracts present; live signed delivery not tested |
| 4 | File08 real patient appointments versus File05 clinical education simulations | Source owner separation present; real clinical DB untouched/unverified |
| 5 | File20/25 public learning UI, private dashboard, responsive/RTL and cache | Source layer found; real browser, accessibility acceptance pending |
| 6 | File26 safe search, visibility, taxonomy, noindex and citation source projection | Existing source guards; counterpart index/cache not verified |
| 7 | File05 enrollment, prerequisites, course version and academic progress | Previous source guards found; live MySQL concurrency not proven |
| 8 | Assessments, assignments, CPD record source and credentials | Existing academic guards found; new CPD ID defect noted below |
| 9 | Historic patient case consent, File24 retention and revocation | Source rejects unsafe silent regrant; append-only approved re-consent still pending |
| 10 | File19 outbox queue discovery and event-item reread after named lock | **R01:** unreadable selected event silently skipped with no failure evidence |
| 11 | File19 outbox consumer JSON schema and typed payload | **R02:** non-array/broken JSON passed to provider |
| 12 | File19 event handler failures and sensitive exception data | **R03:** uncaught consumer Throwable could abort dispatcher |
| 13 | File19 terminal processed/retry/dead status and ACK truth | **R04:** terminal SQL result ignored, even after side effects |
| 14 | File05 Future18 learner-owned portfolio visibility/consent | **R05:** failed historical SELECT misreported as stale/not-found 409 |
| 15 | File05 mentorship feedback and portfolio evidence relationship | **R06:** persisted feedback with failed portfolio INSERT returned success |
| 16 | Future18 independently recorded CPD academic evidence | **R06:** persisted CPD with failed portfolio INSERT published completed CPD event |
| 17 | Future18 verified CPD and File09 qualified supervisor, mastery | **R07:** verified CPD status followed by ignored portfolio and mastery write failures and incorrect completed verification event |
| 18 | CPD REST identity and File20 learner profile/action mapping | **R08:** `record_cpd()` returned `$wpdb->insert_id` AFTER portfolio INSERT, so response could hold portfolio ID, not CPD ID |
| 19 | File16 Socratic AI, File17 community and File22 editorial correction | Source external/authoritative guards found; actual counterpart compatibility unknown |
| 20 | File19 job recovery, File24 erasure/legal hold, migrations/rollback and live parity | Prior source worker test present; state/outbox atomicity, backup/restore and staging/live unverified |

**20/20 scoped reviews performed; eight newly confirmed source defect groups R01–R08 in focused rounds 10–18.** Some groups cover multiple neighboring paths. Six source/response corrections plus subcases were committed; no claim all specification requirements are fully implemented.

## Root causes and post-review corrections
- **R01 Outbox item read failure:** `LSCH_Operations::process_outbox()` now checks `$wpdb->last_error` after selected-row lookup; on actual SQL failure emits a bounded reliability audit, returns `lsch_outbox_item_read_failed` / 503, and releases its DB named lock in the existing `finally`. A legitimate disappeared row is still skipped safely.
- **R02 Unverified event payload:** JSON must decode to an array before invoking `lsch_dispatch_event`. Malformed/scalar event data is assigned bounded `invalid_payload` retry/dead code, never delivered to the external File19 consumer.
- **R03 Uncaught event provider exception:** Consumer `Throwable` is converted to `consumer_exception`, bounded failure audit and retry/dead treatment; no private exception text is logged.
- **R04 Unverified event terminal status:** A processed/retry/dead SQL UPDATE must affect exactly one row; otherwise return `lsch_outbox_terminal_state_unverified` / 503 and audit. An event consumer may already have acted; retried side effects need a real end-to-end File19 idempotency test. **This is not a claim of exactly-once delivery.**
- **R05 Portfolio share preference privacy:** `LSCH_Future18::set_portfolio_visibility()` now distinguishes a failed prior private portfolio-row query as `lsch_future18_portfolio_visibility_read_failed` / 503 rather than a fictitious 409 conflict. Zero mutation on unknown state.
- **R06 Mentor feedback and self-recorded CPD projection:** After writing an academic record, associated portfolio INSERT must succeed or return a typed 503 partial-state error and audit, suppressing false completed CPD event. Both return operator reconciliation instructions; already-persisted records must NOT be blindly resubmitted.
- **R07 Verified CPD projection:** After changing CPD to verified, verify both portfolio and competency/mastery evidence writes. Failure yields `lsch_future18_verified_cpd_portfolio_pending` or `lsch_future18_verified_cpd_mastery_pending`, audits and no `LearningCPDVerified.v1` completed event. Partial state may already exist; real database transaction and recovery acceptance remain essential.
- **R08 CPD canonical ID integrity:** Save the CPD INSERT's `insert_id` immediately, **before** portfolio INSERT resets `$wpdb->insert_id`, then return the original CPD id in API response. No migration or owner change is needed.

## Regression suites and exact-head CI
- New `tests/eighteenth-outbox-delivery-failures.php`: File19 consumer failure injection for locked event read, invalid payload, consumer exception, rejected handler, failed terminal SQL and valid processed state. Confirms MySQL lock release and bounded errors.
- New `tests/eighteenth-cpd-portfolio-integrity.php`: private portfolio row SQL failure, persisted mentor feedback with failed projection, CPD and verified CPD missing portfolio/mastery projection and no false complete event; healthy CPD verifies original `id` even when new portfolio row uses a different insert ID.
- Both added to mandatory `tests/source-invariants.sh`. First CI showed standalone fixture missing WordPress policy JSON serializer; **test-double corrected without weakening source behavior**. Require complete source-integrity, PHP 7.4 syntax, PHP 8.3 syntax, reproducible package success on the **final exact PR HEAD after documentation changes**, not simply a prior CI SHA.

## Open governance and live acceptance blockers
1. **Patient-case historical re-consent after withdrawal** (append-only evidence, identity, proof, policy version, File24 retention/legal holds) requires Founder-approved schema/contract change, migration and rollback; no arbitrary clinical/consent table mutation made.
2. **True transactional academic write+portfolio/mastery+File19 outbox atomicity** remains unproven. This audit prevents false success but cannot roll back already-written CPD, mentor feedback or downstream event side effects. Stage real MySQL, multi-device concurrency, compensating/audit action and safe idempotent operator reconciliation.
3. Live/staging exact installed File05 and File00/06/08/09/12/16/17/19/20/22/24/25/26 package versions/checksums, WordPress options, real core/aux/Future18 tables, schema versions/rows, migration status, logs, File12 signed file access, File26 cache/search and File24 native privacy workflows are still unverified. No PR merge or deployment performed.
4. Unresolved source design questions such as ended mentorship re-assignment and actual external consumer idempotency must not be patched by inventing new business rules/schema without a dated Founder-approved change request.

## Required separation of realities

| Evidence dimension | Exact state |
|---|---|
| Frozen audited PR HEAD | `869b502662ed11fd325e2880532e69b54018699e` |
| Corrected+documented PR HEAD | Confirm final SHA and 4-job CI after docs commits |
| Repository main at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR #4 | DRAFT / OPEN / UNMERGED |
| Declared plugin source version | 4.0.0 |
| Source expected DB versions | Core 18 / Auxiliary 3 / Future18 2 |
| Exact deployed code/package/version/checksum | UNVERIFIED |
| Live DB version, actual schema/tables/rows | UNVERIFIED |
| Migration state | UNVERIFIED |
| Staging/Live Verification | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
