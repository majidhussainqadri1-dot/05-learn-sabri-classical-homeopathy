# File 05 — Fifth 40-Point Focused Source and Governing-Plan Review
Date: 2026-10-16

## Reality freeze and evidentiary method
- Frozen reviewed PR#4 HEAD: `d90b5b3c6a1ee49a330f22d76ed48f0957d1345e`, branch `codex/file-05-complete-v3.0.0`; `main` remains `e2667d69d43d655e3d86b0a358dec5f28b61dd96`.
- Project authority compared: attached File05 v1.1/Future18 encyclopedic master plan (F05-FR-001…018, F05-NFR-001…010), consolidated Three Central Plans governing v3.0, and companion File06/08/09/12/25 plans; cross-owner File00/15/16/17/19/20/21/22/24/26 contracts from `CONTRACTS.md` and repository traceability.
- Inspected 19 PHP implementation classes (~440 KB) through exact-GitHub-HEAD reads, source call paths and CI test fixtures.
- This is **40 distinct scoped source reviews**, not 40 independent exhaustive whole-project audits or 40 installed WordPress E2E acceptance tests. A symbol found means code exists, not that staging/live integration passes.
- Source-presence/targeted guard audit: **37/40** had expected evidence; 3 had clear publication fail-open defects. Detailed examination of the same patient-case publishing path proved a fourth defect in mandatory-case-tag persistence. All four are addressed below.

## Forty reviewed areas
| # | Requirement / owner | Source/contract matter | Evidence and disposition |
|---:|---|---|---|
| 01 | F05-FR-001 | Four education levels — `LSCH_Content::levels` | Source-defined; staging curriculum seed verification outstanding |
| 02 | F05-FR-001 | Sixteen approved learning topics — `LSCH_Content::topics` | Source-defined; actual term counts require DB |
| 03 | F05-FR-002 | Eight Founder book placeholders — `LSCH_Content::seed_founder_book_slots` | Draft slots only; authentic titles/chapters not populated/verified |
| 04 | F05-FR-003 | Course/program content types — `LSCH_Content::register` | Source registration present |
| 05 | F05-FR-004 | Editorial governance form — `LSCH_Admin::save_governance_meta` | Nonce/allowed input gates present |
| 06 | F05-FR-004 | Versioned save and publish — `LSCH_Plugin::lesson_saved` | **D01 fixed**: reject unpersisted version rather than publishing |
| 07 | F05-FR-004; File08/24 | Patient case taxonomy and consent — `LSCH_Plugin::lesson_saved` | **D02 fixed**: taxonomy WP_Error/non-array cannot bypass consent gate; case tag check separately fixed as D04 |
| 08 | F05-FR-004; File22 | Independent reviewer evidence — `LSCH_Plugin::lesson_saved` | **D03 fixed**: rejected reviewer meta write cannot pass publication |
| 09 | F05-FR-005 | Enrollment prerequisites — `LSCH_Services::enroll` | Prerequisite revalidation present |
| 10 | F05-FR-005 | Enrollment postwrite confidence — `LSCH_Services::enroll` | Explicit readback error present |
| 11 | F05-FR-006 | Progress version/concurrency — `LSCH_Services::progress` | Optimistic version conflicts checked |
| 12 | F05-FR-006 | Assessment evidence used for progress — `LSCH_Services::progress` | DB read errors fail closed |
| 13 | F05-FR-007 | Bookmark ownership — `LSCH_Services::toggle_bookmark` | Owner-scoped state path present |
| 14 | F05-FR-007; File24 | Encrypted private learning notes — `LSCH_Policy::encrypt_note` | Source crypto path; actual keys/backup not verified |
| 15 | F05-FR-008 | Assessment initiation — `LSCH_Services::start_assessment` | Source path present |
| 16 | F05-FR-008 | Assessment submission and versions — `LSCH_Services::submit_assessment` | Source path present |
| 17 | F05-FR-009 | Grading/assessor scope — `LSCH_Services::grade_submission` | Source path present |
| 18 | F05-FR-009 | Assignment appeals — `LSCH_Services::appeal_submission` | Source path present |
| 19 | F05-FR-010 | Private learner dashboard — `LSCH_Services::dashboard` | Owner-scoped state path present |
| 20 | F05-FR-011; File26 | Learning public catalog API — `LSCH_REST::catalog` | Source route exists; public cache/index staging pending |
| 21 | F05-FR-012; Files06/12 | Related learning references — `LSCH_Services::upsert_related_link` | Typed references; foreign truth not copied |
| 22 | F05-FR-013; File17 | Study/community context — `LSCH_Plugin::community_context_provider` | Context bridge only |
| 23 | F05-FR-014 | Scoped academic staff — `LSCH_Capabilities::active_staff_rows` | Active/cleared-role filter present |
| 24 | F05-FR-015 | Completion revocation — `LSCH_Services::recalculate_completion` | Versioned revoked history guard present |
| 25 | F05-FR-016 | Certificates and identity claims — `LSCH_Services::certificate_readiness` | Source readiness path; external accreditation unverified |
| 26 | F05-FR-017; File00 | Single free tier policy — `LSCH_Dependencies::governing_policy_ready` | Central contract guard present |
| 27 | F05-FR-018 | Correction provenance — `LSCH_State::apply_correction` | Previously corrected note write and request transaction guard remains |
| 28 | F05-NFR-001; File00 | Canonical membership claims — `LSCH_Dependencies::claims` | No local claim override path |
| 29 | F05-NFR-002; File24 | Privacy export/erasure — `LSCH_Privacy::export/erase` | Source paths; legal hold/real data test outstanding |
| 30 | F05-NFR-003 | REST transaction/rollback — `LSCH_Idempotency::after_callbacks` | Source rollback present |
| 31 | F05-NFR-003; File19 | Durable outbox worker retries — `LSCH_Operations::process_outbox` | Source retry/dead-letter path; delivery untested |
| 32 | F05-NFR-004 | Completion query scope limit — `LSCH_Services::recalculate_completion` | 500-lesson bound |
| 33 | F05-NFR-005 | Private surface cache suppression — `LSCH_Frontend::send_private_headers` | Source headers; browser/CDN validation outstanding |
| 34 | F05-NFR-006 | Operational system diagnostics — `LSCH_Operations::system_check` | Source path present |
| 35 | F05-NFR-007 | DB schema migration — `LSCH_Database::maybe_upgrade` | Source path present; real DB migration not run |
| 36 | F05-NFR-008 | Job recovery — `LSCH_Operations::process_jobs` | Source path present; real outbox and jobs unverified |
| 37 | F05-NFR-009 | Activation and compatibility — `LSCH_Activator::activate_site` | Source path present; PHP lint is not WP staging |
| 38 | F05-NFR-010; File25 | Localization/RTL — `File05 textdomain and File20/25 shell` | Code contract present; RTL/browser acceptance pending |
| 39 | Files06/12/15/16 | Knowledge/PDF/repertory/AI owner boundaries — `LSCH_Value::download_provider, LSCH_Future18::socratic_tutor` | Adapters present; installed provider parity unverified |
| 40 | Files19/20/21/22/25/26 | Notifications/shell/composer/feed/search — `LSCH_Operations and LSCH_Plugin adapters` | No foreign owner overwrite found in scoped checks; actual contract acceptance pending |

## Four new confirmed source defects and corrections

**C5-D01 — Missing publication refusal when version metadata cannot be persisted.**
`LSCH_Plugin::lesson_saved` previously ignored the returned zero from `LSCH_Content::bump_version`, so an updated lesson could remain published with stale revision evidence. New guard checks the result; it blocks publication on a failed version update. Corresponds to F05-FR-004/018 and NFR-003.

**C5-D02 — Consent check bypassed by taxonomy read failure.**
`LSCH_Plugin::lesson_saved` previously entered the patient-case consent branch only when `! is_wp_error( $topics )`. A `WP_Error` or unexpected taxonomy response therefore bypassed the branch entirely. New guard blocks publication if taxonomy lookup fails or does not return a list. Corresponds to F05-FR-004, File08 clinical-owner separation, File24 patient privacy.

**C5-D03 — Unverified independent review metadata allowed publication.**
`LSCH_Plugin::lesson_saved` validated reviewer ID but ignored whether `_lsch_reviewer` evidence actually persisted. New guard accepts either successful meta write or a readback of the identical value; otherwise publication is blocked. F05-FR-004, reviewer governance.

**C5-D04 — Mandatory ‘کامیاب کیس’ tag write ignored.**
`wp_set_post_tags` failures were ignored in case-learning lessons even though the governing plan requires the tag for relevant case posts. New guard checks WP_Error and post-save `has_term` before publication may proceed.

### Regression and exact-source testing
- Updated `05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php` at commit `7a3abacf92de745886fce8fb4bd84619043a8072`.
- Added `tests/lesson-publication-evidence-failclosed.php` with injected failed version, reviewer meta, taxonomy WP_Error, taxonomy null, withdrawn patient consent, failed case tag and valid full-evidence success. Registered test in `tests/source-invariants.sh`.
- Corrected code plus regression suite SHA `91f419c1c1453df759e8224863740dcc773a82d5`.
- GitHub Actions `File 05 Current Plans Integrity`, Run `38925624668`, on that **exact SHA**: **success**, all four jobs passed (PHP 7.4, PHP 8.3, source-integrity, reproducible-package).
- The report and following status commits have new SHAs; check their CI separately before claiming exact-HEAD success.

## Still missing / not yet verified
1. **Design-dependent implementation:** append-only patient-case new/re-consent after withdrawal, including previously revoked history and File08/File24 privacy data model/migration/rollback, remains explicitly incomplete.
2. **Design-dependent implementation:** durable append-only mentor feedback history beyond 100 records remains incomplete; existing capacity refusal preserves prior evidence, not full archive functionality.
3. **Educational content:** eight Founder book draft slots do not establish complete real bibliographic works, chapters, lessons or verified source material.
4. **Atomic publication on actual WordPress:** the `block_publication` helper calls `wp_update_post` but does not verify the resulting persisted status; a real WP write failure can still leave a public post. Source guards above do not prove absolute fail-closed protection when the WordPress persistence layer itself fails. A governed pre-publication workflow / transactional staging redesign plus MySQL and cache tests remains necessary; do not describe this as resolved.
5. **Other gates:** actual installed plugin/provider checksums and contract parity, File00/08/09/12/16/17/19/20/21/22/24/25/26 real integration, database migrations/rollback/backup restore, WordPress hooks’ ordering, RTL/a11y, CDN/SEO public revocation, production deployment and live retest not yet established. No source-derived inference can substitute for live evidence.

## Mandatory incident reality register
| Reality | Result |
|---|---|
| Reviewed repository PR HEAD | `d90b5b3c6a1ee49a330f22d76ed48f0957d1345e` |
| Source/test correction SHA before this report | `91f419c1c1453df759e8224863740dcc773a82d5` |
| Main HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Source-declared runtime | `4.0.0` |
| Source-expected DB schema | Core 18, auxiliary 3, Future18 2 |
| Deployed Version | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
