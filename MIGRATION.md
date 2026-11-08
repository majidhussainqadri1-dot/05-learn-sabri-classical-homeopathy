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

## Runtime 4.0.0 / Future-18 sub-schema 2

Additive migration creates eight `lsch_f18_*` tables and `lsch_future18_schema=2`; core schema remains 18 and auxiliary state schema remains 3. Existing learning rows are not rewritten. Activation/maybe-upgrade is idempotent through `dbDelta`. Roll forward is blocked/fail-closed if the new schema cannot be created; real upgrade evidence remains a Hostinger staging gate.


## File 05 v12 candidate — normalized mentoring feedback, schema `lsch_mentor_history_schema=1`

This is an **additive** migration: `wp_{prefix}lsch_f18_mentor_feedback` (actual prefix is derived from `$wpdb->prefix`; never hardcode `wp_`), unique `(mentorship_id,sequence_no)`, preserved legacy `feedback_json`. The migration checkpoint is `lsch_mentor_history_schema`. Do not mistake a GitHub schema marker for a deployed database state.

1. Freeze exact staging package SHA-256, installed plugin/runtime/schema versions, `wp_options` values, WordPress/PHP/MySQL version, original mentoring row counts and every related File00/08/24 contract. Backup and **restore-test** first.
2. Execute schema upgrade on an isolated WordPress staging clone. Confirm new table/index, rollback option and schema marker using actual DB reads; abort on any mismatch.
3. Seed a prior mentor relationship with 0, 99, 100 and 500 valid historical JSON feedback entries. An authorized new write migrates all old entries once, then appends `next sequence_no`; no preexisting entries may disappear.
4. Force malformed JSON, partial interrupted backfill, missing table, insert failure, stale `mentorship.version`, duplicate concurrent writes and portfolio failure. The whole REST command's outer transaction must roll back: no unpaired history/portfolio row, no false successful event.
5. Run WordPress native export/erase and File24 legal-hold retention with the **new table**, including actor-only vs learner-owned data. Test pagination over 200, old JSON freeze and pseudonymized history.
6. Only then may the capability be staging-accepted. Real database migration was **not** executed by GitHub Actions.

Historical patient case *re-consent after withdrawal* remains separately blocked by File08/File24 clinical/legal governance and independent approval; see `FILE05-V12-IMPLEMENTATION-AND-RECONSENT-DECISIONS.md`. No older consent is revived by this migration.
