# File 05 — Fourth independent twenty-focus audit — 2026-10-11 (Cycle D)

## 0. Evidence and status

Frozen immutable review baseline: **`018e07d2d19900a0ea56f8f71e64c8ddd47baa71`**, Draft PR #4 branch `codex/file-05-complete-v3.0.0`. `main` was still `e2667d69d43d655e3d86b0a358dec5f28b61dd96` (README governance only). 19 PHP implementation files (~399,791 bytes) were fetched and inspected. Source foundations: supplied **File 05 v1.1 Future-18 Complete Master Plan**, **Three Central Plans Consolidated Governing Master Plan 2026**, and File 06/12/25 companion plans. File05 plan expressly defines **18 FR**, **10 NFR**, 18 Future enhancements, one free tier and native ownership for external File 06/10/12/15 knowledge, File 16 AI, File 17 collaboration, File 19 delivery, File 20 shell and File 26 global discovery.

This is a *fourth NEW cycle of 20 independent focused source inspections*, not 20 production/staging WordPress runs. Reviews and fixes are separated: review 1–20 completed, defects consolidated, then corrective edits and executable regressions. Functional presence is not a guarantee of runtime correctness.

## 1. All 20 source focus inspections

| # | Focus, plan ownership | Source looked at | Finding at frozen baseline |
|---:|---|---|---|
| 01 | F05-FR-001 four levels, topics, competency prerequisites | `class-lsch-content.php` | Relevant source present; actual curriculum acceptance pending |
| 02 | F05-FR-002 eight Founder works, chapter/edition structure | `class-lsch-content.php` | Catalog/seeding code present; actual published inventory pending |
| 03 | F05-FR-003 free public books/courses/programs | `class-lsch-frontend.php` | Public catalog read guards from previous cycles present |
| 04 | F05-FR-004 lesson authoring, public/private/case-consent classification | `class-lsch-policy.php` | Authorization and consent code present; real role tests pending |
| 05 | F05-FR-005 enrollment, prerequisite, audit and version | `class-lsch-services.php` | Enrollment paths present; real DB transitions pending |
| 06 | F05-FR-006 progress/completion/concurrent learner updates | `class-lsch-services.php` | Progress/version checks found; concurrent WordPress tests pending |
| 07 | F05-FR-007 bookmark and encrypted-note ownership | `class-lsch-services.php`, `class-lsch-policy.php` | Bookmark toggle/private note crypto found; key tests pending |
| 08 | F05-FR-008 assessment attempt timing and grading integrity | `class-lsch-services.php` | Start/submit/timing handlers found; real clock/DB acceptance pending |
| 09 | F05-FR-009 assignments, conflicts, appeals | `class-lsch-services.php` | Submission/grade/appeal handlers found; peer File12 pending |
| 10 | F05-FR-010 dashboard and protected private-cache headers | `class-lsch-frontend.php` | Dashboard, no-store boundaries found |
| 11 | F05-FR-011 search filters and F05-FR-018 correction audit | `class-lsch-state.php`, `class-lsch-rest.php` | Local filter/correction routes present; real interface pending |
| 12 | F05-FR-012 links to File06/10/12/15 canonical knowledge | `class-lsch-operations.php` | **D03: persisted active reference emitted without target owner visibility/revocation recheck** |
| 13 | F05-FR-013 File17 study circles, File19 outbox events | `class-lsch-plugin.php`, `class-lsch-operations.php` | Community adapter/event functions found; peer delivery pending |
| 14 | F05-FR-014 File00 roles, staff scope and conflict | `class-lsch-capabilities.php`, `class-lsch-dependencies.php` | Central assertion contract and native staff checks found |
| 15 | F05-FR-015 completion durability and guarded publication | `class-lsch-services.php`, `class-lsch-plugin.php` | Versioned completion and publishing handlers found |
| 16 | F05-FR-016 certificate readiness/wording/jurisdiction | `class-lsch-services.php` | Gated readiness source found; legal signoff pending |
| 17 | F05-FR-011, F05-CEN-04 and File26 public discovery; File16/17 guest contexts | `class-lsch-value.php`, `class-lsch-plugin.php` | **D04: public search recheck used viewer-sensitive `can_read_post` and guest context could inherit ambient current user** |
| 18 | F05-NFR-002 and privacy lifecycle for learners/assessors/reviewers | `class-lsch-privacy.php` | **D01+D02: full-row `SELECT *` matched another learner's submission or correction when requester acted as assessor/reviewer** |
| 19 | F05-FUT-01..18 mastery, spaced review, simulation, mentor, CPD, AI | `class-lsch-future18.php` | All 18 named functions / associated tables present; runtime testing pending |
| 20 | F05-NFR-003/007/008/009 upgrade, retries, safe mode and compatibility | `class-lsch-database.php`, `class-lsch-operations.php` | Code paths present; real deployed/DB versions and rollback unverified |

**Total:** 20 focused source inspections completed. **Four distinct proven defect classes** found across focus rounds 12, 17 and 18. The other focus rounds cannot certify zero remaining bugs.

## 2. Root cause and fixes after all 20 reviews

### D01 — Other learner's assignment content could leak through an assessor's GDPR/WP export
Before: `LSCH_Privacy::export()` had `SELECT *` with `user_id=%d OR assessor_id=%d` against `submissions`; a requester who assessed learner B could receive learner B's `body`, attachments and appeal text. After: only the person's own submissions are exported in full; assessor history uses an explicit small field allowlist without learner identity, answer, attachments, feedback or appeal.

### D02 — Other proposer's correction details could leak through a reviewer's export
Before: correction export used `SELECT *` with `proposer_id=%d OR reviewer_id=%d`. After: the reviewer's actor history is separately projected to a narrow status/version/date dataset, while full correction proposal text exports only to the proposer. The source invariant `tests/static-invariants.py` was deliberately updated: the old `OR` query was an unsafe legacy assumption, not a contract to preserve.

### D03 — Canonical File06/10/12/15 target references lacked live owner revalidation
Before: `LSCH_Operations::related_links()` returned stored references marked `active`, even if the source/target visibility later changed or an external owner revoked a target. After: source visibility is checked again, and every target requires explicit `true` from versionable `lsch_related_learning_reference_visible` owner hook. Missing/invalid provider and DB failures fail closed (empty list). No external File06/10/12/15 data or permissions are copied into File05. **This requires the actual companion plugin(s) to register visibility responders in staging.** A functioning target without an owner responder is intentionally hidden rather than silently exposed.

### D04 — File26 public search and guest AI/community cross-file context
Before: both File26 public search adapters declared `visibility_recheck = LSCH_Policy::can_read_post` which is viewer-specific; `viewer_id=0` in AI/community resolvers could be interpreted as ambient current user. After: public File26 adapters use `LSCH_Policy::publicly_visible`. AI/community resolvers with explicit viewer 0 are strictly guest-safe; a nonzero eligible viewer still uses native per-user authorization.

## 3. Executable regressions and CI

Added:
- `tests/core-privacy-projection.php` — learner-owned raw export and actor-only projections; no other learner response/appeal/correction text.
- `tests/cross-owner-visibility.php` — fail closed when no current owner/revoked reference/source denied/DB failed, guest-only public File26 projections, guest and explicit-user File16/17 context.
- `tests/source-invariants.sh` now invokes both.
- Replaced unsafe legacy cross-subject query assumption in `tests/static-invariants.py` with specific actor-only and SQL absence requirements.

**Pre-documentation corrected/tested HEAD:** `05d70b932b05343a0ff29d9b600e337c04811aba` — GitHub Actions run `38121924971` succeeded in all four jobs: source-integrity including new regressions, PHP 7.4 syntax, PHP 8.3 syntax, deterministic ZIP/package. Earlier CI failures were real and resolved: legacy invariant required unsafe broad export, and new unit fixture needed to implement the WordPress `wpdb::prepare` array argument form. Staging E2E was NOT performed. The final post-report HEAD must be tested again separately.

## 4. Required remaining File05 + companion acceptance

- Hostinger staging: guest, Member, Doctor, Teacher, Assessor, Founder with public/account/restricted File05 and File06/10/12/15 references, including consent withdrawn, unpublished, deleted, owner missing or incompatible. Supply canonical owner permission responder and prove revocations hide links everywhere, including File25 cards and File26.
- File26: searchable public projection must never expose account/restricted learning, including a staff/Founder logged-in session; File16 and File17 guest context must never derive authorizing identity from ambient session.
- WP privacy tools: actor A assessed learner B and reviewed learner C's proposal. Export A's records without B/C answers or proposal details; export B's and C's own data completely. Test pagination, query errors, legal hold and erasure after role revocation.
- Review actual File00/20/22/24/25/26 versions, DB schemas, migration state, failure logs and deployed artifact checksum. Retry, rollback and cache/index invalidation acceptance remain mandatory.

## 5. Separate reality registry

| Reality | Status |
|---|---|
| Frozen review baseline / PR#4 branch | `018e07d2d19900a0ea56f8f71e64c8ddd47baa71` |
| Source/test correction head | `05d70b932b05343a0ff29d9b600e337c04811aba` |
| Exact `main` HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Plugin runtime version declared in source | `4.0.0` |
| Source schema expected | core `18` / auxiliary `3` / Future18 `2` |
| Actual deployed plugin version and package SHA/checksum | **Unverified** |
| Actual deployed DB version and migration state | **Unverified** |
| Live/staging runtime validation | **Not performed** |
| GitHub PR#4 | **Open Draft, unmerged** |

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
