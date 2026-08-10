# File 05 Change-Control Record — 3.3.0

## Governing order

1. Founder’s latest explicit approved directives.
2. Current consolidated central governing plan corpus.
3. Current File 05 Complete Master Plan.
4. Current versioned companion owner contracts.
5. Exact repository HEAD as implementation truth; historical packages/PRs only as provenance.

## 2026-08-10 corrective decision

### Trigger

The active Draft PR described a newer candidate than the source tree actually materialized, and the historical materialization transport failed exact-head execution. Repeated diagnostic runs showed the transport itself was corrupt rather than a simple workflow-pin defect. Root-cause-first policy therefore forbids further patch stacking on the materializer.

### Decision

The materialization workflow and payload are retired. The current-plan release is implemented directly in reviewable repository source. Runtime `3.3.0` is intentionally chosen so it does not falsely claim byte parity with an unavailable historical local `3.2.0` artifact.

### Contract changes

- File 00: consume only `SMC_Contracts::assertions()` plus `smc_policy()`; minimum contract `1.2.2`.
- File 26: owns global search/discovery/ranking; File 05 registers a learning-only projection.
- File 25: owns design tokens; File 05 carries only `#087A4E` fallback and semantic component styling.
- File 16: receives source-safe educational context only; no clinical authority.
- File 12/06/10/17/19/20/21/22/24/25/26: remain native owners of their domains; File 05 stores only owned state or typed references.

### Business and safety changes

- Single free tier is mandatory; no PKR 400, Pro/Premium, paid unlock or donor advantage.
- Public-safe learning may be read without account where content policy permits; protected actions require current eligible File 00 claims.
- Patient-case learning publication requires valid consent and the approved successful-case taxonomy policy.
- Private note writes require independent key material; missing key fails closed.

### Migration / rollback

Schema changes are additive. Existing core tables remain; File 05 auxiliary state schema is `3`. Historical note key generation `1` is decrypt-only for controlled migration. Destructive uninstall remains separately guarded. Rollback must preserve post-cutover data and follow `ROLLBACK.md` / `BACKUP-RESTORE.md`; no destructive down-migration is assumed safe.

### Acceptance

This record authorizes repository-source correction only. It does not authorize live deployment. Exact-head CI, two fresh review/fix rounds, deterministic package evidence and then Hostinger staging acceptance remain separate gates.
