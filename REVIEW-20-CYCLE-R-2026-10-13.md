# File 05 — Eighteenth Twenty-Focus Source Review — Cycle R (2026-10-13)

## Frozen evidence and governing requirements

- Reviewed exact **Draft PR#4 HEAD `2796e988ea853d7516a843989c421143115c9989`**; base/main `e2667d69d43d655e3d86b0a358dec5f28b61dd96`, unmerged. The branch `codex/file-05-complete-v3.0.0` is a historical name; declared source plugin version is **4.0.0**.
- Read supplied `05-File-05-Learn-Sabri-Classical-Homeopathy-Complete-Master-Plan-2026-v1.1-Future-18-Enhancements-FINAL.docx` and `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`. Both explicitly separate specification, code, package, automated QA, staging acceptance and live reality. Central v3.0 requires Founder-approved, dated change control for schema/contract changes. Cross-file checks were performed against File05-owned source routes and the available File06/08/09/12 governing plans; **actual partner deployments not verified**.
- Twenty **separate requirements-and-source focus areas** were screened, not twenty complete WordPress E2E test runs. Existing passing code/test checks from previous rounds were not counted as newly discovered defects.

## Twenty review focus records

| Round | Requirement / canonical owner | Finding |
|---:|---|---|
| 1 | File05 course and learning-level hierarchy vs File06 encyclopedia | Source-owned curriculum architecture present; authored content not proven complete |
| 2 | File05 learning routes vs File20 shell | Source integration exists; real shell version unverified |
| 3 | File25 UI, student dashboard, device/RTL accessibility | Source frontend and dashboard present; browser E2E pending |
| 4 | File00 identity, guardian, login/authorization | File05 policy/claims gates present; deployed File00 unverified |
| 5 | File09 verified tutor/mentor/doctor roles | Source role eligibility present; active doctor verification owner unverified |
| 6 | File12 protected PDF link / assignment media | Source attachment owner hooks present; actual File12 runtime unverified |
| 7 | File08 real clinic/patient versus File05 educational case | Canonical ownership separated; real clinical integration not tested |
| 8 | File16 educational AI learning paths and citations | Source learning-path adapter present, runtime claims unverified |
| 9 | File17 community educational context | Source bridge present; partner privacy acceptance pending |
| 10 | File26 public search/index and projection | Source public visibility rules present; index/cache parity unknown |
| 11 | File05 enroll/withdraw and prerequisite audit | Existing version and read-error guards present |
| 12 | Private learning notes, bookmarks, progress | Existing storage/security source present; live encryption/keyring unverified |
| 13 | Assessments, assignment grading, version evidence | Existing protected source paths present; live real-role tests pending |
| 14 | Course completions, certified results, revoked evidence | Previous fail-closed paths present; true DB+outbox atomicity pending |
| 15 | File22 correction proposals and editorial versioning | Source correction workflow present; staging owner integration unverified |
| 16 | File24 consent withdrawal, right to export/erase, legal hold | Source retention/withdrawal protection present; full append-only patient re-consent still not coded |
| 17 | Future18 mastery, spaced review, portfolio/CPD | Source methods and prior partial-state guards present; staging compensation pending |
| 18 | Future18 mentorship assignment and historical end/reassign | **R01 confirmed:** `assign_mentor` previously ran `ON DUPLICATE KEY UPDATE status='active'`, silently reactivating ended mentorship and overwriting previous goals/incrementing version without an approved re-grant |
| 19 | File19 outbox/inbox and durable background job worker | Prior worker reliability guards present; real MySQL retry and consumer semantics unverified |
| 20 | DB migrations, activation, package and rollback vs three central plans | Source expected versions exist; live DB/deployed artifact/migration not frozen |

**Outcome:** 20 distinct focus comparisons, **one newly verified source defect** R01. Other areas had no new defect proven **in the inspected source routes**. No claim that all File05 Master Plan features are coded.

## Confirmed R01 root cause and correction

`LSCH_Future18::assign_mentor()` inserted active mentorship using SQL `ON DUPLICATE KEY UPDATE status='active',goals_json=...,version=version+1`. Existing ended records could be resurrected without explicit consent/history workflow, and the academic trust system could receive a misleading `LearningMentorshipAssigned.v1` event. New source implementation performs a **read of existing mentor/learner/course identity** and:
1. fails with typed `lsch_future18_mentorship_history_read_failed` 503 on unknown DB state;
2. refuses both ended and previously active duplicate records with `lsch_future18_mentorship_reassignment_required` 409 until Founder-approved history-preserving reassignment;
3. performs a plain INSERT only for a genuinely new record (with database unique key still enforcing concurrent duplicate rejection) and emits event only after successful INSERT;
4. makes **no schema/data-model migration**. Proper closed→renewed mentorship records, consent and operator workflow remain a separately governed future change.

New `tests/eighteenth-mentorship-reassignment.php` covers ended/active duplicate, SQL read failure, permitted new record, writes and event behavior, registered in `tests/source-invariants.sh`. Initial CI caught missing `LSCH_Policy::sanitize_json` in the isolated test double, which was corrected without changing plugin business rules. Exact final SHA-matched four-job CI to be recorded separately.

## Unresolved/quality gates

- **Historic patient re-consent** after withdrawal with append-only evidence, File24 legal hold and approved migration/rollback remains incomplete; the present source correctly prevents silent revival but does not implement a governed renewal.
- **Historic mentorship reassignment** likewise intentionally blocked pending approved append-only/data model/interface changes and real File09 active-professional/guardian rules; rejecting unsafe revival is not full workflow implementation.
- State/worker/outbox atomicity and true File19/06/08/09/12/16/17/20/22/24/25/26 plugin integration require actual WordPress/MySQL staging, backup/restore, installation and rollback tests.
- Before any live/staging bug diagnosis, freeze deployed File05 source/version/package checksum, actual core/aux/Future18 schema/version/tables/rows, `wp_options`, current dependencies, migration state and logs. GitHub main, Draft PR, CI and ZIP do not establish deployed parity. No merge or live deployment has occurred in this review.

| Mandatory reality register | Status |
|---|---|
| Reviewed immutable PR HEAD | `2796e988ea853d7516a843989c421143115c9989` |
| Corrected PR HEAD | Refer to final SHA-matched CI |
| Repository main HEAD | `e2667d69d43d655e3d86b0a358dec5f28b61dd96` |
| PR#4 | Draft, open, unmerged |
| Source plugin version | 4.0.0 |
| Source-expected DB | Core 18 / Aux 3 / Future18 2 |
| Exact deployed version/files/package checksum | UNVERIFIED |
| Actual live DB version/rows/migration | UNVERIFIED |
| Staging and live re-test | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
