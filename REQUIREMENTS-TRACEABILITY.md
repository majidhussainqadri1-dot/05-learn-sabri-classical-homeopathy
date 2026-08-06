# File 05 Requirements-to-Code Traceability — 2.0.0

| Requirement | Implementation | Automated evidence |
|---|---|---|
| F05-FR-001 Curriculum architecture | `LSCH_Content`: four levels, 16 topics, competency taxonomy, prerequisite/equivalence/version metadata | static invariants; PHP lint |
| F05-FR-002 Founder book catalog | eight governed non-public seed slots; edition/authorship/copyright/course metadata | static invariants; activation review |
| F05-FR-003 Program/course catalog | program/course CPTs, public routes, metadata, catalog REST/UI | static invariants; route checks |
| F05-FR-004 Lesson authoring | lesson CPT, objectives/sources/reviewer/version/accessibility/safety gates, File 22 adapter | static invariants; publication gate review |
| F05-FR-005 Enrollment | normalized enrollments, prerequisite/verified-entry/idempotency/state transitions | policy unit tests; route checks |
| F05-FR-006 Progress tracking | normalized progress derived from required components, resume/version/needs-review/export/reset lifecycle | static invariants; privacy tests |
| F05-FR-007 Bookmarks/private notes | bookmarks plus AES-256-GCM account-owned notes, conflict versioning, export/erase | encryption unit test |
| F05-FR-008 Knowledge checks | versioned assessment blueprint, explicit start, server-side expiry, bounded attempts, idempotent scored attempts and feedback | static invariants; route checks |
| F05-FR-009 Assignments and review | submissions, File 12 validated attachment references, rubric version, scoped conflict-cleared assessor/feedback, appeal state and PII-minimized privacy lifecycle | static invariants; authorization review |
| F05-FR-010 Course dashboard | private dashboard for enrollment, reminders, progress, bookmarks, notes, attempts, submissions, weak-area review and completion records | static invariants; no-cache review |
| F05-FR-011 Learning search | catalog REST and public UI filters by type/topic/level/search with access recheck | route/static tests |
| F05-FR-012 Related knowledge | normalized owner-reference table and query; no copied external truth | table/static checks |
| F05-FR-013 Learning community bridge | cohort entity, File 17 context provider, study-circle/Q&A boundary and event/filter extension points | contract review |
| F05-FR-014 Teacher/assessor governance | scoped staff assignments, roles, conflict state, least-privilege capabilities and audit | static/auth review |
| F05-FR-015 Completion records | versioned competency snapshots surviving access-model changes | table/static checks |
| F05-FR-016 Certificate readiness | identity/integrity/status/revocation fields; no automatic legal certificate claim | table/static checks |
| F05-FR-017 Education entitlement | Founder-amended complete free tier; verified account gates only; no payment truth | free-tier unit test |
| F05-FR-018 Content correction/versioning | version increment, correction reason, learner `needs_review`, correction event | static/event checks |
| F05-NFR-001 Object/field authorization | File 00 claims + native object/state checks; REST permission callbacks; anti-IDOR 404/403 behavior | static review |
| F05-NFR-002 Privacy lifecycle | explicit private tables, noindex/no-cache, WordPress export/erase, legal hold | privacy code review |
| F05-NFR-003 Reliability | outbox/inbox/jobs, retries, dead state, reconciliation and safe mode | static table/event checks |
| F05-NFR-004 Performance | bounded pages/limits, indexed tables, lazy media, public/private cache separation | static review |
| F05-NFR-005 Accessibility | semantic headings/landmarks, labels, focus, keyboard controls, reduced motion, RTL logical CSS | CSS/static review; staging pending |
| F05-NFR-006 Observability | structured audit, trace IDs, System Check, Site Health and redacted reasons | static review |
| F05-NFR-007 Migration/rollback | schema checkpoints, legacy CPT/meta/progress/bookmark migration, retained rollback data | migration review; staging pending |
| F05-NFR-008 Operability | System Check, safe mode, owner-scoped dry-run repair, queue/outbox inspection and reconciliation | static review |
| F05-NFR-009 Compatibility | PHP 7.4/8.3 lint matrix; WordPress contract guards | CI matrix |
| F05-NFR-010 Localization | US-English strings, translation domain, Urdu-safe Unicode, RTL logical CSS and locale-ready dates | static/unit checks; linguistic QA pending |

No Must requirement is intentionally omitted from source. Real browser, Hostinger, companion-runtime and Founder acceptance evidence remains governed by `STAGING-ACCEPTANCE.md`.
