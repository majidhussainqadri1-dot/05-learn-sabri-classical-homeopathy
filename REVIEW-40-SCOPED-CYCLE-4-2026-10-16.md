# File 05 — Fourth 40-Area Plan/Repository Audit, 16 October 2026

## Scope and exact source
- Frozen PR #4 source HEAD: `fb4a4fe5b7a00226b786159149d3fab77839efb4`; baseline `main`: `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. This PR remains Draft/unmerged.
- The **attached primary master specification**, `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx`, was read directly, including its F05-FR-001…018, F05-NFR-001…010, privacy, UI, integrations, state/transaction and migration requirements.
- The attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026` and File06/08/09/12/25 counterpart plans were also inspected. The **current override in the primary plan** explicitly preserves the single-free-tier model and educational canonical owner against superseded legacy paid membership text in some historic central-plan sections.
- All 19 File05 implementation class sources (~439 KB) were inspected through their GitHub exact-SHA files; regression files, `CONTRACTS.md`, `ARCHITECTURE.md` and `REQUIREMENTS-TRACEABILITY.md` were checked.
- These **40 independently identified technical topics** are scoped review passes, NOT 40 complete independent repository sweeps, not 40 real WordPress E2E tests, and not proof that every requirement is implemented to acceptance. Source symbol/pattern presence supports only preliminary source implementation, not staging/live truth.

## Forty topics and findings
| Review | Governing requirement | Scoped code/contract subject | Evidence / disposition |
|---:|---|---|---|
| 01 | F05-FR-001 | 4 levels; 16 topic taxonomy; prerequisite gate | Source present |
| 02 | F05-FR-002 | Eight Founder-book catalog slots and governed edition/chapter data | Draft slots present; real book data not populated/verified |
| 03 | F05-FR-003 | Public program/course catalogue and REST DTO | Source path present; browser acceptance pending |
| 04 | F05-FR-004 | Lesson editor governance metadata write and persistence | NEW D01 fixed: previously counted failed meta write as successful |
| 05 | F05-FR-005 | Enrollment identity, eligibility, prerequisite and replay guard | Source guard present |
| 06 | F05-FR-006 | Private progress/version and delegated refresh path | False-positive cleared: refresh_progress_from_evidence delegates to progress(), which writes lesson_version |
| 07 | F05-FR-007 | Bookmarks, encrypted notes, export/erasure | Source path present; real backup/key rotation unverified |
| 08 | F05-FR-008 | Knowledge checks, assessment attempt versions and integrity | Source path present |
| 09 | F05-FR-009 | Assignment submission/assessor grading and appeal | Source path present |
| 10 | F05-FR-010 | Dashboard, resume and private learning projections | Source path present |
| 11 | F05-FR-011 | Learning discovery and File26-owned global ranking | Adapter present; live contract unverified |
| 12 | F05-FR-012 | Knowledge/video/PDF/repertory typed references | File06/10/12/15 linked boundary present |
| 13 | F05-FR-013 | Moderated learning/community context only | File17 adapter present |
| 14 | F05-FR-014 | Teacher/reviewer/assessor scoped permissions | Source guards present; real-role acceptance pending |
| 15 | F05-FR-015 | Completed-course competency snapshot and revocation | Source checks present |
| 16 | F05-FR-016 | Identity + academic certificate readiness | Source gate present; credentials external/legal acceptance unverified |
| 17 | F05-FR-017 | Single-free-tier entitlement based on File00 | Source contract present; live File00 unverified |
| 18 | F05-FR-018 | Versioned learning correction & metadata provenance | NEW D02 fixed: correction note write failures no longer generate false version/event |
| 19 | F05-NFR-001 | Object/field authorization and current File00 assertions | Source guard present |
| 20 | F05-NFR-002 | Privacy, consent, export, erase, legal holds | Source lifecycle present; renewed consent unresolved |
| 21 | F05-NFR-003 | Idempotent transactions, outbox, content-version write reality | NEW D03 fixed: false version-bumped event on failed canonical metadata write |
| 22 | F05-NFR-004 | Query bounds, pagination, performance targets | Bounded source reads present; measured p75/p95 missing |
| 23 | F05-NFR-005 | Keyboard, semantic focus, labels, zoom and screen reader | Staging visual/a11y gate open |
| 24 | F05-NFR-006 | Audit logs, telemetry and safe error handling | Source implementation present |
| 25 | F05-NFR-007 | Migration, rollback, reset and backup/restore | Documented paths; live schema/restore proof missing |
| 26 | F05-NFR-008 | System Check, safe repair, queues and safe mode | Source operations present; operational drill pending |
| 27 | F05-NFR-009 | WordPress/PHP/Hostinger compatibility | PHP 7.4/8.3 CI passed; real WordPress runtime unknown |
| 28 | F05-NFR-010 | en-US, Urdu/Arabic RTL/timezones and visual acceptance | Initial scan flag classified NOT a proven source defect: browser RTL, zoom, locale verification missing |
| 29 | X-F00 | Identity/guardian/approved/suspension canonical claims | No local claims override; installed File00 parity unknown |
| 30 | X-F06 | Knowledge source citations, links and ownership | Related references only; live File06 verification missing |
| 31 | X-F08 | Educational case consent vs clinical patient owner | Boundary code present; append-only reconsent pending |
| 32 | X-F09 | Doctor/mentor professional approval and access | Contract reliance present; live File09 unknown |
| 33 | X-F12 | PDF book/document access and download provider | Typed adapter; actual permission/rights staging pending |
| 34 | X-F15 | Repertory teaching references rather than repertory truth | Practice/read reference path present |
| 35 | X-F16 | Cited Socratic tutor, no prescription authority | Educational AI adapter; provider behavior unverified |
| 36 | X-F17 | Community cohort context vs transport owner | Community context bridge present |
| 37 | X-F19 | Notification outbox, retry, dead letter and delivery | Source queue present; delivery and atomicity staging missing |
| 38 | X-F20/25 | Shell layout/design tokens/RTL ownership | Shell adapter present; cross-module visual acceptance missing |
| 39 | X-F21/22 | Feed publication and composer authoring boundaries | Source bridge present; external owner deployment unknown |
| 40 | X-F24/26 | Privacy/erasure and public search index ownership | Bounded projections present; CDN/index purge unverified |

## Defect register: this cycle (new defects only)
| ID | Verified root cause | Correction committed | Regression coverage |
|---|---|---|---|
| C4-D01 | `LSCH_Admin::save_governance_meta` counted rejected WordPress meta writes as successful and could bump version based on an unsaved governance field | Verify persistence/readback, audit `learning_governance_meta_write_failed` and do not count rejected field | Extended `tests/access-governance-contract.php` |
| C4-D02 | `LSCH_State::apply_correction` ignored rejected `_lsch_correction_note` persistence, then could advance correction version and emit success | Verify the actual correction reason before versioning; reject and let the outer REST transaction roll back | Extended `tests/correction-nested-transaction-atomicity.php` |
| C4-D03 | `LSCH_Content::bump_version` ignored rejected `_lsch_version` persistence, then cached and published a fictitious new content version | Verify write+readback; audit failure; return zero; suppress the false version-bumped notification | New `tests/content-version-persistence.php`, registered in mandatory CI |

**Correction count:** 3 new proven source defects; 3 code corrections; 3 test changes (two expanded files, one new file). A review #6 initial flag was a code-search false positive, not a defect; review #28 is a browser acceptance gap, not a proven missing source instruction.

## Source verification
- Post-correction source/test HEAD before this report: `02a3e413469ec07c35964bc3e9c43d82e9de19c7`.
- GitHub Actions `File 05 Current Plans Integrity` run **38774929922** on later corrected HEAD `fb4a4fe5b7a00226b786159149d3fab77839efb4`: **SUCCESS**, four jobs (source-integrity, PHP syntax 7.4, PHP syntax 8.3, reproducible package) independently confirmed.
- Adding this report changes HEAD; separately verify the new exact HEAD workflow. Do not reuse old run as proof for new HEAD.
- No merge to `main`, no deployment, no live DB read or actual WordPress acceptance was performed.

## Known remaining blocks and limitations
1. **Re-consent** after withdrawn patient case permission remains deliberately fail closed; compliant append-only history needs formally approved schema, File08/File24 privacy boundary, migration, rollback and acceptance.
2. **Mentor-feedback append-only archive** beyond the existing safe limit of 100 entries remains unimplemented, pending approved schema/retention/export/migration.
3. **Eight actual Founder books**: eight private draft placeholders are seeded, but full validated bibliographic titles/editions/chapters/published curriculum cannot be inferred.
4. **All-or-nothing multi-field admin edit**: failed fields are no longer counted as successes, but the whole admin form has no proven atomic rollback over earlier successful field writes; this is a known transactional work item.
5. Real cross-owner File00/06/08/09/12/16/17/19/20/21/22/24/25/26 installed versions and contract checks, MySQL races, post/meta/outbox atomicity, cache/SEO privacy, recovery/migrations, legal holds, accessibility/RTL, and deploy→live retest still need staging/live evidence.
6. No new speculative fixes added for the dependent re-consent/archive/schema features without approved data model and Founder governance; source safety has priority over accumulating patches.

## Required reality register
| Field | Status |
|---|---|
| Repository HEAD before this report | `fb4a4fe5b7a00226b786159149d3fab77839efb4` (Draft PR #4) |
| `main` HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Source-declared plugin version | `4.0.0` |
| Expected source schemas | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed Version / checksum | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
