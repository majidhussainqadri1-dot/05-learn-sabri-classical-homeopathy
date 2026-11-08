# File 05 — Fresh Review/Fix Round 1

## Reviewed exact source

Baseline exact HEAD: `0979b41c23e81284baf77386d1def079588d972c`.

This was the first fresh adversarial review after the actual 3.3.0 source tree had replaced the broken historical materialization transport and the normal exact-head integrity workflow had passed.

## Findings

1. **High — mutating REST replay gap.** Individual endpoints had mixed optimistic-state/idempotency behavior, but File 05 did not yet enforce one durable replay guard for every mutating REST request as required by the governing API constitution.
2. **High — correction-governance bypass.** The legacy `/lesson/{id}/correct` route could change lesson version directly instead of entering the proposal → independent review → apply workflow.
3. **High — same-object correction race.** Two separately accepted correction records for one object could both validate the same object version before either serialized the object mutation.

## Corrections completed

- Core schema advanced to **18** and adds `wp_lsch_request_keys`, a 24-hour privacy-minimized replay ledger. It stores key/request hashes, route/method, processing/completed status, HTTP status and whitelisted scalar response references only; canonical/sensitive request or response bodies are not duplicated.
- `LSCH_Idempotency` was added and wired into File 05 so mutating REST actions require an `Idempotency-Key` and concurrent duplicate keys are serialized with a database advisory lock.
- The legacy direct correction REST route and callback were removed.
- Correction application now acquires an object-scoped MySQL advisory lock, clears object cache, re-reads the canonical version, applies the metadata/progress change atomically where possible, and releases the lock in `finally`.
- Exact-source invariants were extended for schema 18, request-key persistence, lock/release behavior and absence of the REST bypass.

## Round-1 retest

The correction helper run completed successfully: PHP syntax, `tests/source-invariants.sh`, deterministic build and ZIP integrity all passed before the corrective source commit was pushed.

**Round-1 disposition: CLOSED.** A separate fresh adversarial Round 2 was then required on the corrected source.

## Future-18 Fresh Review Round 1 — 2026-08-10

Findings corrected before materialization: (1) manual mastery evidence authority was too broad; (2) manual practice grading lacked source-object assessor scope; (3) CPD verification lacked mentor/manager scope; (4) File16 tutor did not enforce/sanitize citations; (5) change-impact versions were re-derived instead of consuming correction-event versions; (6) mentorship privacy erasure retained direct user IDs. Regression invariants added.
