# File 05 — Mandatory Real WordPress Staging Acceptance

Source completion and automated checks do not replace real-environment evidence. Do not merge to production or activate live until every gate below passes on the Hostinger-equivalent staging site.

## Environment and backup

- Record exact WordPress, PHP, database, LiteSpeed, object-cache and companion-plugin versions.
- Create database/files backup and prove isolated restoration.
- Record current SLC data counts and checksum samples.

## Installation and migration

- Fresh install.
- Upgrade from SLC 1.0.0 with books, lessons, progress, bookmarks and consent-linked content.
- Concurrent activation lock/idempotency test.
- Deactivate/reactivate and retention-first uninstall.
- Rollback rehearsal preserving new post-cutover records.

## Real-role journeys

- Guest reads a public lesson without login.
- Unapproved account can read public material but cannot enroll, save, note or assess.
- Approved adult account enrolls, resumes, saves encrypted notes, completes lessons and assessments.
- Minor account is denied protected action until verified guardian context passes.
- Founder publishes directly; verified doctor contribution follows governed review; self-review is denied.
- Assigned assessor grades; unassigned/self/conflicted assessor is denied.
- Correction marks affected progress `needs_review` and preserves old result versions.
- Completion record survives access-model/account-plan changes and supports correction/revocation.

## Cross-file contracts

Validate Files 00, 01, 17, 19, 20, 21, 22, 24, 25 and 26 with real installed versions. Prove fail-closed behavior for missing/incompatible/malformed dependencies without breaking public safe reading.

## UI/accessibility/performance

Test 320, 375, 768, 1024, 1440 and 1920 widths; English and Urdu/RTL; keyboard-only; screen reader; visible focus; 200%/400% zoom; reduced motion; long labels; slow/offline network. Record Core Web Vitals and API p75/p95.

## Operations

Exercise System Check, safe mode, dry-run repair, queue retry/dead state, cache/index purge, privacy export/erase/legal hold, alerts, incident/rollback runbooks and post-deployment monitoring.

## Sign-off

Required explicit, dated approvals: technical review, security/privacy, medical/academic governance, Sharīʿah where applicable, visual/accessibility, and Founder acceptance.
