# File 05 — New 40-Check Source/Plan Review and Two Corrective Fixes
**Review date:** 2026-10-15. **Target:** Draft PR #4 `codex/file-05-complete-v3.0.0`.

## Governing plans and exact reality
- File05 v1.1 Future-18 Master Plan; Three Central Plans Consolidated Governing Master Plan; linked ownership boundaries of File00/06/08/09/12/15/16/17/19/20/21/22/24/25/26.
- **Pre-review immutable source HEAD:** `d398d80f481c776c1ca3eba12d5fb4b3b7c6abd5`.
- **Corrected source/test HEAD (prior to this documentation-only commit):** `1899003ce4be5227d5592555aae902d689070c0c`.
- `main` remains at `e2667d69d43d655e3d86b0a358dec5f28b61dd96`.
- The 40 numbered items below are **40 different scoped technical checks**, with specific call-path review for confirmed defects; they are **not 40 independent full end-to-end deployments**, 40 entire-source rereads or a claim of exhaustive absence of missing work. A passing source check confirms the cited implementation pattern, not actual WordPress/peer-plugin behaviour.

## 40 new checked subjects
- 01. REST mutation guard applies to File05 namespace — LSCH_Idempotency::is_mutation
- 02. Correction decision REST path enters idempotent wrapper — LSCH_State::routes + Idempotency hooks
- 03. **DEFECT D04**: inner correction started a second MySQL transaction — LSCH_State::apply_correction
- 04. Correction review and actual source version update belong to one request — LSCH_State::rest_decide_correction
- 05. Source correction checks expected lesson version — LSCH_State::apply_correction
- 06. Self-review prohibition for proposer — LSCH_State::rest_decide_correction
- 07. Content correction event remains versioned — LearningContentCorrected.v1
- 08. Durable outbox insert precedes dispatch — LSCH_Events::publish
- 09. Rest after-callback flushes events only on success — LSCH_Idempotency::after_callbacks
- 10. Request-level integrity failure leads to rollback — LSCH_Idempotency::after_callbacks
- 11. Ambiguous prior request fails closed — LSCH_Idempotency::before_callbacks
- 12. File00 identity assertions are not overridden by local claims filters — LSCH_Dependencies::claims
- 13. File00 minimum contract version is pinned — LSCH_Dependencies
- 14. Guardian eligibility from File00 checked — LSCH_Capabilities::guardian_gate_passes
- 15. Unauthorized education capabilities removed — LSCH_Capabilities::filter_user_caps
- 16. Founder and authorized-doctor learning publication scope — LSCH_Capabilities::can_author
- 17. Four education levels enumerated — LSCH_Content::levels
- 18. Sixteen approved learning topics enumerated — LSCH_Content::topics
- 19. Founder book seed records remain unapproved drafts — LSCH_Content::seed_founder_book_slots
- 20. Lesson parents and patient-case consent gate public reads — LSCH_Policy::publicly_visible
- 21. Revoked patient consent cannot overwrite prior record — LSCH_Services::store_case_consent
- 22. **DEFECT D05**: withdrawal previously ignored a failed WordPress private-post transition — LSCH_Services::withdraw_case_consent
- 23. Withdrawal event and privacy-purpose audit retained — LSCH_Services::withdraw_case_consent
- 24. Learner progress carries versioned state — LSCH_Services::refresh_progress_from_evidence
- 25. Prerequisite failures are explicit — LSCH_Policy::validate_prerequisites
- 26. Revoked course completion cannot auto revive — LSCH_Services::recalculate_completion
- 27. Assessment submission checks versioned evidence — LSCH_Services::submit_assessment
- 28. Assignment grading uses assessor authority and scope — LSCH_Services::grade_submission
- 29. Scoped academic staff grants checked — LSCH_Capabilities::filter_user_caps
- 30. Private notes have authenticated encryption path — LSCH_Policy::encrypt_note
- 31. Future18 mentor-history JSON must be a literal list — LSCH_Future18::mentor_feedback
- 32. Full mentor history no longer silently drops old entries — LSCH_Future18::mentor_feedback
- 33. File06 knowledge source-link contract consumed — LSCH_Value::sources
- 34. File12 document/download owner adapter provided — LSCH_Value::download_provider
- 35. File16 educational tutor adapter exists — LSCH_Future18::socratic_tutor
- 36. File17 community/messaging remains a cross-owner context — LSCH_Plugin::community_context_provider
- 37. File19 events use bounded retry and dead-letter status — LSCH_Operations::process_outbox
- 38. File20/25 shell/visual layering boundary — LSCH_Frontend::shell_layout
- 39. File22 composer integration does not claim File21 feed ownership — LSCH_Plugin::composer_adapter
- 40. File26 search integration via source query adapter — LSCH_Plugin::search_connector

**Before fixes:** 38/40 targeted source checks had the indicated guard or implementation pattern. Checks #03 and #22 failed and were independently confirmed by reading their corresponding function and enclosing REST transaction. **After fixes:** the same 40 patterns rechecked at source HEAD `1899003ce4be5227d5592555aae902d689070c0c`: 40/40 source-pattern checks passed. This is not whole-plan or live completeness.

## Proven code defects, root cause, correction and test
### D04 — Nested transaction in editorial correction, high severity
- Route: `LSCH_State::rest_decide_correction` invokes `apply_correction`. Earlier `LSCH_Idempotency::before_callbacks` had already started the MySQL transaction for the mutating REST request.
- Root cause: `apply_correction` issued its own `START TRANSACTION`, `COMMIT` and `ROLLBACK`. MySQL does not support true nested transactions via these commands, so inner START can implicitly commit the caller's prior `applying` governance state; later owner/outbox failures then cannot roll back everything.
- Fix: remove nested transaction control from `apply_correction`, preserve advisory lock, version verification, progress update and event; use only `LSCH_Idempotency::after_callbacks` to finalize or roll back the enclosing request.
- Regression: **new** `tests/correction-nested-transaction-atomicity.php` checks correction success and explicit SQL failure without any extra START/COMMIT/ROLLBACK, no event on failed update, and lock release. Full real MySQL transaction/cache behavior still requires staging.

### D05 — Failed patient-case privacy transition wrongly reported as withdrawal success, high severity
- Route: `LSCH_Services::withdraw_case_consent` previously called `wp_update_post` to privatize a public lesson but did not inspect the result.
- Root cause: the code could continue to record the withdrawal reason, emit `LearningCaseConsentWithdrawn.v1`, audit success and return `true` even when WordPress had not set the lesson private. It also did not validate reason persistence.
- Fix: request WP_Error-aware post transition; require the returned post ID **and** read-back status `private`; reject failures with typed 503 and audit. Verify that the reason was written or already matches; fail the protected REST request otherwise, allowing its outer DB transaction to roll back.
- Regression: **new** `tests/case-consent-withdrawal-private-state.php` injects post update error, zero ID, unchanged public status, failed metadata write and valid success. It asserts zero success-events on failures.
- If a developer calls the public service outside the guarded REST operation, a caller-managed transaction and reconciliation are not independently demonstrated; do not claim a globally atomic service API.

### Source verification
- Both new fixtures registered in mandatory `tests/source-invariants.sh`.
- GitHub Actions `File 05 Current Plans Integrity`, run **38771410251** on corrected source/test SHA `1899003ce4be5227d5592555aae902d689070c0c`: **SUCCESS**, 4/4 jobs green (source-integrity, PHP7.4 syntax, PHP8.3 syntax, reproducible package).
- This report is a documentation commit following that source SHA. Its own exact-HEAD four-job CI must be inspected separately.

## Existing incomplete work or unverified acceptance
1. Governed **append-only patient-case re-consent** after withdrawal, with new lawful evidence history, approved schema/migration/rollback, File08 clinical owner and File24 retention/hold integration remains **source-incomplete pending approved design**. Current reactivation deliberately fails closed.
2. Mentor feedback above 100 entries now fails safely but a scalable **append-only archival extension** still requires an approved DB design/migration/export/retention solution.
3. Eight Founder books in File05 are governed nonpublic placeholder slots, not actual signed-off published book titles/chapters/content. Cannot truthfully count them as educational content completion.
4. Real WordPress/Hostinger staging: File00 guardian and role claims; File06 citations; File08 case privacy; File09 doctor verification; File12 protected-document access; File16 tutor safety; File17 transport; File19 outbox/inbox; File20/25 visual/RTL; File21/22 publication; File24 privacy; File26 CDN/search cache; migrations, races, key backup, accessibility and rollback are **not accepted** from source-level tests.
5. Real deployment: installed plugin source/package SHA, active WordPress/PHP, `wp_options`, DB rows/schema versions, migration status and logs were **not obtained**. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔

## Five-part required final status
| Reality | Status |
|---|---|
| Repository HEAD (Draft PR source/test before doc write) | `1899003ce4be5227d5592555aae902d689070c0c` |
| Repository main HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Source-declared Runtime | `4.0.0` |
| Source-expected DB schemas | Core `18`, Auxiliary `3`, Future18 `2` |
| Deployed Version | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Numerical defect closure for this new audit:** 2 new proven source defects; 2 corrections committed; 2 new executable PHP regressions; 4/4 CI jobs green on corrected code. Remaining incompleteness and staging/live verification are clearly separated from these fixes. No merge, migration or production deploy is authorized or claimed.
