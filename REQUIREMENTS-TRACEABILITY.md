# File 05 — Current Two-Plan Requirements-to-Code Traceability — 3.3.0

Governing basis: the current consolidated central plan plus the current File 05 master plan. Historical local 3.1/3.2 packages are evidence of prior work only; this table maps the actual GitHub corrective candidate.

| Requirement | Current implementation evidence | Automated / external evidence |
|---|---|---|
| F05-FR-001 Curriculum architecture | `LSCH_Content`: four levels, 16 topics, competency taxonomy, prerequisite/equivalence/version metadata | current-plan static invariants; PHP lint |
| F05-FR-002 Founder book catalog | eight governed non-public seed slots; Founder ID accepted only through a public provider and validated by File 00; edition/chapter/rights metadata | static ownership scan; activation/staging pending |
| F05-FR-003 Program/course catalog | program/course CPTs, public metadata/routes, access states and catalog REST/UI | route/static checks |
| F05-FR-004 Lesson authoring | objectives/sources/reviewer/version/accessibility/safety gates, structured-source normalization, File 22 adapter v3 | publish-guard/static checks |
| F05-FR-005 Enrollment | normalized enrollment state, prerequisite/current File 00 assertion gate, idempotency, pause/withdraw/resume | unit/static; real-role staging pending |
| F05-FR-006 Progress tracking | required-component-derived percent, resume/version/needs-review/export/reset lifecycle | state/static; database staging pending |
| F05-FR-007 Bookmarks/private notes | account-owned bookmarks and AES-256-GCM notes; new writes require an independent deployment/File24 keyring, not WP auth salts; export/erase | policy crypto unit; key-rotation staging pending |
| F05-FR-008 Knowledge checks | versioned assessment blueprint, explicit start, expiry, bounded attempts, idempotent score/feedback | static/routes; concurrency staging pending |
| F05-FR-009 Assignments/review | submission, File12 attachment references, rubric version, scoped conflict-cleared assessor, revision/resubmit/appeal | static/auth; companion staging pending |
| F05-FR-010 Course dashboard | private no-cache learner record: enrollments, reminders, progress, bookmarks, notes, attempts, submissions, completions | UI/static; browser staging pending |
| F05-FR-011 Learning search | bounded File05 catalog filters plus account-owned local saved searches; File26 remains global search/discovery/ranking owner with why/freshness projection contract | current-plan ownership scan; File26 staging pending |
| F05-FR-012 Related knowledge | normalized owner references and File05 value/provider pointers; no copied 06/10/12/15/16 truth | ownership/static; companion staging pending |
| F05-FR-013 Learning community bridge | File17 context provider with visibility recheck and moderated educational boundary | contract/static; File17 staging pending |
| F05-FR-014 Teacher/assessor governance | scoped staff, conflict/independent-review gates, public File00 assertions, no private membership storage coupling | security/static; real-role staging pending |
| F05-FR-015 Completion records | versioned competency snapshots durable across access-policy changes | state/static; migration staging pending |
| F05-FR-016 Certificate readiness | identity/integrity/competence/status/revocation/wording gates; never automatic legal credential claim | static/state; Founder/legal wording acceptance pending |
| F05-FR-017 Education entitlement | `single-free-tier-v2`; File00 current policy contract must confirm free baseline/no paid unlock/no donor advantage | unit/static; real File00 staging pending |
| F05-FR-018 Content correction/versioning | proposer/reviewer correction ledger, self-approval conflict gate, accepted correction version bump, learner `needs_review`, correction events | state/static; database concurrency staging pending |
| F05-NFR-001 Object/field authorization | File00 `SMC_Contracts::assertions()` contract 1.2.2+ plus native object/state/owner checks; no File00 private meta/table reads | forbidden-coupling scan; real-role IDOR matrix pending |
| F05-NFR-002 Privacy lifecycle | private tables, noindex/no-cache, export/erase, correction pseudonymization, legal hold | static/privacy code; WP privacy-tool staging pending |
| F05-NFR-003 Reliability | existing outbox/inbox/jobs/retry/dead-state/reconcile/safe-mode plus concurrency-versioned correction state | static; failure-injection staging pending |
| F05-NFR-004 Performance | bounded pagination/queries, conditional assets, aggregate-only value metrics, no raw global search warehouse | static; Hostinger p75/p95/load pending |
| F05-NFR-005 Accessibility | semantic UI baseline, 44px controls, focus, keyboard, reduced motion, RTL logical CSS, text-first capability | CSS/static; human WCAG/browser pending |
| F05-NFR-006 Observability | structured audit, trace/request IDs, health/System Check, privacy-minimized value events | static; alerts/operator rehearsal pending |
| F05-NFR-007 Migration/rollback | core schema 17, auxiliary state schema 3, legacy note decrypt compatibility, non-destructive uninstall/rollback docs | static; real upgrade/restore drill pending |
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
