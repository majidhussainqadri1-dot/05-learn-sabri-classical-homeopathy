# File 05 Migration Guide — 3.3.0

1. Freeze the actual staging/deployed reality: plugin version, core/state schema, database counts, companion versions and checksums.
2. Restore-test backup before upgrade.
3. Install the exact tested package on staging only.
4. Activation runs additive core schema migration plus auxiliary state schema `3`; it must be idempotent.
5. Legacy SLC posts/taxonomies/meta/progress/bookmarks are mapped by owned migration code. Foreign domain data is never rewritten.
6. Existing note key generation `1` is decrypt-only compatibility. Configure an independent File 05 keyring; bounded repair re-encrypts readable old notes to the highest current key generation.
7. Reconcile record counts, orphan checks, content versions, `needs_review`, jobs/outbox and managed pages.
8. Keep predecessor data required for rollback until staging acceptance explicitly authorizes retirement.

A newer repository schema number is not proof that live/staging has migrated. Database state must be read from the target environment.
