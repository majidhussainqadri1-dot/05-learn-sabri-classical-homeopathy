# File 05 Decision Log

## F05-D-2026-08-10-01 — Retire corrupt materializer

**Decision:** Remove the historical materialization workflow/payload after exact-head diagnostics proved it corrupt and not uniquely recoverable. Implement reviewed source directly.

**Reason:** Repository truth must be executable source, not a narrative candidate hidden behind a broken transport.

**Rollback:** Git history preserves the transport; do not restore it into an active release branch unless independently reconstructed and verified.

## F05-D-2026-08-10-02 — Runtime 3.3.0

**Decision:** Use 3.3.0 for the current-plan direct-source corrective candidate.

**Reason:** Avoid falsely claiming byte identity with historical local 3.2.0 evidence that is not the current GitHub source.

## F05-D-2026-08-10-03 — Public File 00 contract only

**Decision:** Minimum File 00 contract 1.2.2, `SMC_Contracts::assertions()` + `smc_policy()`; no `_smc_*` private meta or File 00 table access.

## F05-D-2026-08-10-04 — Independent note keyring

**Decision:** New notes require independent AES-256-GCM key material; highest configured generation is the write key. Legacy salt-derived v1 key is decrypt-only migration compatibility.

## F05-D-2026-08-10-05 — File 26 owns global discovery

**Decision:** File 05 exposes a learning-only search projection with current visibility/freshness/why metadata. File 26 remains global ranking owner.
