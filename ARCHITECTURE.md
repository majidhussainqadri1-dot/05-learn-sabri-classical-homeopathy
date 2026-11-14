# File 05 Architecture — 4.1.0 draft source candidate (V12.3 audit)

> This document concerns repository source only. Current deployed code, database/schema and production state are not verified. Do not infer Live parity from GitHub tests.

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

## V12.3 proposed cross-owner adapter boundary

- `LSCH_File06_Source_Links`: exposes approved lesson links only when File05 policy, independent current reviewer, exact lesson version, rights evidence and **fresh File06 owner identity/version/publication proof** all pass. Source retraction, stale version, missing owner or owner exception suppresses the link. Source approval is forbidden under learning safe mode.
- `LSCH_File26_Connector`: a **proposed** public-only lesson/course/book provider; it does not transfer ranking or global discovery ownership. It revalidates source visibility, current object version and current canonical URL and aborts source batches on data-read errors. Owner event/tombstone/reconciliation and real File26 approval remain mandatory before indexing.
- The `LSCH_PLAN_VERSION` constant currently records a v12 provenance identifier. V12.3 requirements are governed by the external latest master plan and its scoped evidence matrix; a version string alone is not a compliance or deployment certificate.

## Security invariants

Every protected action rechecks current File 00 assertions and native object/state authorization. Availability is not authorization. Sensitive data is private/no-cache. New private-note keys are independent from WordPress authentication salts. Corrections forbid proposer self-approval and require reviewer object scope. Heavy/retryable work is bounded and reconciled.

## Future Superset 18 architecture

Runtime 4.0.0 adds `LSCH_Future18` and `LSCH_Future18_REST`. The layer uses an additive sub-schema (`lsch_future18_schema=2`) rather than silently changing core schema 18. Existing outbox persistence remains canonical; a local post-persistence `lsch_event_published` hook feeds mastery, portfolio and correction-impact projections only after event storage succeeds.

All practice modes are learning simulations; real patient identifiers are rejected by common-key guardrails and external clinical authority is not created.
