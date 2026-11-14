# File 05 — P00 Live Reality Freeze: operator evidence sheet (V12.3)

**State:** Unexecuted template. No user, production or database access is implied.  
**Authority:** V12.3 Live-First Exact-Deployed-State Rule, F05-00.01..06 and CEN-REL-001.  
**Use:** Authorized operator collects read-only, redacted evidence **before** a live incident diagnosis or source-based patch/deployment.

## A. Strictly separate three environments

| Environment | URL/Host identifier | Collection timestamp (UTC) | Operator | Evidence artifact/checksum |
| --- | --- | --- | --- | --- |
| Production | **UNKNOWN** (public host alone does not verify origin) | Pending | Pending | Pending |
| Staging | **UNKNOWN** | Pending | Pending | Pending |
| GitHub repository | majidhussainqadri1-dot/05-learn-sabri-classical-homeopathy | Validate fresh | GitHub read-only | Exact branch SHA needed |

**2026-11-14 observed access symptoms:** WordPress.com/Jetpack site-scoped connector reported **disconnected**; WPVibe direct probe of both hostname variants returned **HTTP 530**. Neither proves the failing hop or origin, and no plugin/DB/version claims follow.

## B. Read-only inventory (run from authorized environment only)

Record commands, exact output file SHA-256, timestamp and environment. Redact usernames, passwords, salts, tokens, personal data and patient identifiers **before sharing**.

1. WordPress/PHP/runtime: WordPress version, PHP version, database engine/version, LiteSpeed/object cache and current maintenance/safe modes.
2. Plugins: full **active** plugin slug, version, source path and status (especially Files 00, 01, 05, 06, 08, 10, 12, 15, 16, 17, 19, 20, 21, 22, 24, 25, 26).
3. File05 source: actual active plugin main PHP header; plugin directory tree checksum; package ZIP SHA-256 if available; deployed manifest and package manifest verification; note-key **presence/generation only**, never secret value.
4. DB option **values only after redaction**. In File05 candidate source, relevant exact names include:
   - \`lsch_version\`, \`lsch_plan_version\`, \`lsch_access_model\`
   - \`lsch_schema_version\` (candidate core schema marker: 18)
   - \`lsch_state_schema_version\` (candidate auxiliary marker: 3)
   - \`lsch_future18_schema\` (candidate Future18 marker: 2)
   - \`lsch_mentor_history_schema\` (candidate mentorship marker: 1)
   - \`lsch_runtime_failure\`, \`lsch_safe_mode\`, \`lsch_activation_checkpoint\`
   These are **source-code identifiers, not measured production values**.
5. Table inventory: list names, columns/indexes and row **counts**, not private row contents, for \`<prefix>lsch_%\` and \`<prefix>lsch_f18_%\`. Use the actual WordPress prefix from the authorized installation. Inspect custom migration/altered indexes only with immutable schema report.
6. Queue/outbox: status aggregates only, oldest pending age and errors **redacted**; look for failed or partially applied migrations and unusable schema before investigating an application bug.
7. Logs: PHP fatal/error class, stack location, plugin/file/version, timestamp, request trace ID **redacted**, no request bodies or personal data.
8. Dependency policy: File00 public assertion contract and current free-tier/guardian/verification authority; current File06 assertion, File16 educational responder, File19 delivery, File24 legal hold, File26 global search manifest and active index policy. Verify on deployed builds, not GitHub READMEs.
9. Backup: authoritative recent site+DB+keyring reference, retention window, restore rehearsal and rollback owner. Do **not** activate, edit, migrate, roll back or regenerate note keys during this freeze.

### Illustrative read-only WP-CLI commands

Only if WP-CLI and authorization are available; use the correct site directory and capabilities:

\`\`\`sh
wp core version
wp plugin list --status=active --format=json
wp option get lsch_version
wp option get lsch_plan_version
wp option get lsch_schema_version
wp option get lsch_state_schema_version
wp option get lsch_future18_schema
wp option get lsch_mentor_history_schema
wp option get lsch_safe_mode
wp db prefix
\`\`\`

Never use \`wp config get\` for credentials, a table-wide \`SELECT *\` of personal data, or plaintext note-key output in a shared evidence file. For row counts use separate **read-only** \`SELECT COUNT(*)\` statements after confirming actual table names; for schema use \`SHOW CREATE TABLE\` / \`SHOW INDEX\`, with sensitive names reviewed before distribution. Note that unrecognized tables or version markers require investigation, not auto-creation.

## C. Parity decision

1. Freeze GitHub \`main\` SHA and each candidate PR SHA independently.
2. Compute source/package checksum on the **deployed artifact**. If the active artifact cannot be read, mark **Deployed Version = UNVERIFIED**.
3. Compare package/source file hashes and manifest against **one** immutable candidate, not a branch name.
4. Compare live schema+options+data migration checkpoints against the *same* package's schema definition.
5. If any hash/version mismatch, stop application root-cause claims and begin **Deployment-Parity Audit**.
6. If an active File05 dependency has an incompatible deployed schema, stop and identify upgrade/reconciliation protocol before changing application code.

## D. Evidence register template

| Check | Required observation | Evidence timestamp/ref | Result |
| --- | --- | --- | --- |
| F05-P00-01 | Active plugin/version/package and tree SHA | Pending | Unverified |
| F05-P00-02 | DB schema/columns/indexes/options | Pending | Unverified |
| F05-P00-03 | Migration/checkpoint and row-count consistency | Pending | Unverified |
| F05-P00-04 | Companion deployed contract versions | Pending | Unverified |
| F05-P00-05 | Logs/config and runtime symptom | Pending | Unverified |
| F05-P00-06 | Backup/isolated restoration evidence | Pending | Unverified |
| F05-P00-07 | Source-to-deployed exact checksum parity | Pending | Unverified |

## E. Gate decision — must be completed with real evidence

- **Repository HEAD:** GitHub candidate evidence may be attached, but does not prove Live source.
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED.
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.
- **G0 decision:** **BLOCKED** until the seven observations above are independently collected.
- **Do not:** merge source into \`main\`, install a ZIP, activate a proposed File26 connector, move patient case consent or call this incident resolved.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
