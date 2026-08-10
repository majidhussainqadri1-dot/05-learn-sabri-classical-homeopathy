# File 05 Data Dictionary — 3.3.0

## Core tables

- `lsch_enrollments` — account/course lifecycle, course/access version and timestamps.
- `lsch_progress` — account/lesson component-derived progress, resume, lesson version and `needs_review`.
- `lsch_bookmarks` — account-owned learning object bookmarks.
- `lsch_notes` — AES-256-GCM ciphertext, IV, tag, key generation and optimistic version; plaintext is never stored.
- `lsch_attempts` — immutable assessment attempt/snapshot result state and integrity status.
- `lsch_submissions` — assignment submission/grading/revision/appeal state.
- `lsch_staff_assignments` — scoped teacher/assessor/reviewer assignments and conflict state.
- `lsch_completions` — durable competency/course snapshots, identity/integrity/certificate-readiness state.
- `lsch_related_links` — typed pointers to native owners; not copied foreign truth.
- `lsch_case_consents` — versioned patient-case learning consent/withdrawal evidence reference.
- `lsch_reminders` — opt-in reminder preference/cadence/quiet-hours state.
- `lsch_outbox` / `lsch_inbox` — reliable event delivery/deduplication.
- `lsch_jobs` — bounded retryable background jobs.
- `lsch_audit_log` — privacy-minimized File 05 operational/governance audit.

## Auxiliary state schema 3

- `lsch_saved_searches` — private account-owned File 05 learning filter/query presets; never a global search index.
- `lsch_corrections` — proposal/source/reviewer/decision lifecycle with proposed/applied object versions, independent review, resubmission/withdrawal and recoverable application states.
- `lsch_value_events` — bounded, privacy-minimized event metadata for aggregate learning value; raw search text, notes, answers and clinical/patient data are forbidden.

## Privacy classes

Public catalog/content DTOs contain only publishable learning data. Enrollment/progress/notes/attempts/submissions/reminders/saved searches are account-private. Governance/audit/correction records are role-scoped. Patient-case consent evidence and private note keys are never public repository/browser payloads.
