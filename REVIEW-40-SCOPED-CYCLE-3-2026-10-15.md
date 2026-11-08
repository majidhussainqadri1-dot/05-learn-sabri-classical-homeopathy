# File 05 — Third 40-Area Independent Focus Review — 2026-10-15

## Exact evidence and limits

- Source freeze: Draft PR #4 branch `codex/file-05-complete-v3.0.0`, SHA `70a130b81462e8ae8f5b1a532f1e8a329d1e2a10`.
- Governing sources read: attached File05 Future-18 v1.1 approved master plan; consolidated Three Central Plans master; File06, File08, File09, File12, File25 and relevant File04/File21 companion plans. Versioned owner contract register `CONTRACTS.md` and source `REQUIREMENTS-TRACEABILITY.md` reviewed.
- Inspection corpus: all nineteen native File05 PHP implementation classes, ~439,162 UTF-8 characters, plus targeted regression tests and code paths.
- The 40 numbered rounds below are **40 distinct, focused code/plan/owner-contract reviews**. They are **not 40 independent full-tree recoding cycles, 40 WordPress staging E2E tests or proof that no uninspected defect exists**. Source-anchor presence is weaker than implemented behavior.
- Initial static diagnostic: 36/40 focus predicates matched, four flags (#04, #06, #18, #28). #06 was a false positive because `refresh_progress_from_evidence` delegates to `progress` and the latter writes `lesson_version`; #28 is a real-world visual verification gap rather than a proven coding defect. #04 and #18 were confirmed code defects. A call-path extension to #18 / NFR reliability additionally demonstrated a separate persisted-version defect in `LSCH_Content::bump_version`.

## Forty completed focus rounds

| # | Approved requirement / cross-file owner | Observed evidence or finding |
|---:|---|---|
| 01 | F05-FR-001 — Curriculum four levels, sixteen topics and prerequisite evidence | Source structures and owner boundary inspected; real runtime proof separately pending |
| 02 | F05-FR-002 — Eight Founder book draft seeds, provenance and non-public catalog status | Eight only draft slots; actual authored bibliographic/chapters and public course content not proven. |
| 03 | F05-FR-003 — Program/course catalog access, metadata and REST projection | Source structures and owner boundary inspected; real runtime proof separately pending |
| 04 | F05-FR-004 — Lesson governance metadata write success and version accuracy | CONFIRMED source defect: unchecked admin metadata write. Corrected with readback verification, bounded reliability audit and regression. |
| 05 | F05-FR-005 — Enrollment identity, prerequisites, postwrite evidence and replay | Source structures and owner boundary inspected; real runtime proof separately pending |
| 06 | F05-FR-006 — Versioned progress and delegated refresh_progress_from_evidence → progress | Initial string scan false-positive: refresh delegates to progress(), which writes lesson_version; no confirmed defect. |
| 07 | F05-FR-007 — Encrypted notes, private bookmarks, export/erase boundaries | Source structures and owner boundary inspected; real runtime proof separately pending |
| 08 | F05-FR-008 — Assessment attempts, current item versions and grading | Source structures and owner boundary inspected; real runtime proof separately pending |
| 09 | F05-FR-009 — Assignments, assessor object scope, appeals and postwrite verification | Source structures and owner boundary inspected; real runtime proof separately pending |
| 10 | F05-FR-010 — Learner dashboard, resume, private cache handling | Source structures and owner boundary inspected; real runtime proof separately pending |
| 11 | F05-FR-011 — Catalog search, filter projection and File26 owner boundary | Source structures and owner boundary inspected; real runtime proof separately pending |
| 12 | F05-FR-012 — File06/12/10/15 related knowledge references | Source structures and owner boundary inspected; real runtime proof separately pending |
| 13 | F05-FR-013 — File17 moderated learning community context bridge | Source structures and owner boundary inspected; real runtime proof separately pending |
| 14 | F05-FR-014 — Teacher/assessor staff assignment and revocation | Source structures and owner boundary inspected; real runtime proof separately pending |
| 15 | F05-FR-015 — Completion snapshots, course version and revocation | Source structures and owner boundary inspected; real runtime proof separately pending |
| 16 | F05-FR-016 — Certificate competence, identity and wording gates | Source structures and owner boundary inspected; real runtime proof separately pending |
| 17 | F05-FR-017 — File00 single-free-tier and donor neutrality | Source structures and owner boundary inspected; real runtime proof separately pending |
| 18 | F05-FR-018 — Correction-note provenance, version and re-study events | CONFIRMED source defect: correction note previously advanced version on failed meta write. Corrected with failure and transaction rollback signalling. |
| 19 | F05-NFR-001 — File00 assertion + File05 object/field authorization | Source structures and owner boundary inspected; real runtime proof separately pending |
| 20 | F05-NFR-002 — Purpose-limited privacy, export, erasure, consent and holds | Source structures and owner boundary inspected; real runtime proof separately pending |
| 21 | F05-NFR-003 — MySQL owner transaction, idempotency, event outbox/retry | Confirmed additional related defect in LSCH_Content::bump_version: failed meta write could emit a false success version. Corrected with readback, audit, zero return and regression. |
| 22 | F05-NFR-004 — Paginated/bounded assessment, progress and course queries | Source structures and owner boundary inspected; real runtime proof separately pending |
| 23 | F05-NFR-005 — Accessibility semantics and real browser/zoom/screen-reader gate | Source structures and owner boundary inspected; real runtime proof separately pending |
| 24 | F05-NFR-006 — Safe observability and operator audit evidence | Source structures and owner boundary inspected; real runtime proof separately pending |
| 25 | F05-NFR-007 — Core/aux/Future18 schema, installation, rollback and restoration | Source migrations and docs present; real DB, upgrade/rollback evidence absent. |
| 26 | F05-NFR-008 — System Check, safe mode, queues, maintenance, repair | Source structures and owner boundary inspected; real runtime proof separately pending |
| 27 | F05-NFR-009 — PHP 7.4/8.3 syntax and real WordPress compatibility gate | Source structures and owner boundary inspected; real runtime proof separately pending |
| 28 | F05-NFR-010 — English base / Urdu-Arabic RTL and File25 browser visual acceptance | Not proven in source: real Urdu/Arabic RTL, 400% zoom and File25 visual acceptance require browser staging. |
| 29 | File00 — Current identity contract, approved status, suspension and guardian | Source structures and owner boundary inspected; real runtime proof separately pending |
| 30 | File06 — Knowledge source/edition/citation canonical owner | Source structures and owner boundary inspected; real runtime proof separately pending |
| 31 | File08 — Case consent educational boundary vs real clinic/patient data | Source structures and owner boundary inspected; real runtime proof separately pending |
| 32 | File09 — Verified professional and teacher/mentor role dependence | Source structures and owner boundary inspected; real runtime proof separately pending |
| 33 | File12 — Protected digital reading and document permission owner | Source structures and owner boundary inspected; real runtime proof separately pending |
| 34 | File15 — Repertory training reference without canonical database duplication | Source structures and owner boundary inspected; real runtime proof separately pending |
| 35 | File16 — Citation-gated Socratic tutor with no clinical authority | Source structures and owner boundary inspected; real runtime proof separately pending |
| 36 | File17 — Cohort context without message-delivery ownership | Source structures and owner boundary inspected; real runtime proof separately pending |
| 37 | File19 — Outbox projection, retry/dead letters, quiet hours external delivery | Source structures and owner boundary inspected; real runtime proof separately pending |
| 38 | File20/25 — Global shell and profile-design ownership | Source structures and owner boundary inspected; real runtime proof separately pending |
| 39 | File21/22 — Feed/composer publication integration without cross-owner writes | Source structures and owner boundary inspected; real runtime proof separately pending |
| 40 | File24/26 — Consent, privacy assurance, public discovery and cache boundaries | Source structures and owner boundary inspected; real runtime proof separately pending |

## Three newly confirmed defects and corrective source changes

**C3-D01 — Administrative learning meta write false success (FR04, NFR03, Files00/20/22).**
`LSCH_Admin::save_governance_meta` marked an attempted metadata update as changed even when `update_post_meta` failed, then invoked version bump. Corrected: verify write/readback; audit `learning_governance_meta_write_failed` with field ID and no sensitive field values; do not count the failed field as changed. Regression expanded in `tests/access-governance-contract.php`. **Limit:** multi-field admin edit still lacks proven all-or-nothing transactional rollback; successful earlier field writes can remain after a later failure. This is a separately tracked operability/atomicity gap, not hidden by source green.

**C3-D02 — Correction-note provenance ignored (FR18, NFR03, File22).**
`LSCH_State::apply_correction` formerly ignored a failed `_lsch_correction_note` metadata write and still advanced canonical version and generated correction events. Corrected: reject a failed/unverified note write with a typed correction error and delegate entire REST rollback to `LSCH_Idempotency::after_callbacks`. Added a failure-injection scenario to `tests/correction-nested-transaction-atomicity.php`. Real MySQL state and cache assertions are still staging-gated.

**C3-D03 — False version-bumped event from failed canonical metadata write (FR04/18, NFR03).**
`LSCH_Content::bump_version` formerly did not verify `update_post_meta`, then updated its per-request version map and published `lsch_content_version_bumped`. Corrected: check persistence/readback, audit `learning_content_version_write_failed`, return 0, and do not cache/publish a fictitious version. New `tests/content-version-persistence.php` covers failure, success, and same-request replay. **Limit:** WordPress post body and plugin metadata updates outside a single transaction are not globally atomic merely because this false notification is now prevented.

## Evidence of correction and QA
- Three source fixes plus regressions are on Draft PR #4, not `main`.
- `tests/source-invariants.sh` includes new version persistence regression and existing expanded correction and governance tests.
- Source/test corrected exact SHA `02a3e413469ec07c35964bc3e9c43d82e9de19c7`.
- Required `File 05 Current Plans Integrity` GitHub Actions Run **38774530911** completed `success` on this exact SHA. Job-level details should be independently checked before treating the result as four-job green.
- A later status/documentation commit requires its own exact-HEAD rerun before a newer source-complete QA claim.

## Remaining plan incompleteness and release gates
1. Historical **append-only patient-case re-consent** after withdrawal remains unimplemented pending formal schema, owner and privacy/rollback decisions. Never revive old consent in place.
2. **Mentor feedback archival** above 100 historical entries requires governed append-only storage and migrations; existing safe 409 on capacity is not complete archival functionality.
3. **Eight Founder books** are private draft placeholders; actual titles, editions, chapters, approved learning content and publication acceptance are not proven.
4. Staging proof absent for real File00/08/09/12/16/17/19/20/24/25/26 contracts, WordPress/MySQL concurrency, Cache/LiteSpeed, patient-case revocation, true rollback/compensation, RTL accessibility and privacy export/erase.
5. Source-level known limits: nontransactional WordPress administrative form metadata writes, post content + metadata cross-table consistency and real live event/queue delivery remain unresolved or acceptance-gated. No claim of universal zero defects.

## Mandatory reality register
| Reality | Verified result |
|---|---|
| `main` HEAD at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Audited PR source freeze | `70a130b81462e8ae8f5b1a532f1e8a329d1e2a10` |
| Corrected source/test SHA before report | `02a3e413469ec07c35964bc3e9c43d82e9de19c7` |
| Source-declared runtime | 4.0.0 |
| Source-expected database schemas | Core 18 / Auxiliary 3 / Future-18 2 |
| Deployed Version / package checksum | UNVERIFIED |
| Live DB Version / actual schema | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
