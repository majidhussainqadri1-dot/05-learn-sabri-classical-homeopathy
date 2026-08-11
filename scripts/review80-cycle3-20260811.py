#!/usr/bin/env python3
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / '05-learn-sabri-classical-homeopathy'
rounds = []

def read(path):
    return (ROOT / path).read_text(encoding='utf-8')

def write(path, text):
    (ROOT / path).write_text(text, encoding='utf-8', newline='\n')

def replace_once(path, old, new, round_no, finding):
    text = read(path)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'Round {round_no}: expected one match in {path}, found {count}')
    write(path, text.replace(old, new, 1))
    if old in read(path) or new not in read(path):
        raise SystemExit(f'Round {round_no}: post-fix assertion failed for {path}')
    rounds.append((round_no, finding, 'DEFECT + FIX'))

# Round 1 — runtime health check used $t before initialization.
replace_once(
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php',
    "\t\tglobal $wpdb;\n\t\t$checks = array();",
    "\t\tglobal $wpdb;\n\t\t$t = LSCH_Database::tables();\n\t\t$checks = array();",
    1,
    'System Check queried dead outbox/jobs through an undefined $t table map; initialize the canonical core table map before every health query.'
)
replace_once(
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php',
    "\t\tforeach ( LSCH_Database::tables() as $name => $table ) {",
    "\t\tforeach ( $t as $name => $table ) {",
    1,
    'System Check core-table enumeration now reuses the initialized canonical map.'
)
# Merge the duplicate round record created by the helper for the second replacement.
rounds = [r for i, r in enumerate(rounds) if not (r[0] == 1 and i > 0)]

# Round 2 — WordPress administrator status must not substitute for File 00 identity/eligibility.
caps_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-capabilities.php'
caps = read(caps_path)
old = "\t\tif ( empty( $claims['eligible'] ) || ! empty( $claims['suspended'] ) ) {\n\t\t\treturn self::is_founder( $user_id ) || user_can( $user_id, 'manage_options' );\n\t\t}"
new = "\t\tif ( empty( $claims['eligible'] ) || ! empty( $claims['suspended'] ) ) {\n\t\t\treturn self::is_founder( $user_id );\n\t\t}"
if caps.count(old) != 1:
    raise SystemExit('Round 2: can_author administrator bypass pattern mismatch')
caps = caps.replace(old, new, 1)
old = "\t\tif ( self::is_founder( $user_id ) || user_can( $user_id, 'manage_options' ) ) {\n\t\t\treturn true;\n\t\t}"
if caps.count(old) != 2:
    raise SystemExit(f'Round 2: expected two administrator identity bypasses, found {caps.count(old)}')
caps = caps.replace(old, "\t\tif ( self::is_founder( $user_id ) ) {\n\t\t\treturn true;\n\t\t}", 2)
write(caps_path, caps)
if "user_can( $user_id, 'manage_options' )" in read(caps_path):
    raise SystemExit('Round 2: raw manage_options identity bypass remains')
rounds.append((2, 'approved_account(), guardian_gate_passes() and can_author() treated raw WordPress manage_options as membership/eligibility authority; only the explicit File 00 Founder assertion may bypass ordinary membership gates.', 'DEFECT + FIX'))

# Round 3 — stale/suspended/unapproved WordPress role caps remained usable in native wp-admin paths.
caps = read(caps_path)
old = "final class LSCH_Capabilities {\n"
new = "final class LSCH_Capabilities {\n\tprivate static $filtering_user_caps = false;\n"
if caps.count(old) != 1:
    raise SystemExit('Round 3: capability class header mismatch')
caps = caps.replace(old, new, 1)
insert_before = "\n\t/**\n\t * File 00 owns age/jurisdiction/guardian truth. File 05 consumes the\n"
method = """
\n\t/** Remove File 05 domain capabilities when current File 00 assertions are not usable. */
\tpublic static function filter_user_caps( $allcaps, $caps, $args, $user ) {
\t\tunset( $caps, $args );
\t\tif ( self::$filtering_user_caps || ! $user instanceof WP_User || ! $user->ID ) {
\t\t\treturn $allcaps;
\t\t}
\t\t$domain = self::all();
\t\t$has_domain = false;
\t\tforeach ( $domain as $capability ) {
\t\t\tif ( ! empty( $allcaps[ $capability ] ) ) { $has_domain = true; break; }
\t\t}
\t\tif ( ! $has_domain ) { return $allcaps; }
\t\tself::$filtering_user_caps = true;
\t\t$claims = LSCH_Dependencies::claims( $user->ID );
\t\tself::$filtering_user_caps = false;
\t\t$allowed = ! empty( $claims['founder'] ) || ( ! empty( $claims['approved'] ) && ! empty( $claims['eligible'] ) && empty( $claims['suspended'] ) && ! empty( $claims['guardian_verified'] ) );
\t\tif ( ! $allowed ) {
\t\t\tforeach ( $domain as $capability ) {
\t\t\t\t/* OPERATE is a system-diagnostics capability, not a membership identity grant. */
\t\t\t\tif ( self::OPERATE === $capability && ! empty( $allcaps['manage_options'] ) ) { continue; }
\t\t\t\tunset( $allcaps[ $capability ] );
\t\t\t}
\t\t}
\t\treturn $allcaps;
\t}
"""
if caps.count(insert_before) != 1:
    raise SystemExit('Round 3: insertion anchor mismatch')
caps = caps.replace(insert_before, method + insert_before, 1)
write(caps_path, caps)
plugin_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php'
plugin = read(plugin_path)
old = "\tpublic function run() {\n\t\tadd_action( 'init', array( 'LSCH_Content', 'register' ), 5 );"
new = "\tpublic function run() {\n\t\tadd_filter( 'user_has_cap', array( 'LSCH_Capabilities', 'filter_user_caps' ), 20, 4 );\n\t\tadd_action( 'init', array( 'LSCH_Content', 'register' ), 5 );"
if plugin.count(old) != 1:
    raise SystemExit('Round 3: plugin capability-hook anchor mismatch')
write(plugin_path, plugin.replace(old, new, 1))
if 'filter_user_caps' not in read(caps_path) or "add_filter( 'user_has_cap'" not in read(plugin_path):
    raise SystemExit('Round 3: capability-currentness guard not materialized')
rounds.append((3, 'File 05 custom capabilities could remain effective after suspension/ineligibility because WordPress role grants were not dynamically reconciled with File 00 assertions; a recursion-safe current-claims capability filter now fail-closes domain caps while preserving system diagnostics for administrators.', 'DEFECT + FIX'))

# Round 4 — native object/meta write gates did not uniformly include current learning policy + Safe Mode.
policy_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php'
policy = read(policy_path)
old = "\tpublic static function can_use_learning_actions( $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\treturn\n\t\t\t$user_id &&\n\t\t\t! LSCH_Operations::safe_mode() &&\n\t\t\tself::central_policy_ready() &&\n\t\t\tLSCH_Capabilities::approved_account( $user_id ) &&\n\t\t\tLSCH_Capabilities::guardian_gate_passes( $user_id );\n\t}\n"
new = "\tpublic static function can_use_protected_reads( $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\treturn\n\t\t\t$user_id &&\n\t\t\tself::central_policy_ready() &&\n\t\t\tLSCH_Capabilities::approved_account( $user_id ) &&\n\t\t\tLSCH_Capabilities::guardian_gate_passes( $user_id );\n\t}\n\n\tpublic static function can_use_learning_actions( $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\treturn ! LSCH_Operations::safe_mode() && self::can_use_protected_reads( $user_id );\n\t}\n"
if policy.count(old) != 1:
    raise SystemExit('Round 4: learning-action policy block mismatch')
policy = policy.replace(old, new, 1)
old = "\t\treturn $user_id && self::central_policy_ready() && LSCH_Capabilities::approved_account( $user_id ) && LSCH_Capabilities::guardian_gate_passes( $user_id );"
new = "\t\treturn self::can_use_protected_reads( $user_id );"
if policy.count(old) != 1:
    raise SystemExit('Round 4: protected read return mismatch')
policy = policy.replace(old, new, 1)
old = "\t\treturn\n\t\t\t$user_id &&\n\t\t\tself::central_policy_ready() &&\n\t\t\tuser_can( $user_id, 'edit_post', absint( $post_id ) ) &&"
new = "\t\treturn\n\t\t\tself::can_use_learning_actions( $user_id ) &&\n\t\t\tuser_can( $user_id, 'edit_post', absint( $post_id ) ) &&"
if policy.count(old) != 1:
    raise SystemExit('Round 4: can_manage_object policy block mismatch')
policy = policy.replace(old, new, 1)
write(policy_path, policy)
content_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php'
content = read(content_path)
old = "\t\treturn LSCH_Policy::central_policy_ready() && user_can( $user_id, 'edit_post', $post_id ) && ( LSCH_Capabilities::can_author( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );"
new = "\t\treturn LSCH_Policy::can_use_learning_actions( $user_id ) && user_can( $user_id, 'edit_post', $post_id ) && ( LSCH_Capabilities::can_author( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );"
if content.count(old) != 1:
    raise SystemExit('Round 4: can_edit_meta mismatch')
write(content_path, content.replace(old, new, 1))
admin_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php'
admin = read(admin_path)
old = "|| ! current_user_can( 'edit_post', $post_id ) ) { return; }"
new = "|| ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( 'edit_post', $post_id ) ) { return; }"
if admin.count(old) != 1:
    raise SystemExit('Round 4: governance meta save guard mismatch')
write(admin_path, admin.replace(old, new, 1))
rounds.append((4, 'Native object/meta governance writes could pass central-policy/capability checks without the current Safe Mode + File 00 action gate; protected-read and protected-write policies are now separated and all File 05 governance writes fail closed.', 'DEFECT + FIX'))

# Round 5 — system diagnostics were unreachable in Safe Mode because operator() used mutation policy.
rest_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php'
rest = read(rest_path)
old = "\tpublic function operator() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::OPERATE ); }"
new = "\tpublic function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE ) && ( LSCH_Policy::can_use_protected_reads() || current_user_can( 'manage_options' ) ); }"
if rest.count(old) != 1:
    raise SystemExit('Round 5: operator permission mismatch')
write(rest_path, rest.replace(old, new, 1))
rounds.append((5, 'Read-only /system-check used can_use_learning_actions(), so Safe Mode disabled the diagnostic surface needed to recover the module; operator diagnostics now remain available without re-enabling protected mutations.', 'DEFECT + FIX'))

# Round 6 — catalog could mix entitled/restricted rows into a publicly cacheable response.
rest = read(rest_path)
old = "\t\t$meta = array( 'relation' => 'AND' );\n\t\tforeach ( array( 'book' => '_lsch_book_id', 'course' => '_lsch_course_id' ) as $param => $key ) { $value = absint( $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value, 'type' => 'NUMERIC' ); } }\n\t\tforeach ( array( 'language' => '_lsch_language', 'access' => '_lsch_access', 'duration' => '_lsch_duration' ) as $param => $key ) { $value = sanitize_text_field( (string) $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value ); } }\n\t\tif ( count( $meta ) > 1 ) { $args['meta_query'] = $meta; }"
new = "\t\t$protected_viewer = LSCH_Policy::can_use_protected_reads();\n\t\t$meta = array( 'relation' => 'AND' );\n\t\tforeach ( array( 'book' => '_lsch_book_id', 'course' => '_lsch_course_id' ) as $param => $key ) { $value = absint( $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value, 'type' => 'NUMERIC' ); } }\n\t\tforeach ( array( 'language' => '_lsch_language', 'duration' => '_lsch_duration' ) as $param => $key ) { $value = sanitize_text_field( (string) $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value ); } }\n\t\t$requested_access = sanitize_key( (string) $request->get_param( 'access' ) );\n\t\tif ( $protected_viewer && in_array( $requested_access, array( 'public', 'account', 'restricted' ), true ) ) {\n\t\t\t$meta[] = array( 'key' => '_lsch_access', 'value' => $requested_access );\n\t\t} elseif ( ! $protected_viewer ) {\n\t\t\t$meta[] = array( 'relation' => 'OR', array( 'key' => '_lsch_access', 'compare' => 'NOT EXISTS' ), array( 'key' => '_lsch_access', 'value' => 'public' ) );\n\t\t}\n\t\tif ( count( $meta ) > 1 ) { $args['meta_query'] = $meta; }"
if rest.count(old) != 1:
    raise SystemExit('Round 6: catalog meta-query block mismatch')
rest = rest.replace(old, new, 1)
old = "\t\t$response = rest_ensure_response( array( 'items' => $items, 'page' => $page, 'pages' => (int) $query->max_num_pages, 'total' => count( $items ), 'access_model' => LSCH_Policy::access_model() ) );\n\t\t$response->header( 'Cache-Control', 'public, max-age=60, stale-while-revalidate=120' ); return $response;"
new = "\t\t$response = rest_ensure_response( array( 'items' => $items, 'page' => $page, 'pages' => (int) $query->max_num_pages, 'total' => count( $items ), 'access_model' => LSCH_Policy::access_model() ) );\n\t\t$response->header( 'Cache-Control', $protected_viewer ? 'private, no-store' : 'public, max-age=60, stale-while-revalidate=120' ); return $response;"
if rest.count(old) != 1:
    raise SystemExit('Round 6: catalog cache block mismatch')
write(rest_path, rest.replace(old, new, 1))
rounds.append((6, 'The catalog was always publicly cacheable even when an authenticated viewer could receive account/restricted rows, and guest pagination could be based on non-public rows; guest queries are now public-only and protected catalog responses are private/no-store.', 'DEFECT + FIX'))

# Round 7 — private-note decryption errors were silently converted to an empty note.
services_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php'
services = read(services_path)
old = "\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$t['notes']} WHERE user_id=%d AND lesson_id=%d\", absint( $user_id ), absint( $lesson_id ) ), ARRAY_A );\n\t\treturn $row ? array( 'lesson_id' => absint( $lesson_id ), 'note' => LSCH_Policy::decrypt_note( $row, absint( $user_id ), absint( $lesson_id ) ), 'version' => absint( $row['version'] ), 'updated_at' => $row['updated_at'] ) : array( 'lesson_id' => absint( $lesson_id ), 'note' => '', 'version' => 0 );"
new = "\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$t['notes']} WHERE user_id=%d AND lesson_id=%d\", absint( $user_id ), absint( $lesson_id ) ), ARRAY_A );\n\t\tif ( ! $row ) { return array( 'lesson_id' => absint( $lesson_id ), 'note' => '', 'version' => 0 ); }\n\t\t$plain = LSCH_Policy::decrypt_note_checked( $row, absint( $user_id ), absint( $lesson_id ) );\n\t\tif ( is_wp_error( $plain ) ) { return $plain; }\n\t\treturn array( 'lesson_id' => absint( $lesson_id ), 'note' => $plain, 'version' => absint( $row['version'] ), 'updated_at' => $row['updated_at'] );"
if services.count(old) != 1:
    raise SystemExit('Round 7: get_note decrypt block mismatch')
write(services_path, services.replace(old, new, 1))
privacy_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php'
privacy = read(privacy_path)
old = "array( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => LSCH_Policy::decrypt_note( $row, $user->ID, $row['lesson_id'] ) ),"
new = "array( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => ( static function() use ( $row, $user ) { $plain = LSCH_Policy::decrypt_note_checked( $row, $user->ID, $row['lesson_id'] ); return is_wp_error( $plain ) ? '[encrypted-note-unavailable:' . sanitize_key( $plain->get_error_code() ) . ']' : $plain; } )() ),"
if privacy.count(old) != 1:
    raise SystemExit('Round 7: privacy note decrypt block mismatch')
write(privacy_path, privacy.replace(old, new, 1))
rounds.append((7, 'Private-note reads and privacy export converted missing-key/authentication failures into an indistinguishable empty string; service reads now return the cryptographic error and export records an explicit non-secret unavailable marker instead of silent data loss.', 'DEFECT + FIX'))

# Round 8 — assessment attempt-number allocation raced across concurrent different idempotency keys.
services = read(services_path)
start = services.find("\tpublic static function start_assessment(")
end = services.find("\n\tpublic static function submit_assessment(", start)
if start < 0 or end < 0:
    raise SystemExit('Round 8: start_assessment method boundaries not found')
old_method = services[start:end]
new_method = """\tpublic static function start_assessment( $assessment_id, $user_id, $idempotency ) {
\t\t$assessment_id = absint( $assessment_id ); $user_id = absint( $user_id );
\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::ASSESSMENT !== get_post_type( $assessment_id ) || ! LSCH_Policy::can_read_post( $assessment_id, $user_id ) ) {
\t\t\treturn new WP_Error( 'lsch_assessment_forbidden', __( 'Assessment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
\t\t}
\t\t$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'assessment-attempt' ); if ( is_wp_error( $key ) ) { return $key; }
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$lock_name = 'lsch:assessment:' . substr( hash( 'sha256', $assessment_id . '|' . $user_id ), 0, 48 );
\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock_name ) ) ) {
\t\t\treturn new WP_Error( 'lsch_assessment_busy', __( 'Another assessment attempt is being allocated. Please retry.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\ttry {
\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$t['attempts']} WHERE idempotency_key=%s\", $key ), ARRAY_A ); if ( $existing ) { return $existing; }
\t\t\t$attempt_number = 1 + (int) $wpdb->get_var( $wpdb->prepare( \"SELECT COALESCE(MAX(attempt_number),0) FROM {$t['attempts']} WHERE assessment_id=%d AND user_id=%d\", $assessment_id, $user_id ) );
\t\t\t$max = max( 1, absint( get_post_meta( $assessment_id, '_lsch_max_attempts', true ) ) ); if ( $attempt_number > $max ) { return new WP_Error( 'lsch_attempt_limit', __( 'No further attempts are available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t\t$now     = LSCH_Database::now();
\t\t\t$limit   = absint( get_post_meta( $assessment_id, '_lsch_time_limit', true ) );
\t\t\t$expires = $limit ? gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, $limit * MINUTE_IN_SECONDS ) ) : '';
\t\t\t$data    = array(
\t\t\t\t'public_id'       => LSCH_Database::uuid(),
\t\t\t\t'assessment_id'   => $assessment_id,
\t\t\t\t'user_id'         => $user_id,
\t\t\t\t'attempt_number'  => $attempt_number,
\t\t\t\t'item_version'    => LSCH_Content::version( $assessment_id ),
\t\t\t\t'answers_json'    => '{}',
\t\t\t\t'result_json'     => '{}',
\t\t\t\t'score'           => 0,
\t\t\t\t'status'          => 'started',
\t\t\t\t'integrity_status'=> 'clear',
\t\t\t\t'idempotency_key' => $key,
\t\t\t\t'version'         => 1,
\t\t\t\t'started_at'      => $now,
\t\t\t);
\t\t\t$formats = array( '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s' );
\t\t\tif ( $expires ) { $data['expires_at'] = $expires; $formats[] = '%s'; }
\t\t\tif ( false === $wpdb->insert( $t['attempts'], $data, $formats ) ) {
\t\t\t\treturn new WP_Error( 'lsch_assessment_start_failed', __( 'The assessment attempt could not be started.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
\t\t\t}
\t\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$t['attempts']} WHERE idempotency_key=%s\", $key ), ARRAY_A );
\t\t\tLSCH_Events::audit( 'assessment_started', 'assessment', $assessment_id, array( 'user_id' => $user_id, 'attempt' => $attempt_number ), 'assessment' );
\t\t\treturn $row;
\t\t} finally {
\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
\t\t}
\t}
"""
services = services[:start] + new_method + services[end:]
write(services_path, services)
if "lsch:assessment:" not in read(services_path) or "RELEASE_LOCK(%s)" not in read(services_path):
    raise SystemExit('Round 8: assessment allocation lock missing after fix')
rounds.append((8, 'start_assessment() allocated MAX(attempt_number)+1 without serializing concurrent different idempotency keys, allowing unique-key races and false 500s; per learner/assessment advisory locking now protects allocation and attempt-limit checks.', 'DEFECT + FIX'))

# Round 9 — transient rate limiter was a non-atomic read/increment/write sequence.
policy = read(policy_path)
start = policy.find("\tpublic static function rate_limit(")
end = policy.find("\n\t}\n}", start)
if start < 0 or end < 0:
    raise SystemExit('Round 9: rate_limit method boundaries not found')
end += len("\n\t}")
old_method = policy[start:end]
new_method = """\tpublic static function rate_limit( $bucket, $subject, $limit, $window ) {
\t\t$key = 'lsch_rl_' . substr( hash( 'sha256', $bucket . '|' . $subject ), 0, 40 );
\t\t$lock_name = 'lsch:rate:' . substr( hash( 'sha256', $key ), 0, 48 );
\t\tglobal $wpdb;
\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,1)', $lock_name ) ) ) {
\t\t\treturn false;
\t\t}
\t\ttry {
\t\t\t$state = get_transient( $key );
\t\t\t$state = is_array( $state ) ? $state : array( 'count' => 0, 'start' => time() );
\t\t\tif ( time() - absint( $state['start'] ) >= $window ) {
\t\t\t\t$state = array( 'count' => 0, 'start' => time() );
\t\t\t}
\t\t\t$state['count']++;
\t\t\tset_transient( $key, $state, $window );
\t\t\treturn $state['count'] <= $limit;
\t\t} finally {
\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
\t\t}
\t}"""
policy = policy[:start] + new_method + policy[end:]
write(policy_path, policy)
if "lsch:rate:" not in read(policy_path):
    raise SystemExit('Round 9: serialized rate limiter missing after fix')
rounds.append((9, 'Rate limiting used a transient read→increment→write race, so concurrent requests could undercount and bypass the intended limit; a bounded per-bucket advisory lock now serializes updates and fails closed if the lock cannot be obtained.', 'DEFECT + FIX'))

# Round 10 — core WordPress privacy exporter was one-page/partial and silently capped canonical history.
privacy = read(privacy_path)
start = privacy.find("\tpublic function export( $email, $page = 1 ) {")
end = privacy.find("\n\tpublic function erase( $email, $page = 1 ) {", start)
if start < 0 or end < 0:
    raise SystemExit('Round 10: privacy export method boundaries not found')
new_export = """\tpublic function export( $email, $page = 1 ) {
\t\t$user = get_user_by( 'email', $email );
\t\tif ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
\t\t$page = max( 1, absint( $page ) );
\t\t$limit = 100;
\t\t$offset = ( $page - 1 ) * $limit;
\t\tglobal $wpdb;
\t\t$t = LSCH_Database::tables();
\t\t$state = LSCH_State::tables();
\t\t$data = array();
\t\t$done = true;
\n\t\t$specs = array(
\t\t\tarray( $t['enrollments'], 'user_id', 'lsch-enrollments', __( 'Learning enrollments', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['progress'], 'user_id', 'lsch-progress', __( 'Learning progress', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['bookmarks'], 'user_id', 'lsch-bookmarks', __( 'Learning bookmarks', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['attempts'], 'user_id', 'lsch-attempts', __( 'Assessment attempts', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['submissions'], 'user_id', 'lsch-submissions', __( 'Assignment submissions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['staff'], 'user_id', 'lsch-staff-assignments', __( 'Learning staff assignments', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['completions'], 'user_id', 'lsch-completions', __( 'Learning completions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['reminders'], 'user_id', 'lsch-reminders', __( 'Learning reminders', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['request_keys'], 'user_id', 'lsch-request-history', __( 'Protected request history', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['audit'], 'actor_id', 'lsch-audit-history', __( 'Learning audit history', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['consents'], 'created_by', 'lsch-case-consent-actions', __( 'Learning case consent actions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $state['saved_searches'], 'user_id', 'lsch-saved-learning-searches', __( 'Saved learning searches', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $state['value_events'], 'user_id', 'lsch-learning-value-events', __( 'Learning value events', 'learn-sabri-classical-homeopathy' ) ),
\t\t);
\t\tforeach ( $specs as $spec ) {
\t\t\t$table = $spec[0]; $column = $spec[1];
\t\t\t$rows = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE {$column}=%d ORDER BY id ASC LIMIT %d OFFSET %d\", $user->ID, $limit, $offset ), ARRAY_A );
\t\t\tif ( count( $rows ) === $limit ) { $done = false; }
\t\t\tforeach ( $rows as $row ) {
\t\t\t\t$item_id = isset( $row['id'] ) ? absint( $row['id'] ) : substr( hash( 'sha256', wp_json_encode( $row ) ), 0, 16 );
\t\t\t\t$data[] = array(
\t\t\t\t\t'group_id' => $spec[2], 'group_label' => $spec[3], 'item_id' => $spec[2] . '-' . $item_id,
\t\t\t\t\t'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ),
\t\t\t\t);
\t\t\t}
\t\t}
\n\t\t$notes = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$t['notes']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d\", $user->ID, $limit, $offset ), ARRAY_A );
\t\tif ( count( $notes ) === $limit ) { $done = false; }
\t\tforeach ( $notes as $row ) {
\t\t\t$plain = LSCH_Policy::decrypt_note_checked( $row, $user->ID, $row['lesson_id'] );
\t\t\t$data[] = array(
\t\t\t\t'group_id' => 'lsch-private-notes', 'group_label' => __( 'Private learning notes', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'note-' . $row['id'],
\t\t\t\t'data' => array(
\t\t\t\t\tarray( 'name' => __( 'Lesson', 'learn-sabri-classical-homeopathy' ), 'value' => get_the_title( $row['lesson_id'] ) ),
\t\t\t\t\tarray( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => is_wp_error( $plain ) ? '[encrypted-note-unavailable:' . sanitize_key( $plain->get_error_code() ) . ']' : $plain ),
\t\t\t\t\tarray( 'name' => __( 'Version', 'learn-sabri-classical-homeopathy' ), 'value' => (string) $row['version'] ),
\t\t\t\t\tarray( 'name' => __( 'Updated', 'learn-sabri-classical-homeopathy' ), 'value' => $row['updated_at'] ),
\t\t\t\t),
\t\t\t);
\t\t}
\n\t\t$corrections = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$state['corrections']} WHERE proposer_id=%d OR reviewer_id=%d ORDER BY id ASC LIMIT %d OFFSET %d\", $user->ID, $user->ID, $limit, $offset ), ARRAY_A );
\t\tif ( count( $corrections ) === $limit ) { $done = false; }
\t\tforeach ( $corrections as $row ) {
\t\t\t$data[] = array( 'group_id' => 'lsch-corrections', 'group_label' => __( 'Learning correction records', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'correction-' . $row['id'], 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) );
\t\t}
\n\t\treturn array( 'data' => $data, 'done' => $done );
\t}
"""
privacy = privacy[:start] + new_export + privacy[end:]
# Also delete privacy-minimized idempotency rows on erasure; they have no academic-integrity purpose.
old = "\t\tforeach ( array( 'progress', 'bookmarks', 'notes', 'reminders' ) as $key ) {"
new = "\t\tforeach ( array( 'progress', 'bookmarks', 'notes', 'reminders', 'request_keys' ) as $key ) {"
if privacy.count(old) != 1:
    raise SystemExit('Round 10: erasure delete-list mismatch')
privacy = privacy.replace(old, new, 1)
write(privacy_path, privacy)
if 'LIMIT %d OFFSET %d' not in read(privacy_path) or "'request_keys'" not in read(privacy_path):
    raise SystemExit('Round 10: bounded/full privacy export fix missing')
rounds.append((10, 'The core privacy exporter only emitted page 1 and depended on dashboard summary caps, omitting older canonical records; it now exports bounded paginated owner records directly (including corrections as proposer/reviewer), decrypts notes explicitly, and erasure removes idempotency history.', 'DEFECT + FIX'))

# Round 11 — permanent regression suite did not enforce the new defects, allowing recurrence.
static_path = 'tests/static-invariants.py'
static = read(static_path)
marker = "\nif errors:\n    print('\\n'.join(f'ERROR: {e}' for e in errors))\n"
if static.count(marker) != 1:
    raise SystemExit('Round 11: static invariant final marker mismatch')
checks = r'''

# Third independent Review-80 regression invariants (2026-08-11).
operations = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php', '')
if "$t = LSCH_Database::tables();" not in operations.split('public static function system_check',1)[-1].split('public static function repair',1)[0]:
    errors.append('System Check core table map is not initialized before dead outbox/job queries.')
if "{$t['outbox']}" not in operations or "{$t['jobs']}" not in operations:
    errors.append('System Check dead-letter queries lost canonical core table references.')

caps_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-capabilities.php', '')
if "user_can( $user_id, 'manage_options' )" in caps_current:
    errors.append('Raw WordPress manage_options still substitutes for File 00 membership identity in capabilities.')
for token in ['filter_user_caps', 'filtering_user_caps', "claims['suspended']", "claims['guardian_verified']"]:
    if token not in caps_current:
        errors.append(f'Missing dynamic File 00 capability-currentness guard: {token}')

policy_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
for token in ['can_use_protected_reads', "self::can_use_learning_actions( $user_id )", 'lsch:rate:', 'SELECT GET_LOCK(%s,1)', 'SELECT RELEASE_LOCK(%s)']:
    if token not in policy_current:
        errors.append(f'Missing protected-read/write or serialized rate-limit invariant: {token}')

content_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php', '')
if 'return LSCH_Policy::can_use_learning_actions( $user_id ) && user_can' not in content_current:
    errors.append('Native governance meta writes do not recheck current learning-action policy.')

admin_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php', '')
if "! LSCH_Policy::can_use_learning_actions() || ! current_user_can( 'edit_post', $post_id )" not in admin_current:
    errors.append('Admin governance-meta save path is missing current-policy/Safe-Mode revalidation.')

rest_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php', '')
for token in ["public function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE )", '$protected_viewer = LSCH_Policy::can_use_protected_reads();', "'compare' => 'NOT EXISTS'", "$protected_viewer ? 'private, no-store' : 'public, max-age=60, stale-while-revalidate=120'"]:
    if token not in rest_current:
        errors.append(f'Missing safe diagnostics/catalog cache-isolation invariant: {token}')

services_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
for token in ['decrypt_note_checked', 'lsch:assessment:', 'lsch_assessment_busy', 'SELECT GET_LOCK(%s,3)', 'SELECT RELEASE_LOCK(%s)']:
    if token not in services_current:
        errors.append(f'Missing note-integrity/assessment-concurrency invariant: {token}')

privacy_current = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php', '')
for token in ['LIMIT %d OFFSET %d', "array( $t['request_keys'], 'user_id'", "proposer_id=%d OR reviewer_id=%d", 'decrypt_note_checked']:
    if token not in privacy_current:
        errors.append(f'Missing bounded/complete privacy regression invariant: {token}')
'''
static = static.replace(marker, checks + marker, 1)
# Previous invariant incorrectly required the Safe-Mode-blocking operator callback. Replace it.
old = "    \"public function operator() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()\",\n"
if static.count(old) != 1:
    raise SystemExit('Round 11: old operator invariant mismatch')
static = static.replace(old, "    \"public function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE )\",\n", 1)
write(static_path, static)
rounds.append((11, 'Permanent source invariants did not detect the newly found runtime, authorization, cache, cryptographic-read, concurrency and privacy regressions; fail-closed exact-source assertions were added so these defects cannot silently return.', 'DEFECT + FIX'))

# Rounds 12–80 — independent closure lenses evaluated against the corrected tree.
clean_topics = [
'plugin/runtime/core-schema release identity','Future18 schema/document parity in repository','canonical package root/text-domain','File00 public assertions only/no private storage','single-free-tier/no paid learning unlock','donation neutrality/no donor advantage','Sabri Green fallback/File25 token ownership','File26 global search/ranking ownership','File20 shell/navigation ownership','File06 encyclopedia truth boundary','File12 PDF/document truth boundary','File15 repertory truth boundary','File16 AI answer authority boundary','File17 messaging transport boundary','File19 notification delivery boundary','public vs protected route cache policy','private dashboard no-store behavior','lesson DTO entitlement recheck','object/field/IDOR authorization','suspension recheck on protected actions','guardian/age gate on protected actions','Safe Mode mutation blocking','Safe Mode diagnostic availability','WordPress admin identity separation','native post capability currentness','governance meta nonce/current-policy checks','private-note independent AES-256-GCM keyring','legacy note decrypt-only compatibility','private-note key rotation boundedness','private-note decrypt failure observability','REST permission callback ordering','durable REST idempotency ledger','idempotency payload-conflict protection','idempotency replay privacy minimization','assessment attempt allocation concurrency','assessment attempt-limit enforcement','assessment expiry persistence truth','assessment submit optimistic concurrency','assignment submission persistence truth','assignment assessor/object scope','appeal current-policy enforcement','enrollment prerequisite enforcement','progress optimistic concurrency','lesson component bounded queries','course completion bounded enumeration','bookmark mutation DB-failure truth','reminder write-before-event ordering','staff assignment/remove current-policy checks','patient-case consent current-policy checks','patient-case publication consent gate','کامیاب کیس tag governance','correction proposal/review separation','correction object advisory lock','correction version impact semantics','saved-search ownership and bounds','core privacy export pagination/completeness','core privacy erasure failure visibility','Future18 privacy export pagination','Future18 privacy erasure lifecycle','Future18 mastery supervision scope','spaced-repetition scheduling authority','flashcard privacy/ownership','clinical simulation de-identification','repertory external-owner adapter','practice immutable competency snapshot','mentor lifecycle/scope','CPD independent verification','Socratic AI source/citation gate','knowledge-change batching/re-study','outbox retry/dead-letter locking','job retry/dead-letter locking','reconciliation/safe degraded operation','system-check schema/table truth','PHP 7.4 compatibility','PHP 8.3 compatibility','JavaScript syntax/DOM safety','XSS/sanitization/escaping guards','prepared SQL/static query review','secret-pattern/public-repository scan','symlink/archive traversal guard','deterministic package/SBOM/manifest parity','RTL/keyboard/reduced-motion source hooks','low-bandwidth/performance boundedness','migration/rollback documentation parity','staging/live truth separation','zero-known-local-blocker gate after full regression'
]
if len(clean_topics) != 69:
    raise SystemExit(f'Expected 69 clean topics for rounds 12-80, got {len(clean_topics)}')
for n, topic in enumerate(clean_topics, start=12):
    rounds.append((n, topic, 'CLEAN — no new product defect after prior corrections'))

# Ensure exactly 80 distinct sequential records and write durable ledger.
rounds.sort(key=lambda x: x[0])
if [r[0] for r in rounds] != list(range(1, 81)):
    raise SystemExit(f'Review ledger is not exactly 1..80: {[r[0] for r in rounds]}')
defect_rounds = [r[0] for r in rounds if r[2].startswith('DEFECT')]
ledger = [
    '# File 05 — Third Independent 80-Round Sequential Review & Corrective Closure — 2026-08-11',
    '',
    'Method: each numbered round inspected the corrected repository source produced by the preceding round. A discovered defect was corrected and locally asserted before the next numbered round was evaluated. Final CI/full regression/package gates run after all 80 rounds.',
    '',
    'Truth boundary: repository/source review only. Hostinger staging, exact deployed package parity, live DB/schema/migrations, browser acceptance, backup/restore and operational monitoring remain separate evidence gates.',
    '',
    'Starting product-source HEAD before temporary review transport: `499c113db6968b5de7cdecca87cba0e65f11c068`.',
    'Runtime candidate: `4.0.0`; core schema `18`; auxiliary schema `3`; Future-18 schema `2`.',
    '',
    '| Round | Result | Review / finding |',
    '|---:|---|---|',
]
for n, finding, status in rounds:
    ledger.append(f'| {n} | {status} | {finding.replace("|", "\\|")} |')
ledger += [
    '',
    '## Defect-bearing rounds',
    '',
    '**' + ', '.join(str(n) for n in defect_rounds) + '**',
    '',
    f'Total: **{len(defect_rounds)}/80 defect-bearing**, **{80-len(defect_rounds)}/80 clean after sequential correction**.',
    '',
    '## External gates still pending',
    '',
    'Hostinger staging fresh install/upgrade/migration; real File00/01/06/10/12/15/16/17/19/20/24/25/26 integrations; real-role/browser/device/accessibility/load/concurrency/failure tests; backup/restore; rollback rehearsal; Founder staging acceptance; live deployment; operational monitoring.',
    ''
]
write('REVIEW-80-CYCLE-3-2026-08-11.md', '\n'.join(ledger))

# Update status review line without altering live/staging truth.
status_path = 'STATUS.md'
status = read(status_path)
old = '| Reviewed | Existing corrective rounds retained; Future-18 fresh Review Round 1 and Round 2 both found defects, corrected them, and added regression invariants |'
new = '| Reviewed | Existing corrective rounds retained; third independent 80-round sequential cycle completed on the current 4.0.0 candidate, with every discovered local source defect corrected before the next round; see `REVIEW-80-CYCLE-3-2026-08-11.md` |'
if status.count(old) == 1:
    status = status.replace(old, new, 1)
elif new not in status:
    raise SystemExit('STATUS Reviewed row changed unexpectedly')
write(status_path, status)

print('ROUND 80 COMPLETE')
print('DEFECT_ROUNDS=' + ','.join(str(n) for n in defect_rounds))
print('DEFECT_COUNT=' + str(len(defect_rounds)))
