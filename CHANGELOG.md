# Changelog

## 4.0.0 — 2026-08-10 — Future Superset 18 candidate

- Added all Founder-approved F05-FUT-01..18 enhancements: mastery, spaced review, flashcards, eight clinical-learning/evidence labs, personal learning prescription, mistake ledger, portfolio, mentorship, CPD, File16 Socratic tutor and knowledge-change re-study.
- Added independent Future-18 sub-schema 2 with eight private learning tables while retaining core schema 18 and auxiliary state schema 3.
- Added versioned `/future18/*` REST surfaces under the existing v2 namespace and a managed `/learn-mastery` page/shortcode.
- Preserved canonical ownership for Files 06/12/15/16/17/19/26; no foreign private-storage coupling or duplicate global ranking/AI/PDF/repertory backend.
- Added de-identified practice payload guards, scoped assessor/mentor governance, independent CPD verification, and source-citation enforcement for File16 Socratic responses.
- Added correction-impact re-study projection and local post-persistence event hook without replacing the canonical outbox.
- Added Future-18 privacy export/erasure and destructive-purge coverage.
- Fresh Review Round 1 and Round 2 both found defects and corrected them before release-candidate status; regression invariants were added for the repaired paths.

## 3.3.0 — 2026-08-10 — Current-plans corrective candidate

- Reconciled source directly against the current consolidated central plan and current File 05 plan after the historical materialization transport was proven corrupt and non-recoverable.
- Replaced File 00 private meta/table coupling with `SMC_Contracts::assertions()` and current `smc_policy()` guardrails; requires File 00 contract `1.2.2+`.
- Enforced single free tier, free baseline, optional donation with no donor advantage, zero commission, Sabri Green `#087A4E`, numbered-file maximum 26 and File 26 global discovery ownership at runtime.
- Added independent AES-256-GCM private-note keyring with write-key generation rotation and bounded re-encryption support; new writes no longer derive keys from WordPress auth salts.
- Added File 05-owned saved learning searches, governed correction proposals/review/resubmission/withdrawal, stale-object protection, independent reviewer scope and recoverable application states.
- Added privacy-minimized learning-value events with bounded retention and aggregate-only analytics.
- Added File 26 learning-domain provider with owner/freshness/why metadata while preserving File 26 as global rank owner.
- Added source-grounded File 16 AI context contract without diagnosis, prescription or emergency-replacement authority.
- Added citation exports (text, APA, Vancouver, BibTeX, RIS) and account-owned learning-record export.
- Strengthened outbox and background-job concurrency claims, stale-job reconciliation, System Check, Safe Mode/repair and note-key rotation diagnostics.
- Removed direct Founder lookup through `wp_usermeta`; Founder identity is validated only through the public File 00 owner contract/filter path.
- Extended privacy export/erasure/pseudonymization to new File 05 state.
- Added current-plan security/static tests, PHP 7.4/8.3 syntax matrix, deterministic package parity, embedded manifest and deterministic SPDX SBOM verification.
- Removed obsolete/corrupt v3 materialization workflow/transport from the release branch.

## 2.0.0 — Historical candidate

- Earlier canonical LSCH source candidate. Superseded by 3.3.0 current-plan corrective work.

## 1.0.0 RC1 — Historical corrective baseline

- Earlier SLC corrective release lineage retained only for provenance/migration history.

## 0.1.0 — Baseline

- Original supplied source preserved in repository history/baseline branch.

## 2026-08-11 — Eighth independent ten-round corrective review
- Closed ten sequential source defects covering native WordPress access enforcement, enrollment parity refresh, assessment/assignment immutable-version evidence, optional-vs-required learning components, current-version course/prerequisite completion truth, Future18 optimistic concurrency and blueprint-to-canonical-version integration.
- Added permanent Cycle-8 regression invariants and `REVIEW-10-CYCLE-8-2026-08-11.md`.
- Repository-only correction; staging/live status is unchanged.

## 2026-10-11 — Draft PR #4, Source-level privacy correction

- Completed 20 distinct focused File05 master-plan/current-governance/cross-file source inspections (`REVIEW-20-FOCUSED-2026-10-11.md`).
- Exported persisted Future18 personal learning pathways and restricted assessor/CPD-verifier actor projections.
- Redacted assessor and CPD verifier identifiers in other learners' records on erasure when no active legal hold applies; kept academic results.
- Added standalone PHP privacy contract regression `tests/future18-privacy-contract.php` to mandatory CI.
- No change to plugin runtime/schema versions, `main`, staging or live. Staging/privacy integration gates remain open.

## 2026-10-11 — Second fresh 20-focus File05 code audit

- Separately reviewed twenty File05 source concerns against the File05 v1.1, consolidated central plan and native-owner companion boundaries, from exact baseline `691d7d871f3186e87395e77b2cc54b5d348a9bd5`.
- Corrected three demonstrated source defects: public catalog audience leak, account-sensitive course HTML cache classification, and duplicate lesson version increments across WordPress save hooks.
- Added `LSCH_Policy::publicly_visible()`, `LSCH_Frontend::public_catalog_posts()`, extended `is_private_learning_surface()`, and reused the canonical content version bump path.
- Registered executable `tests/public-visibility-version.php` in mandated CI. Source/testing correction HEAD `17e49739bb0241a5d55edd2199128ed3e6e0efca` passed the four-job CI `38112358573`.
- See `REVIEW-20-CYCLE-B-2026-10-11.md` and the appended staging tests. No main merge or production deployment.

## 2026-10-11 — Third fresh File05 20-focus source review (Cycle C)

- Frozen review baseline `f05c0a6156612498811c1c4156c25128f27042e9`; examined 19 source PHP files in 20 new independent code-focus reviews against current File05 and consolidated master specifications.
- Four defect classes were found (rounds 1, 2, 14, 15): malformed access declassification, unsafe invalid admin access fallback, incomplete private export on DB failure, and privacy erasure reported done on partial errors.
- File05 canonical access enums now fail closed for malformed explicit values, including values normalized to public by sanitization. Admin input now rejects malformed access updates.
- Future18 export now raises an error on any failed dataset read; both core and Future18 erasure record `done=false` when DB failures require retry.
- Regression coverage expanded for both access paths, export failure, two erasure paths and legal hold. Test runner updated; see `REVIEW-20-CYCLE-C-2026-10-11.md` and staging gates.
- Draft PR only: no merge or live deployment; successful source CI does not prove Hostinger/WordPress staging or exact-deployed parity.

## 2026-10-11 — Fourth fresh File05 20-focus audit (Cycle D)

- Reviewed actual 19 PHP source files in 20 focused passes at frozen baseline `018e07d2d19900a0ea56f8f71e64c8ddd47baa71` against the File05 plan, consolidated central plan and canonical companion ownership.
- Corrected third-party student data leakage from WP privacy exports: learner-owned full rows separated from strictly projected assessor/reviewer actor history.
- Applied current canonical-owner authorization at read time for File06/10/12/15 related references; missing owner approval fails closed, including revoked links.
- Restricted global File26 public search adapters to `publicly_visible` and guest File16/17 educational/community resolvers to public-only when no explicit viewer ID is provided.
- Added PHP regressions `tests/core-privacy-projection.php` and `tests/cross-owner-visibility.php`; corrected an unsafe legacy Python invariant. All four GitHub jobs green on code/test HEAD `05d70b932b05343a0ff29d9b600e337c04811aba`.
- Cross-provider staging, main merge, deployed runtime/database parity and live re-test remain unverified/unperformed. Details: `REVIEW-20-CYCLE-D-2026-10-11.md`.

## 2026-10-11 — Fifth File05 twenty-focus source review (Cycle E)

- Froze Draft PR#4 at `c1c072a3754bc51f404f1bd5188bf4f4ec21a58b`; inspected nineteen actual PHP source files across twenty new focus areas and the current File05 + consolidated central plans.
- Closed five concrete source defect classes: parent ACL/public lesson leak, patient case taxonomy-error fail-open, third-party consent/staff data exposure in WordPress privacy exports, DB failure handling in background outbox/jobs workers, and silent deletion of historical case consent records by daily reconciliation.
- Added `tests/parent-access-consent.php` and `tests/queue-and-consent-retention.php`; extended `tests/core-privacy-projection.php`; registered all in mandatory source QA.
- Details `REVIEW-20-CYCLE-E-2026-10-11.md`. CI success must be verified on exact final documentation HEAD; no live/staging changes or main merge.

## 2026-10-11 — Sixth new twenty-focus source audit (Cycle F)

- Inspected 19 native PHP source files at frozen exact branch HEAD `e8f0596a527a4957c4c479ff9ec5883408162545` in twenty fresh areas against File05 v1.1 Future18 and governing Three Central Plans.
- **Four confirmed new source defects corrected:** nonempty malformed curriculum prerequisites were treated as optional; note key rotation hid DB-read errors or fabricated zero remaining; lesson REST used raw child `public` rather than effective ancestor/viewer privacy for shared caches; same-policy case consent upsert erased withdrawn history and silently reactivated consent.
- Added four PHP regression files to required CI. A failing test for `4,,5` identified and corrected the overly permissive delimiter parsing before final acceptance. Extra rotation check covers null/false count errors.
- Patient re-consent is now safe-by-default (no silent rewrite); a proper history-preserving renewal and schema migration remain a separate approved integration task. See `REVIEW-20-CYCLE-F-2026-10-11.md`.
- No merge/deploy or Staging/Live runtime verification performed.

## 2026-10-11 — Seventh File05 20-focus audit, Cycle G

- Reviewed nineteen actual PHP implementation files in twenty fresh areas at frozen exact PR HEAD dbef11220009676ec716f016a67652b1fdfde0c5 against File05 v1.1, consolidated Three Central Plans and source-linked File06/12/25 owner specs.
- Patched five proven source defects: unverified enrollment reread leading to false event/PHP offset access, REST taxonomy WP_Error projection, false/null private saved-search rows, unknown privacy-retention count reported erased, and missing DB health metrics reported as zero.
- Created enrollment-postwrite-verification.php, catalog-taxonomy-failure.php, saved-search-read-failure.php, system-health-db-failure.php; extended core-privacy-erasure.php and mandatory source-invariants runner. Repaired missing apply_filters test stub after real CI feedback.
- Documentation: REVIEW-20-CYCLE-G-2026-10-11.md. Staging/live, merged code, migration parity and full governed re-consent remain unverified/pending.

## 2026-10-11 — File05 eighth twenty-focus source audit (Cycle H)

- Fetched and inspected nineteen PHP class source files at exact frozen PR #4 HEAD `5a9297a0fc3d87cccb8b656a213735b2510a412c` across twenty new source focus areas against File05 v1.1 Future18 and consolidated Three Central Plans; five fresh defects confirmed.
- Corrected five actual source defects: invalid required learning components silently waived; failed assessment attempt MAX read cast as zero; failed competency taxonomy turned into empty earned snapshot; certificate readiness counted obsolete assessment-version score; privacy-safe educator analytics cast failed counts/AVG into fake zero results.
- Added PHP regression suites `learning-requirement-failclosed.php`, `current-competency-evidence.php` and `course-analytics-db-failclosed.php` to mandatory integrity runner.
- Report: `REVIEW-20-CYCLE-H-2026-10-11.md`. Proper patient historical re-consent migration and real WordPress staging/live integration are still pending. No PR merge or deployment performed.

## 2026-10-11 — Ninth File05 20-focus source review (Cycle I)

- Froze and inspected Draft PR #4 at b82f044b25b0de376f12eb0dfc18254f74cdedb5; studied File05 v1.1 Future18, consolidated Three Central Plans and related File06/File12 owner boundaries.
- Completed twenty distinct focused source inspections and corrected six confirmed new defects: private note failed read misreported as empty; assessment start post-insert re-read unverified; assessment grade post-update re-read falsely publishing event; reviewer corrections inbox DB error; completion-evidence DB read masquerading as empty; partial daily reconciliation incorrectly marked successful.
- Added three PHP failure-injection regression suites, extended mandatory source runner and corrected missing is_wp_error WordPress fixture stub after live CI failure.
- Full review in REVIEW-20-CYCLE-I-2026-10-11.md. Source-level passing CI does not represent merge, deploy, schema migration or staging/live acceptance; full case re-consent schema still pending governed approval.

## 2026-10-12 — File05 tenth twenty-focus independent source review (Cycle J)
- Inspected nineteen File05 native PHP class source files (414,287 characters) at frozen exact Draft PR #4 SHA 9571a1ddee745148dd0debf0950038d146a8496c, using File05 v1.1 Future18 and the Consolidated Three Central Plans with related File00/06/12/16/17/19/20/22/24/25/26 owner contracts.
- Completed twenty distinct focus reviews and implemented seven confirmed fail-closed fixes: prerequisite DB/grandfather exception, progress MAX(score) DB outages, mixed invalid quiz blueprint, private dashboard/REST/export DB failures, all Future18 legal-hold evidence classes, saved search SQL DELETE failure, aggregate learning metrics false-query result.
- Extended five existing regression fixtures, added tests/tenth-dashboard-projection-db.php and required runner entry. CI feedback corrected missing mock absint and assignment table, plus an incorrect JSON object fixture assumption, without changing source business rules.
- Report REVIEW-20-CYCLE-J-2026-10-12.md. History-preserving patient re-consent workflow/schema and staged/live parity remain unresolved and must not be misrepresented as completed.

## 2026-10-12 — Eleventh File05 twenty-focus audit (Cycle K)
- Read all nineteen native PHP source classes at immutable PR HEAD d08f28949d73ecf54077d0cc8622ac7c08eeb4bc against the actual File05 v1.1 Future18, consolidated Three Central Plans, and companion File06/File12 canonical boundaries. Completed 20 scoped source inspections.
- Corrected seven new source defects K01–K07: unverified enrollment state postwrite, failed progress prewrite read, bookmark existence failed lookup, private note prewrite read error, unverified assignment postwrite, unverified reminder postwrite, and false-zero current certificate assessment DB result.
- Added tests/eleventh-postwrite-verified.php and tests/eleventh-private-read-guards.php to mandatory source-invariants.sh. Corrected two harness deficiencies surfaced by first CI: assignment test active enrollment stage and missing sanitize_key stub.
- Full evidence REVIEW-20-CYCLE-K-2026-10-12.md. No live deployment/merge or schema migration attempted; full patient re-consent and staging/live parity are pending.

## 2026-10-12 — File05 twelfth twenty-focus source review (Cycle L)
- Froze exact Draft PR source at 75d4749079ca4ffd44b40cbbc311fd775a2a9d3a; read nineteen native PHP class files (417,285 characters) against File05 v1.1 Future18 and Three Central Plans plus File06/12 owner plans; twenty distinct focus inspections, nine new source defect categories L01–L09.
- Fixed unknown prior-enrollment permission and enrollment INSERT, unreadable prior consent, certificate completion/enrollment false-missing state, three correction 404-on-DB-error routes, failed original idempotency key-read/release, Future18 mastery and five other private collection DB outages, misleading center/path recommendations, and nine WP_Error REST-header wrappers.
- Added three PHP failure-injection suites to mandatory source-invariants.sh: twelfth-owner-ledger-failures.php, twelfth-corrections-idempotency-db.php, twelfth-future18-private-projections.php. CI execution caught and fixed incomplete isolated Event/REST stubs; source code QA all four jobs green on tested code revision.
- Full report REVIEW-20-CYCLE-L-2026-10-12.md. No merge, live deploy, DB migration, File24 data-rights staging or real partner integration done; historical patient re-consent still pending Founder-approved schema.

## 2026-10-12 — File05 thirteenth 20-focus source review (Cycle M)
- Reviewed all nineteen PHP source classes at frozen immutable Draft PR #4 HEAD `de8ae9a87a743f8ea0c3d5d6b38e8dcb80a3732f` (424,812 source characters) against the original File05 v1.1 Future18, consolidated Three Central Plans v3.0 and linked File06/File12 ownership rules. Performed twenty scoped source checks and confirmed seven new failure/record-history defect classes.
- Corrected failed assessment idempotency lookup, File19 event inbox prior history, Future18 prior mastery history and spaced-review schedule, course completion enrollment and revoked completion history, assignment assessor/appeal academic reads.
- Added `tests/thirteenth-inbox-consumer-db.php`, `tests/thirteenth-mastery-replay-db.php`, `tests/thirteenth-assessor-appeal-db.php`; extended `tests/ninth-private-note-assessment-read.php` and `tests/current-competency-evidence.php`; registered all new suites in mandatory runner. CI found and repaired a missing WordPress day-length test constant; four jobs passed on code revision.
- Full source matrix `REVIEW-20-CYCLE-M-2026-10-12.md`. No PR merge, live patch, DB migration or staging parity verification. Multi-write state/outbox atomicity and patient history-preserving re-consent need separate governed and tested implementation.

## 2026-10-12 — File05 fourteenth fresh twenty-focus source review (Cycle N)
- Audited immutable Draft PR #4 `de8ae9a87a743f8ea0c3d5d6b38e8dcb80a3732f` across 19 source PHP classes and twenty distinct requirement/owner focus areas, against File05 v1.1 Future18, Three Central Plans, and File06/12 boundary documents.
- Fixed seven confirmed academic history/duplicate processing defect classes N01–N07: failed original assessment attempt lookup; File19 inbox consumer read; prior Future18 mastery and spaced-review due-date; course completion active enrollment and historic award lookup; assessor grading and learner appeal read failures.
- Added three PHP regression suites, extended two, and registered new suites with required source-invariants.sh. Isolated test fixture's missing WordPress DAY_IN_SECONDS was corrected after initial CI failure; source CI passed on corrected code SHA.
- Detailed register `REVIEW-20-CYCLE-N-2026-10-12.md`. Draft PR not merged, deployed code/DB/migration and real WordPress partner integration unverified; patient case append-only re-consent and end-to-end owner/outbox atomicity remain pending.

## 2026-10-12 — File05 fifteenth fresh 20-focus source audit (Cycle O)
- Froze GitHub Draft PR #4 `cf71b23db7c5287d7a669c1d1650a65a723eb16e`; reviewed nineteen PHP source classes (426,950 characters) against the attached File05 v1.1 Future18 Master Plan, the governing Three Central Plans and File06/08/12 cross-owner boundaries. Completed 20 separate source-focus checks.
- Corrected five new source defect categories O01–O05: ignored mastery review creation/lookup failures; misclassified spaced-review SQL history outages; simulated practice grading SQL history outages; File06 knowledge-change learner batch/read/write false completion (File19 retry); and File06 re-study impact/resolution DB-read false conflicts.
- Added `tests/fifteenth-future18-impact-batch.php` and `tests/fifteenth-future18-evidence-reads.php` to required `tests/source-invariants.sh`, covering failed source queries and correct healthy/empty results. Test fixture missing JSON policy stub and wrong impact-table name corrected after CI, without weakening business rules. All four CI jobs passed on tested correction SHA `a0ae48e996c49247c33f80e283999344a1be96c3`.
- Full numbered report `REVIEW-20-CYCLE-O-2026-10-12.md`. Draft unmerged, no staging/live deploy, no DB migration, real MySQL partial-state/outbox atomicity and patient re-consent not yet verified/implemented.

## 2026-10-12 — File05 sixteenth twenty-focus source audit (Cycle P)
- Inspected 19 native PHP classes (428,974 characters) at frozen Draft PR#4 HEAD `969a3734b432f83921906d56be8c7dbefc56773f` in 20 different functional/integration focus areas against actual attached File05 v1.1 Future18 and three governing central plans, plus File06/08/09/12 companion plans and File05 other-owner adapters.
- Corrected five source defect categories: P01 prior mentor-feedback read, P02 mentorship closure read, P03 CPD verification read (all now 503 DB outage distinct from 409 conflict), P04 saved review result with failed mastery evidence, P05 graded practice with failed mastery/portfolio evidence. Explicit partial-state reliability audits and no false complete practice event; atomic transactions remain unverified.
- Added `tests/sixteenth-mentor-cpd-history.php` and `tests/sixteenth-grade-evidence-integrity.php` to mandatory `tests/source-invariants.sh`. Corrected standalone DB test-double methods and scenario scoping; exact source code+tests revision `2b1c393399ec5807adef024678e3f8a6683616d7` passed 4/4 CI jobs in `38277238586`.
- Full 20-area evidence `REVIEW-20-CYCLE-P-2026-10-12.md`. PR4 remains Draft/unmerged, live deployed artifacts/DB/migrations and real owner integration unknown; governed patient-case re-consent and transaction/outbox acceptance not complete.

## 2026-10-12 — File05 seventeenth fresh 20-focus source audit (Cycle Q)
- Frozen audited Draft PR4 HEAD 5d912ca81107c7d3b7ff615b4e6a37c01135577c. Reviewed all 19 native PHP classes and 20 distinct File05/master-plan/cross-owner functional areas against File05 v1.1 Future18, three central governing plans and File06/08/09/12 canonical owner requirements.
- Four newly evidenced worker defects Q01-Q04 corrected in LSCH_Operations::process_jobs: stranded already claimed job when reread fails; malformed JSON payload dispatched unchecked; uncaught owner handler exception; unverified persisted terminal completed/retry/dead state.
- Added tests/seventeenth-job-worker-recovery.php into required tests/source-invariants.sh; exact code/test SHA 2b7fa65b68686462f6f3cff739794604a7e1d424 passed all four automated jobs (run 38279836013). Numbered findings: REVIEW-20-CYCLE-Q-2026-10-12.md.
- Still unmerged/undeployed. Real MySQL/WordPress job/outbox atomicity, history-preserving patient re-consent, live database schema/migrations and installed File19/other owner contract parity remain unverified.

## 2026-10-12 — File05 eighteenth twenty-focus source audit (Cycle R)
- Froze Draft PR #4 exact audited HEAD `869b502662ed11fd325e2880532e69b54018699e`. Reviewed all nineteen File05 PHP owner classes in twenty different requirement and integration focus areas versus the attached File05 v1.1 Future18 master, consolidated three central plans, and File08/12 owner boundaries. Full matrix: `REVIEW-20-CYCLE-R-2026-10-12.md`.
- Fixed eight new source failure categories R01–R08: File19 outbox selected event row query errors, malformed event payload, provider exceptions, unchecked terminal-state write; Future18 portfolio sharing prior-row DB errors, ignored mentor/recorded-CPD portfolio failures, ignored verified CPD portfolio/mastery failures, and CPD endpoint returning portfolio INSERT id rather than actual CPD id.
- Added `tests/eighteenth-outbox-delivery-failures.php` and `tests/eighteenth-cpd-portfolio-integrity.php` to required `tests/source-invariants.sh`; fixed missing standalone WordPress `sanitize_json` and `DAY_IN_SECONDS` fixtures exposed by initial CI. Final SHA-matched four-job QA must be confirmed following docs commits.
- No production merge/deploy or schema change. Actual deployed package/DB/migrations and other plugin version parity unknown, true File19+MySQL state/outbox atomicity and patient history-preserving re-consent incomplete/unverified.

## 2026-10-13 — File05 eighteenth independent twenty-focus source audit (Cycle R)
- Compared immutable Draft PR#4 HEAD 2796e988ea853d7516a843989c421143115c9989 with the attached File05 Future18 and consolidated three governing central master plans, and File05-owned cross-file boundaries. Twenty distinct topic scopes checked, one new source defect R01 confirmed.
- Removed unsafe mentorship ON DUPLICATE KEY UPDATE status='active' which could silently resurrect ended academic mentorship. Existing or unreadable prior relation fails closed; only a verified absent relation receives INSERT and event. Approved append-only regrant/reassignment remains future work.
- New tests/eighteenth-mentorship-reassignment.php in mandatory source-invariants.sh, testing ended, active, unknown-DB and first-time states. Full evidence REVIEW-20-CYCLE-R-2026-10-13.md. No live merge or deployment; actual schema/parity and WordPress E2E unverified.
