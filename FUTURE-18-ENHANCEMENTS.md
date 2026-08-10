# File 05 — Future Superset 18 Enhancements — v4.0.0

Founder-approved expansion dated 2026-08-10. This document is a repository implementation companion to the current consolidated central plan and File 05 master plan. It does not convert repository evidence into staging/live evidence.

## Governing invariants

- File 05 owns curriculum, mastery, academic practice, portfolio, mentorship and CPD learning state only.
- File 06 owns encyclopedia truth; File 12 PDF/document truth; File 15 repertory truth; File 16 AI answer truth; File 17 messaging transport; File 19 notification delivery; File 26 global search/discovery/ranking.
- All approved learning remains in the single free tier.
- Simulations are educational and de-identified; no autonomous diagnosis, prescription or emergency-care authority is created.
- No streak, shame or leaderboard mechanics are part of the Future-18 layer.
- Protected writes use the existing File 05 authorization/idempotency gates; privileged assessment/verification is scoped and self-approval is blocked.

## The 18 enhancements

### F05-FUT-01 — Adaptive Mastery Engine

Implementation: `LSCH_Future18` competency mastery/confidence/evidence state; assessment/review/practice evidence integration. External gate: DB/role/browser staging pending.

### F05-FUT-02 — Spaced Repetition & Memory Science

Implementation: bounded review queue/ease/interval scheduling; mastery-linked due review. External gate: time/cron/browser staging pending.

### F05-FUT-03 — Smart Flashcards & Recall Decks

Implementation: account-owned flashcard review items with source/competency metadata. External gate: browser/accessibility staging pending.

### F05-FUT-04 — Clinical Case Simulation Laboratory

Implementation: governed de-identified practice blueprint mode `clinical_case_simulation`. External gate: clinical-education acceptance pending.

### F05-FUT-05 — Remedy Differentiation Laboratory

Implementation: governed practice mode `remedy_differentiation`; File06 truth remains external. External gate: File06/staging acceptance pending.

### F05-FUT-06 — Case-Taking & Questioning Simulator

Implementation: governed practice mode `case_taking_simulator`; simulated/de-identified payload only. External gate: clinical-education acceptance pending.

### F05-FUT-07 — Repertory Reasoning Training Studio

Implementation: governed practice mode `repertory_reasoning`; File15 repertory truth remains external. External gate: File15/staging acceptance pending.

### F05-FUT-08 — Structured Clinical Reasoning Map

Implementation: governed practice mode `clinical_reasoning_map` with assessor/manual path. External gate: rubric acceptance pending.

### F05-FUT-09 — Oral Viva & Structured Practical Examination

Implementation: manual-assessor practice mode `oral_viva`, self-assessment blocked, scoped assessor check. External gate: real assessor staging pending.

### F05-FUT-10 — Clinical OSCE-Style Stations

Implementation: manual-assessor `osce_stations` mode with governed blueprint/version. External gate: real assessor staging pending.

### F05-FUT-11 — Personal Learning Prescription

Implementation: deterministic explainable learner path from mastery/review evidence; GET paths are non-mutating. External gate: browser/value staging pending.

### F05-FUT-12 — Mistake Book / Learning Error Ledger

Implementation: private mistake review items automatically created from weak evidence/practice. External gate: privacy/browser staging pending.

### F05-FUT-13 — Evidence & Source Appraisal Laboratory

Implementation: governed `evidence_appraisal` practice mode; source owners remain external. External gate: source-workflow staging pending.

### F05-FUT-14 — Digital Learning Portfolio & Competency Passport

Implementation: private/shareable-by-consent portfolio records fed by completion/practice/CPD. External gate: consent/share staging pending.

### F05-FUT-15 — Mentorship & Supervision Program

Implementation: manager-assigned active mentor/learner relationships, bounded feedback, supervision checks. External gate: real-role staging pending.

### F05-FUT-16 — Continuing Professional Development

Implementation: verified-doctor/Founder CPD records; mastery evidence only after scoped independent verification. External gate: real-role/legal wording pending.

### F05-FUT-17 — Source-Grounded AI Socratic Tutor

Implementation: File16-owned answer provider contract; mandatory sanitized citation; diagnosis/prescription/emergency authority false. External gate: File16 integration staging pending.

### F05-FUT-18 — Knowledge-Change Impact & Mandatory Re-study

Implementation: correction-event impact ledger, targeted review and `LearningRestudyRequired.v1` event. External gate: correction/load/File19 staging pending.

## Persistence and API

Future-18 uses an additive, independently versioned sub-schema `lsch_future18_schema=2`. The original eight private File 05 tables remain: mastery, review queue, practice, pathways, portfolio, mentorship, CPD and change impacts. Schema 2 adds an immutable practice `competency_key` snapshot so later blueprint changes cannot reclassify already-submitted manual-assessment evidence. Core File 05 schema remains 18; auxiliary value-state schema remains 3.

REST continues under the backward-compatible `learn-sabri-classical-homeopathy/v2` namespace with `/future18/*` endpoints. Runtime is `4.0.0`; plan contract is `SSH-F05-PLAN-2026-v1.1-future18-current-central-2026-08-10`.

## Review evidence — original Future-18 reviews

Fresh Review Round 1 found and corrected: over-broad manual mastery authority, missing assessor-object scope, CPD verification scope, insufficient AI citation enforcement, correction-version derivation and mentorship erasure semantics.

Fresh Review Round 2 found and corrected: privileged actor eligibility gaps, GET/render mutation of learning-path state, spaced-review schedule overwrite, mastery elevation from unverified self-recorded CPD, and privacy export dependence on current learning eligibility.

## Review-80 corrective closure — 2026-08-10

A subsequent founder-ordered 80-pass sequential review was executed under the rule: review one pass → correct any discovered defect immediately → rerun source invariants → only then proceed to the next pass. Temporary review transport/workflow files were removed before the immutable candidate closure.

Product defects were found in review rounds: **1–19, 21–23, 25–29**. No product defect was found in round 20, round 24, or rounds 30–80. Temporary audit-harness assertion/wording mismatches encountered while executing the review were repaired as review infrastructure and were not counted as product defects.

The later passes added/corrected, among other things: Python-cache exclusion; current-policy checks for privileged REST actions; bounded manual mastery supervision; spaced-review schedule authority; broader de-identification; fail-closed external blueprint authorization; immutable practice competency snapshots; revocable portfolio consent; mentorship termination; fail-closed CPD duration validation; approved-source Socratic tutor citations; cursor-batched retryable knowledge-change fan-out; mandatory targeted re-study proof; runtime provider-version alignment; clean-repository guards; privacy export schema parity; current-policy mentorship reads; core REST policy parity; paginated Future-18 privacy export; legal-hold-aware erasure; participant-safe mentorship de-identification; explicit outbox/job acknowledgement; dead-letter health visibility; safe-mode mutation blocking; and installed-vs-expected schema health reporting.

The final Review-80 pass re-ran the full source invariant suite and deterministic package checks. The exact post-cleanup branch candidate is recorded in the pull request/CI evidence; repository completion must not be represented as staging or live acceptance.

## Truth status

Repository source at the exact candidate HEAD may be called repository/source-QA complete only when that immutable HEAD passes the configured Current Plans Integrity workflow. Hostinger staging, deployed plugin/package parity, installed DB/schema migration state, browser/role workflows, live deployment and operational acceptance remain separate evidence gates. No live-site state is inferred from this repository document.
