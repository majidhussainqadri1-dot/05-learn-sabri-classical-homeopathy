# File 05 — V12.3 Requirement-to-Evidence Gap Register

**Date:** 2026-11-14 (Pakistan)  
**Document:** F05-EXEC-V12.3-P01-01  
**Authority:** Sabri Website Master Plan V12.3, especially pp. 39, 79–83, 107–108, 137–139 and 262; File05 execution plan F05-EXEC-V12.3-01.  
**Evidence basis:** File05 PR #5 exact source `ebf888c9ab9e664815f8e4c1cbe20b690f15aee4`, Source traceability, proposed PR #6 and #7. A separate v12.3 integration candidate remains Draft; no live parity is asserted.

## Interpretation and measurable status

This is an **evidence register**, not 53 certificates of completion. An existing PHP class, REST route or successful mock test is *source evidence only*. An item is accepted only after its own negative and successful real WordPress journeys, cross-owner permissions, current content, user-facing presentation, backup/restore and exact package/runtime parity are documented.

| Evidence grade | Meaning |
| --- | --- |
| Specified | Master-plan mandate/ID is written |
| Source-present | Code/fixture is claimed or directly located in candidate source |
| Source-verified | Exact candidate SHA passed applicable automated regression |
| Integrated | Real source-owner contract and lifecycle passed with compatible peer builds |
| Staging-accepted | Actual WP/PHP/MariaDB/LiteSpeed, users/permissions, UI, failure and restore tests passed |
| Live-verified | Deployed ZIP checksum, DB migrations and complete live user journey match the tested build |

**Current aggregate:** 53 IDs traced to repository-documented source evidence, but **0/53 independently established as Live-verified** by this audit. This is an absence of verification, not a finding that zero functions work.

## Canonical File 05 ID register — 53 source claims versus real acceptance gates

| Requirement ID | Required capability | Repository evidence reported | Remaining proof noted by source | Current status |
| --- | --- | --- | --- | --- |
| F05-FR-001 | Curriculum architecture | `LSCH_Content`: four levels, 16 topics, competency taxonomy, prerequisite/equivalence/version metadata | current-plan static invariants; PHP lint | **Source candidate; acceptance pending** |
| F05-FR-002 | Founder book catalog | eight governed non-public seed slots; Founder ID accepted only through a public provider and validated by File 00; edition/chapter/rights metadata | static ownership scan; activation/staging pending | **Source candidate; acceptance pending** |
| F05-FR-003 | Program/course catalog | program/course CPTs, public metadata/routes, access states and catalog REST/UI | route/static checks | **Source candidate; acceptance pending** |
| F05-FR-004 | Lesson authoring | objectives/sources/reviewer/version/accessibility/safety gates, structured-source normalization, File 22 adapter v3 | publish-guard/static checks | **Source candidate; acceptance pending** |
| F05-FR-005 | Enrollment | normalized enrollment state, prerequisite/current File 00 assertion gate, universal mutating-REST idempotency, pause/withdraw/resume | unit/static; real-role staging pending | **Source candidate; acceptance pending** |
| F05-FR-006 | Progress tracking | required-component-derived percent, resume/version/needs-review/export/reset lifecycle | state/static; database staging pending | **Source candidate; acceptance pending** |
| F05-FR-007 | Bookmarks/private notes | account-owned bookmarks and AES-256-GCM notes; new writes require an independent deployment/File24 keyring, not WP auth salts; export/erase | policy crypto unit; key-rotation staging pending | **Source candidate; acceptance pending** |
| F05-FR-008 | Knowledge checks | versioned assessment blueprint, explicit start, expiry, bounded attempts, idempotent score/feedback | static/routes; concurrency staging pending | **Source candidate; acceptance pending** |
| F05-FR-009 | Assignments/review | submission, File12 attachment references, rubric version, scoped conflict-cleared assessor, revision/resubmit/appeal | static/auth; companion staging pending | **Source candidate; acceptance pending** |
| F05-FR-010 | Course dashboard | private no-cache learner record: enrollments, reminders, progress, bookmarks, notes, attempts, submissions, completions | UI/static; browser staging pending | **Source candidate; acceptance pending** |
| F05-FR-011 | Learning search | bounded File05 catalog filters plus account-owned local saved searches; File26 remains global search/discovery/ranking owner with why/freshness projection contract | current-plan ownership scan; File26 staging pending | **Source candidate; acceptance pending** |
| F05-FR-012 | Related knowledge | normalized owner references and File05 value/provider pointers; no copied 06/10/12/15/16 truth | ownership/static; companion staging pending | **Source candidate; acceptance pending** |
| F05-FR-013 | Learning community bridge | File17 context provider with visibility recheck and moderated educational boundary | contract/static; File17 staging pending | **Source candidate; acceptance pending** |
| F05-FR-014 | Teacher/assessor governance | scoped staff, conflict/independent-review gates, public File00 assertions; Curriculum Lead explicitly excludes OPERATE/ASSESS and repair remains named-operator-only | security/static; real-role staging pending | **Source candidate; acceptance pending** |
| F05-FR-015 | Completion records | versioned competency snapshots durable across access-policy changes | state/static; migration staging pending | **Source candidate; acceptance pending** |
| F05-FR-016 | Certificate readiness | private `certificate_readiness()`/REST gate: current File00 identity, earned/non-revoked current-version completion, completed enrollment, clear assessment integrity/minimum pass evidence, jurisdiction + explicitly approved wording; no legal credential claim | static/state; Founder/legal wording acceptance pending | **Source candidate; acceptance pending** |
| F05-FR-017 | Education entitlement | `single-free-tier-v2`; File00 current policy contract must confirm free baseline/no paid unlock/no donor advantage | unit/static; real File00 staging pending | **Source candidate; acceptance pending** |
| F05-FR-018 | Content correction/versioning | proposer/reviewer correction ledger, self-approval conflict gate, sole governed mutation path, object advisory lock, cache-cleared version check, learner `needs_review`, correction events | state/static; database concurrency staging pending | **Source candidate; acceptance pending** |
| F05-NFR-001 | Object/field authorization | File00 `SMC_Contracts::assertions()` contract 1.2.2+ plus native object/state/owner checks; no File00 private meta/table reads | forbidden-coupling scan; real-role IDOR matrix pending | **Source candidate; acceptance pending** |
| F05-NFR-002 | Privacy lifecycle | private tables, noindex/no-cache, export/erase, correction pseudonymization, legal hold | static/privacy code; WP privacy-tool staging pending | **Source candidate; acceptance pending** |
| F05-NFR-003 | Reliability | durable privacy-minimized mutating-REST request ledger after native permission checks; advisory locks; outbox/inbox/jobs/retry/dead-state/reconcile/safe-mode; concurrency-versioned correction state | static/exact-head CI; failure-injection staging pending | **Source candidate; acceptance pending** |
| F05-NFR-004 | Performance | bounded pagination/queries, conditional assets, aggregate-only value metrics, no raw global search warehouse | static; Hostinger p75/p95/load pending | **Source candidate; acceptance pending** |
| F05-NFR-005 | Accessibility | semantic UI baseline, 44px controls, focus, keyboard, reduced motion, RTL logical CSS, text-first capability | CSS/static; human WCAG/browser pending | **Source candidate; acceptance pending** |
| F05-NFR-006 | Observability | structured audit, trace/request IDs, health/System Check, privacy-minimized value events | static; alerts/operator rehearsal pending | **Source candidate; acceptance pending** |
| F05-NFR-007 | Migration/rollback | core schema 18, auxiliary state schema 3, additive request-key ledger, legacy note decrypt compatibility, non-destructive uninstall/rollback docs | static; real upgrade/restore drill pending | **Source candidate; acceptance pending** |
| F05-NFR-008 | Operability | System Check, safe mode, repair/reconcile and key readiness contracts | static; operator rehearsal pending | **Source candidate; acceptance pending** |
| F05-NFR-009 | Compatibility | WordPress 6.6+ header; PHP 7.4/8.3 lint matrix; versioned File00 and cross-file contracts | exact-head CI; WP7.0.1/LiteSpeed staging pending | **Source candidate; acceptance pending** |
| F05-NFR-010 | Localization | American-English base strings, Unicode-safe Urdu/Arabic, RTL logical CSS, locale-safe data | static; mixed-language browser pending | **Source candidate; acceptance pending** |
| F05-CEN-01 | All approved courses/lessons/assessments free; no PKR400/Pro/Premium/AI paywall or donor advantage | File00 current policy is a runtime prerequisite; `single-free-tier-v2`; forbidden-policy scan | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-02 | One integrated free education journey including courses, teacher/cohort, credentials, lifelong learning, source links and low-bandwidth accessibility | File05 curriculum/cohort/completion/citation/download/low-bandwidth capability contracts | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-03 | Sabri Green current brand fallback | exact `#087A4E` File05 fallback; File25 remains design-token owner | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-04 | File26 owns global Search/Discovery/Ranking | File05 provides only learning projection and local filters/saved learning search; `global_rank_owner=file26` | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-05 | One canonical owner; typed/versioned integration, no foreign private storage coupling | File00 public assertion contract; no `_smc_*` meta or `$wpdb->usermeta` reads | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-06 | Healthy use/privacy/value over addictive engagement | opt-in learning reminders; no streak/shame contract; aggregate-only value events | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-CEN-07 | Source-grounded learning/AI and no autonomous clinical authority | structured source export + File16 context provider with diagnosis/prescription/emergency authority false | Live owner/role and release evidence required | **Source candidate; acceptance pending** |
| F05-FUT-01 | Adaptive Mastery Engine | `LSCH_Future18` competency mastery/confidence/evidence state; assessment/review/practice evidence integration | DB/role/browser staging pending | **Source candidate; acceptance pending** |
| F05-FUT-02 | Spaced Repetition & Memory Science | bounded review queue/ease/interval scheduling; mastery-linked due review | time/cron/browser staging pending | **Source candidate; acceptance pending** |
| F05-FUT-03 | Smart Flashcards & Recall Decks | account-owned flashcard review items with source/competency metadata | browser/accessibility staging pending | **Source candidate; acceptance pending** |
| F05-FUT-04 | Clinical Case Simulation Laboratory | governed de-identified practice blueprint mode `clinical_case_simulation` | clinical-education acceptance pending | **Source candidate; acceptance pending** |
| F05-FUT-05 | Remedy Differentiation Laboratory | governed practice mode `remedy_differentiation`; File06 truth remains external | File06/staging acceptance pending | **Source candidate; acceptance pending** |
| F05-FUT-06 | Case-Taking & Questioning Simulator | governed practice mode `case_taking_simulator`; simulated/de-identified payload only | clinical-education acceptance pending | **Source candidate; acceptance pending** |
| F05-FUT-07 | Repertory Reasoning Training Studio | governed practice mode `repertory_reasoning`; File15 repertory truth remains external | File15/staging acceptance pending | **Source candidate; acceptance pending** |
| F05-FUT-08 | Structured Clinical Reasoning Map | governed practice mode `clinical_reasoning_map` with assessor/manual path | rubric acceptance pending | **Source candidate; acceptance pending** |
| F05-FUT-09 | Oral Viva & Structured Practical Examination | manual-assessor practice mode `oral_viva`, self-assessment blocked, scoped assessor check | real assessor staging pending | **Source candidate; acceptance pending** |
| F05-FUT-10 | Clinical OSCE-Style Stations | manual-assessor `osce_stations` mode with governed blueprint/version | real assessor staging pending | **Source candidate; acceptance pending** |
| F05-FUT-11 | Personal Learning Prescription | deterministic explainable learner path from mastery/review evidence; GET paths are non-mutating | browser/value staging pending | **Source candidate; acceptance pending** |
| F05-FUT-12 | Mistake Book / Learning Error Ledger | private mistake review items automatically created from weak evidence/practice | privacy/browser staging pending | **Source candidate; acceptance pending** |
| F05-FUT-13 | Evidence & Source Appraisal Laboratory | governed `evidence_appraisal` practice mode; source owners remain external | source-workflow staging pending | **Source candidate; acceptance pending** |
| F05-FUT-14 | Digital Learning Portfolio & Competency Passport | private/shareable-by-consent portfolio records fed by completion/practice/CPD | consent/share staging pending | **Source candidate; acceptance pending** |
| F05-FUT-15 | Mentorship & Supervision Program | manager-assigned active mentor/learner relationships, bounded feedback, supervision checks | real-role staging pending | **Source candidate; acceptance pending** |
| F05-FUT-16 | Continuing Professional Development | verified-doctor/Founder CPD records; mastery evidence only after scoped independent verification | real-role/legal wording pending | **Source candidate; acceptance pending** |
| F05-FUT-17 | Source-Grounded AI Socratic Tutor | File16-owned answer provider contract; mandatory sanitized citation; diagnosis/prescription/emergency authority false | File16 integration staging pending | **Source candidate; acceptance pending** |
| F05-FUT-18 | Knowledge-Change Impact & Mandatory Re-study | correction-event impact ledger, targeted review and `LearningRestudyRequired.v1` event | correction/load/File19 staging pending | **Source candidate; acceptance pending** |

### Twelve central-value cross-references

| Master-plan ID | Primary File05 owners | V12.3 task requiring fresh evidence |
| --- | --- | --- |
| CV-130 | F05-FR-001 | 4 levels/16 topics, versioned academic approval |
| CV-131 | F05-FR-004, F05-FR-012 | text/audio/video/PDF, source and safety |
| CV-132 | F05-FR-005, F05-FR-006 | enrollment/current progress/re-entry |
| CV-133 | F05-FR-007 | private notes, bookmarks, lawful export |
| CV-134 | F05-FR-008, F05-FR-009 | formative quizzes/assignments/human review |
| CV-135 | F05-FR-013 | study circle through File17 |
| CV-136 | F05-FR-010, F05-FR-014 | teacher dashboard and least privilege |
| CV-137 | F05-FR-015, F05-FR-016 | completion/certificate readiness only |
| CV-138 | F05-FUT-11 | personal learning path |
| CV-139 | F05-FUT-02, F05-FUT-03 | spaced review/flashcards |
| CV-140 | F05-FR-018, F05-FUT-18 | corrected source and targeted re-study |
| CV-141 | F05-FR-017, F05-CEN-01 | single free tier; zero donor advantage |

## V12.3 new cross-cutting gates (do not double count)

| Control | File05 relevance | Exact additional acceptance |
| --- | --- | --- |
| M01 / QA-15 | responsive/browser baseline | 320/390/768/1024/1440 px and keyboard, both directions |
| M02 / QA-16 | WCAG 2.2 AA | applicable manual + automated audit and assistive devices |
| M03 / QA-17 | performance | cold/warm lab, p75 real field quality, payload budgets |
| M04 / QA-18 | multilingual linked source | correct canonical, hreflang, versioned translation/correction |
| M05 / QA-18 | reliable learning sources | author, edition, page/source, rights and correction lifecycle |
| M06 / QA-19 | identity/session/guardian | revocation and secure recovery from canonical File00/02 |
| M07 / QA-20 | consent and learner record | export/erase/hold, owners and no private cache |
| M08 / QA-21 | clinical and AI safety | real File16 citation/uncertainty; human control; no autonomous prescription |
| M09 / QA-22 | media and notifications | File10/12/17/19 failure, cancellation, retry and text fallback |
| M10 / QA-22 | low-bandwidth/offline | public content only; no private device cache after revoke/logout |
| M11 / QA-24 | source→package→staging→live | migration, checksum, rollback and same-build live retest |
| M12 / QA-23 | single free tier | verify File00 policy; zero fee, no donor-linked academic priority |

## Human journeys that must actually work

- **UX-03 / AJ-11:** Level-1 student: eligible signup, enroll, book or lesson, source-verified PDF/video, note, quiz, current progress, resume after device change, accessible error and safe denial.
- **AJ-12:** Teacher/cohort: assigned current teacher sees only their authorized roster and feedback; another learner's private answer, clinical or note data cannot leak.
- **UX-06 / QA-19:** suspend/revoke or guardian withdrawal in mid-request; next protected operation denied without mutation.
- **QA-18 / AJ-13 edge:** File06 concept's exact canonical UUID/version and File05 independently approved lesson links; withdrawal invalidates both.
- **File26 edge:** public-only lesson/course/book indexing; exact owner version and canonical URL on click; deindex/tombstones must be event- and restore-verified.

## Confirmed repository-only findings on 2026-11-14

1. **S-01 — File06 owner assertion version not independently compared.** PR #6 `approve()` required published/owner/UUID but did not compare the returned `version` to the request. File06's own producer *does* validate this version in its inspected draft, so this is a consumer-side defense-in-depth gap, **not a proven live exposure**. A stricter comparison is in the new integrated draft.
2. **S-02 — File26 document reauthorization ignored revision and destination.** PR #7 `can_view()` refetched current public eligibility without requiring the indexed document's `object_version` and `canonical_url` to equal the latest owner document. Draft source is hardened, with regression fixtures.
3. **S-03 — File26 owner readiness incomplete.** PR #7 source `native_ready()` did not query the mandatory File00/01/20 dependency contract and current central policy directly. The integrated draft now fails closed when they are unavailable.
4. **D-01 — Plan marker:** plugin header/source candidate identifies version 4.1.0 but `LSCH_PLAN_VERSION` remains a V12-2026-10-22 string; do not alter a potentially version-bound runtime marker before policy/migration review.
5. **D-02 — Status documentation:** existing `STATUS.md` opens with 4.0.0 while candidate plugin header declares 4.1.0. Refresh only when exact-head source/packaging data is reconciled.
6. **I-01 — Split integration:** PR #6 and #7 separately modify the same bootstrap. Integration draft includes both, without merging either to `main`.

## Remaining blocking evidence (independent of source CI)

- P00: site-scoped Jetpack connector reports disconnected, WPVibe connection probe returned HTTP 530. Neither identifies root cause. Exact deployed plugin, checksum, DB schema and migration cannot be inferred.
- File06 actual versioned plugin runtime, File26 owner index and tombstone/event lifecycle, File16 educational provider, File19 event delivery, File24 legal hold, File08 patient-consent authority and all other companion deployed versions unverified.
- Eight founder books' approved texts, editions, source rights, lessons and independent editorial approval are not established by the eight empty governed seed slots.
- Current patient-case *re-consent after withdrawal* remains explicitly blocked until File08+File24+Founder governance; do not silently enable it.
- No fresh/upgrade staging rehearsal, real shared-device privacy, human assessor practical, restore/rollback, p75/p95 or final approval.

## Acceptance decisions

- **No-GO:** merge/deploy the combined File06/26 adapters to live; index a proposed connector; claim clinical or academic credential; enable withdrawn-case publication.
- **GO for draft-only work:** source review, exact-version tests, branch consolidation, documentation and immutable CI.
- **Next allowed release gate:** P00 exact deployed reality + P01 full differential + File00/06/16/24/26 owner contract acceptance, then controlled staging, two independent reviews and the zero-known-active-defect CEN-REL-001 gate.

**Release truth fields:** Repository main `e2667d69d43d655e3d86b0a358dec5f28b61dd96`; File05 candidate branch based on PR #5 `ebf888c9ab9e664815f8e4c1cbe20b690f15aee4`; deployed version **unverified**; DB **unverified**; migration **unverified**; live verification **not performed**.

> Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
