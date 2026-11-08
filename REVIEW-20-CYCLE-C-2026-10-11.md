# File 05 — Third Fresh Twenty-Focus Source Audit — 2026-10-11 (Cycle C)

## Exact source truth and methodology
Frozen input: Draft PR #4 `codex/file-05-complete-v3.0.0` at `f05c0a6156612498811c1c4156c25128f27042e9`. Its `main` base was `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. Nineteen native implementation PHP files (~399 KB) were fetched against the exact baseline. Governing comparisons: the supplied File 05 v1.1 Future-18 master plan, the Consolidated Three Central Plans Governing Master Plan v3.0 and relevant File 06, 12 and 25 specifications/ownership boundaries.

These are twenty **separate targeted source-level code reviews** (including inspection of the actual PHP method bodies, not twenty staging runs). Their result cannot prove no remaining defects. Source and tests are distinct from deployed WordPress state. For all findings, root-cause comparison was completed across the twenty checks **before** code corrections began.

## Twenty distinct code reviews

| # | Source focus | Result on frozen baseline |
|---:|---|---|
| 01 | Native content access state, unknown or explicitly malformed access metadata | **Defect C01**: invalid explicit access silently fell back to public |
| 02 | Admin governance metadata: invalid access input validation | **Defect C02**: malformed admin value written as public |
| 03 | Native public WordPress search/archive and singular guards | Relevant defenses found; staging validation pending |
| 04 | REST public catalog privacy filtering and pagination | Relevant source present; real-REST negative acceptance pending |
| 05 | Guest vs logged-in File20 public/private cache context | Prior cycle's privacy headers found; CDN staging pending |
| 06 | Patient-case source consent and revocation checks | Fail-closed consent validation path found; staging pending |
| 07 | File00 versioned identity, approved/guardian/suspended assertions | Versioned contract found; peer compatibility pending |
| 08 | Scoped staff assignments and conflict-free revocation | Object-scoped checks found; role-matrix staging pending |
| 09 | Private note AES-GCM, AAD and independent keyring | Encryption paths present; key rotation/recovery pending |
| 10 | Assessment start/submit, expiry and attempt limit | Expiry/lock paths present; runtime timing tests pending |
| 11 | Learner progress and optimistic-version transitions | Versioned state paths present; DB concurrency pending |
| 12 | Correction proposer/reviewer independence and stale versions | Checks found; actual file19 re-study staging pending |
| 13 | File20 app shell, File22 composer and file26 contract ownership | Native adapter hooks found; peer runtime pending |
| 14 | Future18 WordPress privacy export with DB read failure | **Defect C03**: no validation of failed `get_results` in ten private datasets |
| 15 | Future18 and core learning eraser partial DB failure | **Defect C04**: erasers reported `done=true` even after query failure |
| 16 | Future18 simulation/de-identification payload controls | Defense code present; adversarial tests pending |
| 17 | Files06/12/15 typed related knowledge/link metadata | Native related source references present; peer visibility pending |
| 18 | File16 source-grounded Socratic tutoring and clinical boundary | Citation/provider control present; real provider tests pending |
| 19 | File19 event outbox and idempotent delivery ownership | Versioned native event producer found; actual delivery pending |
| 20 | Fresh/legacy migration and File24 safety/health contracts | Schema, upgrade and safe mode paths found; deployed DB unverified |

**Twenty focused code reviews: completed 20/20. Confirmed defect classes in rounds 01, 02, 14 and 15. Sixteen other rounds yielded no confirmed missing source on these inspected paths, not blanket proof of full code completeness.**

## Corrections only after finishing review round 20

- **C01** `LSCH_Content::access()`: absent/legacy access metadata preserves the prescribed public-read behavior, but any explicitly unknown/non-scalar/malformed value fails closed as `restricted`. Exact enum membership is checked *before* any sanitization could turn `public!` into `public`. This protects canonical File05 content, File26 public indexes and File20/25 cards against unintended declassification.
- **C02** `LSCH_Admin::save_governance_meta()`: refuse malformed/array or out-of-enum `_lsch_access` fields; never coerce them to `public`. Valid enum changes retain normal content version tracking.
- **C03** `LSCH_Future18::privacy_export()`: all ten private result sets must be valid arrays. Any DB read error returns explicit `WP_Error('lsch_future18_privacy_export_failed')` instead of a false completed/empty export or a PHP8 runtime TypeError.
- **C04** `LSCH_Future18::privacy_erase()` and `LSCH_Privacy::erase()`: set `done=false` whenever a tracked DB operation fails, preserving retry/operator escalation; retain legal-hold and truthful retention reporting. Both the core and future extension paths were corrected.

## Executable code evidence
- Expanded `tests/public-visibility-version.php`: malformed access, `public!`, array-valued access, and missing legacy access.
- Added `tests/access-governance-contract.php`: unauthorized invalid string/array form values do not widen access or cause false versions; valid transition is versioned.
- Expanded `tests/future18-privacy-contract.php`: DB read failure produces `WP_Error`, erasure failure leaves `done=false`, earlier legal hold and actor export/erasure remain.
- Added `tests/core-privacy-erasure.php`: core erasure retry and legal hold semantics.
- Registered two new PHP test files in `tests/source-invariants.sh`.

The exact coding-plus-tests HEAD (before report/documentation) and current CI must be verified separately; a green earlier HEAD never substitutes for the final HEAD.

## Required real integration checks (unverified)
On actual Hostinger WordPress staging, test File05 public/access metadata for missing, account, restricted, unknown, `public!`, array-like malicious POST and saved corrupt meta. Confirm direct pages, the REST catalog, WordPress search, File26 indexes and File20/25 public cards remain fail closed after a malformed classification. Test legitimate guest public lessons and File00 member access still work.

Inject WordPress/MySQL query failures into each Future18 export dataset and each core/Future18 erasure path, verify failure is reported without pretending export/erasure was finished, test retries and legal hold under File24 policy, and verify no raw private data appears in logs. Staging must also exercise File06/12/15 source visibility, File16 tutor source truth and File19 notification links.

## Status and environment truth
Repository baseline `f05c0a6156612498811c1c4156c25128f27042e9`. Corrections applied only to Draft PR #4 branch, not merged to main. Source expects File05 plugin 4.0.0, core schema 18, auxiliary schema 3, Future18 schema 2.
- **Repository HEAD:** see latest PR #4 and CI after this report update.
- **Deployed version:** unverified.
- **Live DB version:** unverified.
- **Migration state:** unverified.
- **Live verification:** not performed.
- **Staging acceptance:** not yet demonstrated.
- **Main merge:** not performed.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
