#!/usr/bin/env python3
from pathlib import Path
import re


def read(path):
    return Path(path).read_text(encoding='utf-8')


def write(path, text):
    Path(path).write_text(text, encoding='utf-8')


def replace_once(text, old, new, label):
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected one match, found {count}')
    return text.replace(old, new, 1)

# Core schema 18: durable, privacy-minimized request replay ledger.
db_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-database.php'
db = read(db_path)
db = replace_once(
    db,
    "\t\t\t'audit'       => $prefix . 'audit_log',\n",
    "\t\t\t'audit'        => $prefix . 'audit_log',\n\t\t\t'request_keys' => $prefix . 'request_keys',\n",
    'request_keys registry',
)
request_table = '''

\t\tdbDelta( "CREATE TABLE {$t['request_keys']} (
\t\t\tkey_hash char(64) NOT NULL,
\t\t\tuser_id bigint(20) unsigned NOT NULL,
\t\t\troute varchar(190) NOT NULL,
\t\t\tmethod varchar(10) NOT NULL,
\t\t\trequest_hash char(64) NOT NULL,
\t\t\tstatus varchar(20) NOT NULL DEFAULT 'processing',
\t\t\tresponse_status smallint(5) unsigned NOT NULL DEFAULT 0,
\t\t\tresponse_ref_json text NOT NULL,
\t\t\tcreated_at datetime NOT NULL,
\t\t\tupdated_at datetime NOT NULL,
\t\t\texpires_at datetime NOT NULL,
\t\t\tPRIMARY KEY (key_hash), KEY user_created (user_id,created_at), KEY expiry (expires_at), KEY state_updated (status,updated_at)
\t\t) {$c};" );
'''
db = replace_once(db, "\n\t\tself::assert_tables();", request_table + "\n\t\tself::assert_tables();", 'request_keys schema')
write(db_path, db)

# Serialize correction application by learning object before version validation.
state_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php'
state = read(state_path)
replacement = '''
\tprivate static function correction_object_lock( $object_id ) {
\t\tglobal $wpdb;
\t\t$name = 'lsch:corr:' . substr( hash( 'sha256', (string) absint( $object_id ) ), 0, 48 );
\t\t$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $name ) );
\t\treturn 1 === $locked ? $name : new WP_Error( 'lsch_correction_object_busy', __( 'Another correction for this learning object is being applied. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t}

\tprivate static function correction_object_unlock( $name ) {
\t\tif ( ! $name ) {
\t\t\treturn;
\t\t}
\t\tglobal $wpdb;
\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
\t}

\tprivate static function apply_correction( $object_id, $reason, $expected_version ) {
\t\tglobal $wpdb;
\t\t$object_id = absint( $object_id );
\t\t$lock = self::correction_object_lock( $object_id );
\t\tif ( is_wp_error( $lock ) ) {
\t\t\treturn $lock;
\t\t}
\t\ttry {
\t\t\tclean_post_cache( $object_id );
\t\t\t$type = LSCH_Content::object_type( $object_id );
\t\t\tif ( ! $type ) {
\t\t\t\treturn new WP_Error( 'lsch_correction_object_gone', __( 'The learning object is no longer available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 410 ) );
\t\t\t}
\t\t\t$current_version = LSCH_Content::version( $object_id );
\t\t\tif ( $current_version !== absint( $expected_version ) ) {
\t\t\t\treturn new WP_Error( 'lsch_correction_stale', __( 'The learning object changed before the correction could be applied.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t\t}
\t\t\t$new_version = $current_version + 1;
\t\t\t$wpdb->query( 'START TRANSACTION' );
\t\t\ttry {
\t\t\t\tupdate_post_meta( $object_id, '_lsch_correction_note', sanitize_textarea_field( $reason ) );
\t\t\t\tif ( false === update_post_meta( $object_id, '_lsch_version', $new_version ) ) {
\t\t\t\t\tthrow new RuntimeException( 'content_version_write_failed' );
\t\t\t\t}
\t\t\t\tif ( LSCH_Content::LESSON === $type ) {
\t\t\t\t\t$t = LSCH_Database::tables();
\t\t\t\t\t$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$t['progress']} SET needs_review=1,version=version+1,updated_at=%s WHERE lesson_id=%d AND lesson_version<%d", LSCH_Database::now(), $object_id, $new_version ) );
\t\t\t\t\tif ( false === $updated ) {
\t\t\t\t\t\tthrow new RuntimeException( 'learner_review_mark_failed' );
\t\t\t\t\t}
\t\t\t\t}
\t\t\t\tif ( false === $wpdb->query( 'COMMIT' ) ) {
\t\t\t\t\tthrow new RuntimeException( 'correction_commit_failed' );
\t\t\t\t}
\t\t\t} catch ( Throwable $error ) {
\t\t\t\t$wpdb->query( 'ROLLBACK' );
\t\t\t\tclean_post_cache( $object_id );
\t\t\t\treturn new WP_Error( 'lsch_correction_apply_failed', __( 'The correction could not be applied atomically. No partial correction was accepted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500, 'reason_code' => sanitize_key( $error->getMessage() ) ) );
\t\t\t}
\t\t\tclean_post_cache( $object_id );
\t\t\tLSCH_Events::publish( 'LearningContentCorrected.v1', $type, $object_id, array( 'previous_version' => $current_version, 'new_version' => $new_version ) );
\t\t\tLSCH_Events::audit( 'learning_content_corrected', $type, $object_id, array( 'new_version' => $new_version ), 'editorial_governance' );
\t\t\treturn $new_version;
\t\t} finally {
\t\t\tself::correction_object_unlock( $lock );
\t\t}
\t}
'''
pattern = re.compile(r"\n\tprivate static function apply_correction\(.*?\n\t}\n\n\t/\*\*\n\t \* Store only bounded", re.S)
match = pattern.search(state)
if not match:
    raise SystemExit('apply_correction block not found')
state = state[:match.start()] + '\n' + replacement + "\n\t/**\n\t * Store only bounded" + state[match.end():]
write(state_path, state)

# Remove the old direct correction endpoint that bypassed independent governance.
rest_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php'
rest = read(rest_path)
rest = replace_once(
    rest,
    "\t\tregister_rest_route( self::NS, '/lesson/(?P<id>\\d+)/correct', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'correct' ), 'permission_callback' => array( $this, 'reviewer' ) ) );\n",
    '',
    'legacy correction route',
)
rest = replace_once(
    rest,
    "\tpublic function correct( WP_REST_Request $r ) { return LSCH_Services::mark_content_corrected( absint( $r['id'] ), $r->get_param( 'reason' ) ); }\n",
    '',
    'legacy correction method',
)
write(rest_path, rest)

# Strengthen exact-source invariants for the round-1 fixes.
static_path = 'tests/static-invariants.py'
static = read(static_path)
static = replace_once(static, "    '05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php',\n", "    '05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php',\n    '05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php',\n", 'idempotency required file')
static = replace_once(static, "    'LSCH_SCHEMA_VERSION\\', 17',\n", "    'LSCH_SCHEMA_VERSION\\', 18',\n", 'schema invariant')
static = replace_once(static, "    'value_events',\n", "    'value_events',\n    'request_keys',\n    'rest_pre_dispatch',\n    'rest_post_dispatch',\n    'GET_LOCK',\n    'RELEASE_LOCK',\n    'lsch_idempotency_payload_conflict',\n    'lsch_correction_object_busy',\n", 'reliability invariants')
static = replace_once(static, "    'reminders', 'outbox', 'inbox', 'jobs', 'audit_log',\n", "    'reminders', 'outbox', 'inbox', 'jobs', 'audit_log', 'request_keys',\n", 'request keys table invariant')
insert = """
rest_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php', '')
if "/lesson/(?P<id>\\\\d+)/correct" in rest_source or "array( $this, 'correct' )" in rest_source:
    errors.append('Legacy direct correction bypass remains REST-accessible.')
"""
static = static.replace("\nif errors:\n", insert + "\nif errors:\n", 1)
write(static_path, static)

# Remove this temporary helper from the source candidate after successful execution.
Path('.github/file05-review1-fix.py').unlink()
print('Applied File 05 review round 1 source corrections.')
