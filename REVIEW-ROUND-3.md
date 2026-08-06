# File 05 — Fresh Review and Fix Round 3

## Scope

The 2.0.0 candidate was reviewed again against all File 05 functional requirements, non-functional gates, current Founder amendments, and the legacy RC1 source.

## Defects found and corrected

1. **Client-supplied progress risk** — progress is now derived server-side from required lesson components, current assessment pass states, and graded assignment states. A client cannot award its own percentage or completion.
2. **Incomplete assessment lifecycle** — explicit attempt start, server-side expiry, item version, maximum attempts, idempotency, score, review, and integrity status are persisted.
3. **Weak assignment attachment boundary** — assignment attachments are accepted only as validated File 12 canonical references; arbitrary upload or public-media truth is not copied into File 05.
4. **Assessor scope ambiguity** — grading requires an active, conflict-cleared staff assignment unless the actor has curriculum-management authority; self-assessment remains prohibited.
5. **Correction propagation gap** — a lesson correction increments the content version, marks older learner progress `needs_review`, and publishes a correction fact.
6. **Patient-case consent gap** — current consent evidence is required for publication and public access; withdrawal hides the case and emits an auditable event.
7. **Reminder ownership ambiguity** — course reminders are stored as learning preferences while delivery is delegated through a File 19 event.
8. **Related-knowledge duplication risk** — File 05 stores only canonical owner references and versions for Files 06, 10, 12 and 15.
9. **Free-tier conflict** — all obsolete PKR 400/base-membership enforcement was removed. The current access policy is a single complete free tier, with protected actions still subject to verified-entry rules.
10. **Visual identity conflict** — legacy orange identity was replaced with green primary tokens, semantic secondary colors, RTL-safe layout, keyboard focus, reduced motion and inline SVG icon support.

## Result

All round-3 source findings were corrected before proceeding. External browser, companion-runtime and Hostinger staging evidence remains a separate acceptance gate.
