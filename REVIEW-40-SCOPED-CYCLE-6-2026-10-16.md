# File 05 — Sixth Forty-Point Source/Plan Review — 2026-10-16

## Authority, source freeze and scope
- **Frozen reviewed exact development SHA**: `21fc510b59760e84c4dc1c830b90f1637704b0ce` on Draft PR #4, branch `codex/file-05-complete-v3.0.0`. `main` still `e2667d69d43d655e3d86b0a358dec5f28b61dd96`, not merged.
- Reviewed attached **File05 v1.1/Future18 Master Plan** and its F05-FR-001…018, F05-NFR-001…010 requirements; attached **Three Central Plans Consolidated Governing Master Plan**; File08/09/12/25 plans and relevant File06/File21 scope; repo owner contract/traceability.
- Inspected all **19** File05 native PHP class sources (~440,793 characters), relevant REST, policy/visibility, correction, versioning, WordPress hooks and tests.
- **40 distinct scoped technical requirement/source checks**, not 40 comprehensive independent codebases or 40 real Staging/Live tests. A symbol/guard present is not evidence of deployed functional acceptance.
- Initial scoped check classification: **35 code evidence matches**, **5 flags**. 2 were false positives (#26 File00 free-tier normalization, #38 short schema upgrade that delegates to install); **3 confirmed defects** (#27, #30, #35).

## All forty reviews
| Check | Governing requirement | Specific contract/code check | Recorded finding |
|---:|---|---|---|
| 1 | F05-FR-001 | Four curriculum levels — `class-lsch-content.php::levels` | Source gate present |
| 2 | F05-FR-001 | Sixteen educational subjects — `class-lsch-content.php::topics` | Source gate present |
| 3 | F05-FR-002 | Founder book slots — `class-lsch-content.php::seed_founder_book_slots` | Draft placeholders only; actual titles, editions and lessons unverified |
| 4 | F05-FR-003 | Program/course content type — `class-lsch-content.php::register` | Code path present; actual install unknown |
| 5 | F05-FR-004 | Lesson authoring nonce and capability — `class-lsch-admin.php::save_governance_meta` | Auth source gate present |
| 6 | F05-FR-004 | Strict access enum — `class-lsch-admin.php::save_governance_meta` | Invalid access rejects |
| 7 | F05-FR-004 | Governance DB write evidence — `class-lsch-admin.php::save_governance_meta` | Previously fixed; regression retained |
| 8 | F05-FR-004 | Publication content-version evidence — `class-lsch-plugin.php::lesson_saved` | Previously fixed; regression retained |
| 9 | F05-FR-004 | Independent reviewer evidence — `class-lsch-plugin.php::lesson_saved` | Previously fixed; regression retained |
| 10 | F05-FR-004 / File08 | Patient taxonomy failure containment at publish — `class-lsch-plugin.php::lesson_saved` | Previously fixed; regression retained |
| 11 | F05-FR-004 / File21 | Required patient-case tag persistence — `class-lsch-plugin.php::lesson_saved` | Previously fixed; regression retained |
| 12 | F05-FR-005 | Course prerequisites — `class-lsch-services.php::enroll` | Guard present |
| 13 | F05-FR-005 | Enrollment database confirmation — `class-lsch-services.php::enroll` | Source readback path present |
| 14 | F05-FR-006 | Progress optimistic version — `class-lsch-services.php::progress` | Concurrency guard present |
| 15 | F05-FR-007 | Bookmark owner-only state — `class-lsch-services.php::toggle_bookmark` | Scoped owner path present |
| 16 | F05-FR-007 / File24 | Private note cryptography — `class-lsch-policy.php::encrypt_note` | Encrypted pathway; real key backup unknown |
| 17 | F05-FR-008 | Assessment initiation — `class-lsch-services.php::start_assessment` | Versioned attempt path present |
| 18 | F05-FR-009 | Assessment grading/appeal — `class-lsch-services.php::grade_submission` | Source role scope present |
| 19 | F05-FR-010 | Learner dashboard user privacy — `class-lsch-services.php::dashboard` | Private projection path present |
| 20 | F05-FR-011 / File26 | Catalog taxonomy and global search — `class-lsch-rest.php::catalog` | Catalog source path present; runtime search indexing unverified |
| 21 | F05-FR-012 / File06/12/15 | Owner-typed related references — `class-lsch-services.php::upsert_related_link` | Foreign owner references, not duplicated canonical data |
| 22 | F05-FR-013 / File17 | Cohort/community context bridge — `class-lsch-plugin.php::community_context_provider` | No messaging owner override |
| 23 | F05-FR-014 / File09 | Active staff scope — `class-lsch-capabilities.php::active_staff_rows` | Source active scope present |
| 24 | F05-FR-015 | Completion revocation — `class-lsch-services.php::recalculate_completion` | Revoked ledger/source guard present |
| 25 | F05-FR-016 | Certificate readiness — `class-lsch-services.php::certificate_readiness` | Source path exists; legal acceptance external |
| 26 | F05-FR-017 / File00 | Single free tier — `class-lsch-dependencies.php::governing_policy_ready` | False positive: exact policy fields single_free_tier, free_baseline and no paid unlocks enforce rule |
| 27 | F05-FR-018 | Correction persisted new version — `class-lsch-state.php::apply_correction` | C6-D02 fixed: canonical _lsch_version is read back after save, before progress/events |
| 28 | F05-FR-018 | Correction note provenance — `class-lsch-state.php::apply_correction` | Previous note failure guard intact |
| 29 | F05-NFR-001 / File00 | Canonical external identity — `class-lsch-dependencies.php::claims` | Fail-closed File00 assertions; no local editable fallback |
| 30 | F05-NFR-002 / File08/24 | Public case topic taxonomy type — `class-lsch-policy.php::publicly_visible` | C6-D01 fixed: WP_Error and non-array taxonomy responses reject public case visibility |
| 31 | F05-NFR-002 | Revoked case consent gating — `class-lsch-policy.php::publicly_visible` | Existing valid_case_consent check still used |
| 32 | F05-NFR-002 / File24 | Export, erasure, retention — `class-lsch-privacy.php::export/erase` | Source implementation; real legal hold/export not accepted |
| 33 | F05-NFR-003 | REST transaction + rollback — `class-lsch-idempotency.php::before_callbacks/after_callbacks` | Source outer transaction guards present |
| 34 | F05-NFR-003 / File19 | Outbox dead-letter delivery — `class-lsch-operations.php::process_outbox` | Bounded retry/dead state; actual delivery unverified |
| 35 | F05-NFR-003 / File21 | Failed WordPress publication containment — `class-lsch-plugin.php::block_publication` | C6-D03 false-success audit fixed; persisted-failure detection and separate operator escalation added; **underlying WP write failure remains unresolved** |
| 36 | F05-NFR-004 | Large course query guard — `class-lsch-services.php::recalculate_completion` | 500-lesson bound |
| 37 | F05-NFR-005 / File25 | Personalized no-cache surface — `class-lsch-frontend.php::is_private_learning_surface` | Source guard; CDN/browser behavior unverified |
| 38 | F05-NFR-007 | Database schema upgrade — `class-lsch-database.php::maybe_upgrade` | False positive: short function correctly delegates to install(); actual migrations unverified |
| 39 | F05-NFR-008 | Idempotent request replay — `class-lsch-idempotency.php::before_callbacks` | Ambiguous replay guard present |
| 40 | Files06/12/16 | Knowledge/PDF/AI tutoring owner boundaries — `class-lsch-value.php::download_provider; class-lsch-future18.php::socratic_tutor` | Cross-file adapters exist; provider parity unverified |

## Confirmed defects: count, resolution and evidence

**C6-D01 — Unsafe public visibility on malformed topic taxonomy (F05-NFR-002, File08, File24).**
- Initial code accepted a WP taxonomy response unless `is_wp_error`; a **null, false, string or object** was cast to a PHP array for topic membership and could be treated as a non-patient public lesson, bypassing patient consent.
- **Root correction:** `LSCH_Policy::publicly_visible` now requires a genuine PHP array of slugs; unexpected response returns false.
- **Regression:** extended `tests/public-visibility-version.php` with null/false/string/object failure injection and healthy consent-positive restore.
- **Status:** Source corrected, covered by successful mandatory CI, still needs live-cache/SEO/WordPress acceptance.

**C6-D02 — Unverified academic correction version metadata write (F05-FR-018, NFR-003).**
- Existing `LSCH_State::apply_correction` checked `update_post_meta` false return but did not read back the canonical version, unlike the separate governed content bump path.
- **Root correction:** after save and cache refresh, verify `get_post_meta( '_lsch_version' )` matches intended new version; reject and delegate REST rollback before marking learner progress and publishing versioned correction event.
- **Regression:** extended `tests/correction-nested-transaction-atomicity.php` with a simulated misleading successful WP API return that does not actually persist the new version.
- **Status:** Source corrected, CI verified. Real MySQL/cache ordering/atomicity still Staging-gated.

**C6-D03 — False audit claim when WordPress rejects publication containment (F05-FR-004, NFR-003; Files19/21/24/26).**
- Existing `LSCH_Plugin::block_publication` ignored `wp_update_post` result; a rejected pending transition was logged as successful `lesson_publication_blocked`.
- **Correction to evidence only:** now verifies WP_Error, expected returned post ID and read-back status `pending`; separately audits `lesson_publication_block_persist_failed` and returns a typed WP_Error. The WordPress hook is restored via `finally`. The successful-path block audit is only emitted after confirmed persisted `pending`.
- **Regression:** expanded `tests/lesson-publication-evidence-failclosed.php` with a failed simulated WordPress post-status update that verifies a distinct failure audit **and explicitly confirms the article remains public** (no false concealment).
- **Important: NOT RESOLVED AS A CONTAINMENT FAILURE.** This change detects/fails the audit, but `lesson_saved` does not guarantee a rejected WordPress public write is canceled, and `transition_post_status` may emit a premature publication event. Proper **pre-commit publication gating, queue/index invalidation and operational recovery** must be designed, tested in WP/MySQL staging and founder-approved before being called production-safe.

**Additional CI fixture defect, separately tracked (not counted as a fourth application-code defect):** first CI failed because an older unit-test WordPress stub lacked the newly used `get_post_status` function. Test stub was updated, CI rerun and passed.

## Source and CI register
- Corrected source + all failure-injection test SHA: `f032d0e548877c2f53a7634f47339ebac298fb92`.
- GitHub Actions `File 05 Current Plans Integrity` Run **38934401733** on that exact SHA **completed SUCCESS**, all four jobs successful: PHP 7.4, PHP 8.3, source-integrity (all mandatory PHP tests), reproducible package.
- Initial review source HEAD `21fc510b59760e84c4dc1c830b90f1637704b0ce`.
- This report + following status commit change HEAD; verify the final exact SHA run before final report.

## Outstanding implementation and acceptance gates (not fabricated closed)
1. **Highest priority source/design blocker:** robust pre-commit lesson publication validation in WordPress so invalid/unreviewed case material never becomes publicly readable even when a post-status write fails; need status-transition event ordering, File19/21/26 indexing and CDN/cache invalidation, real WP failure-injection tests.
2. **Patient-case append-only re-consent after withdrawal** still unavailable; requires compliant versioned privacy/clinical owner model, migration/rollback and Founder signoff.
3. **Mentor feedback append-only archive above 100 records** remains unavailable; safe error is not complete archival feature.
4. **Eight Founder book records** remain incomplete content: private placeholder slots, not approved bibliographies/editions/chapters.
5. Real WordPress/MySQL multi-field metadata transaction reliability, File00/06/08/09/12/16/17/19/20/21/22/24/25/26 provider parity, staging migrations/backup restore, RTL/browser/accessibility, privacy export/erase, cache and live re-test are not verified.

## Mandatory exact-reality register
| Field | Verified |
|---|---|
| Repository PR HEAD before corrections | `21fc510b59760e84c4dc1c830b90f1637704b0ce` |
| Fixed source/test SHA before report | `f032d0e548877c2f53a7634f47339ebac298fb92` |
| Main HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Source-declared plugin | `4.0.0` |
| Source-expected schema | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed Version / checksum | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

**Count:** 40 scoped reviews, 3 new confirmed application-code defects, **2 safety/code defects patched; one erroneous-success audit corrected with root containment still OPEN**. Two flagged checks were false positives, not defects. One independent test-fixture stub error repaired. CI 4/4 success on corrected code/test SHA, without live claims.
