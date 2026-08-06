# Fresh Review/Fix Round 1 — Architecture and Requirements

## Defects found and corrected

1. Limited Books/Lessons-only architecture did not implement programs, courses, enrollments, assessments, assignments, staff scope or completions — canonical entities and services added.
2. Old SLC namespace contradicted the approved File 05 constitution — controlled LSCH namespace migration added.
3. Paid PKR 400 gate contradicted the Founder’s latest free-platform directive — replaced by a complete free tier.
4. Legacy orange visual identity contradicted the latest green identity — CSS and icon system replaced.
5. Progress lacked course/version/resume/needs-review semantics — normalized schema and correction reconciliation added.
6. Notes did not exist — account-owned authenticated encryption and privacy lifecycle added.
7. No reliable events/jobs — outbox, inbox, retry/dead state and reconciliation added.
8. No completion/certificate-readiness record — versioned competency snapshot and revocation-capable record added.
9. Companion ownership was underspecified — File 20/22/26 and other adapter contracts added without direct companion writes.
10. Existing File 01 Learn page could be duplicated — Foundation-owned route is adopted without mutating its record.

All changed PHP files passed syntax lint after correction.
