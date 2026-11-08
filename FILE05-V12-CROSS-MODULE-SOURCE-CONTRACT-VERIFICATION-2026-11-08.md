# File 05 — v12 exact-source cross-module integration audit (2026-11-08)

**Evidence scope:** Connected GitHub source at stated SHAs, not exact deployed WordPress code or DB.
**File05:** draft PR #5, branch `codex/file05-v12-gap-closure-20261108`, candidate source plugin `4.1.0`.
**Master plan:** `Sabri-Website-Master-Plan-v12.docx`, dated 2026-10-22. No source-only pass is staging or live acceptance.

## Peer source snapshots checked

| Owner | GitHub repository and default-main tree SHA | Direct source observation | Required action |
| --- | --- | --- | --- |
| File 16 AI | `16-ai-sabri-homeopathy-study-guide-temporary-bridge` `0cea92273f9e716ba45e3606b3afaa9751c2d88d` | Inspected `SCHA_Plugin::boot` and `SCHA_Integration`. File03 public-profile `sabri_file16_grounded_profile_ask_v1` was registered, but the File05 consumer hook `lsch_file16_socratic_tutor` was not evidenced there. | File16 owner must implement/verify a *distinct* versioned Socratic educational provider with citations; File05 correctly returns 503 when missing. |
| File 24 privacy | `24-sabri-platform-security-privacy-compliance-and-resilience-center` `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` | Inspected bootstrap `Plugin.php` and privacy policy entrypoints: `spcrc/...` contracts are present; a File05 `lsch_user_legal_hold` callback was not established in inspected files. This is not proof of its absence in all code or runtime. | File24 owner callback/hold decision and lawful retention must be independently attested. File05 now blocks Future18 erasure without any registered hold callback and marks System Check as fail. |
| File 08 clinic | `08-worldwide-clinic-and-appointments-foundation` `d086840f7e2ac0ed328006385f064e0e2fb58cb1` | Inspected `WCA_Contracts`: appointment consent contracts exist; File05 historical patient-case re-consent authority after withdrawal is not proven. | Clinical owner/guardian consent, File24 retention and independent editorial approval before any re-grant code/visibility change. |
| File 19 notifications | `19-sabri-unified-notifications` `04078025b643ab7696e4cb4e37826bf152defa18` | `SUN_Cross_File_Contracts` describes `sun.notifications.api@1.0.0`. File05 source publishes local `lsch_event_published`; exact File19 delivery/ack/retry for this event was not proved. | Verify owner inbox/notification contract with real peers, fail/retry/duplicate/delivery tests. |

Repository `main` is NOT production. GitHub tree SHAs above are source evidence only; peer active plugin versions and deployment parity are unknown.

## v12 F05-FUT-01..18 feature contract matrix

| ID | Feature | File05 candidate source/UI | Unverified external acceptance |
| --- | --- | --- | --- |
| 01 | Competency mastery | Present, private REST/UI | File00 roles and real records |
| 02 | Spaced review | Present, review action/UI | File19 delivery, time zones |
| 03 | Private flashcards | Present, create/review/UI | File06/12 rights and retention |
| 04 | Simulated clinical lab | Present, blueprint/UI | File08 strict simulated-only clinical boundary |
| 05 | Remedy comparison lab | Present, blueprint/UI | File06 original Materia Medica source/version |
| 06 | Case-taking simulation | Present, blueprint/UI | Approved safe case content and real usability |
| 07 | Repertory reasoning lab | Present, blueprint/UI | File15 approved versioned rubric source |
| 08 | Clinical reasoning map | Present, blueprint/UI | Canonical graded rubric and medical-safety review |
| 09 | Oral viva | Manual grading + scoped assessor queue/UI | Real WordPress staff and role matrix; File17 optional calls |
| 10 | OSCE stations | Manual grading + scoped assessor queue/UI | Real stations, roles and academic acceptance |
| 11 | Learning path | Explainable read/save UI | No File26 ranking ownership assumed |
| 12 | Mistake book | Private REST/UI | File24 retention and targeted learning |
| 13 | Evidence appraisal | Blueprint/UI | File06/12 source authority |
| 14 | Academic portfolio | Private versioned state/UI | File03/25 consent-based publication bridge |
| 15 | Mentorship | Append-only 100+ history, immutable prefix verification, legacy read, cursor, redaction, private UI | File24 authority, MySQL rollback, File17/19 messages |
| 16 | CPD | Doctor-only/Founder source; learner UI | File00/09 credentials and independent assessor |
| 17 | Source-grounded Socratic AI | File05 consumer + citations/503 + UI | File16 producer hook **not verified**; no real end-to-end AI |
| 18 | Changed-knowledge re-study | Event/impact record/UI | File19 consumer delivery and File26 revision projection |

**Interpretation:** Every listed File05 source/UI surface has at least a source or mock-test anchor. That is not proof that any full cross-module path, real content, accessibility or staging has passed.

## New issue-to-fix evidence in PR #5

- Historical mentoring feedback was not visible until a new write: corrected with read-only legacy projection, bounded pagination and no GET mutation.
- Count and MAX(sequence_no) parity could conceal a tampered or reordered old record: corrected with content-by-content, ordered legacy prefix matching; corrupt/partial DB blocks write.
- Historical views stopped at page 50: added cursor-based older-page navigation and verified learner/mentor role display.
- File24 anonymized ended mentorships could appear malformed: redacted counterpart tombstones remain privately readable, active malformed history still fails closed.
- Future18 frontend previously lacked an actual independently scoped viva/OSCE grading work queue: added SQL assignment/conflict enforcement and server-versioned human grading forms.
- File24 unregistered legal-hold provider previously defaulted to false in Future18 erasure: now returns retryable `done=false` before destruction; System Check shows failure.
- 18-option client/8 lab exercise has a deterministic DOM/REST behavior test in mandatory CI; this is a mock, NOT a browser staging acceptance.

## Mandatory remaining gates

1. Clinical case re-consent: File08+File24+Founder authority and design/signature approvals; withdrawn legacy consent stays nonpublic.
2. Native File16 producer contract, File24 hold/retention authority, File19 notification events and Files00/06/12/15/17/20/22/25/26 live integration.
3. Eight actual Founder book texts/rights/chapters; learning content and course editorial sign-off.
4. Exact PHP/WordPress/MySQL deployed artifact and schema/options/migration freeze; review, package, restore-tested backup, staging migration, rollback and live re-test.
5. Role/IDOR/CSRF/nonce, provider outage, legacy 0/100/101/1000 mentor cases, simultaneous writes, 200-row privacy exports, Urdu/Arabic RTL, accessibility and p75/p95 latency.

**Repository status:** DRAFT and unmerged. **Deployed Version:** unverified. **DB Version:** unverified. **Migration State:** unverified. **Live Verification Status:** not performed.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔