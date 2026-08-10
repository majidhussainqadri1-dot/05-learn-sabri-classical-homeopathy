# File 05 — Fresh Adversarial Review/Fix Round 2

## Reviewed corrected source

Round 2 began only after Round 1 source corrections and their retest. The corrected implementation lineage included the Round-1 source commit `d4a0ae5525373ad2d2450d929fcc64d535e43d36`; deletion of the temporary helper workflow did not alter runtime source.

## Fresh findings

1. **High — idempotency guard ordering.** The first implementation used `rest_pre_dispatch`, which executes before route permission callbacks. An authenticated but unauthorized caller could therefore create replay-ledger rows before native authorization denied the action.
2. **High — WP_Error finalization path.** `rest_ensure_response()` does not convert every `WP_Error` into a `WP_REST_Response`; the initial post-dispatch finalizer could consequently call response methods on an error object.
3. **Medium — callable legacy correction service.** Although the direct REST route had been removed, `LSCH_Services::mark_content_corrected()` remained public and could be called by companion code to bypass the governed correction state machine.
4. **Medium — request normalization.** Idempotency request hashing needed explicit invalid-UTF-8 handling rather than allowing an encoding failure to collapse to an ambiguous hash input.

## Corrections completed

- The replay guard moved to `rest_request_before_callbacks` / `rest_request_after_callbacks`, so it operates **after the route permission callback** and before/after the authorized handler.
- WP_Error responses now have an explicit error-safe status/reference path; only a sanitized error code is retained for replay, never the underlying sensitive response body.
- Request hashing uses deterministic normalized parameters with `JSON_INVALID_UTF8_SUBSTITUTE`; an encoding failure is a fail-closed 400 error.
- The legacy `mark_content_corrected()` service method was removed entirely. The structured correction workflow is now the sole File 05 correction mutation path.
- Exact-source invariants now reject pre-permission idempotency hooks, require WP_Error-safe finalization and reject any remaining legacy correction service.

## Round-2 retest

The Round-2 helper run passed PHP lint, Python invariant compilation, the full source-invariant suite, deterministic release build and ZIP integrity before pushing the corrected source.

**Round-2 disposition: CLOSED — zero known unresolved repository-source blockers from these two fresh review cycles.** External Hostinger/browser/real-role/load/restore acceptance remains a separate status and is not claimed here.
