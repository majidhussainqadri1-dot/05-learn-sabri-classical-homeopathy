# File 05 — Twelfth Fresh 20-Focus Source Audit (Cycle L, 2026-10-12)

## Governing evidence freeze

- Exact PR #4 source frozen for audit: `75d4749079ca4ffd44b40cbbc311fd775a2a9d3a`; branch `codex/file-05-complete-v3.0.0`; GitHub `main` `e2667d69d43d655e3d86b0a358dec5f28b61dd96` at freeze. **PR #4 is Draft, open and unmerged.**
- All **19 File05 native PHP class files**, **417,285 source characters**, fetched from the frozen immutable source revision. Twenty *different, focused source inspections* performed; this does **not** mean twenty independent WordPress E2E runs or 20 complete repeat tests of the entire codebase.
- Read actual attached **05 File05 Learn Sabri Classical Homeopathy Complete Master Plan v1.1 Future-18**: owner curriculum/course/lesson/assessments/progress/bookmarks/notes, course prerequisites, certificates, consent, structured Future18 learning, File12 references, data rights, event outbox, operations, staging and rollback.
- Read attached **Three Central Plans Consolidated Governing Master Plan 2026** (canonical ownership, identity/consent, atomic transitions, evidence classes, separate specified/coded/staged/live facts); cross-referenced actual attached File06 canonical Knowledge and File12 PDF Library master plans. Other File00/09/16/17/19/20/22/24/25/26 integrations reviewed via their **File05-owned source adapters only**; actual companion packages are NOT proven deployed or compatible.

## All twenty focused source rounds

| Round | File05 requirement / integration source path | Result |
|---:|---|---|
| 1 | Four-level curriculum, topics and founder learning books — `class-lsch-content.php` | Native owner methods present; published data/staging not proved |
| 2 | File06 canonical Encyclopedia versus File05 education references — content/related-links adapters | Source separation found; foreign deployed code unknown |
| 3 | Single free education tier and File00 access/guardian identity — `class-lsch-policy.php` / `class-lsch-dependencies.php` | Native source gates found |
| 4 | Doctor/teacher/reviewer object permissions and File09 onboarding — `class-lsch-capabilities.php` | Capabilities and object policy present |
| 5 | File20/25 public/private rendering, File26 indexing and cache — `class-lsch-frontend.php` / `class-lsch-rest.php` | Existing source privacy guards found; staging pending |
| 6 | Exact current enrollment validation for assessed work — `class-lsch-services.php` | **L01 confirmed:** failed active-enrollment DB read incorrectly appeared as ordinary not-active learner |
| 7 | Enrollment creation vs existing unique row — `class-lsch-services.php` | **L02 confirmed:** failed prewrite SELECT could be misclassified as no enrollment and enter INSERT path |
| 8 | Current curriculum prerequisite/version checks — `class-lsch-policy.php` | Previous strict parser and DB-failure guards found |
| 9 | Lesson progress/notes/bookmarks private versioned writes — `class-lsch-services.php` | Prior fail-closed guards present |
| 10 | File12 assignment attachment validation and assessed grading — `class-lsch-services.php` | Canonical owner validation hook present; counterpart unverified |
| 11 | Current assessment evidence and course certificate — `class-lsch-services.php` | **L03 confirmed:** failed completion or enrollment SELECT was converted into missing academic qualification rather than 503 |
| 12 | Patient-case consent/withdrawal and File24 retention — `class-lsch-services.php` | **L04 confirmed:** failed existing-policy consent history SELECT could attempt new authorization insert |
| 13 | File22 academic correction review decision/resubmit/withdrawal — `class-lsch-state.php` | **L05 confirmed:** three failed private correction record SELECTs were reported as 404 |
| 14 | File19 durable outbox and protected request idempotency — `class-lsch-idempotency.php` | **L06 confirmed:** failed request-key ledger read could attempt fresh replay-key allocation |
| 15 | File16 education-only tutor, File17 community source context — `class-lsch-future18.php` | Owner/adapter contracts present; AI/community staging missing |
| 16 | Future18 private mastery and due-review queues — `class-lsch-future18.php` | **L07 confirmed:** false DB rows could reach PHP `count()` (mastery) or false list replies |
| 17 | Future18 portfolio, mentorship, CPD, File06 knowledge-change impacts — Future18 | **L07 confirmed:** additional private list queries returned failed `get_results` directly |
| 18 | Future18 center and learning-path decisions — Future18 | **L08 confirmed:** failed due-review reads could be converted to no due reviews; child errors embedded in apparent successful center object |
| 19 | Future18 REST presentation/cache headers, Files20/25/26 — `class-lsch-future18-rest.php` | **L09 confirmed:** nine REST handlers attempted to set response headers on possible `WP_Error` |
| 20 | File24 privacy lifecycle/legal hold, File19 queues, core/aux/Future18 schema and legacy migration — `class-lsch-privacy.php` / `class-lsch-database.php` / `class-lsch-operations.php` | Source flows found; active deployed DB migration state/rollback not verified, no uncontrolled schema patch |

**Results: 20/20 focus areas reviewed; nine new source defect categories (L01–L09), found during rounds 6/7/11/12/13/14/16/17/18/19, with L07 shared by rounds 16 and 17.** Other inspected scopes yielded no additional *proven* defect, not proof of overall completeness.

## Corrections after collecting review results

- **L01:** `active_course_enrollment()` returns explicit `lsch_enrollment_gate_read_failed` / 503 when WordPress SELECT returns empty due to actual DB error; legitimate non-active enrollment still produces governed 409.
- **L02:** `enroll()` refuses to INSERT when prewrite enrollment SELECT failed, `lsch_enrollment_prewrite_read_failed` / 503. Prevents unsafe duplicate attempt, preserving existing unique owner and idempotency contract.
- **L03:** `certificate_readiness()` checks academic completion and enrollment ledger SQL reads before treating them as absent/unfinished; 503 for unknown ledger rather than fictional disqualification. Existing identity, source assessment version and certificate wording gates remain.
- **L04:** `store_case_consent()` checks a failed prior policy-history SELECT before any insert, returns 503. Withdrawn consent history is never silently rewritten. The **complete append-only patient re-consent renewal** remains a separate Founder-approved schema/data/reversal requirement.
- **L05:** `LSCH_State` correction decision, proposer resubmission and withdrawal now differentiate failed SQL read from a truly nonexistent/unauthorized correction, using `lsch_correction_read_failed` / 503 instead of false 404.
- **L06:** `LSCH_Idempotency::before_callbacks()` detects failed original-key ledger read, releases named DB lock and returns `lsch_idempotency_ledger_read_failed` / 503 without allocating a new replay record. Operators should retry the **same** idempotency key after recovery.
- **L07:** Future18 mastery snapshot checks array before `count`; review queue, portfolio, mentorship, CPD and knowledge-change impact lists all return explicit 503 on failed query and maintain legitimate empty arrays on healthy empty DB responses.
- **L08:** Future18 learning-path generator no longer turns failed due-review query into `[]`. Mastery Center no longer embeds failed child projections or hides a failed learning path inside a successful JSON object.
- **L09:** All nine Future18 REST response-building handlers now return `WP_Error` without calling response `header()` on it, while successful responses retain `Cache-Control: private, no-store`.

## Executable failure-injection regressions and mandatory runner

- `tests/twelfth-owner-ledger-failures.php` verifies refusal of new enrollment/consent after failed old-row reads, active enrollment status error, distinct certificate completion/enrollment evidence failures and no unintended mutations.
- `tests/twelfth-corrections-idempotency-db.php` checks all three correction actions, idempotency lookup failure, no new ledger INSERT, and named-lock release.
- `tests/twelfth-future18-private-projections.php` checks six native Future18 private DB list reads, nine private REST handlers, Learning Path and Center failure propagation, legitimate empty state and private cache header.
- All three are in `tests/source-invariants.sh`. The first CI executions showed isolated test fixtures missing WordPress `LSCH_Events` / `discard_transaction_buffer` and `WP_REST_Request::get_method` stubs; these test doubles were completed and the **code+test HEAD** CI then passed all four jobs (source integrity; PHP 7.4 syntax; PHP 8.3 syntax; reproducible package). **Re-check exact final HEAD CI after all documentation commits.**

## Explicitly unverified or pending acceptance

**The source contract does not prove live production.** The live reality freeze has **not** been performed: active File05 package/version and checksum, deployed files, real DB core/aux/Future18 schema versions and rows, `wp_options`, migrations/upgrade locks, runtime logs, partner deployed versions, real CDN/cache, and repository-to-deployed parity are unknown.

Staging must use real WordPress/MySQL and reproduce failed prior-row lookups, completion/consent history, original request-key failure and recovery, six Future18 data outages and private REST headers, legally held patient records, multi-device concurrency, rollback, queues, File00/06/12/16/17/19/20/22/24/25/26 owner consumers and native WordPress privacy requests. No migration, merge, deploy, or live retest undertaken.

**Full patient historical re-consent after withdrawal:** still requires approved append-only schema and migration/rollback, signed current evidence, role authorization and real staging acceptance; do not declare coded-complete.

## Separate repository/deployment registry

| Evidence dimension | Actual status |
|---|---|
| Frozen audited PR HEAD | `75d4749079ca4ffd44b40cbbc311fd775a2a9d3a` |
| Final code+docs PR HEAD and CI | See SHA-matched post-documentation GitHub Actions run |
| Repository `main` at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR #4 | Draft / Open / Unmerged |
| Source plugin declared version | `4.0.0` |
| Source expected schema versions | Core 18, Auxiliary 3, Future18 2 |
| Deployed plugin version/files/package checksum | **Unverified** |
| Live actual DB version/schema/rows | **Unverified** |
| Migration state | **Unverified** |
| Staging/live verification | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
