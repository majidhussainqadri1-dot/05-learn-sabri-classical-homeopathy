# File 05: Ninth Manual Forty-Point Governing Plan and Source Audit — 2026-10-18

## Evidence freeze / method
- This is a **manual single-session** audit. The user previously stopped the hourly automation; it was NOT re-enabled.
- Audited original uploaded File05 v1.1 Future18 plan (18 FR and 10 NFR), Consolidated Three Central Governing Master Plan (including File05 ownership rows and later CV-141 open education), related File06/09/12/21/25 uploaded plans, and current repository source/companion interface assumptions.
- Frozen original PR#4 Draft/unmerged HEAD: `8e40d11223be0aa24c35c1b154ce33c742cf2452`; main `e2667d69d43d655e3d86b0a358dec5f28b61dd96`.
- Inspected **19 core class files comprising 448,286 source characters**, key JavaScript and CSS assets, source tests and runner, plus cited original plan tables. **40 distinct scoped inspections**, NOT forty separately executed whole-repository E2E audits.
- 37/40 initial code anchor searches yielded evidence. Three missing anchor flags (NFR005, NFR007, NFR009) were investigated and proved false as source-absence claims; their real-world release acceptance remains unverified.

## Forty checks
| No. | Exact requirement / owner | Source witness | Outcome and constraint |
|---:|---|---|---|
| 01 | FR001 curriculum/four levels/16 topics | content.php:levels,topics | Source present; full curriculum authored content not verified |
| 02 | FR002 eight Founder works | content.php:seed_founder_book_slots | Draft seed slots; original editions and chapters not delivered |
| 03 | FR003 program/course metadata and statuses | content.php:register; services.php | Source present; representative live journeys unverified |
| 04 | FR004 lesson objective/source/reviewer/version/safety | plugin.php:prepublish_lesson; content.php | Source guard present; real WP save hook ordering requires staging |
| 05 | FR005 enrollment and prerequisite/entitlement | services.php:enroll | Source path present; installed File00 parity unverified |
| 06 | FR006 progress derived from evidence and reset | services.php:progress,reset_progress | Source path present; live data/restore unverified |
| 07 | FR007 bookmarks and encrypted private notes | services.php:toggle_bookmark,get_note; rest.php:note_get | NEW DEFECT C9-D01 proven and source FIXED with red-to-green regression |
| 08 | FR008 assessment attempts and identity | services.php:start_assessment,submit_assessment | Source path and keyed attempt evident |
| 09 | FR009 assignments, rubric, grade, appeal | services.php:submit_assignment,grade_submission,appeal_submission | Source path present |
| 10 | FR010 dashboards and return to learning | services.php:dashboard; frontend.php | Source path present |
| 11 | FR011 learning search and visibility filters | plugin.php:search_connector; policy.php | Source visibility path present; search File26 operational parity unknown |
| 12 | FR012 canonical File06/10/12/15 links | services.php:upsert_related_link | Canonical foreign target allowlist observed; counterpart deployment unknown |
| 13 | FR013 moderated cohorts via File17 | plugin.php:community_context_provider,community_context_card | Adapter only; File17 live ownership not reproduced |
| 14 | FR014 teaching and reviewing least privilege | services.php:assign_staff,remove_staff | Role boundaries present; deployed File09 status unverified |
| 15 | FR015 versioned completion durable record | services.php:recalculate_completion | Source path present; historical DB acceptance unverified |
| 16 | FR016 certificates, revocation and jurisdictions | services.php:certificate_readiness | Guarded readiness path present; issued certificate outcome not proven |
| 17 | FR017 current single-free-tier entitlement | dependencies.php:platform_policy,claims | Source aligns with later governing open-access CV-141; historical central pricing row conflicts |
| 18 | FR018 correction records and learning impact | state.php:apply_correction; future18.php | Source/versioning path present; File19 actual receipt unknown |
| 19 | NFR001 object and field authorization | policy.php:can_read_post; rest.php:approved | Source gates present; IDOR real runtime penetration unverified |
| 20 | NFR002 consent, privacy export, erasure | policy.php:valid_case_consent; privacy.php:erase | Existing consent route; NEW revocation→re-consent append-only still OPEN |
| 21 | NFR003 event identity/idempotency/retries | events.php:consume; idempotency.php | Event-name replay mismatch fixed in prior commit; live inbox replay unknown |
| 22 | NFR004 bounded performance and pagination | operations.php:process_outbox | Bounded processing source; p75/p95 measured load tests absent |
| 23 | NFR005 keyboard, focus, RTL, reduced motion | assets/css/learning.css; frontend.php | Initial false-negative: CSS has RTL, :focus-visible, prefers-reduced-motion; browser WCAG acceptance unverified |
| 24 | NFR006 privacy safe diagnostics | operations.php:system_check | Source present; live monitoring/alerts unknown |
| 25 | NFR007 migration, rollback, restore | database.php:install,maybe_upgrade,migrate_legacy | Initial false-negative: delegate exists; migration/rollback on real MySQL/WordPress NOT VERIFIED |
| 26 | NFR008 system health and repair | operations.php:repair,system_check | Operations source present; live safe-repair drill unverified |
| 27 | NFR009 WP7.0.1/PHP8.3 project baseline | dependencies.php:activation_preflight; CI jobs | Initial false-negative: source supports PHP7.4+ and PHP8.3 tested; actual WP7.0.1 environment unknown |
| 28 | NFR010 US-English/Urdu/Arabic/RTL | frontend.php; CSS; WordPress i18n | Source localizable; cross-locale dates/timezones not browser tested |
| 29 | File00 central claims and scope | dependencies.php:claims | Fail-closed policy source; runtime contract unavailable |
| 30 | File06 encyclopedia citation ownership | value.php:sources | Cross-source pointer; File06 authoritative reference untouched |
| 31 | File08 case consent clinical ownership | services.php:store_case_consent | Source enforces consent; approved historical reconsent remains blocked |
| 32 | File09 professional identity/reviewer | capabilities.php:verified_doctor | Native claim boundary source observed |
| 33 | File10 videos and broadcasting | services.php:upsert_related_link | File10 target enum, not media ownership |
| 34 | File12 PDF book and document ownership | services.php:upsert_related_link | File12 target enum, not PDF ingestion |
| 35 | File15 repertory ownership | services.php:upsert_related_link | File15 target enum, not Repertory state |
| 36 | File16 source-grounded AI tutoring | future18.php:socratic_tutor | Citation/reference guard source; provider and model behavior not verified |
| 37 | File17 study circle/peer Q&A ownership | plugin.php:community_context_provider | Card/adapter only; forum state externally owned |
| 38 | File19 event notification and queue | events.php:publish,enqueue | Source emits events; File19 delivery/consumers not staging accepted |
| 39 | File21 home feed / File22 composer | plugin.php:composer_adapter,status_transition | Delegated adapter/event; exact deployed receiver unknown |
| 40 | File24 privacy / File25 public UI / File26 search | policy.php:publicly_visible; plugin.php:search_connector | Public consent guard source; browser/sitemaps/external cache indexing unverified |

## NEW SOURCE DEFECT C9-D01: private-note REST error object header fatal
- Actual root: `LSCH_Services::get_note()` can return a `WP_Error` on SQL read failure and on decryption/key/authentication failure. WordPress `rest_ensure_response()` **preserves** that error object, but original `LSCH_REST::note_get()` called `$response->header(...)` unconditionally. `WP_Error` has no `header()` method; HTTP 503 may be replaced by fatal 500/error handling.
- Existing `tests/rest-private-note-error-propagation.php` was present at frozen HEAD but not included in canonical `tests/source-invariants.sh` and not executed by green CI. Initial green CI therefore did **not** test this defect.
- Reproduction: committed canonical runner enrollment at `9d2bd75374e3525a0978e45c00a583098ad07b41` and GitHub Actions `39138851863` **SOURCE-INTEGRITY FAILED**, log explicitly reported `FAIL: private note error triggered a fatal response-header call: Call to undefined method WP_Error::header()`.
- Root fix committed in `class-lsch-rest.php`: evaluate `LSCH_Services::get_note()` first; if WP_Error, return error unchanged; otherwise set `Cache-Control: private, no-store` on the successful REST response. No DB/schema/central governance modification required.
- Corrected source/test SHA `55d935b74915db146b44593e61985f62e4842fd4`; GitHub Actions `39138911693` completed SUCCESS with 4/4 jobs (Source Integrity; PHP syntax 7.4 & 8.3; reproducible package).
- The regression verifies error propagation on both `lsch_note_read_failed` and `lsch_note_authentication_failed` and no-store success behavior. **WordPress integration and live re-test remain unverified.**

## Product scope / existing incomplete work (NOT newly discovered source defects)
1. Re-consent following case-consent withdrawal has no approved append-only lifecycle with schema/retention/migration. Needs File08/File24 + Founder approved change governance.
2. Mentor feedback history beyond 100 records requires governed append-only archive capacity with export/retention and schema rollback approval.
3. The eight Founder works are scaffold/seed slots, not verified real complete editions and chapters.
4. Real WordPress save-hook/scheduled post behavior, File19 publication notices, File21 feed, File22 composer, File25 rendering, File26 indexing, File00/08/09/12 companion installed parity, DB migration atomicity/backup-restore, PHP+WP staging, RTL, accessibility and performance acceptance remain unverified.
5. Central consolidated document includes historical row for `Education Member — PKR 400/month` but a later feature directive `CV-141` calls for current approved learning fully free; File05 implementation follows the later single-free-tier governing policy. A documentation reconciliation of superseded historical rows may be needed, **not proof of a coding defect**.

## Exact-state and acceptance ledger
- Main: `e2667d69d43d655e3d86b0a358dec5f28b61dd96`.
- Corrected source/test SHA *before report write*: `55d935b74915db146b44593e61985f62e4842fd4`. Source-only CI PASS 4/4: `39138911693`.
- This report and subsequent STATUS update create newer SHAs that must have their own exact-head CI result before final attribution.
- Draft PR #4 unmerged; **NO main merge, production/staging deployment or live DB changes performed.**
- Plugin source-declared version: `4.0.0`; source expectations Core18/Aux3/Future18 2, not deployed evidence.
- **Repository HEAD**: exact latest branch SHA to verify after documentation.
- **Deployed Version**: UNVERIFIED.
- **DB Version**: UNVERIFIED.
- **Migration State**: UNVERIFIED.
- **Live Verification Status**: NOT PERFORMED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔

**Counts:** 40 focused source/plan checks; one newly proven independent source defect (C9-D01), one corrected in draft PR with red-to-green mandatory regression; three initially unmatched source anchors all triaged (none separately proved source defects); two known design-gated missing capabilities and substantial external staging/live acceptance outstanding.