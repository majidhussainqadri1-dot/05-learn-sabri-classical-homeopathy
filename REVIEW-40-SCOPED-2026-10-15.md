# File 05 — Forty Scoped Source/Plan Checks and Corrective Review — 2026-10-15

## Evidence boundary

Repository PR #4 source baseline: `0fdc931d6c22b56f0d19f7827f766cc101dabba3` (`main` remained `e2667d69d43d655e3d86b0a358dec5f28b61dd96`).
This work used the current File 05 v1.1 Future-18 master plan, consolidated central governing plan and companion-owner boundaries for Files 00, 06, 08, 09, 12, 15, 16, 17, 19, 20, 21, 22, 24, 25 and 26.
These are **40 individually numbered, scoped source-presence/consistency checks**, with deeper examination of specific high-risk functions; **not** 40 complete rereads, 40 independent end-to-end suites, a feature-completeness certification, or any staging/live tests. The presence of a code anchor is not proof of runtime correctness.

The requirement traceability document lists 18/18 `F05-FR`, 10/10 `F05-NFR` and 18/18 `F05-FUT` IDs. Those counts establish documentation coverage, not comprehensive implementation acceptance.

## Forty examined source areas

| # | Area checked | Concrete source locus | Finding / limitation |
|---|---|---|---|
| 01 | Activation and rollback | `LSCH_Activator::activate_site` | Source path present; real install/rollback pending |
| 02 | Core migrations | `LSCH_Database::maybe_upgrade` | Source path present; live SQL/schema not frozen |
| 03 | File 00 canonical identity | `LSCH_Dependencies::claims` | **Confirmed D01, corrected:** local claims filters could alter File 00 authority |
| 04 | Minor/guardian gate | `LSCH_Capabilities::guardian_gate_passes` | Source gate present; real guardian transitions pending |
| 05 | Staff capabilities | `LSCH_Capabilities::filter_user_caps` | Scoped grants present; external File 00/09 roles untested |
| 06 | File 20 shell ownership | `LSCH_Frontend::shell_layout` | Delegation present; real shell/RTL pending |
| 07 | Four academic levels | `LSCH_Content::levels` | Four explicit source entries |
| 08 | Sixteen learning topics | `LSCH_Content::topics` | Sixteen explicit source entries |
| 09 | Eight Founder book slots | `LSCH_Content::seed_founder_book_slots` | Eight **draft placeholder slots**, NOT eight verified published book records |
| 10 | Content post types | `LSCH_Content::register_post_type` | Definitions present |
| 11 | Object-level lesson visibility | `LSCH_Policy::can_read_post` | Native gate present; real cache/CDN unknown |
| 12 | Taxonomy failure handling | `LSCH_Content` and taxonomy tests | Checks exist; staging failures untested |
| 13 | Educational revisions | `LSCH_Content::bump_version` | Source revision path present |
| 14 | Private cache headers | `LSCH_Frontend::send_private_headers` | Code present; external cache behaviour unknown |
| 15 | Enrollment versioning | `LSCH_Services::enroll` | Optimistic version checks found |
| 16 | Prerequisite validation | `LSCH_Policy::validate_prerequisites` | Source path present |
| 17 | Course completion | `LSCH_Services::recalculate_completion` | Evidence checks present; MySQL concurrent operation acceptance pending |
| 18 | Bookmarks | `LSCH_Services::toggle_bookmark` | Private source path present |
| 19 | Note encryption | `LSCH_Policy::encrypt_note`/`decrypt_note` | Cryptographic path present; key backup/rotation staging pending |
| 20 | Assessments | `LSCH_Services::start_assessment`/`submit_assessment` | Version/integrity source paths present |
| 21 | Assignments, grading, appeals | `LSCH_Services::grade_submission`/`appeal_submission` | Source paths present |
| 22 | Certificates | `LSCH_Services::certificate_readiness` | Readiness guard present; no accreditation/live claim |
| 23 | Teacher/assessor object scope | `LSCH_Services::staff_scope_allows` | Source path present; actual assigned roles need staging |
| 24 | Patient case consent history | `LSCH_Services::store_case_consent` | History protected; **append-only governed re-consent after withdrawal remains unimplemented** |
| 25 | Consent withdrawal | `LSCH_Services::withdraw_case_consent` | Withdrawal and visibility change coded; real WP rollback/cache pending |
| 26 | Outbox event persistence | `LSCH_Events::publish`; `LSCH_Idempotency` | REST transaction buffering exists; real multiwrite/outbox atomicity and non-REST paths require staging |
| 27 | Request idempotency | `LSCH_Idempotency::before_callbacks/after_callbacks` | Locking/replay/transaction source code exists |
| 28 | Outbox retries/dead letters | `LSCH_Operations::process_outbox` | Retry/dead states present; File 19 actual delivery unverified |
| 29 | File 06 knowledge links | `LSCH_Value::sources` | Canonical link adapter path present |
| 30 | File 12 protected PDF rights | `LSCH_Value::download_provider` | Interface exists; real document rights unverified |
| 31 | File 15 repertory references | `LSCH_Future18` | Source adapter boundary referenced |
| 32 | File 16 AI tutor | `LSCH_Future18::socratic_tutor` | Source path present; real source validation/prompt-injection tests pending |
| 33 | File 17 learning-community transport | `LSCH_Plugin::community_context_provider` | Context adapter; no duplicate messaging owner |
| 34 | File 19 reminders/events | `LSCH_Operations::process_outbox` | Outbound queue exists; delivery parity unknown |
| 35 | File 21 public publishing boundary | `LSCH_Plugin::block_publication` | Source guard present |
| 36 | File 22 composer contract | `LSCH_Plugin::composer_adapter` | Source adapter present; external provider parity unknown |
| 37 | File 25 public portfolio profile | `LSCH_Future18::set_portfolio_visibility` | Bounded learning-owned visibility source path |
| 38 | File 26 search/discovery | `LSCH_Plugin::search_connector` | Cross-owner search adapter present |
| 39 | Future-18 learning and mentoring | `LSCH_Future18::feature_ids`, `mentor_feedback` | All 18 IDs documented; **D02 and D03 corrected** in mentoring history |
| 40 | Operability, safe repair | `LSCH_Operations::system_check/repair` | Source diagnostics present; live operator exercise pending |

## Confirmed source defects and corrective disposition

- **D01 — Canonical File 00 identity escalation path.** Three fail-closed branches and one valid-claims return in `LSCH_Dependencies::claims` previously passed assertions through mutable `lsch_user_claims` hooks. This allowed an installed local extension to override eligibility/suspension/founder decisions instead of enforcing File 00 authority. **Corrected** to return closed values or normalized File 00 claims without local override. Test: `tests/file00-claims-failclosed.php`; registered under mandatory `tests/source-invariants.sh`.
- **D02 — Destructive mentor feedback history trimming.** `LSCH_Future18::mentor_feedback` silently discarded the oldest entries after 100. **Corrected** to return typed 409 `lsch_future18_mentor_feedback_history_full`, with a bounded audit and zero mutation, until a governed append-only archival extension is available. Test: expanded `tests/eighteenth-mentor-feedback-history.php`.
- **D03 — Numeric-key JSON object accepted as feedback list.** PHP `json_decode(..., true)` can turn a numeric-key JSON object into a list-shaped PHP array. Previous list-shape validation therefore misclassified malformed stored history. **Corrected** to require a literal JSON array root before accepting the historical list. Test: numeric-key object cases added to `tests/eighteenth-mentor-feedback-history.php`.

All three corrections are **source-implemented**. Their integrated effectiveness in the deployed WordPress database is **not** claimed.

## Exact source CI

- Corrected code and regression branch SHA (before this audit report): `f4abbf41b86f01cd854ee71fe668bd4a719d931c`.
- `File 05 Current Plans Integrity` run `38768112498`: **SUCCESS** on that SHA. All four jobs passed: source-integrity, PHP 7.4, PHP 8.3, reproducible-package.
- The new report commit must receive its **own** exact-HEAD workflow result; do not reuse the above run as evidence for a later SHA.
- Main branch: intentionally not merged. No staging or production changes were performed.

## Still unresolved / release blockers

1. **Feature/schema design incomplete:** consent withdrawal followed by *new* valid patient educational-case consent requires append-only history, approved new version, migration, rollback, File 08 clinical-owner separation and File 24 privacy/legal hold. The current code intentionally rejects silent reactivation.
2. **History retention extension needed:** mentor feedback now fails safely at 100 entries rather than silently destroying old records; a scalable append-only archive and associated migration/retention/export acceptance are a separately governed enhancement.
3. **Actual content not populated:** the eight Founder book slots are private drafts, not independently confirmed book titles/editions/chapter structures.
4. **Staging verification absent:** actual File 00/06/08/09/12/15/16/17/19/20/21/22/24/25/26 installed versions, WordPress/MySQL state+outbox transaction and retries, RBAC, browser/RTL/accessibility, backup/restore, migration/rollback, race/load and privacy scenarios remain unverified.
5. **Live reality absent:** deployed ZIP/checksum/runtime, active DB core/aux/Future18 version and migration state, `wp_options`, error logs, active dependencies and live rerun are not known. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔

## Mandatory incident-status register

| Reality | Result |
|---|---|
| Repository main HEAD at review | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Reviewed PR source baseline | `0fdc931d6c22b56f0d19f7827f766cc101dabba3` |
| Source-declared plugin version | `4.0.0` |
| Expected source schemas | Core `18`; auxiliary `3`; Future18 `2` |
| Deployed Version | UNVERIFIED |
| Live DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

Status is **source corrections with successful source CI, not complete production acceptance**.
