# File 05 — 20-Focus Code-to-Plan Review and Corrective Evidence — 2026-10-10

## Evidence and scope
Baseline: Draft PR #4, branch codex/file-05-complete-v3.0.0, reviewed HEAD 08e72be0a93e40cfe57ae74ec99ddea6e76a6200.
Governing documents: consolidated central plan, File 05 v1.1 Future-18 plan, and native owner contracts with Files 00/01/02/03/06/10/12/15/16/17/19/20/21/22/23/24/25/26.
This report represents 20 distinct focused repository/source inspections, followed by deep reading of suspected methods and one substantive correction. It does NOT represent 20 fully executed WordPress/staging end-to-end suites or proof of production correctness.

## Twenty focused inspections
| Round | Technical focus | Result |
|---:|---|---|
| 1 | File00 versioned identity and fail-closed dependencies | Guard symbols found; companion runtime unverified |
| 2 | Role/object/capability authorization | Guards found; real-role negative tests pending |
| 3 | Curriculum/CPT/publish review | Implementation paths found; real publication pending |
| 4 | Activation, core/aux/Future18 migration | Routines found; staged upgrade/rollback pending |
| 5 | Free tier, Sabri Green and guardian consent | Policies found; UX acceptance pending |
| 6 | Enrollment, prerequisites, course completion | Code present; transactional testing pending |
| 7 | Progress, stale-version assessment and grading | Guards present; concurrency acceptance pending |
| 8 | Bounded and parameterized database queries | Static source inspected; database load testing pending |
| 9 | Idempotency, events, outbox and retries | Code present; failure recovery pending |
| 10 | Bookmark, private note, export and erasure | Code present; privacy/backup drills pending |
| 11 | Future-18 mapping, practices, mastery and CPD | 18 feature identifiers found; staging pending |
| 12 | Educational simulations and practice scoring | **DEFECT F05-20261010-01**: array-to-string coercion in auto scoring |
| 13 | Native owner integrations, AI and File26 discovery | Hooks/projections present; companion integration pending |
| 14 | REST authorization and mutation guards | Routes/callbacks inspected; negative runtime testing pending |
| 15 | Public/private UI, RTL and accessibility | Source controls inspected; real-device testing pending |
| 16 | 18 FR / 10 NFR traceability tests | Static tests identified; runtime coverage separate |
| 17 | Future18 testing completeness | **TEST GAP tied to F05-20261010-01**: added PHP regression |
| 18 | Security abuse and permission tests | Invariants identified; real-role matrix pending |
| 19 | Manifest/SBOM/reproducible packaging | Build scripts inspected; new HEAD QA pending |
| 20 | CI, PHP matrix and release evidence | PHP 7.4/8.3 and source workflow present; new HEAD CI pending |

## Root cause and correction
F05-20261010-01: The Future18 auto_score() method cast untrusted structured answers and rubric values to strings. In particular, an array answer becomes 'Array', potentially matching a malformed expected value of 'Array' and earning an unsupported score. Non-scalar rubric fields could also cause warnings.
Fix: enforce valid scalar rubric keys/weights and expected options; invalid or structured answers now fail closed to the existing manual assessor path, never automatic positive marks.
Regression: tests/future18-score-safety.php checks legitimate scalar and multi-choice scoring, malformed/structured answers, invalid expected/key/weight, and missing rubric. tests/source-invariants.sh now invokes this test.
No other module's canonical data, main branch or live website was modified.

## Counts and limits
Focused source inspections: 20/20. Inspections with correction work: #12 (code) and #17 (corresponding test coverage). One confirmed product defect corrected in source, one regression suite added. Other inspections found no additional confirmed defect during this scoped pass; do not describe them as exhaustive clean runtime results.
Full real WordPress role/guardian/assessor tests, File00/06/12/15/16/17/19/26 contract operation, DB migration/parity, real-browser a11y, performance, backup/restore/rollback and Founder acceptance remain outstanding.
Final CI must run on the corrected exact HEAD and must be reported separately.

Repository HEAD: obtain from the corrected branch after final commit.
Deployed Version: unverified.
DB Version: unverified (source declares core 18, auxiliary 3, Future18 2).
Migration State: unverified on staging and live.
Live Verification Status: not performed.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
