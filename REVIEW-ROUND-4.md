# File 05 — Final Adversarial Review and Fix Round 4

## Negative-path review

The final source candidate was challenged for stale writes, dependency loss, replay, privilege drift, withdrawn consent, timed-attempt expiry, self-review, privacy erasure, legacy migration, queue failure, and partial activation.

## Defects found and corrected

1. **Untimed attempt null persistence ambiguity** — `expires_at` is omitted when no time limit exists rather than writing an ambiguous null/empty value; database insert failure now returns a controlled error.
2. **Schema evolution evidence** — the candidate schema version was promoted to `7` so the final attempt-expiry structure is independently migratable and auditable.
3. **Incomplete administrator governance surface** — structured fields for access, hierarchy, objectives, prerequisites, equivalence, teachers/reviewers, assessments, rubrics, safety, accessibility and versioning are available through the canonical admin metadata interface.
4. **Incomplete learner UI** — course enrollment/reminders, lesson progress/bookmarks/private notes, assessment submission and assignment submission have real front-end controls and authenticated REST handlers; no decorative action remains.
5. **Queue failure opacity** — outbox, inbox and jobs expose bounded retries, dead states, redacted diagnostics, reconciliation and safe-mode operation.
6. **Privacy lifecycle incompleteness** — private notes and reminders are erased; retained academic/editorial records are anonymized where required; legal holds prevent destructive erasure and are reported.
7. **Namespace collision/legacy ambiguity** — the production candidate uses the canonical `LSCH_` namespace, `learn-sabri-classical-homeopathy` text domain and `05-learn-sabri-classical-homeopathy` package root, with explicit migration from legacy `SLC_` records.
8. **Release-truth ambiguity** — documentation distinguishes Specified, Coded, Packaged, Automated-QA Green, Staging-Accepted, Live-Deployed and Operational statuses. Source completion does not impersonate staging or production acceptance.

## Final source decision

No known unresolved Critical or High source defect remains in the reviewed candidate. The remaining gates require a real WordPress/Hostinger staging environment, installed companion modules, browser/device/accessibility testing, backup/restore and Founder sign-off.
