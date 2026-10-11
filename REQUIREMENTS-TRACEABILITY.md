# File 05 — Current Two-Plan Requirements-to-Code Traceability — 4.0.0

Governing basis: the current consolidated central plan plus the current File 05 master plan. Historical local 3.1/3.2 packages are evidence of prior work only; this table maps the actual GitHub corrective candidate.

| Requirement | Current implementation evidence | Automated / external evidence |
|---|---|---|
| F05-FR-001 Curriculum architecture | `LSCH_Content`: four levels, 16 topics, competency taxonomy, prerequisite/equivalence/version metadata | current-plan static invariants; PHP lint |
| F05-FR-002 Founder book catalog | eight governed non-public seed slots; Founder ID accepted only through a public provider and validated by File 00; edition/chapter/rights metadata | static ownership scan; activation/staging pending |
| F05-FR-003 Program/course catalog | program/course CPTs, public metadata/routes, access states and catalog REST/UI | route/static checks |
| F05-FR-004 Lesson authoring | objectives/sources/reviewer/version/accessibility/safety gates, structured-source normalization, File 22 adapter v3 | publish-guard/static checks |
| F05-FR-005 Enrollment | normalized enrollment state, prerequisite/current File 00 assertion gate, universal mutating-REST idempotency, pause/withdraw/resume | unit/static; real-role staging pending |
| F05-FR-006 Progress tracking | required-component-derived percent, resume/version/needs-review/export/reset lifecycle | state/static; database staging pending |
| F05-FR-007 Bookmarks/private notes | account-owned bookmarks and AES-256-GCM notes; new writes require an independent deployment/File24 keyring, not WP auth salts; export/erase | policy crypto unit; key-rotation staging pending |
| F05-FR-008 Knowledge checks | versioned assessment blueprint, explicit start, expiry, bounded attempts, idempotent score/feedback | static/routes; concurrency staging pending |
| F05-FR-009 Assignments/review | submission, File12 attachment references, rubric version, scoped conflict-cleared assessor, revision/resubmit/appeal | static/auth; companion staging pending |
| F05-FR-010 Course dashboard | private no-cache learner record: enrollments, reminders, progress, bookmarks, notes, attempts, submissions, completions | UI/static; browser staging pending |
| F05-FR-011 Learning search | bounded File05 catalog filters plus account-owned local saved searches; File26 remains global search/discovery/ranking owner with why/freshness projection contract | current-plan ownership scan; File26 staging pending |
| F05-FR-012 Related knowledge | normalized owner references and File05 value/provider pointers; no copied 06/10/12/15/16 truth | ownership/static; companion staging pending |
| F05-FR-013 Learning community bridge | File17 context provider with visibility recheck and moderated educational boundary | contract/static; File17 staging pending |
| F05-FR-014 Teacher/assessor governance | scoped staff, conflict/independent-review gates, public File00 assertions; Curriculum Lead explicitly excludes OPERATE/ASSESS and repair remains named-operator-only | security/static; real-role staging pending |
| F05-FR-015 Completion records | versioned competency snapshots durable across access-policy changes | state/static; migration staging pending |
| F05-FR-016 Certificate readiness | private `certificate_readiness()`/REST gate: current File00 identity, earned/non-revoked current-version completion, completed enrollment, clear assessment integrity/minimum pass evidence, jurisdiction + explicitly approved wording; no legal credential claim | static/state; Founder/legal wording acceptance pending |
| F05-FR-017 Education entitlement | `single-free-tier-v2`; File00 current policy contract must confirm free baseline/no paid unlock/no donor advantage | unit/static; real File00 staging pending |
| F05-FR-018 Content correction/versioning | proposer/reviewer correction ledger, self-approval conflict gate, sole governed mutation path, object advisory lock, cache-cleared version check, learner `needs_review`, correction events | state/static; database concurrency staging pending |
| F05-NFR-001 Object/field authorization | File00 `SMC_Contracts::assertions()` contract 1.2.2+ plus native object/state/owner checks; no File00 private meta/table reads | forbidden-coupling scan; real-role IDOR matrix pending |
| F05-NFR-002 Privacy lifecycle | private tables, noindex/no-cache, export/erase, correction pseudonymization, legal hold | static/privacy code; WP privacy-tool staging pending |
| F05-NFR-003 Reliability | durable privacy-minimized mutating-REST request ledger after native permission checks; advisory locks; outbox/inbox/jobs/retry/dead-state/reconcile/safe-mode; concurrency-versioned correction state | static/exact-head CI; failure-injection staging pending |
| F05-NFR-004 Performance | bounded pagination/queries, conditional assets, aggregate-only value metrics, no raw global search warehouse | static; Hostinger p75/p95/load pending |
| F05-NFR-005 Accessibility | semantic UI baseline, 44px controls, focus, keyboard, reduced motion, RTL logical CSS, text-first capability | CSS/static; human WCAG/browser pending |
| F05-NFR-006 Observability | structured audit, trace/request IDs, health/System Check, privacy-minimized value events | static; alerts/operator rehearsal pending |
| F05-NFR-007 Migration/rollback | core schema 18, auxiliary state schema 3, additive request-key ledger, legacy note decrypt compatibility, non-destructive uninstall/rollback docs | static; real upgrade/restore drill pending |
| F05-NFR-008 Operability | System Check, safe mode, repair/reconcile and key readiness contracts | static; operator rehearsal pending |
| F05-NFR-009 Compatibility | WordPress 6.6+ header; PHP 7.4/8.3 lint matrix; versioned File00 and cross-file contracts | exact-head CI; WP7.0.1/LiteSpeed staging pending |
| F05-NFR-010 Localization | American-English base strings, Unicode-safe Urdu/Arabic, RTL logical CSS, locale-safe data | static; mixed-language browser pending |

## Current central-plan amendments

| ID | Governing requirement | Current source implementation |
|---|---|---|
| F05-CEN-01 | All approved courses/lessons/assessments free; no PKR400/Pro/Premium/AI paywall or donor advantage | File00 current policy is a runtime prerequisite; `single-free-tier-v2`; forbidden-policy scan |
| F05-CEN-02 | One integrated free education journey including courses, teacher/cohort, credentials, lifelong learning, source links and low-bandwidth accessibility | File05 curriculum/cohort/completion/citation/download/low-bandwidth capability contracts |
| F05-CEN-03 | Sabri Green current brand fallback | exact `#087A4E` File05 fallback; File25 remains design-token owner |
| F05-CEN-04 | File26 owns global Search/Discovery/Ranking | File05 provides only learning projection and local filters/saved learning search; `global_rank_owner=file26` |
| F05-CEN-05 | One canonical owner; typed/versioned integration, no foreign private storage coupling | File00 public assertion contract; no `_smc_*` meta or `$wpdb->usermeta` reads |
| F05-CEN-06 | Healthy use/privacy/value over addictive engagement | opt-in learning reminders; no streak/shame contract; aggregate-only value events |
| F05-CEN-07 | Source-grounded learning/AI and no autonomous clinical authority | structured source export + File16 context provider with diagnosis/prescription/emergency authority false |

No Must requirement is intentionally omitted from repository source. Browser/Hostinger/real companion runtimes, backup/restore, rollback and Founder acceptance remain external staging gates and are not represented as completed by this document.

## Future Superset 18 enhancements — approved 2026-08-10

| ID | Enhancement | Repository implementation evidence | External acceptance |
|---|---|---|---|
| F05-FUT-01 | Adaptive Mastery Engine | `LSCH_Future18` competency mastery/confidence/evidence state; assessment/review/practice evidence integration | DB/role/browser staging pending |
| F05-FUT-02 | Spaced Repetition & Memory Science | bounded review queue/ease/interval scheduling; mastery-linked due review | time/cron/browser staging pending |
| F05-FUT-03 | Smart Flashcards & Recall Decks | account-owned flashcard review items with source/competency metadata | browser/accessibility staging pending |
| F05-FUT-04 | Clinical Case Simulation Laboratory | governed de-identified practice blueprint mode `clinical_case_simulation` | clinical-education acceptance pending |
| F05-FUT-05 | Remedy Differentiation Laboratory | governed practice mode `remedy_differentiation`; File06 truth remains external | File06/staging acceptance pending |
| F05-FUT-06 | Case-Taking & Questioning Simulator | governed practice mode `case_taking_simulator`; simulated/de-identified payload only | clinical-education acceptance pending |
| F05-FUT-07 | Repertory Reasoning Training Studio | governed practice mode `repertory_reasoning`; File15 repertory truth remains external | File15/staging acceptance pending |
| F05-FUT-08 | Structured Clinical Reasoning Map | governed practice mode `clinical_reasoning_map` with assessor/manual path | rubric acceptance pending |
| F05-FUT-09 | Oral Viva & Structured Practical Examination | manual-assessor practice mode `oral_viva`, self-assessment blocked, scoped assessor check | real assessor staging pending |
| F05-FUT-10 | Clinical OSCE-Style Stations | manual-assessor `osce_stations` mode with governed blueprint/version | real assessor staging pending |
| F05-FUT-11 | Personal Learning Prescription | deterministic explainable learner path from mastery/review evidence; GET paths are non-mutating | browser/value staging pending |
| F05-FUT-12 | Mistake Book / Learning Error Ledger | private mistake review items automatically created from weak evidence/practice | privacy/browser staging pending |
| F05-FUT-13 | Evidence & Source Appraisal Laboratory | governed `evidence_appraisal` practice mode; source owners remain external | source-workflow staging pending |
| F05-FUT-14 | Digital Learning Portfolio & Competency Passport | private/shareable-by-consent portfolio records fed by completion/practice/CPD | consent/share staging pending |
| F05-FUT-15 | Mentorship & Supervision Program | manager-assigned active mentor/learner relationships, bounded feedback, supervision checks | real-role staging pending |
| F05-FUT-16 | Continuing Professional Development | verified-doctor/Founder CPD records; mastery evidence only after scoped independent verification | real-role/legal wording pending |
| F05-FUT-17 | Source-Grounded AI Socratic Tutor | File16-owned answer provider contract; mandatory sanitized citation; diagnosis/prescription/emergency authority false | File16 integration staging pending |
| F05-FUT-18 | Knowledge-Change Impact & Mandatory Re-study | correction-event impact ledger, targeted review and `LearningRestudyRequired.v1` event | correction/load/File19 staging pending |

These enhancements remain inside File 05 learning/mastery ownership. File 06/12/15/16/17/19/26 retain their canonical truths; simulations and AI do not acquire real-patient diagnosis, prescription or emergency authority.

## 2026-10-11 — Twenty-focus review: privacy gap closure

Source-only corrective IDs:
- **F05-20261011-01 / F05-NFR-002 / F05-FUT-11:** The persisted Future18 personal learning-path rows (`pathways`) are now included in WordPress privacy export, in bounded pages alongside other private learning state.
- **F05-20261011-02 / F05-NFR-002 / F05-FUT-09/10/16:** A user who assessed another learner's simulated practice or verified CPD may have actor identifiers in those learners' records. The export now gives actor-only, redacted projections; the eraser now clears `practice.assessor_id` and `cpd.verified_by` outside active legal hold, preserving academic result records.
- **Regression:** `tests/future18-privacy-contract.php` invoked from `tests/source-invariants.sh`; WordPress staging role/privacy export/erasure and File24 legal-hold acceptance remain pending.
- **Evidence:** `REVIEW-20-FOCUSED-2026-10-11.md`. This is source correction evidence, not a claim of WordPress, staging or live acceptance.

## 2026-10-11 — Second 20-focus inspection and source-closure addendum

- `F05-20261011-B01` (**FR-002/003, NFR-001/002; Files 00/20/26**): `LSCH_Policy::publicly_visible()` and `LSCH_Frontend::public_catalog_posts()` now prevent a public homepage list from showing published but account/restricted books, courses and programs. The public list does not elevate entries for the logged-in viewer.
- `F05-20261011-B02` (**FR-003/004/010, NFR-002/004; Files 20/24/25**): learning dashboard/mastery/cohort/assessment/assignment and viewer-sensitive learning singular requests get no-cache/noindex private headers. Guest public items keep the existing public reading behavior.
- `F05-20261011-B03` (**FR-004/006/018, NFR-003; Files 06/19**): lesson save path reuses the guarded `LSCH_Content::bump_version` instead of independently incrementing a version already changed by `post_updated`.
- Regression: executable `tests/public-visibility-version.php`; required in `tests/source-invariants.sh`.
- Full evidence and twenty scoped results: `REVIEW-20-CYCLE-B-2026-10-11.md`. Static/source and fixture success are NOT production/WordPress/LiteSpeed companion acceptance.

## 2026-10-11 — Third independent 20-focus audit (Cycle C)

- `C01 / F05-NFR-001/002 / F05-FR-003`: File05 canonical `_lsch_access` classification now recognizes explicit only `public`, `account`, `restricted`; malformed non-empty values, including `public!` or arrays, fail closed. Legacy missing value remains publicly readable under the existing single-free-tier public catalog rule. Cross-files: 00 identity; 20/25 presentation; 26 public search.
- `C02 / F05-FR-004/018 / F05-NFR-001/003`: Admin post-meta update strictly validates `_lsch_access`, rejecting invalid form input instead of downgrading to public; valid changes follow File05 version rules and audit evidence.
- `C03 / F05-NFR-002/003 / F05-FUT-01..18`: Future18 privacy exporter detects any failed `get_results` query and returns an explicit error, never a partial completed export.
- `C04 / F05-NFR-002/003`: both core File05 and Future18 privacy erasers keep `done=false` on failed DB operations; escalation/retry and File24 legal hold remain mandatory.
- Tests: `tests/public-visibility-version.php`, `tests/access-governance-contract.php`, `tests/future18-privacy-contract.php`, `tests/core-privacy-erasure.php`, all under `tests/source-invariants.sh`.
- Full round-specific report: `REVIEW-20-CYCLE-C-2026-10-11.md`. Code and fixture evidence is not peer staging, deployed DB or live proof.

## 2026-10-11 — Fourth independent 20-focus audit (Cycle D)

- **D01 / F05-FR-009 / F05-NFR-001/002 / File24:** WordPress privacy export now selects full assignment submissions only where `user_id` is the requester. A separate bounded assessor-only projection includes status and version, never another learner's answers, attachments, feedback, appeal or identity.
- **D02 / F05-FR-018 / F05-NFR-002 / Files17/24:** Reviewers' involvement in other authors' correction proposals is exported as limited actor-only metadata, not `reason`, `source_reference` or entire third-party correction rows.
- **D03 / F05-FR-012 / F05-NFR-001/002 / Files06/10/12/15/25:** Native File05 related source must be readable; every foreign target needs current, explicit owner visibility approval through `lsch_related_learning_reference_visible`. Absent/invalid external responder fails closed and degrades safely.
- **D04 / F05-FR-011 / F05-CEN-04 / F05-NFR-001/002 / Files16/17/26:** File26 publicly indexed results use identity-independent `LSCH_Policy::publicly_visible` in both provider adapters. Guest AI/community context refuses ambient logged-in author privileges; explicit viewer permissions remain scoped.
- Tests: `tests/core-privacy-projection.php`, `tests/cross-owner-visibility.php`, `tests/static-invariants.py` (updated safe contract) and `tests/source-invariants.sh`. Exact source-level QA pass `38121924971` on `05d70b932b05343a0ff29d9b600e337c04811aba`, final-doc HEAD QA independently required.
- Detailed review of twenty focus rounds and remaining acceptance gates: `REVIEW-20-CYCLE-D-2026-10-11.md`. This is not staging/live verification.

## 2026-10-11 — Fifth independent 20-focus File05 code audit (Cycle E)

- **E01 / FR-003/004/005/010 / NFR-001/002 / Files00/20/25/26:** public lesson/course/book access now inherits canonical parent publication/access status from `_lsch_course_id`, `_lsch_book_id` and `_lsch_program_id`; authorized managed learning uses governed per-viewer ancestor permissions. Recursive graph is cycle-safe and fail-closed.
- **E02 / FR-004 / NFR-001/002 / File24:** taxonomy WP_Error fails closed in published patient-case lesson permissions; authorized content editors may repair, not guests.
- **E03 / FR-009/018 / NFR-002 / File24:** WordPress privacy exporter no longer leaks another user's consent `evidence_reference` or staff `scope_json` merely because the exporter is the withdrawal or assigning actor. Full rows only for the record owner, with separate strictly projected actor-only activity.
- **E04 / NFR-003/006 / File19:** both background outbox and jobs workers handle failed DB `get_results()` safely, audit the failure and do not mutate any queued record.
- **E05 / FR-004/018 / NFR-002/009 / File24:** daily orphan cleanup no longer destroys historical patient-case learning consent/withdrawal evidence; deletion belongs to governed retention and explicitly approved policy.
- Executable fixtures: `tests/parent-access-consent.php`, expanded `tests/core-privacy-projection.php`, `tests/queue-and-consent-retention.php`; mandatory `tests/source-invariants.sh` runner. Full source review and remaining staging checks: `REVIEW-20-CYCLE-E-2026-10-11.md`.

## 2026-10-11 — Sixth fresh twenty-focus audit (Cycle F)

- **F01 / F05-FR-001,005 / F05-NFR-001,003:** malformed or nonempty garbage prerequisite text cannot silently bypass the course eligibility gate. The parser enforces canonical positive course IDs and valid comma/whitespace delimiters, rejecting mixed text, double commas, zero and self dependencies.
- **F02 / F05-FR-007 / F05-NFR-002,003 / File24:** encrypted private-note key rotation handles failed database reads and unverifiable remaining-count queries with explicit `failed/partial`, audit and `remaining=null`; no success on a DB outage.
- **F03 / F05-FR-003,004,012 / F05-NFR-001,002 / Files00,20,25,26:** lesson REST cache header is public only when the effective canonical public-visibility predicate passes and no protected viewer state is involved. Restricted ancestors and viewer-dependent related links are private/no-store.
- **F04 / F05-FR-004 / F05-NFR-002 / File24:** patient case consent cannot be silently restored by a duplicate same-policy INSERT that resets withdrawal history. New signed consent lifecycle with append-only evidence, DB migration, rollback and Founder approval remains separately **pending**, not implemented.
- Executable source regressions: `tests/prerequisite-integrity.php`, `tests/lesson-rest-cache.php`, `tests/note-rotation-db-errors.php`, `tests/consent-history-protection.php` in `tests/source-invariants.sh`.
- Round-by-round source review: `REVIEW-20-CYCLE-F-2026-10-11.md`. Staging acceptance and exact deployed version remain unverified.

## 2026-10-11 — Seventh independent 20-focus source review (Cycle G)

- G01 / F05-FR-005 / F05-NFR-003 / Files00/19: post-insert enrollment verification fails closed on missing/malformed DB reread, emits no false EnrollmentCreated success event, and audits a read-failure operator escalation. Test: enrollment-postwrite-verification.php.
- G02 / F05-FR-003/011 / F05-NFR-001/002 / Files20/25/26: REST learning catalog fails safely on WordPress taxonomy WP_Error/nonarray lookup, never serializes error objects as valid source facets. Test: catalog-taxonomy-failure.php.
- G03 / F05-FR-011 / F05-NFR-002/003 / File26: private saved-learning-search get_results DB errors are explicit 503, not false empty results or invalid foreach. Test: saved-search-read-failure.php.
- G04 / F05-NFR-002/006 / File24: privacy erasure fails its completion gate when retained-record COUNT cannot be verified; conservative items_retained and retry required. Extended test: core-privacy-erasure.php.
- G05 / F05-NFR-003/006/009 / Files19/24: System Health reports failed DB counts for dead jobs/outbox and stale private-note key records as FAIL, never healthy zero. Test: system-health-db-failure.php.
- All new regression tests added to required tests/source-invariants.sh. Twenty-round evidence registry: REVIEW-20-CYCLE-G-2026-10-11.md. Genuine WP staging/live proof and proper historical re-consent schema remain outstanding.

## 2026-10-11 — Eighth independent 20-focus source audit (Cycle H)

- H01 / F05-FR-004/006/007/010 / F05-NFR-001/003: malformed, empty, duplicate or unsupported `_lsch_required_components` no longer silently reduces course/lesson completion obligations to `content`; only truly absent legacy metadata uses the governed historical default. Test `tests/learning-requirement-failclosed.php`.
- H02 / F05-FR-008 / F05-NFR-003/006: assessment attempts cannot allocate attempt number one after a failed MAX-history DB query. Explicit 503 and existing allocation lock. Test `tests/learning-requirement-failclosed.php`.
- H03 / F05-FR-001/010 / F05-NFR-001/003 / File06/26: `recalculate_completion` must read canonical course competency taxonomy successfully before materializing an earned credential. Taxonomy `WP_Error` or nonarray is fatal to that credit attempt. Test `tests/current-competency-evidence.php`.
- H04 / F05-FR-008/010 / F05-NFR-003 / File00/09: certificate readiness requires the currently governed assessment item_version, not scores from superseded questions. Test `tests/current-competency-evidence.php`.
- H05 / F05-FR-011 / F05-NFR-002/003/006 / File19/26: course analytics fails safely on unverified DB COUNT/AVG instead of returning false zero statistics, preserving minimum-cell aggregate privacy. Test `tests/course-analytics-db-failclosed.php`.
- Tests registered in `tests/source-invariants.sh`. Round-by-round source inspection, root-cause evidence, companion owners and remaining staging gates: `REVIEW-20-CYCLE-H-2026-10-11.md`.

## 2026-10-11 — Ninth Fresh Twenty-Focus Review (Cycle I)
- I01 / File05 private learning notes, File24 encryption/retention: get_note() distinguishes failed WordPress database reads (503) from a truly missing private note; protected note overwrite confusion is not silently allowed.
- I02 / File05 assessment attempts, File19 event integrity: start_assessment() requires verified post-write persisted attempt fields; failed reread returns 503 and bounded audit, not false confirmation.
- I03 / File05 assessed evidence, File19 outbox: submit_assessment() requires verified graded saved result before publishing AssessmentSubmitted.v1. Persisted uncertain results require reconciliation.
- I04 / File05 corrections, File22 reviewer workflow: rest_corrections() fails closed before authorization filtering when get_results() fails.
- I05 / File05 learner progress, earned course evidence: recalculate_completion() rejects failed progress evidence get_results() instead of silently treating that failure as incomplete/no action.
- I06 / File05 operational reconciliation, Files19/24: database cleanup, correction queue reads/writes and value-event retention purge must succeed before lsch_last_reconcile records success. Consent histories are explicitly excluded from automatic deletion.
- Executable focused regressions: tests/ninth-private-note-assessment-read.php, tests/ninth-correction-completion-db.php, tests/ninth-reconcile-failclosed.php; all registered in tests/source-invariants.sh. Full numbered source audit: REVIEW-20-CYCLE-I-2026-10-11.md.
- Real WordPress staging with current File00/06/12/16/17/19/20/22/24/25/26 partner versions, DB schema versions and live parity are NOT verified. Full append-only case re-consent remains separately pending approval and migration.
