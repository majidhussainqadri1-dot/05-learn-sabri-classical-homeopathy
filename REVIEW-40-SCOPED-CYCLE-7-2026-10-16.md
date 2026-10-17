# File 05 — Seventh 40-Point Plan and Cross-File Code Audit — 2026-10-16

## Exact audit boundary
- Starting Draft PR #4 SHA `fafa4b37b2757509684aa3bcbfd9772d37bd2ded`; branch `codex/file-05-complete-v3.0.0`; `main` still at `e2667d69d43d655e3d86b0a358dec5f28b61dd96`.
- Primary source specifications: attached `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx` (Table 6: 18 mandatory FR; Table 7: 10 NFR release gates), attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026`, and relevant File06,08,09,12,21,25 companion-plan documents.
- Exact-source inspection of all 19 native File05 implementation classes (~444,594 source characters at the corrected-source SHA), `CONTRACTS.md`, `REQUIREMENTS-TRACEABILITY.md`, and source PHP regression fixtures.
- **40 different scoped checks**: FR001–018 (18), NFR001–010 (10), twelve cross-owner integrations (12). These are NOT 40 independent exhaustive full-application tests and do not establish live correctness.
- First automated source-pattern scan: **39/40 source patterns found**. #33 File10 was a simple string-search false positive: `LSCH_Services::upsert_related_link` explicitly allows `target_file='10'` along with 06/12/15. No implementation defect was logged for this false positive.

## All 40 check outcomes
| Round | Governing source | Exact code path examined | Finding |
|---:|---|---|---|
| 01 | F05-FR-001 | `class-lsch-content.php::levels/topics` — Four learning levels and sixteen topic categories | Present in source; real WP term seeding still staging-gated |
| 02 | F05-FR-002 | `class-lsch-content.php::seed_founder_book_slots` — Eight Founder book edition/chapter entries | Draft placeholders only; original titles/chapters/content not proven |
| 03 | F05-FR-003 | `class-lsch-content.php::register` — Programs, courses, lessons and course catalog | Post types and catalog code present |
| 04 | F05-FR-004 | `class-lsch-plugin.php::prepublish_lesson` — Precommit lesson content, sources, reviewer and consent governance | **C7-D02 fixed**: wp_insert_post_data now defers incomplete/missing-evidence publication before DB commit |
| 05 | F05-FR-005 | `class-lsch-services.php::enroll` — Enrollment eligibility, prerequisite and grace state | Guards present in source |
| 06 | F05-FR-006 | `class-lsch-services.php::progress` — Lesson progress optimistic version and required evidence | Version conflict and evidence guards present |
| 07 | F05-FR-007 | `class-lsch-services.php::toggle_bookmark; class-lsch-policy.php::encrypt_note` — Bookmark state and encrypted private notes | Owner scopes and encryption source present; real key restore unknown |
| 08 | F05-FR-008 | `class-lsch-services.php::start_assessment/submit_assessment` — Assessment attempt, item version and controlled submission | Source workflow present |
| 09 | F05-FR-009 | `class-lsch-services.php::grade_submission/appeal_submission` — Assignment grading, reviewer and appeal flow | Source workflow present |
| 10 | F05-FR-010 | `class-lsch-services.php::dashboard` — Private user dashboard and resume | Account-scoped source workflow present |
| 11 | F05-FR-011 | `class-lsch-rest.php::catalog` — Public learning search and source filters | Catalog source exists; actual search results/staleness not yet tested |
| 12 | F05-FR-012 | `class-lsch-services.php::upsert_related_link` — File06/10/12/15 typed canonical references | Explicit allowed target_file 06/10/12/15, fail-closed foreign-owner verification |
| 13 | F05-FR-013 | `class-lsch-plugin.php::community_context_provider` — Study community context rather than messaging transport | File17 owner boundary present |
| 14 | F05-FR-014 | `class-lsch-capabilities.php::active_staff_rows` — Staff teaching/assessor least privilege | Source role-scope guards present |
| 15 | F05-FR-015 | `class-lsch-services.php::recalculate_completion` — Course completion snapshots and revocation | Version/revocation source guard present |
| 16 | F05-FR-016 | `class-lsch-services.php::certificate_readiness` — Certificate readiness and identity claims | Source path present; real credential/legal gate unverified |
| 17 | F05-FR-017 | `class-lsch-dependencies.php::governing_policy_ready` — Single free tier contract from File00 | Current central policy source checks present |
| 18 | F05-FR-018 | `class-lsch-state.php::apply_correction` — Correction with versioned learner re-study | Prior correction-version persistence and needs_review logic retained |
| 19 | F05-NFR-001 | `class-lsch-policy.php::can_read_post; class-lsch-content.php::can_edit_meta` — Object and field authorization | Source checks present; IDOR matrix still needs staging |
| 20 | F05-NFR-002 | `class-lsch-privacy.php::export/erase; class-lsch-policy.php::valid_case_consent` — Purpose-limited consent, export and erase | Source paths present; historical re-consent incomplete |
| 21 | F05-NFR-003 | `class-lsch-idempotency.php::after_callbacks; class-lsch-events.php::publish` — Idempotency, transactional outbox and retry | Source paths present; real WP transaction and delivery unverified |
| 22 | F05-NFR-004 | `class-lsch-services.php::recalculate_completion` — Bounded queries and background operations | 500-lesson source bound; measured p75/p95 unavailable |
| 23 | F05-NFR-005 | `class-lsch-frontend.php::send_private_headers` — Accessibility and protected frontend responses | Source guard present; keyboard/screen-reader/RTL staging not done |
| 24 | F05-NFR-006 | `class-lsch-operations.php::system_check; class-lsch-events.php::audit` — Operational audit, health and telemetry | Source path present |
| 25 | F05-NFR-007 | `class-lsch-database.php::maybe_upgrade` — Additive schema migration and rollback | Source path present; DB migration/restore unverified |
| 26 | F05-NFR-008 | `class-lsch-operations.php::process_outbox` — System Check, queue handling, safe repair | Source operations present |
| 27 | F05-NFR-009 | `class-lsch-activator.php::activate_site` — WordPress/PHP compatibility | Automated syntax CI; real WP staging version unverified |
| 28 | F05-NFR-010 | `File05 textdomain and frontend` — English base, Urdu/Arabic and RTL | Source i18n present; visual/timezone acceptance unverified |
| 29 | File00 | `class-lsch-dependencies.php::claims` — Canonical membership/guardian/suspension | SMC_Contracts::assertions consumed; installed File00 parity unknown |
| 30 | File06 | `class-lsch-value.php::sources` — Encyclopedia evidence and citations | Source normalization present; File06 owner remains authoritative |
| 31 | File08 | `class-lsch-policy.php::publicly_visible` — Patient case consent, clinical/educational separation | Patient consent guarded; clinical storage remains File08 |
| 32 | File09 | `class-lsch-capabilities.php::verified_doctor` — Verified professional/mentor claims | Provider claims consumed; real professional account tests absent |
| 33 | File10 | `class-lsch-services.php::upsert_related_link` — Video/live educational related media | **False-positive resolved:** target_file enum '10' is present, though literal 'file10' text is absent |
| 34 | File12 | `class-lsch-value.php::download_provider` — PDF/digital reading source ownership | Typed references/provider source present; real rights gating unverified |
| 35 | File15 | `class-lsch-future18.php` — Repertory training reference | Repertory practice source present; foreign data owner unmodified |
| 36 | File16 | `class-lsch-future18.php::socratic_tutor` — Socratic source-grounded AI tutor | Source tutor adapter present; deployed provider safety unverified |
| 37 | File17 | `class-lsch-plugin.php::community_context_provider` — Community/messaging context | Scoped context; not message transport |
| 38 | File19 | `class-lsch-operations.php::process_outbox` — Event delivery, retries and dead letters | Outbox queue and dead-state source present; live delivery unknown |
| 39 | File20/25 | `class-lsch-frontend.php::shell_layout` — Shell and visual ownership | Shell adapter code present; RTL accessibility unverified |
| 40 | File21/22/24/26 | `class-lsch-plugin.php::composer_adapter/search_connector; class-lsch-privacy.php` — Feed/composer/privacy/global search boundaries | Owner adapters present; global ranking remains File26 |

## Confirmed source defects and corrections

### C7-D01 — Native public archive inadvertently used reader-specific privileges
- **Root:** `LSCH_Content::filter_native_results` used `LSCH_Policy::can_read_post` while presenting the *public* native WordPress search/archive. A logged-in editor's private object authorization is not a public visibility decision.
- **Change:** use viewer-independent `LSCH_Policy::publicly_visible` for File05 post types. Non-File05 posts remain governed by their original owners.
- **Regression:** new `tests/native-public-archive-scope.php` simulates a user who can read *all* learning objects privately, yet public archives include only public File05 rows, preserving regular external posts.
- **Status:** Source corrected/tested; cache/CDN/provider projections still need real WP staging.

### C7-D02 — Publication evidence checks ran after WordPress post-status commit
- **Root:** `LSCH_Plugin::lesson_saved` ran on `save_post_lsch_lesson` after WordPress had already stored a `publish` status. Attempts to revert to pending could fail, and `transition_post_status` could emit a premature `LearningContentPublished.v1` event.
- **Change:** register `wp_insert_post_data` before-commit filter `LSCH_Plugin::prepublish_lesson`. It defers a publish request to `pending` unless the learning post already exists and current central policy, authorized publisher, nonempty proposed title/body, persisted objectives/sources/accessibility/safety, approved source citations, independent reviewer where needed, well-formed topics, patient-case consent, and required ‘کامیاب کیس’ tag all pass. Existing after-save check remains defense in depth.
- **Workflow consequence:** newly created lessons without prior persisted meta/reviewer evidence are saved pending first; a later publish action after completion of those records is required. The prefilter can normalize the required case tag on an existing consent-authorized lesson before storing public status.
- **Regression:** new `tests/lesson-precommit-publication.php` validates multiple negative cases (unsaved post, policy unready, unauthorized actor, missing body/meta/source/reviewer, invalid taxonomy, missing consent, failed case tag), and positive verified publishing.
- **Status:** Source and fixture tests successful; real post-save ordering, composer payload/inline metadata, tax_input modifications during the same save, other WordPress filters' priorities, cache purges and downstream File19/21/26 events must still be acceptance-tested in staging. Do NOT claim this eliminates *every* possible public-state race.

### CI evidence for actual corrected source
- Corrected code + both new registered regressions at SHA `142b28e4e38238c19a33ce65880fd070fee36d31`.
- GitHub Actions `File 05 Current Plans Integrity`, Run `38937425282`, exact HEAD: four jobs **successful**: source-integrity, PHP 7.4 lint, PHP 8.3 lint, reproducible package.
- This report and subsequent `STATUS.md` documentation commit each change HEAD; the final SHA needs its **own** green CI verification.
- Code changes confined to Draft PR #4. No merge to `main`, no deployment, no real DB changes or migration.

## Remaining incomplete requirements and blockers
1. **High-priority staging safety acceptance**: real WordPress `wp_insert_post_data` filter and `save_post` / `transition_post_status` ordering; combined content+taxonomy edits; official File22 composer, File19/21/26 event/cache/index projections; defense against other hooks; previous public posts already live before these changes; backfill and rollback.
2. **Append-only patient-case re-consent** after historical withdrawal remains unimplemented pending File08/File24 retention/clinical authority/schema/rollback approvals; current code intentionally blocks unsafe overwrite.
3. **Append-only Mentor Feedback beyond 100** remains unimplemented pending governed storage/migrations/exports; current code fails closed at capacity.
4. Eight Founder books are **draft slots only**. Approved bibliographies, real editions, chapters and educational course content are not present/verified.
5. Multi-field administrative metadata atomicity and actual WordPress/Postmeta/Outbox transaction integrity, installed cross-owner provider version parity, database migration/rollback/backup recovery, accessibility/RTL and live deployment/retest unverified.

## Live-first five-part reality
| Dimension | Verification |
|---|---|
| Repository `main` SHA | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Development PR source/test SHA before report | `142b28e4e38238c19a33ce65880fd070fee36d31` |
| Source-declared plugin version | `4.0.0` |
| Source-expected schemas | Core `18`, auxiliary `3`, Future18 `2` |
| Deployed Version + artifact checksum | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

**Honest defect count for Cycle 7:** two newly proved source defects; two corrective source commits; two new executed regression fixtures; one original code-pattern false positive (not a defect); four/ four CI jobs green on corrected source. Known product and Staging/Live gates remain OPEN.
