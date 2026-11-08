# File 05 — 22 October 2026 Master Plan v12: Exact-source implementation and pending governance

**Repository candidate:** `codex/file05-v12-gap-closure-20261108`, based on exact source commit `14ae5e5b243be34e4dd7cda9a431a79f141d7ecd`. The default `main` branch is NOT the deployed source. This change is a **draft and cannot be released before staging parity**.

## Status by distinct category

| Area | Candidate source work | Remaining acceptance |
| --- | --- | --- |
| Lesson progress version/read | Protected current-user GET, JS compare-and-swap | Real WP permissions, DB failures, concurrent devices |
| Reminder toggle/state | Protected GET, true persisted state and accessible toggle | Real WP roles, reminder delivery/File19 |
| Global mutation idempotency | Client API supplies keys for all mutations | Exact WP/MySQL transaction/outbox replay |
| Five formative checks | Separate max-five JSON blueprint, fail-closed authoring, private correct keys, source-only scoring | Independent lesson editorial approval and browser/RTL tests |
| Explicit lesson next action | Lesson approval gate and visible next step | Editor same-save hook, course navigation with real File20 |
| Future-18 learner workbench | All 18 selectors, eight role-checked lab paths, protected blueprint projection, review/portfolio/CPD/mentoring/AI views | Real source/progress/data content, File00/06/12/15/16/19/24/25/26, RTL/a11y/load |
| Mentoring feedback 101+ | Additive normalized append-only table, migration from valid prior array, ordered private reads, privacy export/redact, strict parent versioning | Exact DB schema, 0/100/101/500/1000 old entries, race/rollback/legal-hold, File24 approval |
| Patient-case re-consent | **Not enabled or silently implemented**; prior withdrawal remains authoritative | Separate independent File08+File24 clinical/privacy authority and governance approval |

## Patient-case re-consent: binding design boundary before code can activate

The current schema's `UNIQUE(lesson_id,policy_version)` and `LSCH_Services::store_case_consent` deliberately prevent replacing, duplicating, or reviving a withdrawn authorization. This **must remain unchanged** until the following policy fields, owners and acceptance data are available. A mere new signed submission does not revive an earlier consent.

1. **Authority and identity**: patient or lawful guardian authority, age/guardian/agency proof and geographic applicability are certified by the canonical File08/24 owner, **never a File05 self-asserted checkbox**. Record only opaque authorized reference, source policy revision, evidence digest and timestamps, not patient identity.
2. **Immutable history**: original consent, withdrawal identity/timestamp, every proposed renewal, reviewer decisions and any fresh withdrawal remain independently auditable. Do not UPDATE away history or infer current consent from a stale row.
3. **Latest-event policy**: deny when the prior consent was withdrawn, when the owner provider is unavailable, signatures/authorization have expired, a later withdrawal is present, policy revision is stale, or any database read fails.
4. **Separate editorial approval**: File05's curriculum manager/editor and independent reviewer must re-approve the newly permitted *de-identified* lesson; no auto-publication. A new consent event must not bypass File00 access, File24 retention or lesson prepublication gates.
5. **Withdrawal immediately hides**: revoke public visibility, purge derived File25/26/File16/17 indexes/links, abort if private transition or outbox evidence cannot commit together, and support subsequent withdrawal of a renewed case.
6. **Retention and privacy**: File24 legal hold, WordPress native export/erase, historical evidence preservation and pseudonymization must have explicit, reviewed rules. Only authority-provided consent may be treated as valid; erasure must not silently destroy legal evidence.
7. **Migration and rollback**: a separate additive event table and deterministic backfill from the existing one-row evidence, seeded duplicates/conflicts, IDOR/CSRF/same-policy replay, offline partner, concurrent grant/revoke, backup restore and preserved prior artifact hash.
8. **Owner approvals still needed**: File08 clinical case owner, File24 consent/retention owner, Founder editorial authority and exact staging contracts. **Until these approvals, no implementation should turn a withdrawn record public.**

This deliberate block is **not** a completed capability. It is a pre-implementation safety gate; do not mark historical re-consent “fixed”, “approved” or “live”.

## Mandatory staged verification matrix

- Exact active plugin ZIP SHA-256, GitHub SHA parity, WordPress/PHP/MySQL/LiteSpeed and deployed File00/01/06/08/10/12/15/16/17/19/20/22/24/25/26 versions.
- Database `lsch_schema_version`, auxiliary `lsch_state_schema`, `lsch_future18_schema`, new `lsch_mentor_history_schema` and exact table/index/row counts; get current option names via canonical class constants, not guessed wp_options entries.
- Fresh install and predecessor upgrade: 0, 99, 100, 101, 500 historical feedback rows; deliberately corrupt JSON, partial backfill, duplicate sequence, stale parent version and two concurrent mentors. No truncation or false successful append.
- Native WP privacy export pages beyond 200, suspended account access, guardian checks, learner/mentor separation, assessor-only redaction, legal hold and deletion failures; ensure counterpart records and stored academic sequence are handled according to File24.
- For all 18 future features: actual form, role authorization, API, persistence, reload, no answer leakage, typed provider failures, accessibility, slow network, Urdu/Arabic/English rendering. Viva and OSCE require independent human assessment, never automatic credentialing.
- Formative learning questions: 0, 1, 5 and 6 entries; invalid answer key; no correct answer in pre-submit GET/HTML; post-submit feedback does not unlock certification.
- Repeat source test and deterministic package; restore backup; verify readback, outbox/inbox, version conflict and rollback with additive tables preserved. **Only then stage/production signoff.**

## Release-truth fields

- Repository `main`: `e2667d69d43d655e3d86b0a358dec5f28b61dd96` at audit start; verify again at release.
- Candidate: track exact PR #5 HEAD from GitHub, not simply a branch label.
- Deployed Version: **unverified**
- DB Version: **unverified**
- Migration State: **unverified**
- Live Verification Status: **not performed**
- Source CI: only the green job corresponding to the final immutable commit has meaning.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
