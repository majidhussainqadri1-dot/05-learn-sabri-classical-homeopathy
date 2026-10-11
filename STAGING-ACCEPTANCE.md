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
