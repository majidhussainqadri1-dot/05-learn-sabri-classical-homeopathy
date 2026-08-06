# Fresh Review/Fix Round 2 — Adversarial and Failure Review

## Defects found and corrected

1. Protected actions could have been mistaken for login-only permissions — approved-account, guardian, suspension and object-state gates are rechecked server-side.
2. Duplicate submissions after timeout — idempotency keys and unique constraints added to enrollment/assessment flows.
3. Concurrent note edits — expected-version conflict protection added.
4. Content correction could make old completion results uninterpretable — lesson version snapshots and `needs_review` transitions added.
5. Private learning data could enter public caches — private REST/dashboard responses use no-store and private pages are noindex.
6. Queue/provider failure lacked bounded retry — exponential retry, maximum attempts and dead state added.
7. Uninstall risked destructive loss — retention-first uninstall with two explicit purge controls added.
8. Missing dependencies could cause partial boot — runtime fails closed and records a safe administrator notice.
9. Public source objects could be copied into File 05 — normalized canonical related-link references added.
10. Founder book count requirement could cause invented bibliographic claims — eight draft governance slots are seeded but never published until actual Founder-approved titles are supplied.

Static invariants, unit policy tests, complete PHP lint and deterministic double-build verification pass locally.
