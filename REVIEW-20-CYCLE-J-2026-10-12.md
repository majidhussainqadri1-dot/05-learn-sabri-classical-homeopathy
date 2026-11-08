# File 05 — Tenth New Twenty-Focus Source Audit — Cycle J (2026-10-12)

## Frozen evidence and scope
- Frozen review source: Draft PR #4 HEAD 9571a1ddee745148dd0debf0950038d146a8496c, branch codex/file-05-complete-v3.0.0; repository main e2667d69d43d655e3d86b0a358dec5f28b61dd96. Main, PR, staging and live are separate states.
- Fetched all 19 native PHP class files, 414,287 characters, at frozen HEAD and completed twenty different source-focused checks before modifying the branch. The twenty checks are NOT twenty real WordPress end-to-end runs.
- Exact attached specification basis: 05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx, notably F05-FR-001/005/006/007/008/010/011, F05-NFR-001/002/003/006/007/008, tables 6–20, canonical owner and QA laws. Governing basis: Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026 [Version 3.0], including source versus staged versus live states, central free education, owner boundaries, privacy legal hold, and test/rollback laws. Related File06/12/16/17/19/20/22/24/25/26 contracts were checked from File05 adapters; actual foreign deployed plugin packages were not verified.

## Twenty focus checks (fresh)
| # | File05 source focus and governing/cross-file relationship | Result at frozen HEAD |
|---:|---|---|
| 1 | File05 four-level curriculum, sixteen topics and eight founder catalog seed works; class-lsch-content.php | Native ownership and vocab methods found; actual curricular content acceptance unverified |
| 2 | Native WordPress public query and File26 discovery policy; class-lsch-content.php | Visibility filters found; real search index acceptance pending |
| 3 | Central no-premium, free education and governed account access; class-lsch-policy.php | Policy source present; runtime version parity unknown |
| 4 | File00 approved identity, minor/guardian and claim bridge; class-lsch-dependencies.php | Native resolver source found; actual File00 plugin unverified |
| 5 | F05-FR-005 prerequisite completion/grandfather exception; class-lsch-policy.php | **J01 confirmed:** failed ledger read could be mistaken for missing completion and eligible grandfather grant |
| 6 | Enrollment and canonical post/terms-version checks; class-lsch-services.php | Existing strict enrollment logic found |
| 7 | F05-FR-006 assessment/assignment evidence-derived lesson progress; class-lsch-services.php | **J02 confirmed:** false/null MAX(score) cast to zero, a false academic evidence value |
| 8 | Previously corrected invalid required components; class-lsch-services.php | Required component source rejection present |
| 9 | F05-FR-008 question blueprint item/answer grading; class-lsch-services.php | **J03 confirmed:** mixed valid/malformed questions silently skipped and denominator shrank |
| 10 | File12 governed assignment attachments and assessor workflow; class-lsch-services.php | Foreign owner verification hook found, real integration pending |
| 11 | File19 outbox delivery and transaction buffer; class-lsch-events.php | Source outbox/transaction buffer found; real consumer and atomic E2E pending |
| 12 | File22 corrections, versioned reviewer flow, consent archive; class-lsch-state.php | Source workflow present; staging pending |
| 13 | File20/25 private dashboard and File05 record export/REST; class-lsch-services.php, frontend/rest/value.php | **J04 confirmed:** failed private get_results could enter arrays/UI/projection as a success; REST might attempt response-header method on WP_Error |
| 14 | File16 educational AI / File17 community guest-safe context; class-lsch-value.php | Canonical owner projection source present |
| 15 | File24 legal hold for Future18 learner records; class-lsch-future18.php | **J05 confirmed:** mastery/review/pathways/portfolio deleted before checking legal hold |
| 16 | File26 owned learning saved-search deletion; class-lsch-state.php | **J06 confirmed:** failed SQL DELETE returned misleading 404 |
| 17 | File19/26 aggregate learning value counts; class-lsch-value.php | **J07 confirmed:** failed aggregate DB query serialized as a successful metric payload |
| 18 | File00/19/24 assessment REST idempotency and replay; class-lsch-idempotency.php | Gate and replay code found |
| 19 | File05 core, auxiliary and Future18 schemas, version migration and rollback; class-lsch-database.php | Repository install/migration source present; DB actual state unknown |
| 20 | File24 assurance, privacy exporters, daily maintenance; class-lsch-privacy.php | Source routines found; actual WP data-rights staging acceptance pending |

**Evidence count:** 20 different focused source inspections; 7 new confirmed defect classes J01/J02/J03/J04/J05/J06/J07 discovered in rounds 5/7/9/13/15/16/17. Thirteen inspections did not identify a further new proven defect; this does not prove full feature completeness.

## Corrected only after the source inspection batch
- J01: LSCH_Policy::validate_prerequisites checks false DB return and null plus actual wpdb last_error before considering any grandfather exception. Fail 503 instead of allowing a false prerequisite waiver during DB failure.
- J02: LSCH_Services::progress validates each required assessment or assignment MAX(score) is a numeric, verified DB result before deriving percent or writing progress. Null/false/invalid results return 503, not zero/phantom academic credit.
- J03: LSCH_Services::submit_assessment validates the complete numbered question blueprint (nonempty bounded array; each item with scalar, nonempty answer key; safe explanation) before calculating scores. Invalid/missing question entries no longer vanish from denominator; return 409 with no grade write.
- J04: LSCH_Services::dashboard rejects any failed one of seven private data queries; LSCH_REST::dashboard checks WP_Error before writing headers; LSCH_Frontend::dashboard shows a safe unavailable state; LSCH_Value::learning_record_export propagates errors instead of embedding one in a successful export. Healthy truly empty datasets remain valid.
- J05: LSCH_Future18::privacy_erase checks File24 legal-hold before ALL Future18 user-owned deletions; legal hold keeps mastery, review, pathway, portfolio, practice, CPD, impacts and mentorship evidence. Existing nonhold privacy redaction remains in place; explicit retrospective retention/migration governance is not fabricated.
- J06: LSCH_State::rest_delete_saved_search differentiates DB failure (503), truly absent/not owned (404), successful delete (204).
- J07: LSCH_Value::rest_value_summary validates results as array; false/null counts return 503, not a misleading metrics response.

## New/expanded executable regressions
- Extended tests/prerequisite-integrity.php: valid explicitly approved grandfather exception versus failed ledger (false and null+error) denying it.
- Extended tests/learning-requirement-failclosed.php: failed assessment and assignment scores cannot write progress, on top of previous invalid configuration and attempt-limit tests.
- Extended tests/ninth-private-note-assessment-read.php: malformed mixed quiz blueprints do not write academic grades.
- Extended tests/future18-privacy-contract.php: legal hold produces zero DELETE calls to all Future18 owned tables.
- Extended tests/saved-search-read-failure.php: DB delete failure vs legitimate not-found and success.
- New tests/tenth-dashboard-projection-db.php: seven private query failures propagate through services, REST, frontend and export; aggregate event DB failure does not manufacture statistics.
- All tests registered under mandatory tests/source-invariants.sh, with original PHP 7.4/8.3 syntax and reproducible package jobs unchanged.
- CI fixture feedback caught a missing absint stub, a missing assignment mock table, and an incorrect expectation for an integer-keyed JSON object. Those fixtures were corrected in source-control. All named tests must be run at the final exact document HEAD before claiming green.

## Still outstanding — not marked completed
- Append-only documented patient re-consent renewal with dated evidence/consent withdrawal history, approved schema/migration/reversibility, File24 retention and real-role UI: NOT implemented by this review.
- Full File05 completion/outbox atomicity, database concurrency, true MySQL rollback, WordPress privacy rights processing, real File00/06/12/16/17/19/20/22/24/25/26 owner integration: require staged acceptance and proof beyond this source-level review.
- Current staged/live deployed package checksum and active plugin files, wp_options, installed core/aux/Future18 schema versions and rows, migration progress/errors, logs, owner adapters, backup/restore, and exact repository-to-deployed parity must be captured prior to live incident diagnosis or deploy.
- No merge, live deploy, staging restore or live re-test was undertaken.

## Mandatory separate reality registry
| Dimension | Status |
|---|---|
| Frozen audited repository PR HEAD | 9571a1ddee745148dd0debf0950038d146a8496c |
| Latest source QA branch HEAD / CI | See final exact PR HEAD and matched workflow after documentation commit |
| GitHub main HEAD at freeze | e2667d69d43d655e3d86b0a358dec5f28b61dd96 |
| PR #4 | OPEN / DRAFT / UNMERGED |
| Source plugin | 4.0.0 |
| Expected source schema | Core 18 / auxiliary 3 / Future18 2 |
| Exact deployed plugin version / artifact checksum | UNVERIFIED |
| Live DB versions / actual schema / rows | UNVERIFIED |
| Migration state | UNVERIFIED |
| Staging/live verification | NOT EXECUTED |

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
