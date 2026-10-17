# File 05 — Eighth Independent Twenty-Focus Source Audit — Cycle H (2026-10-11)

## Freeze and evidence

- Exact frozen source-review SHA: `5a9297a0fc3d87cccb8b656a213735b2510a412c` (Draft, open, unmerged PR #4).
- Repository `main` at freeze: `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. GitHub HEAD is **not** live WordPress evidence.
- All **19 actual native PHP class source files**, approximately **408,532 characters**, fetched and inspected at immutable review baseline.
- Original attached master plans inspected: File 05 v1.1 Future-18 (requirements F05-FR-001 through FR-018, NFR-001 through NFR-010; enrollment/completion/evidence/credential readiness) and consolidated Three Central Plans (CEN-OWN-001, CEN-PUB-001, CEN-NAV-001, release/assurance gates). Related File 06 canonical knowledge, File 12 protected PDF/attachments, and File 25 presentation source boundaries considered.
- **Scope:** twenty different topic-focused source inspections completed before the correction batch. These were NOT twenty staging E2E executions and do not prove the entire site complete.

## Twenty fresh focus reviews

| # | Source and governing/cross-file scope | Outcome |
|---:|---|---|
| 1 | F05-FR-001 levels, 16 topics; File26 discovery; `class-lsch-content.php` | Native vocabulary paths found; live curriculum content unverified |
| 2 | Founder books/edition/chapter seeding; `class-lsch-content.php` | Native founder slots found; actual book inventory and rights acceptance pending |
| 3 | Native public query filtering and File25/26 search; `class-lsch-content.php` | Current source filters found; actual external index unknown |
| 4 | File00 guardian, identity, roles and current claims; `class-lsch-dependencies.php` | Contract source found; deployed File00 integration unverified |
| 5 | Enrollment and strict prerequisites; `class-lsch-policy.php` | Previous strict validation found; staging pending |
| 6 | Parent access and patient-case source consent; `class-lsch-policy.php` | Previous fail-closed owner authorization found |
| 7 | F05-FR-006/007 required lesson completion components; `class-lsch-services.php` | **H01:** malformed configured requirements silently collapse to smaller subset or default reading |
| 8 | Current required assessments/assignments and course progress; `class-lsch-services.php` | Required evidence present; DB/staging integration pending |
| 9 | F05-FR-008 assessment attempt allocation after DB outage; `class-lsch-services.php` | **H02:** failed MAX attempt count interpreted as 0 and permits an unverified new attempt |
| 10 | File12 assignments and attachments; `class-lsch-services.php` | Strict foreign-owner attachment callback source found |
| 11 | F05-FR-010 completion competency taxonomy; `class-lsch-services.php` | **H03:** taxonomy WP_Error converted to empty competency snapshot, then could be awarded |
| 12 | F05-FR-010 certificates, integrity and current question version; `class-lsch-services.php` | **H04:** readiness query accepted grades from obsolete assessment versions |
| 13 | File20/25 private dashboard and lesson presentation; `class-lsch-frontend.php` | Viewer-aware source and cache controls found |
| 14 | File26 public and authenticated learning REST; `class-lsch-rest.php` | Public/restricted source separation found; staging pending |
| 15 | File16 cited Socratic tutor and educational boundary; `class-lsch-future18.php` | Source-only gating found; AI service/real runtime pending |
| 16 | File17 community and File19 delivery contracts; `class-lsch-plugin.php`, `class-lsch-events.php` | Native adapters present; consumer version parity unknown |
| 17 | File24 privacy exports/erasures and independent private note keyring; `class-lsch-privacy.php` | Prior source privacy fixes present; WordPress native tooling pending |
| 18 | F05-FR-011 institution analytics, privacy cell threshold; `class-lsch-services.php` | **H05:** false/null counts or average cast to zero, generating false statistics |
| 19 | File22 composer/editorial approval; `class-lsch-plugin.php` | Native editorial adapter found; partner real integration pending |
| 20 | Core/aux/Future18 schema/migrations, File24 rollback; `class-lsch-database.php` | Migration source paths found; exact WordPress DB and installed checksum unknown |

**Fresh result:** 20 distinct source review scopes, **5 confirmed defects** (rounds 7, 9, 11, 12, 18) and 15 scopes without new confirmed source defects. No claim that unresolved omissions are impossible.

## Corrected only after twenty-focus source review

### H01: Missing/malformed Required Components cannot award completion

The old parser used `array_intersect` to silently discard an unknown required component, or default an invalid/empty JSON array to `['content']`. That could mark a lesson complete although its governed learning requirements were corrupt. New validation differentiates truly absent legacy metadata (`['content']`) from nonempty invalid JSON, rejects unknown/non-string/duplicate/empty/malformed component lists with explicit conflict error before any progress write. Prevents unintended 100% or earned course credit.

### H02: Assessment attempt history must be verifiable

`start_assessment` used `1 + (int) $wpdb->get_var(MAX(attempt_number))` and treated failed DB reads as zero. It now handles false/null/non-numeric/negative values with explicit 503, preserving the lock/finally and avoiding allocation of a new attempt based on unknown history.

### H03: Course completion cannot silently erase competency truth

`recalculate_completion` previously transformed failed `wp_get_object_terms(course COMPETENCY)` into an empty competency array while continuing the earned-record write. It now returns an explicit 503 if the canonical taxonomy is WP_Error/non-array. No earned completion is written from unverifiable competency information.

### H04: Certificate readiness must verify current assessment version

`certificate_readiness` previously queried passing attempts without `item_version`, enabling historical scores against superseded assessment questions to count as current. The predicate now includes `item_version = LSCH_Content::version($assessment_id)`. Old graded attempts remain historical academic evidence, but cannot prove readiness for revised current competency assessments.

### H05: Course analytics must not substitute fake zero statistics for DB outages

`course_analytics` had multiple direct `(int)` and `(float)` casts around DB COUNT/AVG results; failed reads become zero while the caller receives apparently valid analytics. All metric reads now validate null/false and return explicit 503; existing minimum cell-size 5 suppression and subgroup protections remain. Healthy counts retain the source-defined privacy threshold.

## Required source regression tests

- `tests/learning-requirement-failclosed.php`: invalid, unknown, duplicated, nonarray required lesson components; unavailable MAX attempt count prevents insertion.
- `tests/current-competency-evidence.php`: failed competency taxonomy cannot write earned completion; old-version assessment score cannot qualify for certificate.
- `tests/course-analytics-db-failclosed.php`: count and average DB outages emit errors, healthy metrics remain valid, small-count subgroup suppression is preserved.
- `tests/source-invariants.sh` executes new tests plus earlier source contracts and all PHP syntax/packaging jobs. Re-check exact HEAD matched CI after final documentation commit.

## Remaining owner integration / live release gates

Separate unresolved historical patient **re-consent** lifecycle still needs approved append-only schema/data migration, rollback, File24 privacy/consent and real WordPress staging validation. New code does not fabricate a signed renewed consent. Source check ≠ real consent/guardian evidence.

Staging on exact deployed artifact must test curriculum setting corruption, DB MAX failure, taxonomy error before completion, old/current assessment attempts under revised question versions, and failed analytics COUNT/AVG at small/large privacy-cell sizes. Check actual File00/06/10/12/15/16/17/19/20/22/24/25/26 installed adapter versions, File19 event consumers, File26 index/caches, WordPress privacy/export/retention and File24 migration backup/restore. Verified deploy -> live re-test -> parity confirmation required.

## Mandatory source/deployed/DB/migration/live registry

| Fact | Status |
|---|---|
| Frozen repository review HEAD | `5a9297a0fc3d87cccb8b656a213735b2510a412c` |
| Final exact branch HEAD and CI | Check post-documentation GitHub workflow |
| Repository `main` HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Branch / PR | `codex/file-05-complete-v3.0.0` / Draft PR #4, not merged |
| Source plugin version | `4.0.0` |
| Expected source schemas | Core 18 / Auxiliary 3 / Future18 2 (per source; historical uploaded plan has different earlier Future18 baseline) |
| Deployed plugin/package/checksum | Unverified |
| Actual live DB schema version / rows | Unverified |
| Migration state | Unverified |
| Staging/live verification | Not performed |

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
