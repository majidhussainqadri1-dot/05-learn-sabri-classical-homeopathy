# File 05 Corrective Review — v1.0.0 RC1

## Decision

The immutable `0.1.0` baseline remains evidence only. Corrective development is isolated on `audit/file-05-source-review`. This branch is not authorized for merge, staging acceptance, production, or live deployment until automated checks and the complete WordPress staging protocol pass.

## Closed source-review findings

1. **Generic WordPress post capabilities** — replaced with File 05-specific book and lesson capability maps. Legacy `manage_sabri_learning` is removed from roles during migration; only administrators receive the new manager/reviewer capabilities.
2. **Non-authoritative doctor checks** — File 00 is mandatory and is the sole source for account type, status, identity, doctor, email, mobile, 2FA, and approval-version eligibility.
3. **Missing dependency failure behavior** — File 00, File 01, and File 20 contracts are checked before activation and runtime. Missing contracts pause File 05 instead of partially booting.
4. **Global comment-registration override** — removed. Login and moderation rules now apply only to File 05 lessons.
5. **Page-slug hijacking** — managed pages are accepted only by stored ID, File 05 ownership metadata, and exact shortcode. A conflicting foreign slug is never adopted.
6. **Unsafe activation lifecycle** — preflight, checkpoint state, idempotent schema installation, created-content rollback, explicit failure state, and safe deactivation are implemented.
7. **Client-only required fields** — title, summary, content, objectives, references, classification, and medical notice are enforced server-side.
8. **Incomplete moderation state machine** — self-review is prohibited; transitions are explicit; reject/hide notes are mandatory; author eligibility is rechecked; optimistic row-version locking prevents stale concurrent decisions; post-update errors stop audit success.
9. **Boolean-only patient consent** — version, source, subject type, scope, evidence reference, confirmation time, withdrawal time, and withdrawal actor are persisted. Withdrawal hides the case lesson.
10. **Incomplete privacy integration** — progress, bookmarks, lessons, and consent records are exported; erasure removes private activity, deletes unpublished submissions, anonymizes retained editorial records, honors legal holds, and hides patient-case lessons when consent evidence is erased.
11. **File 20 shell bypass** — hard-coded platform navigation and nested `<main>` output were removed. File 05 exposes the `sabri_learning` integration shortcode and cooperates with the File 20 layout filter.
12. **Incorrect progress/privacy boundaries** — dashboard, bookmarks, scores, Continue Learning, and level totals join only currently published lessons and cap overall completion at 100 percent.
13. **Missing catalog pagination and misleading search** — lesson-only wording and preserved-filter pagination are implemented.
14. **Racy view counter** — replaced with an atomic `INSERT ... ON DUPLICATE KEY UPDATE` metrics table and a controlled popularity join.
15. **Incomplete image controls** — server verifies upload state, size, MIME/extension agreement, dimensions, pixel count, active-content signatures, and an extensible scanner result; post creation rolls back on image failure.
16. **Unsafe quiz DOM output** — dynamic result output uses `replaceChildren`, `textContent`, and text nodes rather than `innerHTML`.
17. **Translation foundation** — text domain loading and translation functions cover the corrected user-facing interface.
18. **Unverifiable tree hash** — `scripts/source-tree-hash.py` defines and reproduces the canonical sorted per-file SHA-256 tree algorithm.
19. **Mutable GitHub Action references** — corrective CI pins checkout, setup-php, and upload-artifact to full commit SHAs.
20. **Uninstall and staging gaps** — uninstall is retention-first and destructive only with two explicit controls; multisite is handled; a complete staging acceptance protocol is included.

## Remaining evidence gates

- GitHub corrective CI must pass on PHP 7.4 and PHP 8.3.
- The release ZIP must be byte-identical across independent builds.
- Real WordPress staging must pass dependency, migration, role, workflow, consent, privacy, upload, shell, accessibility, caching, responsive, rollback, and uninstall gates.
- Baseline PR #1 and corrective PR #2 must remain unmerged until these gates are accepted.
