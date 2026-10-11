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
