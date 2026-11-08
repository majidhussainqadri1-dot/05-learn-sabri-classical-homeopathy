# File 05 — Twenty Focused Code/Plan/Companion Inspections — 2026-10-11

## Evidence boundary
The inspected immutable baseline was Draft PR #4 `codex/file-05-complete-v3.0.0` at `f0f0e354df294cfd14473e9a360e6a574d49fa4e`. Nineteen implementation PHP files (~394 KB of source) were fetched directly from this exact head. Governing comparison: File 05 v1.1 Future-18 master plan; the three-central-plans consolidated governing master; File 05 requirements traceability; boundaries for Files 00, 01, 02, 03, 06, 10, 12, 15, 16, 17, 19, 20, 21, 22, 23, 24, 25 and 26.

**Methodological limitation:** These are twenty distinct focused source inspections, plus deeper inspection of the privacy workflows. Marker presence proves code presence, NOT correctness or complete runtime acceptance. They are NOT twenty complete WordPress/Hostinger browser or end-to-end test suites. No live deployed files, schema or migration state was read.

## Twenty inspections and outcome
| # | Focus | Key source | Finding at baseline |
|---:|---|---|---|
| 1 | Four levels / approved 16 topic taxonomy | `class-lsch-content.php` | Source present; real curriculum content acceptance pending |
| 2 | File00 current-version identity and policy | `class-lsch-dependencies.php` | Versioned assertions found; companion runtime pending |
| 3 | Scoped roles, Founder, doctor eligibility | `class-lsch-capabilities.php` | Guards found; real-role tests pending |
| 4 | Founder books and chapter architecture | `class-lsch-content.php` | Governed slots and chapter metadata found; real bibliography pending |
| 5 | Public/account/restricted reads and case consent | `class-lsch-policy.php` | Policy paths found; hostile-access testing pending |
| 6 | Native WordPress direct URLs / archives | `class-lsch-content.php` | Public access guards found; browser and SEO testing pending |
| 7 | Enrollment, prerequisites and completion | `class-lsch-services.php` | Core flows present; real database transitions pending |
| 8 | Assessment, assignment, grading and appeal | `class-lsch-services.php` | Workflow handlers present; assessors/concurrency pending |
| 9 | Idempotency, transactions, request ledger | `class-lsch-idempotency.php` | Transaction/replay guards present; failure injection pending |
| 10 | Independent private-note encryption | `class-lsch-policy.php` | AES-GCM/keyring paths present; key rotation pending |
| 11 | File20/22/26 layout, composer and discovery | `class-lsch-plugin.php` | Native adapters/projections present; peer-contract staging pending |
| 12 | REST authorization and private DTOs | `class-lsch-rest.php` | Route gates present; IDOR/negative tests pending |
| 13 | Corrections and privacy-minimized metrics | `class-lsch-state.php` | Workflows found; recovery/event proof pending |
| 14 | Core migration and upgrades | `class-lsch-database.php` | Migration paths found; actual DB/parity unknown |
| 15 | Future18 mastery, review, flashcards and labs | `class-lsch-future18.php` | Feature functions present; real academic review pending |
| 16 | Mentor scope, CPD and verification | `class-lsch-future18.php` | Native functions found; real-role tests pending |
| 17 | File16 AI, citation gating and no medical authority | `class-lsch-future18.php` | Source/safety guards found; real provider pending |
| 18 | Privacy export, erasure, legal hold and actor history | `class-lsch-future18.php` | **F05-20261011-01/02 confirmed**; corrective source committed |
| 19 | Public and private UI/accessibility | `class-lsch-frontend.php` | Render/header handlers found; WCAG/browser testing pending |
| 20 | System-check, repair and operational guardrails | `class-lsch-operations.php` | Operations functions found; staging drill pending |

## Proven defects and correction (after all twenty inspections)
- **F05-20261011-01 — missing personal learning-path export.** Future18 `pathways` data is persisted by `build_learning_path()` but was not included in the registered WordPress personal data export. Corrective change adds a bounded, paginated pathway projection and counts it in export completion.
- **F05-20261011-02 — incomplete cross-subject actor privacy.** Future18 `practice.assessor_id` and `cpd.verified_by` may reference the requester in records owned by a different learner. The original privacy eraser deleted the user's learner records but did not redact these cross-subject actor fields. Corrective change adds scoped de-identification when there is no legal hold, while retaining governed academic rows and truthful `items_retained`. Export receives restricted actor-only projections without revealing the other learner's response or evidence.

Changed source: `05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php`.
Regression: `tests/future18-privacy-contract.php`, executed by `tests/source-invariants.sh`.
Regression tests use an isolated database stub. Real WP privacy tooling, legal-hold classification, transactional rollback and the File00/24 policy stack must still be demonstrated in staging.

## Completion and exact-deployed-state discipline
This review found no whole approved domain provably absent from the examined repository source. That does not establish zero defects or complete implementation in all real environments. No production patch, merge or deployment was authorized or performed.

Repository baseline: `f0f0e354df294cfd14473e9a360e6a574d49fa4e` (PR #4).
Correction commits: `17496978cb38da049ca7f8192f6d94fb2522ba6f`, `e4f39c923fb0d0978d57b256da5d858b23903df5`, `a7e63aa725964f94c26d7e310cfdb7e4c7497216`.
Repository final HEAD and exact-head CI: evaluate after the last report/documentation commit.
Deployed Version: unverified.
DB Version: unverified (source contract core 18, auxiliary 3, Future18 2).
Migration State: unverified.
Live Verification Status: not performed.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
