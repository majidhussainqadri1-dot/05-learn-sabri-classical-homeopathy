# File 05 Rollback Guide — 3.3.0

Rollback is evidence-driven and non-destructive by default.

- Before deployment record exact package checksum, code version, core/state schema, configuration and backup identifier.
- Prefer code rollback with forward-compatible additive schema retained. Destructive down-migration is forbidden unless separately proved safe.
- Preserve valid post-cutover enrollments, progress, submissions, corrections and private notes.
- If a migration partially fails, enter Safe Mode, stop privileged/background mutation, preserve the activation/migration checkpoint and diagnose exact database state before retry.
- If note-key configuration is wrong/missing, do not overwrite ciphertext; restore the correct keyring and re-test decryption.
- After rollback: purge only derivative caches/indexes, run System Check/reconciliation, test representative guest/learner/reviewer paths and verify no schema/data loss.

Live rollback must be rehearsed on staging before production release.
