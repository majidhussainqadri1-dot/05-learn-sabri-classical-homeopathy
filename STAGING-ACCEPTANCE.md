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
