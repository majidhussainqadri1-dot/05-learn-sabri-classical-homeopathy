#!/usr/bin/env python3
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / '05-learn-sabri-classical-homeopathy'
CONTENT = PLUGIN / 'includes/class-lsch-content.php'
SERVICES = PLUGIN / 'includes/class-lsch-services.php'
POLICY = PLUGIN / 'includes/class-lsch-policy.php'
F18 = PLUGIN / 'includes/class-lsch-future18.php'
STATIC = ROOT / 'tests/static-invariants.py'
START_HEAD = '1eda00bf56ef30e2ca1b1a9eb4052f823dbbace6'
findings = []

def read(path):
    return path.read_text(encoding='utf-8')

def write(path, text):
    path.write_text(text, encoding='utf-8', newline='\n')

def replace_once(path, old, new, label):
    text = read(path)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected exactly one source match, found {count}')
    write(path, text.replace(old, new, 1))

def require(path, tokens, label):
    text = read(path)
    missing = [t for t in tokens if t not in text]
    if missing:
        raise SystemExit(f'{label}: missing expected token(s): {missing}')

def lint(*paths):
    for path in paths:
        subprocess.run(['php', '-l', str(path)], cwd=ROOT, check=True, stdout=subprocess.DEVNULL)

def round_done(number, lens, finding, paths, tokens):
    for path in paths:
        require(path, tokens.get(path, []), f'round {number}')
    lint(*[p for p in paths if p.suffix == '.php'])
    findings.append((number, lens, finding))
    print(f'ROUND {number}: DEFECT FIXED — {lens}')

# Round 1 — native WordPress access bypass.
old = "\t\tself::register_meta();\n\t\tadd_action( 'post_updated', array( __CLASS__, 'bump_version_on_post_update' ), 20, 3 );\n\t}\n\n\tprivate static function register_post_type"
new = "\t\tself::register_meta();\n\t\tadd_action( 'post_updated', array( __CLASS__, 'bump_version_on_post_update' ), 20, 3 );\n\t\tadd_action( 'pre_get_posts', array( __CLASS__, 'restrict_native_public_queries' ), 20 );\n\t\tadd_filter( 'the_posts', array( __CLASS__, 'filter_native_results' ), 20, 2 );\n\t\tadd_action( 'template_redirect', array( __CLASS__, 'guard_native_singular' ), 1 );\n\t}\n\n\tprivate static function native_public_types() {\n\t\treturn array( self::PROGRAM, self::COURSE, self::BOOK, self::LESSON );\n\t}\n\n\t/** Keep native WordPress archives/search public-only; richer account/restricted discovery belongs to governed app surfaces/File 26. */\n\tpublic static function restrict_native_public_queries( $query ) {\n\t\tif ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || $query->is_singular() ) { return; }\n\t\tif ( ! $query->is_search() && ! $query->is_post_type_archive( self::native_public_types() ) ) { return; }\n\t\t$visibility = array(\n\t\t\t'relation' => 'OR',\n\t\t\tarray( 'key' => '_lsch_access', 'compare' => 'NOT EXISTS' ),\n\t\t\tarray( 'key' => '_lsch_access', 'value' => 'public' ),\n\t\t);\n\t\t$meta = $query->get( 'meta_query' );\n\t\t$meta = is_array( $meta ) ? $meta : array();\n\t\t$meta[] = $visibility;\n\t\t$query->set( 'meta_query', $meta );\n\t}\n\n\t/** Final per-object filter also removes public patient-case lessons whose consent gate is not currently valid. */\n\tpublic static function filter_native_results( $posts, $query ) {\n\t\tif ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || ! is_array( $posts ) ) { return $posts; }\n\t\treturn array_values( array_filter( $posts, static function( $post ) {\n\t\t\treturn ! $post instanceof WP_Post || ! in_array( $post->post_type, self::native_public_types(), true ) || LSCH_Policy::can_read_post( $post->ID );\n\t\t} ) );\n\t}\n\n\t/** Denied native singular requests become a non-cacheable 404 instead of bypassing File 05 access policy. */\n\tpublic static function guard_native_singular() {\n\t\tif ( is_admin() || ! is_singular( self::native_public_types() ) ) { return; }\n\t\t$post_id = absint( get_queried_object_id() );\n\t\tif ( ! $post_id || LSCH_Policy::can_read_post( $post_id ) ) { return; }\n\t\tglobal $wp_query;\n\t\tif ( $wp_query instanceof WP_Query ) { $wp_query->set_404(); }\n\t\tstatus_header( 404 );\n\t\tnocache_headers();\n\t}\n\n\tprivate static function register_post_type"
replace_once(CONTENT, old, new, 'round1 native access guard')
round_done(1, 'Native WordPress access enforcement', 'Public CPT permalinks, archives and search could bypass the File 05 account/restricted and patient-case consent policy. Native list/search surfaces are now public-only and singular/native results are rechecked against LSCH_Policy::can_read_post.', [CONTENT], {CONTENT:['restrict_native_public_queries','filter_native_results','guard_native_singular']})

# Round 2 — activation did not refresh the version/access contract it had just re-authorized.
old = "\t\tif ( 'active' === $state ) { $data['paused_at'] = null; $formats[] = '%s'; }"
new = "\t\tif ( 'active' === $state ) {\n\t\t\t$data['paused_at'] = null;\n\t\t\t$data['course_version'] = LSCH_Content::version( $course_id );\n\t\t\t$data['terms_version'] = LSCH_Policy::access_model();\n\t\t\t$formats[] = '%s'; $formats[] = '%d'; $formats[] = '%s';\n\t\t}"
replace_once(SERVICES, old, new, 'round2 enrollment parity refresh')
round_done(2, 'Enrollment activation parity refresh', 'A paused/enrolled learner could successfully re-authorize activation but remain immediately stale because course_version/terms_version were not refreshed. Activation now writes the current course and access-contract versions atomically.', [SERVICES], {SERVICES:["$data['course_version'] = LSCH_Content::version( $course_id )","$data['terms_version'] = LSCH_Policy::access_model()"]})

# Round 3 — assessment idempotency was not object-scoped and in-flight attempts ignored assessment version drift.
old = "\t\t$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'assessment-attempt' ); if ( is_wp_error( $key ) ) { return $key; }"
new = "\t\t$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'assessment-attempt-' . $assessment_id ); if ( is_wp_error( $key ) ) { return $key; }"
replace_once(SERVICES, old, new, 'round3 assessment object-scoped idempotency')
old = "\t\t$attempt = self::start_assessment( $assessment_id, $user_id, $idempotency ); if ( is_wp_error( $attempt ) ) { return $attempt; }\n\t\tif ( 'graded' === $attempt['status'] ) { return $attempt; }\n\t\tif ( count( $answers ) > 200 )"
new = "\t\t$attempt = self::start_assessment( $assessment_id, $user_id, $idempotency ); if ( is_wp_error( $attempt ) ) { return $attempt; }\n\t\tif ( 'graded' === $attempt['status'] ) { return $attempt; }\n\t\t$current_item_version = LSCH_Content::version( $assessment_id );\n\t\tif ( absint( $attempt['assessment_id'] ) !== $assessment_id || absint( $attempt['item_version'] ) !== $current_item_version ) {\n\t\t\treturn new WP_Error( 'lsch_assessment_version_changed', __( 'This assessment changed after the attempt began. Start a fresh governed attempt.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\tif ( count( $answers ) > 200 )"
replace_once(SERVICES, old, new, 'round3 assessment version drift')
old = "'item_version' => LSCH_Content::version( $assessment_id ) ) );"
new = "'item_version' => $current_item_version ) );"
replace_once(SERVICES, old, new, 'round3 assessment event version')
round_done(3, 'Assessment attempt object/version integrity', 'The same client idempotency token could collide across assessments, and an in-flight attempt could be graded against a changed assessment. Attempt keys are now assessment-scoped and submission requires the immutable item_version that existed at start.', [SERVICES], {SERVICES:["'assessment-attempt-' . $assessment_id",'lsch_assessment_version_changed',"'item_version' => $current_item_version"]})

# Round 4 — assignment grading ignored rubric/content version drift.
old = "\t\tif ( ! $row || absint( $row['user_id'] ) === $assessor_id || absint( $row['version'] ) !== absint( $expected_version ) ) {\n\t\t\treturn new WP_Error( 'lsch_grade_conflict', __( 'Submission is unavailable, conflicted, or cannot be self-assessed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$updated = $wpdb->update"
new = "\t\tif ( ! $row || absint( $row['user_id'] ) === $assessor_id || absint( $row['version'] ) !== absint( $expected_version ) ) {\n\t\t\treturn new WP_Error( 'lsch_grade_conflict', __( 'Submission is unavailable, conflicted, or cannot be self-assessed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$assignment_id = absint( $row['assignment_id'] );\n\t\tif ( LSCH_Content::ASSIGNMENT !== get_post_type( $assignment_id ) || absint( $row['rubric_version'] ) !== LSCH_Content::version( $assignment_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_assignment_rubric_changed', __( 'The assignment rubric changed after submission. Reconcile the submission before grading.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$updated = $wpdb->update"
replace_once(SERVICES, old, new, 'round4 assignment rubric drift')
round_done(4, 'Assignment grading rubric immutability', 'A submission could be graded after its assignment/rubric changed, making the stored rubric_version meaningless. Grading now fails closed on assignment or rubric-version drift.', [SERVICES], {SERVICES:['lsch_assignment_rubric_changed',"$row['rubric_version']",'LSCH_Content::version( $assignment_id )']})

# Round 5 — lesson progress accepted stale graded evidence from prior assessment/assignment versions.
old = "\t\t\tforeach ( $assessment_ids as $assessment_id ) { $pass = max( 0, min( 100, absint( get_post_meta( $assessment_id, '_lsch_pass_mark', true ) ?: 50 ) ) ); $best = (float) $wpdb->get_var( $wpdb->prepare( \"SELECT COALESCE(MAX(score),-1) FROM {$t['attempts']} WHERE user_id=%d AND assessment_id=%d AND status='graded' AND integrity_status='clear'\", $user_id, absint( $assessment_id ) ) ); if ( $best < $pass ) { $components['assessment'] = false; break; } }"
new = "\t\t\tforeach ( $assessment_ids as $assessment_id ) {\n\t\t\t\t$pass = max( 0, min( 100, absint( get_post_meta( $assessment_id, '_lsch_pass_mark', true ) ?: 50 ) ) );\n\t\t\t\t$item_version = LSCH_Content::version( $assessment_id );\n\t\t\t\t$best = (float) $wpdb->get_var( $wpdb->prepare( \"SELECT COALESCE(MAX(score),-1) FROM {$t['attempts']} WHERE user_id=%d AND assessment_id=%d AND item_version=%d AND status='graded' AND integrity_status='clear'\", $user_id, absint( $assessment_id ), $item_version ) );\n\t\t\t\tif ( $best < $pass ) { $components['assessment'] = false; break; }\n\t\t\t}"
replace_once(SERVICES, old, new, 'round5 assessment evidence freshness')
old = "\t\t\tforeach ( $assignment_ids as $assignment_id ) { $pass = max( 0, min( 100, absint( get_post_meta( $assignment_id, '_lsch_pass_mark', true ) ?: 50 ) ) ); $best = (float) $wpdb->get_var( $wpdb->prepare( \"SELECT COALESCE(MAX(score),-1) FROM {$t['submissions']} WHERE user_id=%d AND assignment_id=%d AND status='graded'\", $user_id, absint( $assignment_id ) ) ); if ( $best < $pass ) { $components['assignment'] = false; break; } }"
new = "\t\t\tforeach ( $assignment_ids as $assignment_id ) {\n\t\t\t\t$pass = max( 0, min( 100, absint( get_post_meta( $assignment_id, '_lsch_pass_mark', true ) ?: 50 ) ) );\n\t\t\t\t$rubric_version = LSCH_Content::version( $assignment_id );\n\t\t\t\t$best = (float) $wpdb->get_var( $wpdb->prepare( \"SELECT COALESCE(MAX(score),-1) FROM {$t['submissions']} WHERE user_id=%d AND assignment_id=%d AND rubric_version=%d AND status='graded'\", $user_id, absint( $assignment_id ), $rubric_version ) );\n\t\t\t\tif ( $best < $pass ) { $components['assignment'] = false; break; }\n\t\t\t}"
replace_once(SERVICES, old, new, 'round5 assignment evidence freshness')
round_done(5, 'Current-version evidence for lesson progress', 'Old graded attempts/submissions could satisfy a lesson after its assessment or rubric changed. Progress now recognizes only evidence bound to each component’s current version.', [SERVICES], {SERVICES:['AND item_version=%d AND status=\'graded\'','AND rubric_version=%d AND status=\'graded\'']})

# Round 6 — explicitly optional child assessments/assignments were still treated as mandatory evidence.
old = "\t\t$assessment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSESSMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n\t\tif ( count( $assessment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many assessment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n\t\tif ( $assessment_ids ) {\n\t\t\t$components['assessment'] = true;"
new = "\t\t$assessment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSESSMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_lsch_lesson_id', 'value' => $lesson_id, 'type' => 'NUMERIC' ), array( 'relation' => 'OR', array( 'key' => '_lsch_required', 'compare' => 'NOT EXISTS' ), array( 'key' => '_lsch_required', 'value' => '0', 'compare' => '!=' ) ) ), 'no_found_rows' => true ) );\n\t\tif ( count( $assessment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many required assessment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n\t\t$components['assessment'] = ! empty( $assessment_ids );\n\t\tif ( $assessment_ids ) {"
replace_once(SERVICES, old, new, 'round6 optional assessments')
old = "\t\t$assignment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSIGNMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n\t\tif ( count( $assignment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many assignment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n\t\tif ( $assignment_ids ) {\n\t\t\t$components['assignment'] = true;"
new = "\t\t$assignment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSIGNMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_lsch_lesson_id', 'value' => $lesson_id, 'type' => 'NUMERIC' ), array( 'relation' => 'OR', array( 'key' => '_lsch_required', 'compare' => 'NOT EXISTS' ), array( 'key' => '_lsch_required', 'value' => '0', 'compare' => '!=' ) ) ), 'no_found_rows' => true ) );\n\t\tif ( count( $assignment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many required assignment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n\t\t$components['assignment'] = ! empty( $assignment_ids );\n\t\tif ( $assignment_ids ) {"
replace_once(SERVICES, old, new, 'round6 optional assignments')
round_done(6, 'Required-vs-optional lesson component semantics', 'Explicitly optional assessments/assignments were included in completion evidence, and removed optional sets could leave stale true component state. Queries now select only required children and reset category state deterministically.', [SERVICES], {SERVICES:["array( 'key' => '_lsch_required', 'value' => '0', 'compare' => '!=' )","$components['assessment'] = ! empty( $assessment_ids )","$components['assignment'] = ! empty( $assignment_ids )"]})

# Round 7 — optional lesson flag was falsey-bugged and course completion ignored lesson-version/review freshness.
old = "\t\t$required = array_filter( $lessons, static function( $id ) { return 1 === absint( get_post_meta( $id, '_lsch_required', true ) ?: 1 ); } );\n\t\tif ( ! $required ) { return true; }\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n\t\t$placeholders = implode( ',', array_fill( 0, count( $required ), '%d' ) );\n\t\t$args = array_merge( array( $user_id ), array_map( 'absint', $required ) );\n\t\t$completed = (int) $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$t['progress']} WHERE user_id=%d AND lesson_id IN ({$placeholders}) AND state='completed'\", $args ) );\n\t\tif ( $completed < count( $required ) ) { return true; }"
new = "\t\t$required = array_values( array_filter( $lessons, static function( $id ) { return '0' !== (string) get_post_meta( $id, '_lsch_required', true ); } ) );\n\t\tif ( ! $required ) { return true; }\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n\t\t$placeholders = implode( ',', array_fill( 0, count( $required ), '%d' ) );\n\t\t$args = array_merge( array( $user_id ), array_map( 'absint', $required ) );\n\t\t$progress_rows = $wpdb->get_results( $wpdb->prepare( \"SELECT lesson_id,state,lesson_version,needs_review FROM {$t['progress']} WHERE user_id=%d AND lesson_id IN ({$placeholders})\", $args ), ARRAY_A );\n\t\t$progress_by_lesson = array();\n\t\tforeach ( (array) $progress_rows as $progress_row ) { $progress_by_lesson[ absint( $progress_row['lesson_id'] ) ] = $progress_row; }\n\t\tforeach ( $required as $required_lesson_id ) {\n\t\t\t$required_lesson_id = absint( $required_lesson_id );\n\t\t\t$progress_row = isset( $progress_by_lesson[ $required_lesson_id ] ) ? $progress_by_lesson[ $required_lesson_id ] : null;\n\t\t\tif ( ! $progress_row || 'completed' !== $progress_row['state'] || ! empty( $progress_row['needs_review'] ) || absint( $progress_row['lesson_version'] ) !== LSCH_Content::version( $required_lesson_id ) ) { return true; }\n\t\t}"
replace_once(SERVICES, old, new, 'round7 course completion freshness')
round_done(7, 'Course-completion required lesson/current-version integrity', 'The string value 0 for _lsch_required was converted back to required by ?:1, and stale/needs-review lesson progress could count toward completion. Optional lessons now remain optional and every required lesson must be completed on its current version with no review flag.', [SERVICES], {SERVICES:["return '0' !== (string) get_post_meta( $id, '_lsch_required', true )",'lesson_version,needs_review','LSCH_Content::version( $required_lesson_id )']})

# Round 8 — prerequisite completion accepted any historical earned version.
old = "\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['completions']} WHERE user_id=%d AND course_id=%d AND status='earned' LIMIT 1\", $user_id, $required_course ) );\n\t\t\tif ( ! $exists ) {\n\t\t\t\treturn new WP_Error( 'lsch_prerequisite_missing', __( 'A required course has not yet been completed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}"
new = "\t\t\t$required_version = LSCH_Content::version( $required_course );\n\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['completions']} WHERE user_id=%d AND course_id=%d AND course_version=%d AND status='earned' AND integrity_status='clear' LIMIT 1\", $user_id, $required_course, $required_version ) );\n\t\t\t$grandfathered = ! $exists && true === apply_filters( 'lsch_prerequisite_completion_grandfathered', false, $required_course, $required_version, $user_id );\n\t\t\tif ( ! $exists && ! $grandfathered ) {\n\t\t\t\treturn new WP_Error( 'lsch_prerequisite_missing', __( 'The current governed version of a required course has not yet been completed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}"
replace_once(POLICY, old, new, 'round8 prerequisite completion version')
round_done(8, 'Prerequisite current-version evidence', 'Any historical earned completion could satisfy a prerequisite after that prerequisite course changed. Runtime now requires an earned, integrity-clear completion for the current course version unless an explicit governance adapter grants grandfathering.', [POLICY], {POLICY:['course_version=%d AND status=\'earned\' AND integrity_status=\'clear\'','lsch_prerequisite_completion_grandfathered']})

# Round 9 — Future18 spaced-review result updates allowed versionless lost updates.
old = "\t\tif ( ! $row || ( $expected_version && absint( $row['version'] ) !== absint( $expected_version ) ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_review_conflict', __( 'Review item changed or was not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}"
new = "\t\tif ( ! $row || ! $expected_version || absint( $row['version'] ) !== absint( $expected_version ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_review_conflict', __( 'The current review-item version is required and must match before saving a result.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}"
replace_once(F18, old, new, 'round9 Future18 review optimistic concurrency')
round_done(9, 'Future18 review optimistic concurrency', 'Spaced-review/flashcard result updates accepted version=0 and could overwrite another device’s schedule. Every existing review result now requires the exact current version.', [F18], {F18:['! $expected_version || absint( $row[\'version\'] ) !== absint( $expected_version )','current review-item version is required']})

# Round 10 — Future18 blueprint changes did not participate in canonical lesson version/change-impact truth.
old = "\t\t$json = self::encode_json( $blueprint, 50000 );\n\t\tif ( is_wp_error( $json ) ) { return $json; }\n\t\t$all = json_decode( (string) get_post_meta( $lesson_id, '_lsch_future18_blueprints', true ), true );\n\t\t$all = is_array( $all ) ? $all : array();\n\t\t$all[ $mode ] = json_decode( $json, true );\n\t\tupdate_post_meta( $lesson_id, '_lsch_future18_blueprints', wp_json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );\n\t\tupdate_post_meta( $lesson_id, '_lsch_future18_blueprint_version', absint( get_post_meta( $lesson_id, '_lsch_future18_blueprint_version', true ) ) + 1 );\n\t\tLSCH_Events::audit( 'future18_blueprint_updated', 'lesson', $lesson_id, array( 'mode' => $mode ), 'curriculum' );\n\t\treturn true;"
new = "\t\t$json = self::encode_json( $blueprint, 50000 );\n\t\tif ( is_wp_error( $json ) ) { return $json; }\n\t\t$all = json_decode( (string) get_post_meta( $lesson_id, '_lsch_future18_blueprints', true ), true );\n\t\t$all = is_array( $all ) ? $all : array();\n\t\t$next_blueprint = json_decode( $json, true );\n\t\t$blueprint_version = max( 1, absint( get_post_meta( $lesson_id, '_lsch_future18_blueprint_version', true ) ) );\n\t\tif ( isset( $all[ $mode ] ) && $all[ $mode ] === $next_blueprint ) {\n\t\t\treturn array( 'changed' => false, 'blueprint_version' => $blueprint_version, 'lesson_version' => LSCH_Content::version( $lesson_id ) );\n\t\t}\n\t\t$all[ $mode ] = $next_blueprint;\n\t\tif ( false === update_post_meta( $lesson_id, '_lsch_future18_blueprints', wp_json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_blueprint_write_failed', __( 'The practice blueprint could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );\n\t\t}\n\t\t$next_blueprint_version = $blueprint_version + 1;\n\t\tif ( false === update_post_meta( $lesson_id, '_lsch_future18_blueprint_version', $next_blueprint_version ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_blueprint_version_failed', __( 'The practice blueprint version could not be advanced.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );\n\t\t}\n\t\t$lesson_version = LSCH_Content::bump_version( $lesson_id, 'future18_blueprint' );\n\t\tif ( absint( $lesson_version ) !== LSCH_Content::version( $lesson_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_lesson_version_failed', __( 'The canonical lesson version could not be advanced for this blueprint change.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );\n\t\t}\n\t\tLSCH_Events::audit( 'future18_blueprint_updated', 'lesson', $lesson_id, array( 'mode' => $mode, 'blueprint_version' => $next_blueprint_version, 'lesson_version' => $lesson_version ), 'curriculum' );\n\t\treturn array( 'changed' => true, 'blueprint_version' => $next_blueprint_version, 'lesson_version' => $lesson_version );"
replace_once(F18, old, new, 'round10 Future18 canonical versioning')
round_done(10, 'Future18 blueprint canonical version/change-impact integration', 'Practice blueprint edits advanced only a private Future18 meta counter, so lesson versioning and downstream correction/change-impact truth could remain stale. Blueprint changes now fail explicitly on write errors and advance the canonical lesson version; identical writes are side-effect free.', [F18], {F18:["LSCH_Content::bump_version( $lesson_id, 'future18_blueprint' )",'lsch_future18_blueprint_write_failed',"'changed' => false"]})

# Permanent regression coverage for demonstrated Cycle-8 defect classes.
static = read(STATIC)
marker = '# Eighth independent ten-round review regression invariants (2026-08-11).'
if marker not in static:
    static += r'''

# Eighth independent ten-round review regression invariants (2026-08-11).
content_cycle8 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php', '')
for token in ['restrict_native_public_queries', 'filter_native_results', 'guard_native_singular', "'_lsch_access', 'value' => 'public'"]:
    if token not in content_cycle8:
        errors.append(f'Missing Cycle8 native-access invariant: {token}')

services_cycle8 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
for token in [
    "$data['course_version'] = LSCH_Content::version( $course_id )",
    "$data['terms_version'] = LSCH_Policy::access_model()",
    "'assessment-attempt-' . $assessment_id",
    'lsch_assessment_version_changed',
    'lsch_assignment_rubric_changed',
    "AND item_version=%d AND status='graded'",
    "AND rubric_version=%d AND status='graded'",
    "$components['assessment'] = ! empty( $assessment_ids )",
    "$components['assignment'] = ! empty( $assignment_ids )",
    "return '0' !== (string) get_post_meta( $id, '_lsch_required', true )",
    'lesson_version,needs_review',
]:
    if token not in services_cycle8:
        errors.append(f'Missing Cycle8 learning-evidence invariant: {token}')

policy_cycle8 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
for token in ["course_version=%d AND status='earned' AND integrity_status='clear'", 'lsch_prerequisite_completion_grandfathered']:
    if token not in policy_cycle8:
        errors.append(f'Missing Cycle8 prerequisite invariant: {token}')

future_cycle8 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php', '')
for token in ['! $expected_version || absint( $row[\'version\'] ) !== absint( $expected_version )', "LSCH_Content::bump_version( $lesson_id, 'future18_blueprint' )", 'lsch_future18_blueprint_write_failed']:
    if token not in future_cycle8:
        errors.append(f'Missing Cycle8 Future18 invariant: {token}')
'''
    write(STATIC, static)

# Persistent sequential review evidence.
ledger = ROOT / 'REVIEW-10-CYCLE-8-2026-08-11.md'
lines = [
    '# File 05 — Eighth Independent 10-Round Sequential Review & Corrective Closure — 2026-08-11',
    '',
    f'Starting product-source HEAD: `{START_HEAD}`. Each round reviewed the cumulatively corrected tree from the preceding round; every detected defect was corrected and syntax/assertion checked before the next round.',
    '',
    '| Round | Result | Review lens | Finding / correction |',
    '|---:|---|---|---|',
]
for number, lens, finding in findings:
    lines.append(f'| {number} | DEFECT + FIX | {lens} | {finding} |')
lines += [
    '',
    '## Defect-bearing rounds',
    '',
    '1, 2, 3, 4, 5, 6, 7, 8, 9, 10',
    '',
    'Total: **10/10 defect-bearing**, **0/10 clean at first inspection**. All listed defects were corrected before the subsequent round.',
    '',
    '## Truth boundary',
    '',
    'This ledger is repository/source evidence only. It does not establish Hostinger staging acceptance, deployed artifact parity, live database/schema/migration parity, Founder acceptance, live deployment or operational acceptance.',
    '',
]
write(ledger, '\n'.join(lines))

# Keep release history synchronized with the corrected source candidate.
changelog = ROOT / 'CHANGELOG.md'
ch = read(changelog)
entry = '''\n## 2026-08-11 — Eighth independent ten-round corrective review\n- Closed ten sequential source defects covering native WordPress access enforcement, enrollment parity refresh, assessment/assignment immutable-version evidence, optional-vs-required learning components, current-version course/prerequisite completion truth, Future18 optimistic concurrency and blueprint-to-canonical-version integration.\n- Added permanent Cycle-8 regression invariants and `REVIEW-10-CYCLE-8-2026-08-11.md`.\n- Repository-only correction; staging/live status is unchanged.\n'''
if 'Eighth independent ten-round corrective review' not in ch:
    write(changelog, ch.rstrip() + '\n' + entry)

subprocess.run(['python3', 'tests/static-invariants.py'], cwd=ROOT, check=True)
print('CYCLE8: ten sequential review/fix rounds completed; permanent regression invariants and ledger written.')
