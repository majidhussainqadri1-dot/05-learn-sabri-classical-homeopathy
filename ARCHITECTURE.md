# File 05 Architecture — 4.0.0

## Canonical ownership

File 05 owns learning-domain source of truth only: curriculum taxonomy, programs/courses/books/lessons, assessments/assignments, enrollments, progress, bookmarks/private notes, learning staff scope, completions, related typed references, consent records, reminders, reliable events/jobs/audit, learning-local saved searches, corrections and learning-value events.

Foreign domains are consumed through versioned APIs/events/adapters. Direct writes to File 00 membership storage, File 06 encyclopedia truth, File 10 media truth, File 12 PDF objects, File 16 AI output, File 17 conversations, File 19 notification delivery, File 20 shell state, File 21 publishing truth, File 25 design-token truth or File 26 global ranking are forbidden.

## Runtime layers

1. `LSCH_Dependencies` — owner contracts/current central policy and fail-closed runtime readiness.
2. `LSCH_Capabilities` / `LSCH_Policy` — current assertion + object/state authorization and private-note cryptography.
3. `LSCH_Content` — WordPress learning objects/taxonomies and publication gates.
4. `LSCH_Database` — normalized core learning tables and legacy migration.
5. `LSCH_State` — auxiliary schema 3 for saved searches, correction workflow and value telemetry.
6. `LSCH_Services` — domain commands/queries for enrollment, progress, assessments, assignments, consent, reminders, completion.
7. `LSCH_REST` — versioned public/private REST surface.
8. `LSCH_Value` — File 26, File 16, citation/download and aggregate-value contracts.
9. `LSCH_Events` / `LSCH_Operations` — reliable outbox/inbox/jobs, audit, reconciliation, safe mode and repair.
10. `LSCH_Privacy` — WordPress export/erasure/legal-hold integration.
11. `LSCH_Frontend` / `LSCH_Admin` — semantic learning surfaces; global shell/tokens remain external owners.

## Security invariants

Every protected action rechecks current File 00 assertions and native object/state authorization. Availability is not authorization. Sensitive data is private/no-cache. New private-note keys are independent from WordPress authentication salts. Corrections forbid proposer self-approval and require reviewer object scope. Heavy/retryable work is bounded and reconciled.

## Future Superset 18 architecture

Runtime 4.0.0 adds `LSCH_Future18` and `LSCH_Future18_REST`. The layer uses an additive sub-schema (`lsch_future18_schema=2`) rather than silently changing core schema 18. Existing outbox persistence remains canonical; a local post-persistence `lsch_event_published` hook feeds mastery, portfolio and correction-impact projections only after event storage succeeds.

All practice modes are learning simulations; real patient identifiers are rejected by common-key guardrails and external clinical authority is not created.
