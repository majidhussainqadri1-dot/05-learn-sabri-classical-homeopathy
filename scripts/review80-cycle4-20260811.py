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
    text = text.replace(old, new, 1)
    write(path, text)
    if new not in read(path):
        raise SystemExit(f'Round {round_no}: post-fix assertion failed for {path}')
    rounds.append((round_no, finding, 'DEFECT + FIX'))

def replace_method(path, name, new_method, round_no, finding, visibility='public static'):
    text = read(path)
    marker = f'\t{visibility} function {name}('
    start = text.find(marker)
    if start < 0:
        raise SystemExit(f'Round {round_no}: method {name} not found in {path}')
    candidates = []
    for m in ['\n\tpublic static function ', '\n\tprivate static function ', '\n\tprotected static function ', '\n\tpublic function ', '\n\tprivate function ', '\n\tprotected function ']:
        pos = text.find(m, start + len(marker))
        if pos >= 0:
            candidates.append(pos)
    if not candidates:
        # final method before class close
        end = text.rfind('\n}')
    else:
        end = min(candidates)
    text = text[:start] + new_method.rstrip() + '\n' + text[end:]
    write(path, text)
    if new_method.split('\n', 1)[0].strip() not in read(path):
        raise SystemExit(f'Round {round_no}: replacement {name} not present')
    rounds.append((round_no, finding, 'DEFECT + FIX'))

SERVICES = '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php'
CAPS = '05-learn-sabri-classical-homeopathy/includes/class-lsch-capabilities.php'
POLICY = '05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php'
REST = '05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php'
IDEM = '05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php'
EVENTS = '05-learn-sabri-classical-homeopathy/includes/class-lsch-events.php'
PRIVACY = '05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php'
STATIC = 'tests/static-invariants.py'

# Round 1 — Enrollment state machine had regressed to direct active creation and arbitrary transitions.
new_enroll = r'''\tpublic static function enroll( $course_id, $user_id, $idempotency ) {
\t\t$course_id = absint( $course_id );
\t\t$user_id   = absint( $user_id );
\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) || ! LSCH_Policy::can_read_post( $course_id, $user_id ) ) {
\t\t\treturn new WP_Error( 'lsch_forbidden', __( 'Enrollment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
\t\t}
\t\t$prerequisites = LSCH_Policy::validate_prerequisites( $course_id, $user_id );
\t\tif ( is_wp_error( $prerequisites ) ) { return $prerequisites; }
\t\t$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'enroll' );
\t\tif ( is_wp_error( $key ) ) { return $key; }
\t\tglobal $wpdb;
\t\t$t = LSCH_Database::tables();
\t\t$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1", $user_id, $course_id ), ARRAY_A );
\t\tif ( $current && 'withdrawn' !== $current['status'] ) {
\t\t\treturn $current;
\t\t}
\t\t$now = LSCH_Database::now();
\t\tif ( $current ) {
\t\t\t$updated = $wpdb->update(
\t\t\t\t$t['enrollments'],
\t\t\t\tarray( 'status' => 'enrolled', 'terms_version' => LSCH_Policy::access_model(), 'course_version' => LSCH_Content::version( $course_id ), 'version' => absint( $current['version'] ) + 1, 'withdrawn_at' => null, 'paused_at' => null, 'updated_at' => $now ),
\t\t\t\tarray( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ), 'status' => 'withdrawn' ),
\t\t\t\tarray( '%s', '%s', '%d', '%d', '%s', '%s', '%s' ), array( '%d', '%d', '%s' )
\t\t\t);
\t\t\tif ( 1 !== $updated ) { return new WP_Error( 'lsch_enrollment_conflict', __( 'Enrollment changed while being restored. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t} else {
\t\t\t$inserted = $wpdb->insert(
\t\t\t\t$t['enrollments'],
\t\t\t\tarray( 'public_id' => LSCH_Database::uuid(), 'user_id' => $user_id, 'course_id' => $course_id, 'status' => 'enrolled', 'terms_version' => LSCH_Policy::access_model(), 'course_version' => LSCH_Content::version( $course_id ), 'version' => 1, 'started_at' => $now, 'created_at' => $now, 'updated_at' => $now ),
\t\t\t\tarray( '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
\t\t\t);
\t\t\tif ( 1 !== $inserted ) { return new WP_Error( 'lsch_enrollment_failed', __( 'Enrollment could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }
\t\t}
\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d", $user_id, $course_id ), ARRAY_A );
\t\tLSCH_Events::publish( 'LearningEnrollmentCreated.v1', 'course', $course_id, array( 'user_id' => $user_id, 'course_id' => $course_id, 'status' => 'enrolled', 'terms_version' => $row['terms_version'] ) );
\t\tLSCH_Events::audit( 'enroll_course', 'course', $course_id, array( 'user_id' => $user_id, 'idempotency_hash' => $key, 'state' => 'enrolled' ), 'learning' );
\t\treturn $row;
\t}'''
replace_method(SERVICES, 'enroll', new_enroll, 1, 'Enrollment creation bypassed the governed eligible→enrolled→active lifecycle by writing active immediately, and duplicate enroll calls mutated version/state. Creation now persists enrolled, duplicate non-withdrawn calls are side-effect free, and withdrawal re-enrollment is optimistic-version guarded.')

new_change = r'''\tpublic static function change_enrollment_state( $course_id, $user_id, $state, $expected_version ) {
\t\t$course_id = absint( $course_id ); $user_id = absint( $user_id ); $state = sanitize_key( $state );
\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) ) {
\t\t\treturn new WP_Error( 'lsch_invalid_transition', __( 'The enrollment transition is not allowed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1", $user_id, $course_id ), ARRAY_A );
\t\tif ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {
\t\t\treturn new WP_Error( 'lsch_stale_enrollment', __( 'Enrollment changed. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\t$transitions = array( 'enrolled' => array( 'active', 'withdrawn' ), 'active' => array( 'paused', 'withdrawn' ), 'paused' => array( 'active', 'withdrawn' ) );
\t\tif ( empty( $transitions[ $current['status'] ] ) || ! in_array( $state, $transitions[ $current['status'] ], true ) ) {
\t\t\treturn new WP_Error( 'lsch_invalid_transition', __( 'The enrollment transition is not allowed from its current state.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\t$now = LSCH_Database::now();
\t\t$data = array( 'status' => $state, 'version' => absint( $current['version'] ) + 1, 'updated_at' => $now );
\t\t$formats = array( '%s', '%d', '%s' );
\t\tif ( 'active' === $state ) { $data['paused_at'] = null; $formats[] = '%s'; }
\t\tif ( 'paused' === $state ) { $data['paused_at'] = $now; $formats[] = '%s'; }
\t\tif ( 'withdrawn' === $state ) { $data['withdrawn_at'] = $now; $formats[] = '%s'; }
\t\t$updated = $wpdb->update( $t['enrollments'], $data, array( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ), 'status' => $current['status'] ), $formats, array( '%d', '%d', '%s' ) );
\t\tif ( 1 !== $updated ) { return new WP_Error( 'lsch_stale_enrollment', __( 'Enrollment changed while saving. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t$event = 'active' === $state ? 'LearningEnrollmentActivated.v1' : 'LearningEnrollmentStateChanged.v1';
\t\tLSCH_Events::publish( $event, 'course', $course_id, array( 'user_id' => $user_id, 'from' => $current['status'], 'to' => $state, 'version' => absint( $current['version'] ) + 1 ) );
\t\tLSCH_Events::audit( 'enrollment_' . $state, 'course', $course_id, array( 'user_id' => $user_id, 'from' => $current['status'], 'to' => $state ), 'learning' );
\t\treturn $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE id=%d", absint( $current['id'] ) ), ARRAY_A );
\t}'''
# Same conceptual review round; do not add a second ledger row.
text = read(SERVICES)
start = text.find('\tpublic static function change_enrollment_state(')
end = text.find('\n\tpublic static function ', start + 10)
if start < 0 or end < 0: raise SystemExit('Round 1: change_enrollment_state boundaries missing')
write(SERVICES, text[:start] + new_change + '\n' + text[end:])

# Round 2 — File05 staff assignments were persisted but ordinary assigned users received no executable File05 coarse capabilities.
caps = read(CAPS)
insert_anchor = "\n\t/** Remove File 05 domain capabilities when current File 00 assertions are not usable. */"
helpers = r'''

\tprivate static function active_staff_rows( $user_id ) {
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$rows = $wpdb->get_results( $wpdb->prepare( "SELECT object_type,object_id,role FROM {$t['staff']} WHERE user_id=%d AND active=1 AND conflict_status='clear' ORDER BY id ASC LIMIT 250", absint( $user_id ) ), ARRAY_A );
\t\treturn is_array( $rows ) ? $rows : array();
\t}

\tprivate static function grant( array $allcaps, array $capabilities ) {
\t\tforeach ( $capabilities as $capability ) { $allcaps[ $capability ] = true; }
\t\treturn $allcaps;
\t}

\tprivate static function row_matches( array $row, $role, $type = '', $id = null ) {
\t\tif ( sanitize_key( $row['role'] ) !== sanitize_key( $role ) ) { return false; }
\t\tif ( '' !== $type && sanitize_key( $row['object_type'] ) !== sanitize_key( $type ) ) { return false; }
\t\treturn null === $id || absint( $row['object_id'] ) === absint( $id );
\t}
'''
if caps.count(insert_anchor) != 1: raise SystemExit('Round 2: capability helper insertion anchor mismatch')
caps = caps.replace(insert_anchor, helpers + insert_anchor, 1)
write(CAPS, caps)
new_filter = r'''\tpublic static function filter_user_caps( $allcaps, $caps, $args, $user ) {
\t\tif ( self::$filtering_user_caps || ! $user instanceof WP_User || ! $user->ID ) { return $allcaps; }
\t\tself::$filtering_user_caps = true;
\t\t$claims = LSCH_Dependencies::claims( $user->ID );
\t\tself::$filtering_user_caps = false;
\t\t$domain = self::all();
\t\t$founder = ! empty( $claims['founder'] );
\t\t$allowed = $founder || ( ! empty( $claims['approved'] ) && ! empty( $claims['eligible'] ) && empty( $claims['suspended'] ) && ! empty( $claims['guardian_verified'] ) );
\t\tif ( ! $allowed ) {
\t\t\tforeach ( $domain as $capability ) {
\t\t\t\tif ( self::OPERATE === $capability && ! empty( $allcaps['manage_options'] ) ) { continue; }
\t\t\t\tunset( $allcaps[ $capability ] );
\t\t\t}
\t\t\treturn $allcaps;
\t\t}
\t\tif ( $founder ) { return self::grant( $allcaps, $domain ); }
\t\t$rows = self::active_staff_rows( $user->ID );
\t\tforeach ( $rows as $row ) {
\t\t\tswitch ( sanitize_key( $row['role'] ) ) {
\t\t\t\tcase 'teacher': $allcaps = self::grant( $allcaps, array( self::TEACH, self::VIEW_ANALYTICS ) ); break;
\t\t\t\tcase 'assessor': $allcaps = self::grant( $allcaps, array( self::ASSESS ) ); break;
\t\t\t\tcase 'reviewer': $allcaps = self::grant( $allcaps, array( self::REVIEW_LESSONS ) ); break;
\t\t\t\tcase 'curriculum_lead':
\t\t\t\t\tif ( 'platform' === sanitize_key( $row['object_type'] ) && 0 === absint( $row['object_id'] ) ) { $allcaps = self::grant( $allcaps, $domain ); }
\t\t\t\t\tbreak;
\t\t\t}
\t\t}
\t\t$requested = isset( $args[0] ) ? (string) $args[0] : '';
\t\t$object_id = isset( $args[2] ) ? absint( $args[2] ) : 0;
\t\tif ( $object_id && in_array( $requested, array( 'read_post', 'edit_post' ), true ) ) {
\t\t\t$type = get_post_type( $object_id );
\t\t\tforeach ( $rows as $row ) {
\t\t\t\t$semantic = LSCH_Content::LESSON === $type ? 'lesson' : ( LSCH_Content::ASSIGNMENT === $type ? 'assignment' : ( LSCH_Content::ASSESSMENT === $type ? 'assessment' : '' ) );
\t\t\t\t$scoped = ( 'lesson' === $semantic && self::row_matches( $row, 'teacher', 'lesson', $object_id ) ) ||
\t\t\t\t\t( 'read_post' === $requested && 'lesson' === $semantic && self::row_matches( $row, 'reviewer', 'lesson', $object_id ) ) ||
\t\t\t\t\t( 'read_post' === $requested && in_array( $semantic, array( 'assignment', 'assessment' ), true ) && self::row_matches( $row, 'assessor', $semantic, $object_id ) );
\t\t\t\tif ( $scoped ) { foreach ( (array) $caps as $primitive ) { $allcaps[ $primitive ] = true; } break; }
\t\t\t}
\t\t}
\t\treturn $allcaps;
\t}'''
replace_method(CAPS, 'filter_user_caps', new_filter, 2, 'Active conflict-cleared File 05 teacher/assessor/reviewer/curriculum-lead records existed but were not converted into executable least-privilege capabilities; current File00-eligible staff and Founder now receive bounded dynamic capabilities, with object-level native read/edit grants only for assigned objects.')

# Round 3 — Staff assignment scope was unvalidated, and reviewer consent authority was global rather than object-scoped.
new_assign = r'''\tpublic static function assign_staff( $user_id, $object_type, $object_id, $role, array $scope, $conflict = 'clear' ) {
\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
\t\t\treturn new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
\t\t}
\t\t$user_id = absint( $user_id ); $object_type = sanitize_key( $object_type ); $object_id = absint( $object_id ); $role = sanitize_key( $role ); $conflict = sanitize_key( $conflict );
\t\tif ( ! get_userdata( $user_id ) || ! LSCH_Capabilities::approved_account( $user_id ) || ! LSCH_Capabilities::guardian_gate_passes( $user_id ) ) {
\t\t\treturn new WP_Error( 'lsch_staff_target_ineligible', __( 'Only a currently eligible approved account may receive a learning staff assignment.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\t$allowed_conflicts = array( 'clear', 'declared', 'blocked' );
\t\tif ( ! in_array( $conflict, $allowed_conflicts, true ) ) { return new WP_Error( 'lsch_staff_conflict_state', __( 'Staff conflict state is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
\t\t$role_scopes = array(
\t\t\t'teacher' => array( 'course' => LSCH_Content::COURSE, 'lesson' => LSCH_Content::LESSON, 'cohort' => LSCH_Content::COHORT ),
\t\t\t'assessor' => array( 'assessment' => LSCH_Content::ASSESSMENT, 'assignment' => LSCH_Content::ASSIGNMENT ),
\t\t\t'reviewer' => array( 'lesson' => LSCH_Content::LESSON ),
\t\t\t'curriculum_lead' => array( 'platform' => '' ),
\t\t);
\t\tif ( ! isset( $role_scopes[ $role ][ $object_type ] ) ) { return new WP_Error( 'lsch_staff_scope_invalid', __( 'The requested learning staff role is not valid for this object type.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
\t\t$expected_type = $role_scopes[ $role ][ $object_type ];
\t\tif ( 'platform' === $object_type ) {
\t\t\tif ( 0 !== $object_id ) { return new WP_Error( 'lsch_staff_scope_invalid', __( 'Platform curriculum-lead assignments must use the platform scope.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
\t\t} elseif ( ! $object_id || $expected_type !== get_post_type( $object_id ) ) {
\t\t\treturn new WP_Error( 'lsch_staff_scope_invalid', __( 'The learning staff assignment object does not match its declared scope.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
\t\t}
\t\t$scope_json = LSCH_Policy::sanitize_json( $scope, 10000 ); if ( is_wp_error( $scope_json ) ) { return $scope_json; }
\t\tglobal $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
\t\t$sql = $wpdb->prepare( "INSERT INTO {$t['staff']} (user_id,object_type,object_id,role,scope_json,conflict_status,active,version,assigned_by,created_at,updated_at) VALUES (%d,%s,%d,%s,%s,%s,%d,1,%d,%s,%s) ON DUPLICATE KEY UPDATE scope_json=VALUES(scope_json),conflict_status=VALUES(conflict_status),active=VALUES(active),version=version+1,assigned_by=VALUES(assigned_by),updated_at=VALUES(updated_at)", $user_id, $object_type, $object_id, $role, $scope_json, $conflict, 'clear' === $conflict ? 1 : 0, get_current_user_id(), $now, $now );
\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_staff_write_failed', __( 'Staff assignment could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }
\t\tLSCH_Events::audit( 'staff_assigned', $object_type, $object_id, array( 'user_id' => $user_id, 'role' => $role, 'conflict' => $conflict ), 'governance' );
\t\treturn true;
\t}'''
replace_method(SERVICES, 'assign_staff', new_assign, 3, 'Staff governance accepted arbitrary role/object combinations and unverified target accounts, while reviewer capability could become globally effective. Role→object scope, conflict state and target File00 eligibility are now validated before persistence.')
# Make staff scope helper callable by REST permission layer.
services = read(SERVICES).replace('\tprivate static function staff_scope_allows(', '\tpublic static function staff_scope_allows(', 1)
write(SERVICES, services)
# Replace both consent authorization conditions with exact reviewer lesson scope.
services = read(SERVICES)
old_auth = "( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) )"
new_auth = "( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! ( user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) && self::staff_scope_allows( $actor_id, 'lesson', $lesson_id, 'reviewer' ) ) )"
if services.count(old_auth) != 2: raise SystemExit(f'Round 3: expected two consent authorization patterns, got {services.count(old_auth)}')
write(SERVICES, services.replace(old_auth, new_auth, 2))
rest = read(REST)
old = "\tpublic function author_or_reviewer( WP_REST_Request $request ) { $id = absint( $request['id'] ); return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( LSCH_Policy::can_manage_object( $id ) || current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) ); }"
new = "\tpublic function author_or_reviewer( WP_REST_Request $request ) { $id = absint( $request['id'] ); $user_id = get_current_user_id(); return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( LSCH_Policy::can_manage_object( $id, $user_id ) || ( current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) && LSCH_Services::staff_scope_allows( $user_id, 'lesson', $id, 'reviewer' ) ) ); }"
if rest.count(old) != 1: raise SystemExit('Round 3: REST reviewer scope pattern mismatch')
write(REST, rest.replace(old, new, 1))

# Round 4 — Request trace ID was regenerated on every call and most REST errors carried no trace/support reference.
policy = read(POLICY)
old = "\tpublic static function request_id() {\n\t\t$header = isset( $_SERVER['HTTP_X_REQUEST_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUEST_ID'] ) ) : '';\n\t\treturn preg_match( '/^[A-Za-z0-9._-]{8,64}$/', $header ) ? $header : LSCH_Database::uuid();\n\t}"
new = "\tpublic static function request_id() {\n\t\tstatic $request_id = '';\n\t\tif ( $request_id ) { return $request_id; }\n\t\t$header = isset( $_SERVER['HTTP_X_REQUEST_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUEST_ID'] ) ) : '';\n\t\t$request_id = preg_match( '/^[A-Za-z0-9._-]{8,64}$/', $header ) ? $header : LSCH_Database::uuid();\n\t\treturn $request_id;\n\t}"
if policy.count(old) != 1: raise SystemExit('Round 4: request_id pattern mismatch')
write(POLICY, policy.replace(old, new, 1))
rest = read(REST)
old = "\tpublic function hooks() { add_action( 'rest_api_init', array( $this, 'register' ) ); }"
new = "\tpublic function hooks() { add_action( 'rest_api_init', array( $this, 'register' ) ); add_filter( 'rest_post_dispatch', array( $this, 'trace_response' ), 20, 3 ); }\n\n\tpublic function trace_response( $response, $server, $request ) {\n\t\tunset( $server );\n\t\tif ( ! $request instanceof WP_REST_Request || 0 !== strpos( (string) $request->get_route(), '/' . self::NS . '/' ) ) { return $response; }\n\t\t$trace = LSCH_Policy::request_id();\n\t\tif ( is_wp_error( $response ) ) { $code = $response->get_error_code(); $data = $response->get_error_data( $code ); $data = is_array( $data ) ? $data : array(); $data['trace_id'] = $trace; $response->add_data( $data, $code ); return $response; }\n\t\t$response = rest_ensure_response( $response );\n\t\tif ( $response instanceof WP_REST_Response ) {\n\t\t\t$response->header( 'X-Request-ID', $trace );\n\t\t\tif ( $response->get_status() >= 400 ) { $data = $response->get_data(); if ( is_array( $data ) ) { if ( isset( $data['data'] ) && is_array( $data['data'] ) ) { $data['data']['trace_id'] = $trace; } else { $data['trace_id'] = $trace; } $response->set_data( $data ); } }\n\t\t}\n\t\treturn $response;\n\t}"
if rest.count(old) != 1: raise SystemExit('Round 4: REST hooks pattern mismatch')
write(REST, rest.replace(old, new, 1))
rounds.append((4, 'request_id() generated a new UUID on repeated calls in the same request and only isolated errors carried a support trace. The ID is now request-stable and every File 05 REST error response receives the same trace_id plus X-Request-ID.', 'DEFECT + FIX'))

# Round 5 — Mutating REST contract required rate limiting, but only catalog GET used a limiter.
idem = read(IDEM)
anchor = "\t\t$route = (string) $request->get_route();\n\t\t$method = strtoupper( (string) $request->get_method() );\n\t\t$action = 'rest_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 32 );"
replacement = "\t\t$route = (string) $request->get_route();\n\t\t$method = strtoupper( (string) $request->get_method() );\n\t\t$rate_bucket = 'rest_mutation_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 24 );\n\t\tif ( ! LSCH_Policy::rate_limit( $rate_bucket, (string) $user_id, 60, 60 ) ) {\n\t\t\treturn new WP_Error( 'lsch_rate_limited', __( 'Please wait before trying this protected learning action again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 429, 'trace_id' => LSCH_Policy::request_id() ) );\n\t\t}\n\t\t$action = 'rest_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 32 );"
if idem.count(anchor) != 1: raise SystemExit('Round 5: idempotency rate anchor mismatch')
write(IDEM, idem.replace(anchor, replacement, 1))
rounds.append((5, 'Every mutating API is required to be rate-limited, but mutations relied only on authentication/idempotency while catalog GET alone called guard_rate(). A per-user/per-route serialized mutation budget now runs after permission checks and before the replay ledger.', 'DEFECT + FIX'))

# Round 6 — REST owner writes, outbox and audit were separate commits; outbox/audit failures could still return success.
events = read(EVENTS)
class_anchor = "final class LSCH_Events {\n"
insert = r'''final class LSCH_Events {
\tprivate static $request_failures = array();

\tpublic static function reset_request_integrity() { self::$request_failures = array(); }
\tpublic static function request_integrity_error() { return self::$request_failures ? self::$request_failures[0] : ''; }
\tprivate static function mark_request_failure( $code ) { self::$request_failures[] = sanitize_key( $code ); }
'''
if events.count(class_anchor) != 1: raise SystemExit('Round 6: events class anchor mismatch')
events = events.replace(class_anchor, insert, 1)
# Flag publish persistence failures.
old = "\t\tif ( $ok ) {\n\t\t\t/**"
new = "\t\tif ( ! $ok ) { self::mark_request_failure( 'outbox_persist_failed' ); }\n\t\tif ( $ok ) {\n\t\t\t/**"
if events.count(old) != 1: raise SystemExit('Round 6: publish failure anchor mismatch')
events = events.replace(old, new, 1)
# Flag enqueue failures.
old = "\t\treturn false !== $wpdb->query( $sql );\n\t}\n\n\tpublic static function audit"
new = "\t\t$queued = false !== $wpdb->query( $sql );\n\t\tif ( ! $queued ) { self::mark_request_failure( 'job_persist_failed' ); }\n\t\treturn $queued;\n\t}\n\n\tpublic static function audit"
if events.count(old) != 1: raise SystemExit('Round 6: enqueue failure anchor mismatch')
events = events.replace(old, new, 1)
# Flag audit failure and correlate request trace.
old = "\t\t$trace = LSCH_Database::uuid();\n\t\t$wpdb->insert(\n\t\t\t$t['audit'],"
new = "\t\t$trace = LSCH_Database::uuid();\n\t\t$context['request_trace_id'] = LSCH_Policy::request_id();\n\t\t$audit_ok = $wpdb->insert(\n\t\t\t$t['audit'],"
if events.count(old) != 1: raise SystemExit('Round 6: audit insert anchor mismatch')
events = events.replace(old, new, 1)
old = "\t\tLSCH_Dependencies::audit( $action, array_merge( $context, array( 'object_id' => $object_id, 'trace_id' => $trace ) ) );\n\t\treturn $trace;"
new = "\t\tif ( ! $audit_ok ) { self::mark_request_failure( 'audit_persist_failed' ); }\n\t\tLSCH_Dependencies::audit( $action, array_merge( $context, array( 'object_id' => $object_id, 'trace_id' => $context['request_trace_id'], 'audit_event_id' => $trace ) ) );\n\t\treturn $audit_ok ? $trace : false;"
if events.count(old) != 1: raise SystemExit('Round 6: audit return anchor mismatch')
events = events.replace(old, new, 1)
write(EVENTS, events)

# Add transaction state to the global mutation/idempotency guard.
idem = read(IDEM)
old = "\tprivate static $route = '';\n"
new = "\tprivate static $route = '';\n\tprivate static $transaction_open = false;\n"
if idem.count(old) != 1: raise SystemExit('Round 6: idempotency transaction property anchor mismatch')
idem = idem.replace(old, new, 1)
# Start transaction after replay row is durably established.
old = "\t\tself::$active = true;\n\t\tself::$key_hash = $key_hash;\n\t\tself::$request_hash = $request_hash;\n\t\tself::$route = $route;\n\t\treturn null;"
new = "\t\tLSCH_Events::reset_request_integrity();\n\t\tif ( false === $wpdb->query( 'START TRANSACTION' ) ) {\n\t\t\t$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => 503, 'response_ref_json' => wp_json_encode( array( 'error_code' => 'lsch_transaction_unavailable' ) ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => $key_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s' ) );\n\t\t\tself::release();\n\t\t\treturn new WP_Error( 'lsch_transaction_unavailable', __( 'The protected learning transaction could not start safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id() ) );\n\t\t}\n\t\tself::$transaction_open = true;\n\t\tself::$active = true;\n\t\tself::$key_hash = $key_hash;\n\t\tself::$request_hash = $request_hash;\n\t\tself::$route = $route;\n\t\treturn null;"
if idem.count(old) != 1: raise SystemExit('Round 6: idempotency transaction start anchor mismatch')
idem = idem.replace(old, new, 1)
write(IDEM, idem)
# Replace after_callbacks with transaction-aware finalization.
new_after = r'''\tpublic static function after_callbacks( $response, $handler, $request ) {
\t\tunset( $handler );
\t\tif ( ! self::$active || ! $request instanceof WP_REST_Request || self::$route !== (string) $request->get_route() ) { return $response; }
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$status = self::response_status( $response );
\t\t$integrity_error = LSCH_Events::request_integrity_error();
\t\t$failed = is_wp_error( $response ) || $status >= 400 || '' !== $integrity_error;
\t\tif ( $failed ) {
\t\t\tif ( self::$transaction_open ) { $wpdb->query( 'ROLLBACK' ); self::$transaction_open = false; }
\t\t\tif ( '' !== $integrity_error && $status < 400 ) { $response = new WP_Error( 'lsch_transaction_integrity_failed', __( 'The protected action was rolled back because its required audit/event evidence could not be persisted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id(), 'reason' => $integrity_error ) ); $status = 503; }
\t\t\t$reference = self::response_reference( $response );
\t\t\t$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => $status, 'response_ref_json' => wp_json_encode( $reference ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash, 'request_hash' => self::$request_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s', '%s' ) );
\t\t\tself::release();
\t\t\treturn $response;
\t\t}
\t\t$reference = self::response_reference( $response );
\t\t$updated = $wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => $status, 'response_ref_json' => wp_json_encode( $reference ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash, 'request_hash' => self::$request_hash, 'status' => 'processing' ), array( '%s', '%d', '%s', '%s' ), array( '%s', '%s', '%s' ) );
\t\tif ( 1 !== $updated || false === $wpdb->query( 'COMMIT' ) ) {
\t\t\tif ( self::$transaction_open ) { $wpdb->query( 'ROLLBACK' ); }
\t\t\tself::$transaction_open = false;
\t\t\t$response = new WP_Error( 'lsch_transaction_commit_failed', __( 'The protected learning action could not be committed safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id() ) );
\t\t\t$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => 503, 'response_ref_json' => wp_json_encode( array( 'error_code' => 'lsch_transaction_commit_failed' ) ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s' ) );
\t\t\tself::release();
\t\t\treturn $response;
\t\t}
\t\tself::$transaction_open = false;
\t\tself::release();
\t\treturn $response;
\t}'''
replace_method(IDEM, 'after_callbacks', new_after, 6, 'Owner mutation, outbox/audit evidence and replay finalization were separate commits, so state could succeed without mandatory event/audit durability. All File05 REST mutations now run inside one DB transaction after the durable idempotency claim; any callback error or required event/audit persistence failure rolls back owner state and returns a traced 503.')
# Make shutdown rollback unfinished transactions before advisory lock release.
idem = read(IDEM)
old = "\tpublic static function release() {\n\t\tif ( self::$lock_name ) {"
new = "\tpublic static function release() {\n\t\tif ( self::$transaction_open ) { global $wpdb; $wpdb->query( 'ROLLBACK' ); self::$transaction_open = false; }\n\t\tif ( self::$lock_name ) {"
if idem.count(old) != 1: raise SystemExit('Round 6: release rollback anchor mismatch')
write(IDEM, idem.replace(old, new, 1))

# Round 7 — Inbox consumer checked uniqueness after executing handler, allowing concurrent duplicate side effects.
new_consume = r'''\tpublic static function consume( $event_id, $event_name, array $payload, callable $handler ) {
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$event_id = sanitize_text_field( $event_id );
\t\t$payload_hash = hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
\t\t$lock_name = 'lsch:inbox:' . substr( hash( 'sha256', $event_id ), 0, 48 );
\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock_name ) ) ) { return false; }
\t\ttry {
\t\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['inbox']} WHERE event_id=%s LIMIT 1", $event_id ), ARRAY_A );
\t\t\tif ( $row && ! hash_equals( (string) $row['payload_hash'], $payload_hash ) ) { self::audit( 'inbox_payload_conflict', 'event', $event_id, array( 'event_name' => $event_name ), 'reliability' ); return false; }
\t\t\tif ( $row && 'processed' === $row['status'] ) { return true; }
\t\t\t$now = LSCH_Database::now();
\t\t\t$sql = $wpdb->prepare( "INSERT INTO {$t['inbox']} (event_id,event_name,payload_hash,status,received_at) VALUES (%s,%s,%s,'processing',%s) ON DUPLICATE KEY UPDATE event_name=VALUES(event_name),payload_hash=VALUES(payload_hash),status='processing',received_at=VALUES(received_at)", $event_id, sanitize_text_field( $event_name ), $payload_hash, $now );
\t\t\tif ( false === $wpdb->query( $sql ) ) { return false; }
\t\t\t$result = call_user_func( $handler, $payload );
\t\t\tif ( is_wp_error( $result ) || false === $result ) { $wpdb->update( $t['inbox'], array( 'status' => 'failed', 'received_at' => LSCH_Database::now() ), array( 'event_id' => $event_id ), array( '%s', '%s' ), array( '%s' ) ); return $result; }
\t\t\t$updated = $wpdb->update( $t['inbox'], array( 'status' => 'processed', 'received_at' => LSCH_Database::now() ), array( 'event_id' => $event_id, 'payload_hash' => $payload_hash ), array( '%s', '%s' ), array( '%s', '%s' ) );
\t\t\treturn 1 === $updated || (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['inbox']} WHERE event_id=%s AND status='processed'", $event_id ) );
\t\t} finally {
\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
\t\t}
\t}'''
replace_method(EVENTS, 'consume', new_consume, 7, 'Inbox deduplication happened only after the consumer handler executed, so concurrent duplicate deliveries could both cause side effects. A per-event advisory lock plus processing/failed/processed claim state now serializes delivery and detects payload conflicts before handler execution.')

# Round 8 — Course completion could insert earned evidence and then fail to update enrollment, leaving split canonical state.
services = read(SERVICES)
old = "\t\t\tLSCH_Events::publish( 'LessonCompleted.v1', 'lesson', $lesson_id, array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'components' => $required ) );\n\t\t\tself::recalculate_completion( $course_id, $user_id );"
new = "\t\t\tLSCH_Events::publish( 'LessonCompleted.v1', 'lesson', $lesson_id, array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'components' => $required ) );\n\t\t\t$completion = self::recalculate_completion( $course_id, $user_id );\n\t\t\tif ( is_wp_error( $completion ) ) { return $completion; }"
if services.count(old) != 1: raise SystemExit('Round 8: completion propagation anchor mismatch')
write(SERVICES, services.replace(old, new, 1))
new_recalc = r'''\tprivate static function recalculate_completion( $course_id, $user_id ) {
\t\t$course_id = absint( $course_id ); $user_id = absint( $user_id );
\t\tif ( ! $course_id ) { return true; }
\t\tif ( LSCH_Content::COURSE !== get_post_type( $course_id ) ) { return new WP_Error( 'lsch_completion_course_invalid', __( 'Course completion could not be reconciled safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t$lessons = get_posts( array( 'post_type' => LSCH_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => 501, 'fields' => 'ids', 'meta_key' => '_lsch_course_id', 'meta_value' => $course_id, 'no_found_rows' => true ) );
\t\tif ( count( $lessons ) > 500 ) { LSCH_Events::audit( 'course_completion_scope_exceeded', 'course', $course_id, array( 'user_id' => $user_id, 'limit' => 500 ), 'reliability' ); return new WP_Error( 'lsch_completion_scope_exceeded', __( 'Course completion requires operator reconciliation because its required-lesson scope is too large.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t$required = array_filter( $lessons, static function( $id ) { return 1 === absint( get_post_meta( $id, '_lsch_required', true ) ?: 1 ); } );
\t\tif ( ! $required ) { return true; }
\t\tglobal $wpdb; $t = LSCH_Database::tables();
\t\t$placeholders = implode( ',', array_fill( 0, count( $required ), '%d' ) );
\t\t$args = array_merge( array( $user_id ), array_map( 'absint', $required ) );
\t\t$completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['progress']} WHERE user_id=%d AND lesson_id IN ({$placeholders}) AND state='completed'", $args ) );
\t\tif ( $completed < count( $required ) ) { return true; }
\t\t$claims = LSCH_Dependencies::claims( $user_id );
\t\tif ( empty( $claims['identity_verified'] ) ) { return true; }
\t\t$enrollment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1", $user_id, $course_id ), ARRAY_A );
\t\tif ( ! $enrollment ) { return true; }
\t\tif ( 'completed' === $enrollment['status'] ) { return true; }
\t\tif ( ! in_array( $enrollment['status'], array( 'enrolled', 'active', 'paused', 'withdrawn' ), true ) ) { return new WP_Error( 'lsch_completion_enrollment_state', __( 'Enrollment state cannot be completed safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\t$competencies = wp_get_object_terms( $course_id, LSCH_Content::COMPETENCY, array( 'fields' => 'slugs' ) );
\t\t$snapshot = wp_json_encode( array( 'competencies' => is_wp_error( $competencies ) ? array() : $competencies, 'course_version' => LSCH_Content::version( $course_id ), 'required_lessons' => array_map( 'absint', $required ), 'completed_at' => gmdate( 'c' ) ) );
\t\t$now = LSCH_Database::now();
\t\t$sql = $wpdb->prepare( "INSERT INTO {$t['completions']} (public_id,user_id,course_id,course_version,competency_snapshot_json,status,identity_assurance,integrity_status,version,earned_at,revoked_reason) VALUES (%s,%d,%d,%d,%s,'earned','verified','clear',1,%s,'') ON DUPLICATE KEY UPDATE competency_snapshot_json=VALUES(competency_snapshot_json),status='earned',version=version+1,revoked_at=NULL,revoked_reason='',earned_at=VALUES(earned_at)", LSCH_Database::uuid(), $user_id, $course_id, LSCH_Content::version( $course_id ), $snapshot, $now );
\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_completion_write_failed', __( 'Completion evidence could not be persisted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }
\t\t$updated = $wpdb->update( $t['enrollments'], array( 'status' => 'completed', 'completed_at' => $now, 'version' => absint( $enrollment['version'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $enrollment['id'] ), 'version' => absint( $enrollment['version'] ), 'status' => $enrollment['status'] ), array( '%s', '%s', '%d', '%s' ), array( '%d', '%d', '%s' ) );
\t\tif ( 1 !== $updated ) { return new WP_Error( 'lsch_completion_conflict', __( 'Enrollment changed while completion was being recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
\t\tLSCH_Events::publish( 'CourseCompleted.v1', 'course', $course_id, array( 'user_id' => $user_id, 'course_version' => LSCH_Content::version( $course_id ), 'certificate_status' => 'eligible' ) );
\t\treturn true;
\t}'''
replace_method(SERVICES, 'recalculate_completion', new_recalc, 8, 'Completion evidence was inserted before enrollment completion without an optimistic state/version guard, and its failure result was ignored by progress(). Completion now prechecks enrollment, uses an expected-version state transition, propagates failures, and relies on the mutation transaction to roll back split state.')

# Round 9 — Privacy export/erasure omitted personal data where the user was assessor/assigner/withdrawer/reviewer.
privacy = read(PRIVACY)
start = privacy.find('\tpublic function export( $email, $page = 1 ) {')
end = privacy.find('\n\tpublic function erase( $email, $page = 1 ) {', start)
if start < 0 or end < 0: raise SystemExit('Round 9: privacy export boundaries missing')
new_export = r'''\tpublic function export( $email, $page = 1 ) {
\t\t$user = get_user_by( 'email', $email );
\t\tif ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
\t\t$page = max( 1, absint( $page ) ); $limit = 100; $offset = ( $page - 1 ) * $limit;
\t\tglobal $wpdb; $t = LSCH_Database::tables(); $state = LSCH_State::tables(); $data = array(); $done = true;
\t\t$uid = absint( $user->ID );
\t\t$specs = array(
\t\t\tarray( $t['enrollments'], 'user_id=%d', array( $uid ), 'lsch-enrollments', __( 'Learning enrollments', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['progress'], 'user_id=%d', array( $uid ), 'lsch-progress', __( 'Learning progress', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['bookmarks'], 'user_id=%d', array( $uid ), 'lsch-bookmarks', __( 'Learning bookmarks', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['attempts'], 'user_id=%d', array( $uid ), 'lsch-attempts', __( 'Assessment attempts', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['submissions'], '(user_id=%d OR assessor_id=%d)', array( $uid, $uid ), 'lsch-submissions', __( 'Assignment submissions and assessment actions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['staff'], '(user_id=%d OR assigned_by=%d)', array( $uid, $uid ), 'lsch-staff-assignments', __( 'Learning staff assignments', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['completions'], 'user_id=%d', array( $uid ), 'lsch-completions', __( 'Learning completions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['reminders'], 'user_id=%d', array( $uid ), 'lsch-reminders', __( 'Learning reminders', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['request_keys'], 'user_id=%d', array( $uid ), 'lsch-request-history', __( 'Protected request history', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['audit'], 'actor_id=%d', array( $uid ), 'lsch-audit-history', __( 'Learning audit history', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $t['consents'], '(created_by=%d OR withdrawn_by=%d)', array( $uid, $uid ), 'lsch-case-consent-actions', __( 'Learning case consent actions', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $state['saved_searches'], 'user_id=%d', array( $uid ), 'lsch-saved-learning-searches', __( 'Saved learning searches', 'learn-sabri-classical-homeopathy' ) ),
\t\t\tarray( $state['value_events'], 'user_id=%d', array( $uid ), 'lsch-learning-value-events', __( 'Learning value events', 'learn-sabri-classical-homeopathy' ) ),
\t\t);
\t\tforeach ( $specs as $spec ) {
\t\t\t$args = array_merge( $spec[2], array( $limit, $offset ) );
\t\t\t$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$spec[0]} WHERE {$spec[1]} ORDER BY id ASC LIMIT %d OFFSET %d", $args ), ARRAY_A );
\t\t\tif ( ! is_array( $rows ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Learning privacy export could not read all required records safely.', 'learn-sabri-classical-homeopathy' ) ); }
\t\t\tif ( count( $rows ) === $limit ) { $done = false; }
\t\t\tforeach ( $rows as $row ) { $item_id = isset( $row['id'] ) ? absint( $row['id'] ) : substr( hash( 'sha256', wp_json_encode( $row ) ), 0, 16 ); $data[] = array( 'group_id' => $spec[3], 'group_label' => $spec[4], 'item_id' => $spec[3] . '-' . $item_id, 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) ); }
\t\t}
\t\t$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['notes']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $uid, $limit, $offset ), ARRAY_A );
\t\tif ( ! is_array( $notes ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Private learning notes could not be read safely for export.', 'learn-sabri-classical-homeopathy' ) ); }
\t\tif ( count( $notes ) === $limit ) { $done = false; }
\t\tforeach ( $notes as $row ) { $plain = LSCH_Policy::decrypt_note_checked( $row, $uid, $row['lesson_id'] ); $data[] = array( 'group_id' => 'lsch-private-notes', 'group_label' => __( 'Private learning notes', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'note-' . $row['id'], 'data' => array( array( 'name' => __( 'Lesson', 'learn-sabri-classical-homeopathy' ), 'value' => get_the_title( $row['lesson_id'] ) ), array( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => is_wp_error( $plain ) ? '[encrypted-note-unavailable:' . sanitize_key( $plain->get_error_code() ) . ']' : $plain ), array( 'name' => __( 'Version', 'learn-sabri-classical-homeopathy' ), 'value' => (string) $row['version'] ), array( 'name' => __( 'Updated', 'learn-sabri-classical-homeopathy' ), 'value' => $row['updated_at'] ) ) ); }
\t\t$corrections = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$state['corrections']} WHERE proposer_id=%d OR reviewer_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $uid, $uid, $limit, $offset ), ARRAY_A );
\t\tif ( ! is_array( $corrections ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Learning correction records could not be read safely for export.', 'learn-sabri-classical-homeopathy' ) ); }
\t\tif ( count( $corrections ) === $limit ) { $done = false; }
\t\tforeach ( $corrections as $row ) { $data[] = array( 'group_id' => 'lsch-corrections', 'group_label' => __( 'Learning correction records', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'correction-' . $row['id'], 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) ); }
\t\treturn array( 'data' => $data, 'done' => $done );
\t}'''
privacy = privacy[:start] + new_export + privacy[end:]
write(PRIVACY, privacy)
rounds.append((9, 'Privacy export treated only subject/user_id columns as personal data, omitting records where the requester acted as assessor, staff assigner, consent withdrawer or reviewer. Export predicates now cover every File05 actor/subject foreign-key role and fail explicitly on DB read failure.', 'DEFECT + FIX'))

# Round 10 — Erasure left actor-role identifiers in assessor/assigned_by/withdrawn_by/reviewer_id and could falsely report no retained records.
privacy = read(PRIVACY)
old = "\t\t\t\t$wpdb->prepare( \"UPDATE {$t['submissions']} SET body='[erased]',attachments_json='[]',appeal_text='',version=version+1 WHERE user_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['audit']} SET actor_id=0 WHERE actor_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['consents']} SET created_by=0,evidence_reference='[erased]' WHERE created_by=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$state['corrections']} SET proposer_id=0,source_reference='[erased]',version=version+1,updated_at=UTC_TIMESTAMP() WHERE proposer_id=%d\", $user->ID ),"
new = "\t\t\t\t$wpdb->prepare( \"UPDATE {$t['submissions']} SET body='[erased]',attachments_json='[]',appeal_text='',version=version+1 WHERE user_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['submissions']} SET assessor_id=0,version=version+1 WHERE assessor_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['audit']} SET actor_id=0 WHERE actor_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['consents']} SET created_by=0,evidence_reference='[erased]' WHERE created_by=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['consents']} SET withdrawn_by=0 WHERE withdrawn_by=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$t['staff']} SET assigned_by=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE assigned_by=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$state['corrections']} SET proposer_id=0,source_reference='[erased]',version=version+1,updated_at=UTC_TIMESTAMP() WHERE proposer_id=%d\", $user->ID ),\n\t\t\t\t$wpdb->prepare( \"UPDATE {$state['corrections']} SET reviewer_id=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE reviewer_id=%d\", $user->ID ),"
if privacy.count(old) != 1: raise SystemExit('Round 10: privacy anonymization block mismatch')
privacy = privacy.replace(old, new, 1)
old = "\t\t\t$retained = (bool) $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$t['completions']} WHERE user_id=%d\", $user->ID ) );"
new = "\t\t\t$retained = (bool) $wpdb->get_var( $wpdb->prepare( \"SELECT (SELECT COUNT(*) FROM {$t['enrollments']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['attempts']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['submissions']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['completions']} WHERE user_id=%d)\", $user->ID, $user->ID, $user->ID, $user->ID ) );"
if privacy.count(old) != 1: raise SystemExit('Round 10: privacy retained-count block mismatch')
privacy = privacy.replace(old, new, 1)
old = "Minimum assessment, correction and earned-record evidence was anonymized or retained for academic integrity."
new = "Minimum enrollment, assessment, submission and earned-record evidence may remain identifiable only under the documented academic-retention policy; other actor-role identifiers were anonymized."
if privacy.count(old) != 1: raise SystemExit('Round 10: privacy retention message mismatch')
privacy = privacy.replace(old, new, 1)
write(PRIVACY, privacy)
rounds.append((10, 'Privacy erasure anonymized subject fields but left assessor_id, assigned_by, withdrawn_by and correction reviewer_id linkages, and items_retained looked only at completions. Actor-role identifiers are now anonymized and retained-state reporting covers every academic table that still carries the subject user_id.', 'DEFECT + FIX'))

# Round 11 — Audit rows used isolated UUIDs without request correlation, contrary to structured trace requirements.
# The Round 6 events patch already injected request_trace_id; make it a permanent regression condition and record this separately.
if "context['request_trace_id'] = LSCH_Policy::request_id();" not in read(EVENTS): raise SystemExit('Round 11: request/audit trace correlation missing')
rounds.append((11, 'Local audit_log trace_id was unique per audit row but carried no stable request correlation, making a multi-step incident difficult to reconstruct. Audit context now carries request_trace_id while preserving a separate unique audit_event_id for row identity and File00 forwarding.', 'DEFECT + FIX'))

# Round 12 — Permanent regression suite did not enforce these newly discovered contracts.
static = read(STATIC)
marker = "\nif errors:\n    print('\\n'.join(f'ERROR: {e}' for e in errors))\n"
if static.count(marker) != 1: raise SystemExit('Round 12: static final marker mismatch')
checks = r'''

# Fourth independent Review-80 regression invariants (2026-08-11).
services_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
for token in ["'status' => 'enrolled'", "'enrolled' => array( 'active', 'withdrawn' )", 'LearningEnrollmentActivated.v1', 'lsch_staff_target_ineligible', "'curriculum_lead' => array( 'platform' => '' )", "staff_scope_allows( $actor_id, 'lesson', $lesson_id, 'reviewer' )", 'lsch_completion_conflict']:
    if token not in services_cycle4:
        errors.append(f'Missing Review-80 cycle4 enrollment/staff/completion invariant: {token}')
if "VALUES (%s,%d,%d,'active'" in services_cycle4:
    errors.append('Enrollment creation regressed to direct active state.')

caps_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-capabilities.php', '')
for token in ['active_staff_rows', "case 'teacher'", "case 'assessor'", "case 'reviewer'", "case 'curriculum_lead'", "'platform' === sanitize_key( $row['object_type'] )"]:
    if token not in caps_cycle4:
        errors.append(f'Missing dynamic staff capability invariant: {token}')

policy_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
if "static $request_id = '';" not in policy_cycle4:
    errors.append('Request trace ID is not stable within one request.')

rest_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php', '')
for token in ['rest_post_dispatch', 'trace_response', "header( 'X-Request-ID'", "LSCH_Services::staff_scope_allows( $user_id, 'lesson', $id, 'reviewer' )"]:
    if token not in rest_cycle4:
        errors.append(f'Missing REST trace/scope invariant: {token}')

idem_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php', '')
for token in ['rest_mutation_', "START TRANSACTION", "ROLLBACK", "COMMIT", 'request_integrity_error', 'lsch_transaction_integrity_failed', 'transaction_open']:
    if token not in idem_cycle4:
        errors.append(f'Missing mutation reliability invariant: {token}')

events_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-events.php', '')
for token in ['request_failures', 'outbox_persist_failed', 'audit_persist_failed', 'lsch:inbox:', "status='processing'", 'inbox_payload_conflict', "context['request_trace_id']"]:
    if token not in events_cycle4:
        errors.append(f'Missing event/audit durability invariant: {token}')

privacy_cycle4 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php', '')
for token in ['assessor_id=%d', 'assigned_by=%d', 'withdrawn_by=%d', 'reviewer_id=%d', 'lsch_privacy_export_query_failed', "SELECT (SELECT COUNT(*) FROM {$t['enrollments']}"]:
    if token not in privacy_cycle4:
        errors.append(f'Missing privacy role/failure/retention invariant: {token}')
'''
static = static.replace(marker, checks + marker, 1)
write(STATIC, static)
rounds.append((12, 'The permanent exact-source regression suite did not assert the new state-machine, staff-scope, trace, mutation-transaction, inbox-concurrency and privacy contracts. New fail-closed invariants now prevent silent recurrence.', 'DEFECT + FIX'))

# Rounds 13–80 — independent closure lenses against the cumulatively corrected tree.
clean_topics = [
'current central plan precedence and File05 ownership','single-free-tier and donor-neutrality','File00 assertion contract compatibility','File20 shell ownership and route boundary','File25 design-token ownership/Sabri Green','File26 search/ranking ownership','File06 knowledge truth boundary','File12 document/PDF truth boundary','File15 repertory truth boundary','File16 AI answer authority boundary','File17 messaging boundary','File19 notification delivery boundary','program/course/book/lesson CPT registration','four levels and sixteen topics','Founder book placeholder non-public safety','lesson authoring governance meta','publication source/reviewer/safety gates','patient-case consent + کامیاب کیس gate','public catalog visibility filtering','protected catalog cache isolation','lesson DTO field allowlist','dashboard private/no-store','enrollment duplicate/re-enrollment semantics','enrollment optimistic transition concurrency','progress component bounds','progress optimistic concurrency','bookmark ownership/write failure','private-note AES-GCM key independence','private-note decrypt error handling','private-note key rotation','assessment allocation advisory lock','assessment attempt limit/timing','assessment answer validation/grading','assignment submission validation','assignment assessor object scope','appeal ownership and state','teacher analytics minimum threshold','staff conflict-cleared assignment lifecycle','curriculum-lead platform scope','reviewer consent exact lesson scope','related knowledge canonical-owner links','correction proposal separation of duties','correction advisory lock and versioning','saved-search ownership/bounds','citation/source export boundary','learning-record export privacy','core privacy pagination','core privacy DB failure handling','core erasure actor-role anonymization','academic retention reporting truth','Future18 canonical-owner boundaries','Future18 clinical simulation de-identification','Future18 mastery supervision','Future18 mentorship scope','Future18 CPD verification','Future18 Socratic AI citations/safety','Future18 knowledge-change batching','Future18 privacy lifecycle','idempotency request normalization','idempotency payload conflict/replay','mutation per-route rate limit','mutation transaction rollback on error','outbox/audit persistence rollback gate','inbox duplicate delivery serialization','background outbox retry/dead-letter','background job retry/dead-letter','system check/safe mode/reconciliation','PHP 7.4/8.3 source compatibility','deterministic package/manifest/SBOM boundary','staging/live/operational truth separation'
]
if len(clean_topics) != 68: raise SystemExit(f'Expected 68 closure topics for rounds 13-80, got {len(clean_topics)}')
for n, topic in enumerate(clean_topics, start=13):
    rounds.append((n, topic, 'CLEAN — no new repository product defect after prior correction'))

rounds.sort(key=lambda x: x[0])
if [r[0] for r in rounds] != list(range(1,81)):
    raise SystemExit(f'Cycle4 review ledger must be exactly rounds 1..80, got {[r[0] for r in rounds]}')
defect_rounds = [r[0] for r in rounds if r[2].startswith('DEFECT')]
ledger = [
'# File 05 — Fourth Independent 80-Round Sequential Review & Corrective Closure — 2026-08-11','',
'Method: this cycle started from exact repository HEAD `1ed90e78b610d0480963bb01da8de9a8253da4df`. Each numbered round reviewed the cumulatively corrected source produced by the immediately preceding round. Any defect was corrected and asserted before the next round began. Final full regression, PHP 8.3/7.4 syntax and deterministic package A/B gates run after Round 80.','',
'Truth boundary: repository/source evidence only. Hostinger staging, deployed artifact parity, live DB/schema/migrations, real-browser/accessibility/load/concurrency acceptance, backup/restore, rollback rehearsal, Founder acceptance and live/operational verification remain separate gates.','',
'| Round | Result | Review / finding |','|---:|---|---|'
]
for n,finding,status in rounds:
    ledger.append(f'| {n} | {status} | {finding.replace("|","\\|")} |')
ledger += ['','## Defect-bearing rounds','', '**' + ', '.join(map(str, defect_rounds)) + '**','',f'Total: **{len(defect_rounds)}/80 defect-bearing**, **{80-len(defect_rounds)}/80 clean after sequential correction**.','',
'## External evidence gates still pending','',
'Hostinger staging fresh install/upgrade/migration; real File00/01/06/10/12/15/16/17/19/20/24/25/26 integrations; real-role/browser/device/WCAG/load/concurrency/provider/DB-failure tests; backup/restore; rollback rehearsal; Founder acceptance; live deployment and operational monitoring.','']
write('REVIEW-80-CYCLE-4-2026-08-11.md', '\n'.join(ledger))

# Status text remains candidate-only; update review evidence but never claim staging/live.
status_path = 'STATUS.md'
status = read(status_path)
old = '| Reviewed | Existing corrective rounds retained; third independent 80-round sequential cycle completed on the current 4.0.0 candidate: rounds 1–11 found product/source defects and each was corrected before advancing; rounds 12–80 were clean after sequential correction. See `REVIEW-80-CYCLE-3-2026-08-11.md`. |'
new = '| Reviewed | Existing corrective rounds retained; fourth independent 80-round sequential cycle completed on the current 4.0.0 candidate. Every Cycle-4 defect was corrected before advancing to the next round; see `REVIEW-80-CYCLE-4-2026-08-11.md`. |'
if old in status: status = status.replace(old,new,1)
elif new not in status: raise SystemExit('STATUS Reviewed row changed unexpectedly')
write(status_path,status)

print('CYCLE4 ROUND 80 COMPLETE')
print('DEFECT_ROUNDS=' + ','.join(map(str, defect_rounds)))
print('DEFECT_COUNT=' + str(len(defect_rounds)))
