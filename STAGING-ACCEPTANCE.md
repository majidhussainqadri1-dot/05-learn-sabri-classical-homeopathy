# File 05 v1.0.0 RC1 — Mandatory Staging Acceptance

## Stop-the-line rule

Any defect, regression, privacy/security concern, incomplete workflow, failed integration, or missing evidence blocks merge and production. Correct the defect, rebuild the exact package, repeat all affected gates, and record new evidence.

## Evidence header

Record before testing:

- Corrective commit SHA.
- Release ZIP filename and SHA-256.
- Canonical source-tree SHA-256.
- WordPress, PHP, database, web server, theme, and active-plugin versions.
- Staging URL and test date.
- Tester identities and roles.
- Backup identifier and restore verification.

## Gate A — Artifact custody and backup

1. Download the GitHub Actions artifact from the accepted corrective commit.
2. Verify the SHA-256 sidecar before extraction or installation.
3. Confirm the ZIP has one root folder: `sabri-learning/`.
4. Confirm 21 plugin files and 15 PHP files.
5. Take a complete database and filesystem backup; prove that restoration starts successfully.

## Gate B — Mandatory dependencies

1. Verify File 00 1.0.1+, File 01, and File 20 1.0.0+ are active and healthy.
2. Verify File 01’s `spf_page_map['learn']` references a valid, non-trashed page.
3. Deactivate each mandatory dependency separately and confirm File 05 pauses without public partial output, database writes, or fatal errors.
4. Restore dependencies and confirm File 05 resumes only after contracts are available.

## Gate C — Installation and migration

1. Test clean activation on a fresh staging clone.
2. Test upgrade over File 05 v0.1.0 with existing books, lessons, progress, bookmarks, audit rows, and view metadata.
3. Confirm schema version 2 and all six File 05 tables.
4. Confirm legacy views migrate to the metrics table without decreasing counts.
5. Confirm legacy lesson states and row versions are populated exactly once.
6. Force a controlled migration failure and verify safe pause, failure record, no false success notice, and recoverable retry.

## Gate D — Roles and File 00 authority

Test administrator, Founder, verified doctor, suspended/revoked doctor, student, patient, Editor, and anonymous user.

1. Only administrators hold File 05 manager/reviewer and custom CPT capabilities.
2. Generic Editors cannot edit, publish, delete, or privately read File 05 books or lessons through wp-admin, REST, XML-RPC, direct URLs, or crafted requests.
3. Founder and learning administrators publish directly.
4. A currently verified File 00 doctor submits only to pending review.
5. Suspension, revocation, missing 2FA, or removed doctor verification immediately blocks new submission and blocks approval of an existing pending submission.
6. No File 03 role or helper can restore eligibility when File 00 rejects it.

## Gate E — Page ownership and File 20 shell

1. Confirm the File 01 Learn page renders `[sabri_learning]` inside the File 20 shell.
2. Confirm there is one global header/navigation and no nested `<main>` landmark.
3. Create unrelated pages with `my-learning` and `submit-learning-lesson` slugs; activate File 05 and prove they are not adopted or modified.
4. Confirm File 05-owned pages have ownership metadata and exact shortcodes.
5. Confirm desktop, tablet, and mobile shell layouts do not duplicate navigation or overflow.

## Gate F — Submission and image security

1. Submit valid lessons through Founder, administrator, and verified-doctor accounts.
2. Bypass browser validation and prove the server rejects blank title, summary, content, objectives, references, topic, level, and medical confirmation.
3. Test invalid book IDs and classifications.
4. Test JPG, PNG, and WebP at boundaries; reject wrong MIME, renamed scripts, polyglots, malformed images, dimensions outside limits, more than 40 megapixels, and more than 5 MB.
5. Configure the `slc_image_security_scan` filter to reject and confirm the entire lesson transaction rolls back without orphaned posts or attachments.
6. Confirm submission and comment rate limits return HTTP 429 without affecting other users.

## Gate G — Patient Case Learning and consent

1. Prove patient-case submissions require anonymization, consent source, evidence reference, scope, and the current policy version.
2. Confirm reviewers cannot approve a case with missing or withdrawn consent.
3. Withdraw consent and prove the lesson becomes private/hidden, audit records the transition, and public/cache access is denied.
4. Verify no patient evidence reference appears publicly, in page source, structured data, logs, browser storage, or APIs.

## Gate H — Moderation state machine

1. Verified-doctor submission enters `submitted`/pending.
2. The author cannot review their own submission.
3. Approval, rejection, and hiding follow only allowed transitions.
4. Rejection and hiding fail without a note.
5. Open the same lesson in two reviewer sessions; the second stale decision must receive HTTP 409.
6. Force `wp_update_post()` failure and confirm no success audit event is written.
7. Confirm the audit record includes actor, action, from/to states, note, request ID, and UTC time.

## Gate I — Learning privacy and metrics

1. Anonymous users may read public lessons only where File 00’s global policy permits.
2. Progress, bookmarks, and quiz scores are visible only to the owning authenticated user and authorized privacy export.
3. Draft, private, deleted, or wrong-post-type IDs never appear in dashboards or counts.
4. Continue Learning excludes completed lessons.
5. Completion never exceeds 100 percent.
6. Concurrent view requests increment atomically; Popular order follows the metrics table.
7. Catalog pagination preserves search and filter parameters and exposes valid accessible links.

## Gate J — Privacy, retention, and uninstall

1. Export includes progress, bookmarks, authored lessons, workflow state, classifications, and consent records.
2. Erasure removes progress/bookmarks and unpublished submissions.
3. Non-case published lessons retain editorial integrity with personal attribution removed.
4. Patient-case lessons are hidden and consent marked withdrawn when evidence is erased.
5. Active legal hold retains governed records and reports retention accurately.
6. Ordinary uninstall retains data and records retention.
7. Destructive uninstall occurs only when both `SLC_PURGE_ON_UNINSTALL === true` and `slc_allow_destructive_uninstall === yes` are present.
8. Test ordinary and destructive uninstall on single-site and multisite staging.

## Gate K — Accessibility, localization, performance, and compatibility

1. Keyboard-only operation for forms, filters, moderation, quiz, actions, and pagination.
2. Screen-reader landmarks, labels, live regions, focus order, and error messages.
3. 320px, 375px, 768px, 1024px, and 1440px viewport checks at 200 percent zoom.
4. Reduced-motion and high-contrast checks.
5. Translation extraction and a non-English locale smoke test while en-US remains the first-release content standard.
6. LiteSpeed/page-cache tests prove private dashboard and nonce-bearing output are not publicly cached.
7. PHP 7.4 and 8.3, WordPress 6.1 through current staging version, and the production theme.

## Final acceptance record

Record PASS/FAIL for every numbered item, attach screenshots/logs/database evidence, list every defect and corrective commit, and sign the final decision. Only an all-PASS record authorizes moving the corrective PR out of Draft.
