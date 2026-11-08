# File 05 — Fifth Independent Twenty-Focus Review — Cycle E (2026-10-11)

## Frozen repository truth and governing documents

**Baseline exact PR #4 HEAD:** `c1c072a3754bc51f404f1bd5188bf4f4ec21a58b`, Draft branch `codex/file-05-complete-v3.0.0`, prior `main` `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. Nineteen actual PHP implementation files (~402,334 characters) were fetched at that exact source revision. Governing comparison: File 05 v1.1 Future-18 Master Plan 2026; the Three Central Plans Consolidated Governing Master Plan 2026; File 06 knowledge and File 12 reading integration plans and the canonical cross-module matrix.

**Important scope:** These are 20 fresh, distinct *focused source inspections*, not 20 staging/end-to-end executions. Four prior cycles have separate evidence records. All twenty focused checks were completed and findings consolidated *before* implementing source fixes. A path being present is not confirmation of complete feature behavior, and CI does not establish production deployed parity.

## All twenty reviews on the frozen baseline

| # | Focus / governing connection | Outcome |
|---:|---|---|
| 1 | Canonical content access enum, missing and malformed legacy meta | Access fail-closed protections from Cycle C found |
| 2 | Parent curriculum / book access versus published public child lessons (File05, File20/25/26) | **E01:** child can be publicly indexed/read although governed course/book parent is restricted, unpublished or in restricted program |
| 3 | Patient-case lesson topic lookup and consent error (File05, File24) | **E02:** taxonomy query `WP_Error` can bypass special-case consent check in `can_read_post` |
| 4 | File00 approved user, guardian and versioned identity contract | Native assertions present; live File00 version unverified |
| 5 | File20 shell with cache, master page and protected user learning routes | Native private-header hooks present; CDN runtime pending |
| 6 | File06/10/12/15 knowledge and File25 related-read contracts | Owner-driven visibility responder present; real plugin responder pending |
| 7 | File26 public search index ACL, owner visibility and deletion | Public-only native visibility hooks present; real search index pending |
| 8 | File16 source-grounded tutor and prohibition on delegated clinical care | Education-only grounding code present; actual provider tests pending |
| 9 | File17 community projection: zero viewer ID versus logged-in ambient user | Source fix from Cycle D found; partner runtime pending |
| 10 | File19 outbox/audit event delivery and safe handling of errors | Event producer/retry paths present; dependent consumer pending |
| 11 | File22 composer, lesson publication versioning and independent review | Native save/version and review hooks present |
| 12 | Assessment deadline, max attempt, lock, idempotency and learner owner | Service controls present; adversarial DB staging pending |
| 13 | Correction governance: proposer/reviewer, stale version, compensation | Native state machine present; browser/DB concurrency pending |
| 14 | File24 private study note keyring/AES-GCM, decryption and policy | Note secrecy source present; real key rotation pending |
| 15 | WordPress privacy export of third-party consent withdrawal and staff assignment references | **E03:** `SELECT *` with `created_by OR withdrawn_by` (or `user_id OR assigned_by`) can expose another subject's `evidence_reference` or staff `scope_json` |
| 16 | Future18 bounded private mastery, review, pathway and actor-only export | Existing source protections present; actual WordPress privacy export pending |
| 17 | Future18 legal hold and actor erasure | Existing policy flow present; exact live retention scopes pending |
| 18 | File19 outbox / File05 background job worker DB fetch failures | **E04:** `foreach` on failed `get_results()` instead of safe error path in both worker loops |
| 19 | Daily reconciliation and historically auditable case consent | **E05:** housekeeping `DELETE c FROM ... case_consents` discards historical consent/withdrawal evidence if a lesson is deleted/retagged, contrary to archival source/compensation controls |
| 20 | Source schema/migration, backups, rollback, outbox and safe-mode File24 controls | Install/upgrade/runbooks exist; actual WordPress DB/runtime unverified |

**Review outcome:** 20 new source-level focused checks completed; five confirmed defect classes (focus rounds 2, 3, 15, 18, 19). No proven new omission in the remaining 15 scoped paths; no inference that all platform code is complete.

## Corrective batch after all reviews

### E01 — Canonical ancestor permissions
Source: `class-lsch-policy.php`. A published `public` lesson previously ignored restrictions/publication status on `_lsch_course_id` and `_lsch_book_id`; a course ignored `_lsch_program_id`. New `governed_parents` checks all present, typed native owner references. Public indexing recursively requires public approved ancestors; scoped authenticated reads require the current actor can read each ancestor. A per-request stack fails closed on cycles. The existing standalone public/legacy-lessons path remains.

### E02 — Failed topic lookup must not waive patient-case safety
Source: `class-lsch-policy.php`. The former condition `!is_wp_error($topics) && in_array(...)` allowed published lesson read when taxonomy lookup failed. Updated to fail closed to general readers; only an actual authorized `edit_post` reviewer may inspect content for correction. Public view remains fail closed.

### E03 — Consent/staff actor-only personal-data projection
Source: `class-lsch-privacy.php`. Complete consent data is now exported only for `created_by=user`; full staff scope only for `user_id=user`. Distinct allowlisted actor-only projections are used for `withdrawn_by=user AND created_by<>user` and `assigned_by=user AND user_id<>user`. Third-party `evidence_reference`, `scope_json`, personal/protected fields stay hidden. Prior assessor and correction-reviewer protections remain.

### E04 — Queue read failures
Source: `class-lsch-operations.php`. Outbox and job workers check array results from the database before iteration; DB read outage yields bounded audit event plus an early return (no subsequent unsafe state changes), leaving durable retry entries intact.

### E05 — Historical consent destruction by housekeeping
Source: `class-lsch-operations.php`. Removed the unconditional daily cron deletion of case-consent rows after a learning lesson disappears/changes type. Consent and withdrawal evidence remains for the native audited retention/approved File24 lifecycle instead of silent unrelated cleanup.

## Regression suites

- `tests/parent-access-consent.php`: restrictive course, program and book parent scenarios; staff-allowed scoped learning; topic query failure, reviewer-only correction; healthy standalone public lesson.
- `tests/core-privacy-projection.php` expanded: actor-owned staff/consent projection, no cross-subject `evidence_reference` or `scope_json`.
- `tests/queue-and-consent-retention.php`: failed DB reads in both workers, no state writes on failure, audit events; daily reconciliation never destroys historical consent evidence.
- `tests/source-invariants.sh` required runner now runs the two new PHP contracts and the extended privacy suite.
- Existing source assertions/security policy, Future18 features, PHP 7.4 / PHP 8.3 and reproducible release package remain mandatory.

Evidence threshold: code/test HEAD `8d8bf71e3020fe334a596cfc5c5610ab56ed14d3`; automated actions `38124339953` (check final status). The final documentation HEAD must have **its own** full success status.

## Critical next staging gates

Test on **exact deployed WordPress/Staging version** with saved public lesson in a restricted course, revoked/unpublished course, mismatched/missing foreign references, restricted program, and approved restricted viewer. Test actual taxonomy failure injection and patient-case consent. Test privacy data request as withdrawal actor and staff assigner for third-party data, including query failures, pagination and legal holds. Inject MySQL errors into outbox/job retrieval to verify no PHP warnings/no poison queue; test retry/dead-letter and File19 delivery. Verify consent evidence is preserved by nightly reconciliation, correct retention/purge File24 procedure, and WordPress backups.

Cross-owners File00/06/10/12/15/16/17/19/20/22/24/25/26 remain separate realities; partner adapter implementations and live versions are NOT proven by presence of File05 hooks.

## Mandatory reality registry

- **Repository HEAD:** Use latest PR #4 head at exact final test date, source review baseline `c1c072a3754bc51f404f1bd5188bf4f4ec21a58b`.
- **Source plugin version:** `4.0.0`.
- **Expected schema markers:** core 18, auxiliary 3, Future18 2.
- **Deployed Version / deployed artifact checksum:** unverified.
- **DB Version / row-level status:** unverified.
- **Migration state:** unverified.
- **Live Verification:** not performed.
- **Merge/deploy:** none; Draft PR #4 remains unmerged.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
