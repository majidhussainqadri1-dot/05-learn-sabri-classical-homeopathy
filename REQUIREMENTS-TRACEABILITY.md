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
