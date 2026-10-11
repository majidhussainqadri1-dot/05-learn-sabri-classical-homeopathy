# File 05 — Sixth Fresh Twenty-Focus Source Audit — Cycle F (2026-10-11)

## Baseline, basis and scope

**Frozen baseline:** Draft PR #4 / `codex/file-05-complete-v3.0.0` HEAD `e8f0596a527a4957c4c479ff9ec5883408162545`; `main` was `e2667d69d43d655e3d86b0a358dec5f28b61dd96` (README-only baseline). **Actual GitHub source checked:** 19 implementation PHP files (~404,994 characters) from the frozen SHA, not a previously cached package.

Governing specification checked: uploaded `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx`, and `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`. The File05 plan governs eighteen functional, ten non-functional requirements, four governed levels, sixteen learning topics, Future-18, prerequisite integrity, data rights, deletion/revocation, legal hold and exact ownership. The central plan requires no cross-owner direct storage edits and no privacy/cache exceptions.

Method: twenty **distinct, focused code inspections**, *not* twenty WordPress E2E review loops. Code was corrected in the branch only after specific defects had been identified. The twenty-focus registry below records separate source areas, not independent end-to-end tests. Any “present” result means inspected source control found, not a guarantee of runtime completeness.

## All twenty source focus reviews

| # | Source / governing boundary | Finding on frozen baseline |
|---|---|---|
| 1 | Four levels, sixteen topics and competency seeding | Relevant File05 source functions found; actual content audit pending |
| 2 | Founder book slot inventory, chapters and source edition | Native source found; actual published books pending |
| 3 | Public metadata and course/book/program ancestor authorization | Previous cycle's recursive native parent ACL found; staging pending |
| 4 | File00 identity, approved membership, age/guardian claims | Versioned assertion contract found; File00 deployed parity unverified |
| 5 | Course enrollment prerequisites / malformed curriculum setting | **F01:** nonempty garbage/zero/mixed IDs or repeated commas could be silently ignored as optional |
| 6 | Enrollment state machine / optimistic version | Native versioned transitions present; race tests pending |
| 7 | Progress, coursework evidence and earned completion | Native state and completion rules present; DB integration pending |
| 8 | AES-GCM notes, File24 key rotation, DB read/count failure | **F02:** rotation iterated failed `get_results`; missing count cast to zero created false “complete” status |
| 9 | Assessment expiry, attempt limits and replay protection | Native timing/locking functions present; actual DB test pending |
| 10 | Assignment/File12 attachments, grading and appeal | Owner-validation and author/assessor checks present; File12 live pending |
| 11 | File20/25 card, course and private dashboard rendering | Native guards present; shared cache acceptance pending |
| 12 | File26 REST catalog search, page/caching state | Native rate limit/identity-sensitive public-private paths present; real query acceptance pending |
| 13 | Single lesson REST Cache-Control with hierarchical ACL | **F03:** `public` child field could emit public cache header for authorized restricted-parent response; signed-in viewer related links are also viewer-sensitive |
| 14 | File06/10/12/15 external learning-link visibility/revocation | Prior cycle read-time owner callback found; actual companion responder pending |
| 15 | File16 Socratic learning AI and source-safe clinical boundary | Education-only policy present; actual AI provider pending |
| 16 | File17 community and File19 outbox event boundaries | Typed producer/consumer paths present; runtime delivery pending |
| 17 | File22 composer and versioned editorial corrections | Hook/version control found; publication staging pending |
| 18 | WordPress core personal data export/erase/actor-only data | Prior cycle privacy partition found; WP tooling acceptance pending |
| 19 | Future18 mentor/portfolio/CPD/assessment data, governance | Named functions and contracts present; genuine users + consent staging pending |
| 20 | Patient case consent withdrawal, reconsent and historical preservation | **F04:** same-policy `ON DUPLICATE KEY UPDATE` silently cleared `withdrawn_at` and replaced history after withdrawal |

Summary: twenty inspected focus areas. Four confirmed **source-level** defect classes in rounds 5, 8, 13, 20; sixteen other rounds had no newly proven defect in their scoped inspected method set. Zero outstanding code defects cannot be asserted.

## Root causes and code fixes

**F01 — prerequisites fail open when malformed.** `LSCH_Policy::validate_prerequisites` previously turned e.g. `abc` into an empty prerequisite set; `4,abc` silently dropped part and `4,,5` was accepted because `preg_split` collapsed consecutive delimiters. The new strict allowlisted integer/separator validation rejects the entire nonempty malformed input with `lsch_prerequisite_configuration_invalid`. A genuinely empty specification remains allowed; valid completed courses pass; self references remain rejected.

**F02 — private-note key rotation lacks reliable DB failure detection.** `LSCH_Operations::rotate_note_keys` now explicitly handles nonarray failed `get_results`, logs a bounded audit reason and returns `failed/remaining=null`. Verification count `get_var` returning `null` or `false` returns `partial/remaining=null`, never a fabricated all-rotated `0`.

**F03 — effective access, not raw meta, determines REST caching.** `LSCH_REST::lesson` now returns `private, no-store` whenever hierarchical `publicly_visible` is false OR caller has current protected/approved-user context. Public caching is permitted only for genuinely public lessons requested without a protected viewer. This preserves the previous parent-hierarchy and foreign-owner viewer-dependent related-link fixes.

**F04 — withdrawn consent history cannot silently reappear.** `LSCH_Services::store_case_consent` now rejects any existing consent record for the same `lesson_id+policy_version` rather than executing `ON DUPLICATE KEY UPDATE ... withdrawn_at=NULL` which could destroy historical withdrawal evidence. Fresh approved records still INSERT. **Important:** full patient re-consent (new signed evidence, append-only historical record, governed revocation lifecycle) needs approved source/schema design and a live DB migration. This source fix deliberately fails closed until that workflow is implemented; it does NOT claim re-consent is complete.

## Executable tests (source-level; not staging)

- `tests/prerequisite-integrity.php`: absent vs malformed/combined/repeated-separator prerequisite IDs, real prerequisite pass/fail and self-reference.
- `tests/lesson-rest-cache.php`: authenticated access to restricted-ancestor public-child, protected viewer on public lesson, genuine public guest cache, nonpublic response.
- `tests/note-rotation-db-errors.php`: failed note read, failed verification count and healthy-empty rotation.
- `tests/consent-history-protection.php`: withdrawn same-policy record refuses mutation; fresh consent works; no overwrite SQL.
- `tests/source-invariants.sh` runs all four alongside all preexisting tests.
- Actual final GitHub CI should be checked at the **exact final HEAD**; an older all-green run is not evidence of the last commit.

## Next nonnegotiable acceptance gates

Real WordPress staging on exact deployed File05 source, plugin and core/future schemas: guest/staff/member learning access with parent ACL, public edge caches/CDN, File20/25 and File26 global search pages, assessment/enrollment with deliberately malformed metadata, File24 independently rotated keys under DB outage/restore and actual patient-case consent withdrawal/renewal. Re-consent requires formally approved append-only schema change, migration/rollback, proof of no lost historical evidence, and Founder approval before implementation. File06/10/12/15 owner responders, File16/17/19 adapter integration, File00 assertions and File22 composer versions must be genuinely installed and tested.

## Repository / deployed / DB / migration / live states

| Field | Status |
|---|---|
| Frozen source review HEAD | `e8f0596a527a4957c4c479ff9ec5883408162545` |
| PR#4 | Draft, unmerged |
| Main source HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Source plugin version | `4.0.0` |
| Source expected schemas | Core 18, Auxiliary 3, Future-18 2 |
| Latest repository exact HEAD / CI | independently recheck after final documentation update |
| Deployed plugin/package/checksum | Unverified |
| Live database schema/version | Unverified |
| Migration status | Unverified |
| Staging runtime / Live verification | Not performed |
| Re-consent governed implementation | Design/migration/approval pending |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
