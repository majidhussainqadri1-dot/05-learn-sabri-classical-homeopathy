# File05 ↔ File16 Socratic Tutor owner contract — V12.3 candidate (NO-GO)

**Status:** Draft source interface, not confirmed compatible with active File16, not permission to launch AI clinical advice.

## Ownership

- File05 owns the lesson, learner entitlement, objectives, approved lesson bibliography, version, user-facing practice state and privacy limits.
- File16 alone owns AI provider selection, model invocation, prompts, provider safety, source retrieval/answer truth, quotas and deployment.
- File06/12/15 own their original knowledge/PDF/repertory sources; File05 never copies their canonical private records.
- File24 owns retention/legal hold and privacy escalation; File19 owns notifications.

## Actual candidate call

File05 invokes this WordPress filter, with a null default:

\`\`\`php
apply_filters('lsch_file16_socratic_tutor', null, $context)
\`\`\`

Context includes:

| Key | Required |
| --- | --- |
| contract | \`File16.SocraticTutor.v1\` |
| owner / learning_owner | \`file16-ai-answer-truth\` / \`file05\` |
| user_id / lesson_id / lesson_version | Current verified learner, readable lesson and current version |
| objectives | Current lesson objectives, version-protected |
| sources | Approved lesson bibliography from File05, not a free-text web search |
| stage | one of question, hint, explain, challenge, recap |
| question | bounded UTF-8 educational question; no direct patient record |
| safety | educational_only=true; diagnosis_authority=false; prescription_authority=false; emergency_authority=false; source_grounded_required=true |

File16 must return **an array** with string \`text\` and a list \`citations\` of bounded typed source objects. Text length is limited to 12,000 bytes and at most the first 20 citations are considered. A citation must match File05 lesson-approved source identity/URL at the current version; downstream review can veto, never elevate an unrelated URL. No arbitrary extra fields, raw patient identity, model debug prompts or secrets should be returned.

## Failure behavior

| Failure condition | File05 response | Consequence |
| --- | --- | --- |
| No actual File16 handler | 503 \`lsch_future18_tutor_provider_unavailable\` | Safe no-answer state |
| File16 callback throws | 503 \`lsch_future18_tutor_provider_unavailable\` | No throwable exposed to learner |
| Missing/non-string/oversized text | 502 \`lsch_future18_tutor_provider_invalid\` | No unverified answer |
| No matching source | 502 \`lsch_future18_tutor_source_required\` | Citation is mandatory |
| Reviewer veto/exception | 502 \`lsch_future18_tutor_source_required\` | No privilege escalation |
| Current learner/lesson right revoked | 403 \`lsch_future18_tutor_forbidden\` | No prompt or result |
| Qualified citation and valid educational answer | source-bounded text/links and audit count only | Not a diagnosis or certificate |

## Mandatory peer handoff

1. File16 owner must attest a **compatible deployed filter implementation** and provider lifecycle. During source inspection of the File16 2.2.2 candidate, the main integration/AI-teacher/AI-service paths inspected did **not** establish this callback; that limited inspection does not prove the hook is absent from every branch.
2. Use disposable WordPress with actual File16 and File05 before Hostinger staging. Test absent provider, thrown callback, stale version, mismatched URL, revoked File00 user and clinical/prohibited prompts; no raw PII in logs.
3. File24 approves privacy and medical-safety language; Founder approves education wording and no clinical authority. Keep FUT-17 disabled/fail-closed until peer signoff.
4. Once actual owner versions are known, capture exact File05+File16 ZIP SHAs, backend versions, current DB schema, source citations and human signoff. Do not treat source fixture success as integration acceptance.

**Current acceptance:** isolated File05 source tests only. **Actual File16 integration:** UNVERIFIED. **Live:** UNVERIFIED.
