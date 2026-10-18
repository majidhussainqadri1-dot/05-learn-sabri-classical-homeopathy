# File 05 — Fresh Twenty-Focus Coding Completeness Review — 2026-10-11 (Second Independent Cycle)

## Scope, truth, and governing sources

This is a NEW source review after the prior `REVIEW-20-FOCUSED-2026-10-11.md`, not a recount of it. Frozen **review baseline**: PR #4 `codex/file-05-complete-v3.0.0`, commit `691d7d871f3186e87395e77b2cc54b5d348a9bd5`. Nineteen actual PHP implementation files (~397 KB of fetched UTF-8 source) were inspected. The File 05 v1.1 Future-18 DOCX, current consolidated Three Central Plans Governing Master Plan, current cross-file source adapter specifications and File 05 requirement traceability were used as comparison material.

The twenty rounds below are distinct focused static SOURCE reviews. They are NOT twenty WordPress staging suites or an assertion that every line is free of defects. Peer plugin deployed versions, the actual WordPress database, runtime configuration, Hostinger staging and live site were not accessible/verified. No unverified deployment claim is implied.

## Twenty rounds before any corrections

| Round | Focus and evidence | Result at frozen baseline |
|---:|---|---|
| 1 | Public program/course/Founder book home catalog; `LSCH_Frontend::home` used unscoped `get_posts` (publish status alone) | **DEFECT F05-20261011-B01**: account/restricted titles and excerpts can reach a public/cached home list |
| 2 | Public course outline and user-specific lesson visibility; `LSCH_Frontend::course_content` plus `is_private_learning_surface` | **DEFECT F05-20261011-B02**: the course may contain viewer-specific lesson links without explicit private cache headers |
| 3 | Native WordPress archive/singular visibility, `LSCH_Content` query, results and redirect guards | Relevant defensive paths present; actual WordPress/browser tests pending |
| 4 | Scoped File 05 REST catalog and lesson content; `LSCH_REST` filters by current visibility | Relevant code present; real REST authorization tests pending |
| 5 | File 00 current contract, guardian, approval, suspension and single-free-tier policy | Versioned `SMC_Contracts` integration in source; peer runtime unverified |
| 6 | Scoped teacher course analytics and small-cell protection | Code present; privacy threshold staging verification pending |
| 7 | Four learning levels, sixteen topics, Founder book metadata | Canonical File05 content definitions present; real publication/seed content pending |
| 8 | File 20 shell layout and WordPress private page cache headers | Shell hook present; **B02** affecting user-specific course rendering |
| 9 | File 22 learning lesson composer and editorial gates | Versioned adapter/payload validation present; real File22 contract pending |
| 10 | File 26 global search ownership and local saved learning searches | Projection/local operations present; peer contracts unverified |
| 11 | File 06 knowledge, File 12 PDFs and File 15 repertory references | Typed relationship implementation present; real peer resolver pending |
| 12 | File 16 Socratic AI source/citation and no-prescription authority | Code gates present; real provider and unsafe-output tests pending |
| 13 | File 17 moderated learning community context | Visibility-rechecking context adapter present; provider staging pending |
| 14 | File 19 learning correction/re-study notification events | Versioned event production present; delivery acceptance pending |
| 15 | WordPress `post_updated` followed by `save_post_lsch_lesson` | **DEFECT F05-20261011-B03**: two version increments for one changed lesson |
| 16 | Enrollment, active child course and prerequisite/version completion | Service flow present; DB rollback/concurrency testing pending |
| 17 | Assessment allocation/scoring, appeal and request-idempotency transaction | Handlers and locks found; real simultaneous request testing pending |
| 18 | Future-18 mentorship, assessor scope and CPD | Scoped controls present; user role/jurisdiction staging pending |
| 19 | WordPress privacy export, erasure, legal hold and earlier Future18 corrective paths | Earlier code changes present; actual erasure/legal-hold tests pending |
| 20 | Schema/migration, safe mode, health checks, rollback and reproducible packaging | Code/tooling present; exact deployed DB and staging acceptance unverified |

Review total: **20/20 focused source inspections completed**. **Three confirmed defect classes**, detected in rounds **1, 2 and 15**. Remaining 17 focused inspections found relevant implementation paths but do NOT establish full runtime correctness or confirm every accepted requirement end-to-end.

## Corrections made only after completing all twenty inspections

### B01 — Public catalog visibility (File 05 / File 00 / File 20 / File 26)
**Root cause:** `LSCH_Frontend::home` fetched published program/course/book objects without applying File05 access classification, while page HTML included object title, summary, and cover/link. Publication status does not imply public access. **Correction:** `LSCH_Policy::publicly_visible` is an identity-independent public visibility contract. `LSCH_Frontend::public_catalog_posts` applies a public-only metadata query plus a final fail-closed per-object check. The normal public catalog is never enriched by a logged-in editor's/staff member's private permissions.

### B02 — Privacy and cache parity (File 05 / File 20 / File 24 / File 25)
**Root cause:** account/restricted learning and the viewer-sensitive public-course lesson outline were not included in the custom private/noindex/noarchive header detection. **Correction:** `is_private_learning_surface` treats private dashboard/mastery, cohort/assessment/assignment, unauthorized/nonpublic learning objects and logged-in learning singular pages as private. Public guest reading of genuinely public lessons/courses can remain public. This is an application header contract: real LiteSpeed/CDN/object-cache negative tests are still mandatory.

### B03 — Single-event lesson versioning (File 05 / File 06 / File 19 / education progress)
**Root cause:** `LSCH_Content::bump_version_on_post_update` already bumped the version; `LSCH_Plugin::lesson_saved` directly incremented `_lsch_version` a second time on `save_post_lsch_lesson`. This can prematurely stale lesson progress and research/correction comparison snapshots. **Correction:** use `LSCH_Content::bump_version` consistently, which has a per-request guard, so a logical save increments once while version events keep their native ownership.

## Regressions and exact-head test evidence

New executable PHP unit regression: `tests/public-visibility-version.php`. It models logged-in vs guest visibility, draft/account/restricted/public items, denied consent for patient-case lessons, query metadata public filtering, public vs protected course cache classification, private mastery, and content-save version hook behavior. The required `tests/source-invariants.sh` runner invokes it.

Source fix commits:
- `d94049314df2dd85e4b8da1b74b563c780323d66` — identity-independent public visibility
- `a24ec3883ad6451fa55c6d2727004cb17063b7b8` — catalog and private cache fixes
- `c3c7031d8a6ddca68ba82ff9b0e31387762a429b` — version-drift correction
- `5d23bc6ed8818b1d07fcfb559748874b36110809` — regression fixture
- `17e49739bb0241a5d55edd2199128ed3e6e0efca` — runner integration

**CI on corrected source/test HEAD** `17e49739bb0241a5d55edd2199128ed3e6e0efca`: GitHub Actions `38112358573`, **SUCCESS**; source invariants, PHP 7.4, PHP 8.3 and deterministic package jobs passed. The final post-documentation HEAD is checked separately.

## Pending real-system acceptance

Fresh WordPress/Hostinger staging must confirm public catalog hiding of account/restricted published book/course/program titles, privacy of course outlines across guest/member/teacher/Founder roles, LiteSpeed/object-cache and CDN separation, native WordPress archives and direct links, File00 policy/version/guardian state transitions, File20/25 layout/token parity, File22 composer mutation, File26 search indexes, File19 correction notifications, a one-save/one-version database trace, and backup/restore/rollback. A real deployed package checksum and database migration parity are prerequisites for any live incident diagnosis.

**Repository review baseline:** `691d7d871f3186e87395e77b2cc54b5d348a9bd5` (PR #4).  
**Exact deployed version:** unverified.  
**Deployed DB version:** unverified (source expects core 18, state 3, Future18 2).  
**Migration state:** unverified.  
**Live verification status:** not performed.  
**Merge/deployment:** not performed; PR #4 remains Draft.  

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
