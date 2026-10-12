# File 05 — Mandatory Hostinger/WordPress Staging Acceptance

Repository source, CI and a deterministic ZIP do not prove real runtime correctness. Production deployment is blocked until every applicable gate below is evidenced on the actual staging environment.

## 1. Reality freeze and backup

- Record exact deployed/staging File 05 version, File 00 contract/runtime, File 01, File 20 and every companion provider used by File 05.
- Record WordPress, PHP, database, LiteSpeed/object-cache configuration and relevant feature flags.
- Record File 05 core schema and auxiliary state schema from the database.
- Create database/files/key/config backup and prove isolated restoration before migration.

## 2. Fresh install and upgrade

- Fresh install of the exact release ZIP; plugin header/readme/package/checksum parity.
- Upgrade from the actual staging/production-like predecessor, including legacy SLC/LSCH posts, progress, bookmarks and private notes.
- Verify schema idempotency, migration checkpoints, concurrent activation behavior and no unauthorized foreign data mutation.
- Configure independent note keyring; verify legacy-note decrypt and bounded re-encryption to current key generation.
- Deactivate/reactivate and non-destructive uninstall behavior.

## 3. Real-role journeys

- Guest public catalog/lesson reading where eligible, with no protected-state leakage.
- Approved adult learner enrollment, prerequisites, progress/resume/reset/export, bookmark, encrypted note, assessment, assignment, appeal and completion.
- Ineligible/suspended/guardian-blocked account denied at the protected action with no side effect.
- Founder/curriculum lead/teacher/assessor/reviewer scope and conflict rules.
- Correction proposal → needs information → resubmit → independent decision → content version → learner `needs_review`; withdrawal and stale-content conflict paths.
- Completion and certificate-readiness correction/revocation behavior.
- Saved learning search and File 26 discovery handoff without File 05 claiming global ranking.
- Citation exports, File 16 source context, File 12/06/10 related references and account learning-record export.

## 4. Integration failures

Validate real Files 00, 01, 06, 10, 12, 15, 16, 17, 19, 20, 21, 22, 24, 25 and 26 as applicable. Exercise missing, incompatible, malformed, stale and partially available provider states. Public-safe reading may degrade safely; protected actions must fail closed.

## 5. Security/privacy/reliability

- Role/object/field IDOR matrix, CSRF/nonces where browser, replay/idempotency, rate limits and privilege loss mid-request.
- Private DTO/noindex/no-cache behavior, export, erasure, legal hold and pseudonymization.
- Note key loss/rotation/recovery; no plaintext or key leakage in logs/errors/backups.
- Concurrent outbox workers, queue retries/dead states, stale job recovery, correction application recovery, database failures and disk/provider failure injection.
- Secret/supply-chain scan and package manifest/SBOM verification.

## 6. UI/accessibility/performance

Test 320, 375, 768, 1024, 1440 and 1920 widths; Urdu/Arabic RTL and English LTR; keyboard-only; screen reader; visible focus; 200%/400% zoom; reduced motion; long labels; slow/offline connection. Measure representative p75/p95 page/API performance and confirm no cross-user private caching.

## 7. Rollback and sign-off

- Rehearse code rollback with schema/data compatibility and preserve valid post-cutover records.
- Restore backup in an isolated environment and verify counts, hashes, key decrypt, cache/index rebuild and representative roles.
- Record technical, security/privacy, academic/medical, accessibility/visual and Founder approvals.

Only after these gates pass may status advance from `Automated-QA Green` to `Staging-Accepted`. Live deployment and operational acceptance remain later, separately evidenced statuses.

## Future-18 staging acceptance matrix

Before Future-18 may be called staging-accepted, test all 18 IDs with real Member/Doctor/Teacher/Assessor/Founder roles; suspended/guardian states; IDOR and self-assessment negatives; mastery concurrency; spaced-review scheduling; flashcards; all eight labs; File06/12/15 references; File16 citation/no-provider/failure paths; mentorship scope; verified vs self-recorded CPD; correction-impact fan-out and File19 event projection; privacy export/erase; RTL/mobile/keyboard/reduced-motion; weak connection; backup/restore and rollback. No live claim follows automatically.

## 8. 2026-10-11 privacy regression staging gates

- Seed a learner with a persisted `pathways` Personal Learning Prescription, including a second page (more than the export page limit), then run WordPress's native personal-data export. Verify all saved plans are included, with no duplicates or omissions and consistent `done` pagination.
- Seed assessor A grading learner B's Future18 practice, and verifier A validating learner B's CPD. On A's export, verify actor-only projections are present but learner B's private answer, CPD evidence and other personal data are not exposed.
- On A's legal-hold-free erasure, verify the `assessor_id` and `verified_by` links in learner B's records are de-identified, while learner B's academic results remain available under the owner policy. Confirm retained-data messages are truthful.
- Repeat with a verified active legal hold; identities and governed records must remain preserved and visibly reported until lawful retention expires.
- Inject database read/write failure during these privacy operations: no false success, cross-learner disclosure or silent untracked partial erasure. Check retry/operator process, backup, restore, and audit without raw private data in logs.
- Validate File00 consent/eligibility, File24 privacy/retention policy, and WordPress core eraser/exporter runtime. Do not mark this area staging-accepted from static or stub-based CI alone.

## 9. 2026-10-11 second twenty-focus regression staging gates

- Seed public, account, restricted and unpublished File05 books, programs and courses. View the public home `/learn/` as guest/member/teacher/Founder (including cached visits). No nonpublic title, excerpt, cover or link may appear, regardless of the visitor's author/edit permissions.
- Seed public, account and restricted lessons of one public course. Compare guest and approved member course outlines. Verify real WordPress `Cache-Control`/noindex headers, File20 render integration, and LiteSpeed/CDN caches never serve one member's account/restricted lesson links to a guest or another member.
- Confirm a restricted object is absent from public WordPress search, archives and File26 indexed public suggestions; a member-eligible learning-specific read is no-store and every File26 referral rechecks File05 native visibility.
- Verify `/learn/dashboard/`, `/learn/mastery/`, assessment, assignment and cohort surfaces are private, noindex, and not shared across roles/guardians/suspended users.
- Change a learning lesson's content exactly once using native WordPress and File22 authoring; ensure its `_lsch_version` increments once, one content-version event is produced, and affected learner progress/re-study is updated only once. Repeat with metadata-only edit and retry/correction events.
- Verify real File00/06/12/15/16/17/19/20/22/24/25/26 compatibility before any staging signoff. Run failed migration/rollback, user role state changes and concurrent edits under actual MySQL/PHP.

## 10. 2026-10-11 Cycle C — access declassification and privacy-failure acceptance

- Create File05 content with public, account, restricted and empty legacy `_lsch_access`. Inject malformed saved meta (`private-unknown`, `public!`, invalid type/array, whitespace, case-confusable text). None of the *explicitly invalid* values may be visible to unauthorized guests through native pages, archives, File05 REST, File20/25 public cards, File26 global index or cache.
- Submit an administrative governed metadata update with an invalid string, an array `_lsch_access[]=public`, then a valid enum. Verify fail-closed invalid input does not change the prior classification or content version, while the authorized valid transition persists with the correct version/audit trail.
- Keep real intended legacy missing-meta public lessons readable by guests while current File00 entitlement/guardian/suspension gates apply to account/restricted objects.
- Simulate MySQL failure in each of the ten Future18 privacy export datasets. The WordPress privacy exporter must signal failure, never mark the export done or silently omit records. Confirm HTML/JSON and PHP7.4/PHP8.3 behavior without raw data leakage.
- Simulate all core and Future18 privacy erase failures and legal-hold scenarios. A failed operation must not return `done=true`; successful retry finishes, and legally retained records remain auditable. Verify no processing loop, duplicate side effects or erroneous deletion of another learner's academic record.
- Repeat on exact deployed staging version/schema, and capture backup/restore, File24 policy owner approval and WordPress export/erase workflow evidence.

## 11. 2026-10-11 fourth 20-focus review (Cycle D): privacy and foreign-owner visibility gates

- Register a versioned, actual canonical File06/10/12/15 `lsch_related_learning_reference_visible` owner responder. Seed an approved related entry, revoke its visibility/unpublish/withdraw consent, and verify File05 lesson REST, File25 public cards and File26 search never publish the stale target URL. Missing/incompatible/responder error must degrade safely with no link leakage.
- Test guest/member/Doctor/Teacher/Assessor/Founder with identical public File26 search queries. A logged-in elevated author may not enrich public projections. Check guest File16 educational AI and File17 community context called in an elevated session with explicit `viewer_id=0`; both must remain public-only.
- In WordPress privacy export, use assessor A grading learner B's submission containing sensitive `body`, `attachments_json`, `appeal_text` and `feedback`, and reviewer A processing learner C's correction `reason` and `source_reference`. Export A's personal data: actor-only status/version/date but none of B/C's protected fields; export B's/C's own submissions/proposals normally.
- Test the privacy exporter over the 100-item page boundary, database-read errors and failed partial export, guest/expired/suspended entitlements, legal hold, lawful erasure, audit-trail privacy and no secret leakage in logging.
- Confirm File00 assertions, File20/25 display and caching, File24 retention/consent, File26 global discovery and File06/10/12/15 owners with the **exact staging-deployed versions**. Confirm source/plugin ZIP hash, database migrations and rollback. Static PHP fixture/green CI is not Staging-Accepted.

## 12. 2026-10-11 Cycle E — hierarchical access, actor privacy, queues and consent retention

- Create a public File05 lesson inside a restricted or unpublished course; link the course to a restricted program and/or lesson to an unpublished founder book. Verify no guest leakage by File05 native URL, REST, File20/25 card, File26 search and File16/17 public projection. Approved staff or enrolled actors must have precisely their documented parent authorization, not just child authorization.
- Force WordPress `wp_get_object_terms()` error for a published case-study lesson and prove guest access is denied; an authorized editor may inspect only to repair. Retest valid active and withdrawn case consents after error recovery.
- Seed consent record X created by another reviewer but withdrawn by user A; seed staff assignment Y owned by another staff member but assigned by A. Export A's personal data: only allowlisted actor fields (no third-party patient evidence reference or other employee private scope), while actual owners retain their rightful full-data exports. Check pagination, File24 retention and erasure.
- Inject MySQL `get_results` failures separately for outbox and jobs. No PHP warning, job loss, partial worker transaction or false completion may occur; verify audit and operator retry with File19 consumer healthy/unavailable, queued and dead-letter paths.
- Delete/unpublish/retag a previously consented case lesson; run daily reconciliation and confirm historical consent/withdrawal audit row preserved with File24 governed retention and backup/restore. No public content or patient evidence may leak via orphaned record.
- Capture **exact staging active package checksum, plugin/schema/options/migration state and companion runtime versions**. Source tests alone cannot mark this acceptance complete.

## 13. Sixth File05 review (2026-10-11) — malformed prerequisites, REST caching, key rotation, consent renewal

- Test stored and admin-form prerequisites: `abc`, `4,abc`, `4,,5`, `0`, `04`, `4,-5`, `4,2.5`, self-reference; no invalid course setting may allow enrollment. A truly empty prerequisite and fully earned current-version prerequisites must work; test real teacher and member roles.
- In WordPress staging with File20/25 and File26, create a public lesson nested under restricted/unpublished course or book, and a public child in a restricted program. An authorized user may get a protected lesson, but `Cache-Control` must be `private, no-store` and CDN must never replay its content to a guest. Truly public guest lessons remain cacheable, but active protected viewers may not share their personalized related links.
- Inject database read failure and verification count error into File05 private-note File24 key rotation. No false success, unknown remaining count, no destructive fallback; restore keyring and database, retry safely and validate actual decryption of earlier note versions.
- After consent withdrawal, attempt a second `store_case_consent` with the same lesson and current policy. Must return conflict, preserve `withdrawn_at`, `withdrawn_by`, evidence and no new public case publication. A new genuinely authorized append-only re-consent workflow needs a separate approved spec/schema/versioned migration, test and rollback; mark pending rather than falsely claiming completion.
- Verify exact deployed plugin checksum/version, WordPress/PHP/database schema and migration state; all other File00/06/10/12/15/16/17/19/20/22/24/25/26 companion contracts at real runtime before staging signoff.

## 14. 2026-10-11 seventh review (Cycle G) — database failure-injection and health evidence

- File05 enrollment: make initial insert succeed, then force final get_row failure. The action must return an explicit verification error, avoid false Created event, preserve the persisted enrollment and require operator reconciliation/idempotent retry. Repeat with successful reread.
- File05 REST catalog on WordPress: inject WP_Error/nonarray from LEVEL or TOPIC lookup and ensure no malformed JSON/source data or cacheable success. Retest healthy typed level/topic lists with File25 cards and File26 search consumer.
- File05 private saved searches: force false/null get_results; expect explicit error and no false empty list or PHP warning. Test normal owned per-user list recovery.
- File24 WordPress privacy tools: fail the retained academic-record COUNT after actual anonymization. The callback must set done=false, conservatively mark retained evidence and require operator retry. Test File24 hold with real records.
- File19/24 System Check: force false/null dead outbox, dead job and encrypted-note counts individually. Overall health must be fail; then validate true 0 counts pass and true nonzero counts warn, without touching production data.
- Capture exact deployed package checksum, active plugin version, DB/options/migrations, external File00/06/12/16/17/19/20/22/24/25/26 versions and runtime logs before claiming any staging acceptance.

## 15. 2026-10-11 Cycle H — learning eligibility and institutional analytics release gates

- File05 lesson `_lsch_required_components`: test legitimate absent legacy value, valid approved combinations, malformed JSON, duplicates, bogus assessment name and empty `[]`. No malformed nonempty configuration may produce 100% completion or earn course completion. Test actual WordPress metadata/editorial workflow and File22 corrections.
- Inject DB `get_var` false/null into assessment `MAX(attempt_number)`; no new attempt may be allocated, lock must release, no false attempt event may be emitted. After DB recovery, normal idempotent retries must work without bypassing maximum attempt restrictions.
- Inject WP_Error into course COMPETENCY taxonomy at the exact point all lesson evidence is complete. Do not write any new earned completion row; no File19 CourseCompleted.v1 event. Test valid current competency list recovery and File26 projection/index rechecks.
- Update assessment questions/versions after a formerly passing student attempt and keep old score data. Certificate Readiness must be blocked until a new governed passing current-version attempt; verify File00 identity and wording gate remain enforced.
- Inject failures separately into enrollment COUNT, completed COUNT, active COUNT, contributor COUNT and average percent in educator analytics; do not present deceptive zeros. Test true zero, 1–4 suppressed cells, >=5 legitimate metrics, and non-admin forbidden access in File25/26 projections.
- Capture exact active deployed package/version/checksum, File05 source-to-DB core/aux/Future18 schemas, `wp_options`, migrations, File00/06/12/16/19/20/22/24/25/26 deployed partner versions, backup/restore and real live/staging logs. Source CI alone is insufficient.

## 16. 2026-10-11 Cycle I — private note, assessed evidence and maintenance DB faults

- In a backup-restorable WordPress staging copy at the verified exact deployed File05 package, first record an AES-GCM encrypted personal note; force a subsequent DB get_row failure and assert explicit retryable error, not an empty note. Verify a genuinely absent note returns version 0.
- Start an assessment with an inserted attempt but injected failed final DB reread: return unverified/503, audit the error, and check no phantom success. Reconcile already-inserted row under the same idempotency key before retry.
- Submit a graded attempt; force its post-update read to fail: forbid AssessmentSubmitted.v1 confirmation, preserve stored evidence, check no accidental repeat attempt on retry. Restore DB and verify a single eventual event.
- Force a reviewer correction inbox get_results failure; no array_filter error, no false empty inbox, and no other person's correction content in the actor projection. Repeat with a course completion evidence DB read failure: 503 and no fictional academic-state conclusion.
- Fail each daily maintenance SQL write, correction queue get_results read and correction status update separately. Confirm lsch_last_reconcile does NOT advance after any failure, error is actionable, retry resumes safely, and no historical consent/withdrawal evidence is deleted. Test partial-step backup/restore and File19 retry/dead-letter policies.
- Before calling any staging or live defect Resolved: capture deployed plugin version/source checksum, all File05 core/aux/Future18 DB schema versions/rows, wp_options, migration state, runtime logs, neighboring deployed plugin contracts, then exact artifact parity and live re-test after authorized deployment.

## 2026-10-12 — File05 Cycle J staging gates for prerequisite, academic and privacy resilience
- Before live work, freeze actual deployed File05 package/version/checksum, WordPress runtime, File00/06/12/16/17/19/20/22/24/25/26 deployed dependencies, wp_options, active core/aux/Future18 schemas, migration state and production logs. GitHub code alone is not deployed evidence.
- Prerequisite ledger: force false query or null plus DB last_error while a legitimate Founder-approved grandfather filter exists; verify no unauthorized learner enrollment, and healthy explicit exception behavior with audited documentation.
- Progress: fail assessment and assignment MAX(score) independently; verify no false 0/100%, no completion event, no credential award; test healthy zero graded attempts vs qualifying current-version evidence.
- Quiz: test a mixed valid/missing-answer, null entry, non-scalar correct choice, noncontiguous numbering and empty blueprint. Reject before grade writing; verify all-valid rubric, actual answer feedback, replay/idempotent effects and File19 events.
- Private dashboard: each of 7 SQL data reads can fail independently. REST must return typed error, frontend a safe unavailable message, and learning record export must not embed an error as success. Actual File20/25 cached/index projections never expose private data.
- Future18 File24 legal hold: with populated mastery/review/pathways/portfolio/practice/CPD/impacts and related mentorship records, no deletion under active legal hold; test expiration of hold, authorized rights request, operator ledger, retention policy, reversibility of permitted workflow and backup/restore.
- Saved searches: SQL failure => 503, missing or other-user ID => 404, valid delete => 204. Audit File26 discovery and privacy.
- Learning value metrics: fail actual aggregate event query, require explicit non-success with no false zero metrics. Test appropriate access/aggregation privacy threshold.
- Real WordPress staging and rollback/restore acceptance remain REQUIRED. Following approved deployment, run live retest and verify source/package/database parity.

## 2026-10-12 — Cycle K: File05 persisted-state and private academic evidence acceptance
- Freeze active File05 and counterpart File00/06/12/16/17/19/20/22/24/25/26 packages, versions/checksums and schema/migrations before staging. Current GitHub PR and source CI alone do not establish deployed parity.
- Course transition: allow versioned enrolled->active update then force post-update SELECT to fail or return wrong status/version. Return retryable verification error, no activated event, and reconcile DB row/idempotency before replay; healthy confirmed transition should succeed once.
- Private progress, bookmarks and encrypted notes: independently cause prewrite SELECT to fail. Refuse inserts/toggles/edits; do not mistake unknown existing record for empty. Revalidate actual row once DB recovers, preserve encrypted note ciphertext and optimistic lock.
- File12 protected assignment: allow successful submission insert but fail subsequent row read; never return confirmed success/audit or duplicate a submission. Verify owner and File12 attachment authorization during a real concurrent retry; prove audit and source link remain scoped.
- Reminder preference: commit upsert but fail final read or return wrong enabled/cadence; no premature File19 notification event. Repeat with successful read and event.
- Certificate readiness: fail current-version graded assessment MAX(score), show explicit error rather than misleading zero and verify previous-version scores remain excluded; crosscheck File00 identity assurance, File09 doctors and approved jurisdictional wording.
- Post deployment: WordPress real-role E2E, File24 rights/export/legal hold, actual schema migration/backup restore, cache/replay/queue, rollback, live reread and exact checksum parity. Patient case append-only re-consent remains a separate change needing Founder-approved migration and release process.

## 2026-10-12 — Cycle L File05 prior-row DB outage and Future18 private projection acceptance
- Capture exact staging/live File05 plugin ZIP/source checksum, active version, File00/06/12/16/17/19/20/22/24/25/26 deployed partner versions and contract registrations, DB core/aux/Future18 schema versions, tables/indexes/rows, wp_options, migration state and logs BEFORE diagnosing a live symptom.
- Fail initial existing-enrollment SELECT on otherwise valid course: must return 503 without insert, false enrollment Event or second active course row. Fail assessed-action active enrollment lookup separately, and validate correct 503 vs legitimate inactive-409.
- Fail historical case-consent SELECT with existing withdrawn consent: do not insert, revive or destroy historical evidence. Successful renewed consent still blocked until Founder-approved append-only lifecycle/schema+rollback exists; do not improvise a revocation reset.
- Fail academic completion and current enrollment SELECT during certificate readiness: return explicit unverified academic state 503 rather than “not earned”; test actual incomplete and revoked certificates separately with File00 identity.
- Fail each correction decision/resubmit/withdraw private SQL row query and enforce no transition, proper 503; check genuine not-found 404 and review-scope authorization. Idempotency original-key SELECT outage must yield 503, release database named lock and not allocate conflicting replay row.
- Fail Future18 six private table reads independently (mastery, due-reviews, portfolio, mentorships, CPD and knowledge impacts). Center/learning path cannot embed failed child reads or produce fake empty recommendations. Nine REST handlers must not attempt header() on WP_Error; verify healthy, empty and full records with Cache-Control private/no-store and actual File20/25 shells/File26 search.
- Run real WordPress MySQL failure/recovery, request idempotency, File19 outbox atomicity, DB transaction and backup/restore, File24 privacy/legal hold, real File12 attachment and File06 knowledge changes. Record actual installed checksum and parity, authorized deploy, then live retest before release claim.
