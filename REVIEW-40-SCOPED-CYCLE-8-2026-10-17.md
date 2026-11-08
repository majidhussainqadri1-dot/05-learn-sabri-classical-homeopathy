# File 05 Eighth Forty-Focus Governing-Plan and Cross-Owner Source Review - 2026-10-17

## Exact source and limitations
- Frozen initial PR #4 HEAD: 8bbf57d84656a5cb69767ca31cece3027658c477; main: e2667d69d43d655e3d86b0a358dec5f28b61dd96.
- The approved File05 Future18 v1.1 master plan, Consolidated Three Central Plans v3 governing master, File08/09/12/21/25 companion specifications, and File00/06/10/15/16/17/19/22/24/26 interfaces formed the specification basis.
- Forty distinct scoped source/contract checks, not 40 independent exhaustive repository test passes and NOT WordPress staging or live acceptance.
- Initial review: 33 of 40 pattern checks satisfied, 7 flagged. Five flagged source paths were traced to TWO distinct root causes, and two were already known incomplete design-gated requirements.

## Forty rounds - check and disposition
| Round | Requirement / owner | Inspection | Finding |
|---:|---|---|---|
| 01 | FR01 | Four curriculum levels | Present source |
| 02 | FR01 | Sixteen learning subjects | Present source |
| 03 | FR02 | Eight founder draft book slots | Content and editions not yet verified |
| 04 | FR03 | Program/course/book/lesson ownership | Present source |
| 05 | FR04 | Pre-save WordPress publication hook | Present source |
| 06 | FR04 | Avoid gating unrelated post types | Present source |
| 07 | FR04 | New lesson without saved metadata | Pending gate |
| 08 | FR04 | Central policy outage | Pending gate |
| 09 | FR04/File00 | Author and publisher capability | Guard exists |
| 10 | FR04 | Title and body required | Guard exists |
| 11 | FR04 | Objectives, safety and accessibility | Guard exists |
| 12 | FR04/File06 | Source citation requirement | Guard exists |
| 13 | FR04/File09 | Independent reviewer | Guard exists |
| 14 | FR04/File08 | Patient case consent | Guard exists |
| 15 | FR04/File21 | Mandatory successful-case tag | Guard exists |
| 16 | FR04 | Same-save tax_input | D01 fixed, changed term IDs deferred |
| 17 | FR04 | Same-save meta_input | D01 fixed, changed governance metadata deferred |
| 18 | FR04 | Same-save tags_input | D01 fixed, patient case tags protected |
| 19 | FR04/File22 | Same-save lsch_meta admin payload | D01 fixed, changes deferred |
| 20 | FR04 | Status transition events | Source exists, staging ordering pending |
| 21 | FR04 | Failed pending-status persistence | Failure audit exists, real containment pending |
| 22 | FR05 | Enrollment and prerequisites | Guard exists |
| 23 | FR06 | Learner progress and version | Guard exists |
| 24 | FR07 | Private bookmark and encrypted note | Source exists, key restore unverified |
| 25 | FR08 | Assessment versioned attempt | Source exists |
| 26 | FR09 | Assignment and appeal | Source exists |
| 27 | FR10 | Dashboard personal state | Source exists |
| 28 | FR11/File26 | Public catalog without editor privilege | Source exists |
| 29 | NFR02/File08 | Public taxonomy null and malformed member | D02 matching source guard strengthened |
| 30 | NFR02/File24 | Authenticated reader taxonomy failures | D02 fixed, editor repair scope retained |
| 31 | FR12/File06 | Encyclopedia link owner | Canonical foreign pointer |
| 32 | FR12/File10 | Video reference owner | Allowed target enum 10 |
| 33 | FR12/File12 | Document reference owner | Allowed target enum 12 |
| 34 | FR12/File15 | Repertory reference owner | Allowed target enum 15 |
| 35 | FR16/File16 | Socratic AI source boundary | Source exists, provider not verified |
| 36 | FR13/File17 | Cohort community context | Adapter exists |
| 37 | NFR03/File19 | Event outbox and retry | Source exists, deployed delivery unknown |
| 38 | FR17/File00 | Single free-tier policy | Current policy check exists |
| 39 | Future18/File08/24 | Append-only renewed patient consent | OPEN: approved storage and migrations needed |
| 40 | Future18/File09/24 | Mentor feedback append-only archive | OPEN: over-100 archive needs approval and schema |

## C8-D01 - stale governance approved within same WordPress save
Root: wp_insert_post_data checks old stored lesson topic/tag/governance state, but incoming tax_input, tags_input, meta_input and admin lsch_meta can be written later in the same WordPress save. Approved old evidence could publish content under a changed patient-case or reviewer regime.
Correction: prepublish_lesson detects material, not-yet-persisted changes on all four input channels; returns pending with an audit reason uncommitted_lesson_governance. When canonical topic term IDs match, unrelated taxonomy is provided, or genuinely unchanged academic metadata is supplied, publication can proceed. Non-scalar values are rejected without unsafe string coercion. This intentionally introduces two-step review for materially changing governance fields.
Regression: tests/lesson-precommit-publication.php now covers 11+ negative and positive variants, including malformed topic, meta/tag types and stable IDs.

## C8-D02 - malformed topic taxonomy bypassed case protection
Root: can_read_post cast an unexpected taxonomy response into an array; unexpected null/scalar/object or malformed array member could avoid recognizing patient-case-learning and disclose content under ordinary access rules.
Correction: can_read_post, publicly_visible and prepublish_lesson require a real topic-slug string list. Guest/public access fails closed on malformed data, while editors with explicit edit_post may still review and repair the object. Regression tests extended in parent-access-consent.php, public-visibility-version.php and lesson-precommit-publication.php.

## Source fixes, testing and reality
- Corrected code/test HEAD BEFORE this report: 985d016f6b8f188b84166971092245497d151824.
- File05 Current Plans Integrity GitHub Actions Run 38954179320 on that exact SHA completed SUCCESS. Source-integrity, PHP 7.4, PHP 8.3, and reproducible-package jobs passed.
- An earlier source-integrity run failed due to reused patient-case tag state in a positive ordinary-lesson fixture. Fixture isolation was corrected and the later exact-SHA suite passed. This is NOT counted as a new application source defect.
- This report and following STATUS.md commit each produce a different HEAD, requiring a separate latest-head CI check.
- No merge or live deployment in this audit.

## Open requirements, blockers and release acceptance
1. Historical case re-consent after withdrawal requires an approved append-only evidence model, File08/File24 clinical privacy authority, schema, migration/rollback, and Founder signoff. Still UNIMPLEMENTED.
2. Mentor feedback history above 100 requires approved normalized append-only archive, retention/export/erasure policy, schema and rollback. Still UNIMPLEMENTED.
3. Eight Founder books are only draft slots; original reviewed books/editions/chapters/content not proven.
4. WordPress editor, File22 composer, tax_input/meta_input ordering, other plugin filters, scheduled publication, File19/21/26 event/cache/index invalidation, postmeta multiwrite atomicity, Hostinger migration and live acceptance remain unverified. Conservative pending behavior needs actual editor UX checks.
5. Source implementation is not full production completion; do not infer installed module compatibility from repo contracts.

## Mandatory Live-First Reality Ledger
| Reality | Status |
|---|---|
| Repository initial HEAD | 8bbf57d84656a5cb69767ca31cece3027658c477 |
| Corrected source/test HEAD before report | 985d016f6b8f188b84166971092245497d151824 |
| Main HEAD | e2667d69d43d655e3d86b0a358dec5f28b61dd96 |
| Source-declared runtime | 4.0.0 |
| Source-expected schema | Core 18, Auxiliary 3, Future18 2 |
| Deployed Version / checksum | UNVERIFIED |
| Actual DB Version | UNVERIFIED |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔

Counts: 40 scoped reviews, TWO new verified source root defects fixed (affecting five initial routes plus malformed-array variants), two separate design-dependent unfinished capabilities, one corrected test fixture issue, source CI 4/4 successful at the cited corrected SHA.