# File 05 — Eighteenth Twenty-Focus Source Review (Cycle R, 2026-10-14)

## Exact repository evidence and documents
- Frozen audited Draft PR #4 HEAD: `8663f5d087a6237f67561f4c80c7fa8cc46830e5`, branch `codex/file-05-complete-v3.0.0`. The base/main at freeze was `e2667d69d43d655e3d86b0a358dec5f28b61dd96`. The PR is draft and not merged. **The source branch differs from the last Cycle Q HEAD, so the newer exact frozen HEAD was inspected.**
- Read directly the attached File05 v1.1 Future-18 Master Plan and the Three Central Plans Consolidated Governing Master Plan v3.0. Consulted attached File06 knowledge and File08 worldwide-clinic plans to check canonical ownership. Read the actual GitHub source for the owner interfaces, domain authorization, WordPress privacy, Future18, content, REST, frontend, source-level events and background jobs. No deployed WordPress or MySQL evidence was available.
- The plans say a coding-ready requirement is **not** proof of coding, staging, deployment or live acceptance. Central-plan rules require exact-owner boundaries and durable event delivery with retries, audit and proof of migration/rollback; File05 remains owner of educational evidence, while File06 owns encyclopedia and File08 owns real clinic/patient operations.
- These are **twenty distinct focused source review topics**, not twenty full-system source rereads, not twenty independent end-to-end tests and not 100% implementation-certification.

## Twenty individually identified focus areas
| # | Focus, source or companion owner | Outcome |
|---:|---|---|
| 1 | Native education post types, learning levels, content and taxonomy (`LSCH_Content`) | Source definitions inspected, full published curriculum and book content not proven |
| 2 | File06 encyclopedia/educational references and changed-source attribution | File05 bridge and ownership reviewed; actual File06 deployed revision unknown |
| 3 | File08 real patient and clinic workflow versus File05 educational case/simulated practice | Owner separation checked at source; deployed clinical integration not validated |
| 4 | File00 account, guardian, current approval and role claims (`LSCH_Dependencies`) | Source gates reviewed; runtime provider/claims parity unknown |
| 5 | File09 doctor verification, teachers/assessors and current eligibility (`LSCH_Capabilities`) | Source role gate reviewed; actual File09 response unverified |
| 6 | File12 protected PDF/assignment attachments and canonical document owner | File05 source reference boundary inspected, external artifact validation unverified |
| 7 | File20 application shell and File25 profile/visual student views (`LSCH_Frontend`) | Source rendering methods reviewed, real visual, RTL and accessibility tests absent |
| 8 | File26 public search visibility and private caching (`LSCH_REST`/`LSCH_Policy`) | Source hooks reviewed, actual external CDN/index parity unverified |
| 9 | Course enrollment, prerequisite parsing and current academic authority (`LSCH_Services`) | Existing fail-closed guards found; real WP/MySQL race tests absent |
| 10 | Assessment scoring, current-version attempts and replay semantics | Existing source and tests reviewed; no new proven defect in inspected pathway |
| 11 | Submission grading and appeal, independent File09 assessor scope | Existing source guards reviewed; actual role and database integrations pending |
| 12 | Historical course completion, revocation, certificate and File19 events | Existing source checks reviewed; exact state/outbox atomicity unproved |
| 13 | Future18 spaced review, mastery, portfolio and error propagation | Previous error handling confirmed in inspected paths; partial writes still need reconciliation |
| 14 | Mentor assignment, reactivation and existing mentorship history | Source now blocks silent historical reactivation and requires governed reassignment |
| 15 | **Mentor Feedback historical JSON integrity, Future18/portfolio** | **R01 proven defect:** corrupt/non-list prior `feedback_json` decoded to empty array and allowed an overwrite |
| 16 | CPD record, verification, portfolio and competency evidence | Source partial-evidence errors reviewed; real staging cross-module tests pending |
| 17 | File19 durable outbox/inbox, handler payload, background worker | Present source guards inspected; provider delivery/real retry pending |
| 18 | File22 academic editorial correction and source evolution (`LSCH_State`) | Versioned correction source paths present; external publisher authorization not tested |
| 19 | File24 privacy export/erase and active legal hold (`LSCH_Privacy`) | WordPress PHP source reviewed, live user rights workflow and DB retention untested |
| 20 | Core/aux/Future18 migrations, GitHub reproducible package, backup/rollback and live parity | Source migration/test assets exist; actual schema, wp_options, deployed checksum and staging/live unknown |

**Result: 20 different source focus checks; exactly one newly proven defect R01 in the checked branches.** Other checks did not establish new confirmed omissions, which is not a conclusion of complete plan implementation.

## Root cause and implemented change R01
- **Cause:** `LSCH_Future18::mentor_feedback` decoded the existing academic `feedback_json` to PHP, but replaced any invalid/null/object-shaped data with a blank array. The subsequent optimistic-version update could then replace the historic feedback with only the newest entry.
- **Correction:** Reject invalid or non-list historical JSON before any mutation; emit bounded `future18_mentor_feedback_history_invalid` audit and a typed 503 `lsch_future18_mentor_feedback_history_invalid` for operator reconciliation. Do **not** fabricate an empty prior history or silently repair a damaged historical evidence record.
- **Test:** New `tests/eighteenth-mentor-feedback-history.php` covers corrupt JSON, object-shaped JSON, null JSON, partial JSON and zero DB updates. Included in mandatory `tests/source-invariants.sh`. Four source CI jobs succeeded on code/test HEAD `79a127d951cef6cc345124de12f66419d2555b40` (run 38527982251). After this documentation write, confirm the exact final HEAD's own successful workflow again.
- Source code only: no schema migration, live update or case-record edit performed.

## Explicit unresolved requirements
- Full append-only case re-consent after withdrawal, with Founder-approved schema/migrations, File24 privacy hold/retention and File08 clinical owner separation, remains pending.
- Actual WordPress/MySQL state+outbox atomicity for academic grade/mastery/portfolio, File19 consumer delivery, cross-file deployed version parity, real File12 protected-document rights, File26 caches and File09 credential claims are not established by PHP mock tests.
- Conduct live-state freeze before any actual live bug diagnosis: active plugin deployed ZIP/checksum and runtime, exact DB versions/schema/rows/wp_options, migrations/dependencies/errors; prove repo-to-deploy parity before repository root-cause work. Only an approved and independently retested deploy can be marked resolved.

## Mandatory five-part state register
| Reality | Status |
|---|---|
| Repository frozen audit HEAD | `8663f5d087a6237f67561f4c80c7fa8cc46830e5` |
| Corrected source/test HEAD | `79a127d951cef6cc345124de12f66419d2555b40` |
| Repository base/main at freeze | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| Draft PR #4 | Open, draft, unmerged |
| Source-declared plugin version | 4.0.0 |
| Source-expected DB schema versions | Core 18 / Auxiliary 3 / Future18 2 |
| Deployed plugin version/checksum | UNVERIFIED |
| Live DB version and schema | UNVERIFIED |
| Migration state | UNVERIFIED |
| Live verification status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
