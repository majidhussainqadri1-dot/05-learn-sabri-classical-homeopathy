#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / '05-learn-sabri-classical-homeopathy' / 'includes'
START_PRODUCT_HEAD = 'dc774b09776c0dc45819843c5fe692c69976a58a'
rows = []
defects = []

def read(name):
    return (PLUGIN / name).read_text(encoding='utf-8')

def write(name, text):
    (PLUGIN / name).write_text(text, encoding='utf-8', newline='\n')

def replace_once(name, old, new):
    text = read(name)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{name}: expected exactly one patch target, found {count}: {old[:100]!r}')
    write(name, text.replace(old, new, 1))

def record(round_no, lens, finding, fixed):
    if fixed:
        defects.append(round_no)
        rows.append((round_no, 'DEFECT + FIX', lens, finding))
    else:
        rows.append((round_no, 'CLEAN', lens, finding))

def assert_has(name, token):
    if token not in read(name):
        raise SystemExit(f'{name}: post-fix assertion missing: {token}')

def assert_not(name, token):
    if token in read(name):
        raise SystemExit(f'{name}: forbidden post-fix token remains: {token}')

# Round 1 — restricted content authorization must not collapse into ordinary account access.
policy = read('class-lsch-policy.php')
old = """\t\t$access = LSCH_Content::access( $post_id );\n\t\tif ( 'public' === $access ) {\n\t\t\treturn true;\n\t\t}\n\t\treturn self::can_use_protected_reads( $user_id );\n"""
if old in policy:
    new = """\t\t$access = LSCH_Content::access( $post_id );\n\t\tif ( 'public' === $access ) {\n\t\t\treturn true;\n\t\t}\n\t\tif ( 'account' === $access ) {\n\t\t\treturn self::can_use_protected_reads( $user_id );\n\t\t}\n\t\tif ( 'restricted' !== $access || ! self::can_use_protected_reads( $user_id ) ) {\n\t\t\treturn false;\n\t\t}\n\t\tif ( user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) || user_can( $user_id, 'edit_post', $post_id ) ) {\n\t\t\treturn true;\n\t\t}\n\t\tif ( class_exists( 'LSCH_Services' ) ) {\n\t\t\t$type = get_post_type( $post_id );\n\t\t\t$scopes = array();\n\t\t\tif ( LSCH_Content::COURSE === $type ) { $scopes = array( array( 'course', 'teacher' ) ); }\n\t\t\telseif ( LSCH_Content::LESSON === $type ) { $scopes = array( array( 'lesson', 'teacher' ), array( 'lesson', 'reviewer' ) ); }\n\t\t\telseif ( LSCH_Content::ASSESSMENT === $type ) { $scopes = array( array( 'assessment', 'assessor' ) ); }\n\t\t\telseif ( LSCH_Content::ASSIGNMENT === $type ) { $scopes = array( array( 'assignment', 'assessor' ) ); }\n\t\t\tforeach ( $scopes as $scope ) {\n\t\t\t\tif ( LSCH_Services::staff_scope_allows( $user_id, $scope[0], $post_id, $scope[1] ) ) { return true; }\n\t\t\t}\n\t\t}\n\t\treturn true === apply_filters( 'lsch_restricted_learning_access', false, $post_id, $user_id );\n"""
    replace_once('class-lsch-policy.php', old, new)
    assert_has('class-lsch-policy.php', "'restricted' !== $access")
    assert_has('class-lsch-policy.php', 'lsch_restricted_learning_access')
    record(1, 'Public/account/restricted authorization separation', 'Restricted content was readable by every approved account because account and restricted states shared the same return path. Restricted access now fails closed to manager/editor, scoped staff, or an explicit owner-approved access provider.', True)
else:
    record(1, 'Public/account/restricted authorization separation', 'No account/restricted authorization collapse detected.', False)

# Round 2 — progress is an enrollment-owned state and must require an active enrollment.
services = read('class-lsch-services.php')
helper_anchor = "final class LSCH_Services {\n"
helper_token = 'private static function active_course_enrollment'
if helper_token not in services:
    helper = """final class LSCH_Services {\n\tprivate static function active_course_enrollment( $course_id, $user_id ) {\n\t\t$course_id = absint( $course_id ); $user_id = absint( $user_id );\n\t\tif ( ! $course_id || ! $user_id || LSCH_Content::COURSE !== get_post_type( $course_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_required', __( 'An active course enrollment is required for this learning action.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT id,status,version FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1\", $user_id, $course_id ), ARRAY_A );\n\t\tif ( ! $row || 'active' !== $row['status'] ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_required', __( 'Activate this course enrollment before recording assessed learning activity.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn $row;\n\t}\n\n\tprivate static function active_child_enrollment( $object_id, $user_id ) {\n\t\t$object_id = absint( $object_id );\n\t\t$lesson_id = LSCH_Content::LESSON === get_post_type( $object_id ) ? $object_id : absint( get_post_meta( $object_id, '_lsch_lesson_id', true ) );\n\t\tif ( ! $lesson_id || LSCH_Content::LESSON !== get_post_type( $lesson_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_learning_parent_invalid', __( 'This learning object is not attached to a valid lesson.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$course_id = absint( get_post_meta( $lesson_id, '_lsch_course_id', true ) );\n\t\treturn self::active_course_enrollment( $course_id, $user_id );\n\t}\n"""
    if services.count(helper_anchor) != 1:
        raise SystemExit('services helper anchor mismatch')
    write('class-lsch-services.php', services.replace(helper_anchor, helper, 1))
services = read('class-lsch-services.php')
progress_anchor = "\t\t$course_id  = absint( get_post_meta( $lesson_id, '_lsch_course_id', true ) );\n\t\t$current    = self::progress_row( $lesson_id, $user_id );\n"
if "self::active_course_enrollment( $course_id, $user_id )" not in services.split('public static function progress',1)[1].split('public static function reset_progress',1)[0]:
    progress_new = "\t\t$course_id  = absint( get_post_meta( $lesson_id, '_lsch_course_id', true ) );\n\t\t$enrollment_gate = self::active_course_enrollment( $course_id, $user_id );\n\t\tif ( is_wp_error( $enrollment_gate ) ) { return $enrollment_gate; }\n\t\t$current    = self::progress_row( $lesson_id, $user_id );\n"
    replace_once('class-lsch-services.php', progress_anchor, progress_new)
    assert_has('class-lsch-services.php', '$enrollment_gate = self::active_course_enrollment( $course_id, $user_id );')
    record(2, 'Progress/enrollment state integrity', 'Lesson progress could be written without any active enrollment, allowing detached learning state to accumulate outside the enrollment lifecycle. Progress now requires the lesson to belong to a course with the learner in active state.', True)
else:
    record(2, 'Progress/enrollment state integrity', 'Progress already required an active course enrollment.', False)

# Round 3 — assessment attempts must be bound to active enrollment.
services = read('class-lsch-services.php')
start_section = services.split('public static function start_assessment',1)[1].split('public static function submit_assessment',1)[0]
if 'self::active_child_enrollment( $assessment_id, $user_id )' not in start_section:
    anchor = """\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::ASSESSMENT !== get_post_type( $assessment_id ) || ! LSCH_Policy::can_read_post( $assessment_id, $user_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_assessment_forbidden', __( 'Assessment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );\n\t\t}\n\t\t$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'assessment-attempt' ); if ( is_wp_error( $key ) ) { return $key; }\n"""
    new = anchor.replace("\t\t$key =", "\t\t$enrollment_gate = self::active_child_enrollment( $assessment_id, $user_id ); if ( is_wp_error( $enrollment_gate ) ) { return $enrollment_gate; }\n\t\t$key =")
    replace_once('class-lsch-services.php', anchor, new)
    assert_has('class-lsch-services.php', '$enrollment_gate = self::active_child_enrollment( $assessment_id, $user_id );')
    record(3, 'Assessment attempt enrollment binding', 'An approved account could start/submit a readable assessment without an active enrollment in its parent course. Assessment attempts now fail closed unless the assessment maps to a valid lesson and active course enrollment.', True)
else:
    record(3, 'Assessment attempt enrollment binding', 'Assessment attempts already had an active-enrollment gate.', False)

# Round 4 — assignment submissions must be bound to active enrollment.
services = read('class-lsch-services.php')
assign_section = services.split('public static function submit_assignment',1)[1].split('public static function grade_submission',1)[0]
if 'self::active_child_enrollment( $assignment_id, $user_id )' not in assign_section:
    anchor = """\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::ASSIGNMENT !== get_post_type( $assignment_id ) || ! LSCH_Policy::can_read_post( $assignment_id, $user_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_assignment_forbidden', __( 'Assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );\n\t\t}\n\t\t$body = wp_kses_post( $body );\n"""
    new = anchor.replace("\t\t$body =", "\t\t$enrollment_gate = self::active_child_enrollment( $assignment_id, $user_id ); if ( is_wp_error( $enrollment_gate ) ) { return $enrollment_gate; }\n\t\t$body =")
    replace_once('class-lsch-services.php', anchor, new)
    assert_has('class-lsch-services.php', '$enrollment_gate = self::active_child_enrollment( $assignment_id, $user_id );')
    record(4, 'Assignment submission enrollment binding', 'Readable assignments accepted submissions outside an active parent-course enrollment. Assignment submission now requires a valid lesson parent and active course enrollment.', True)
else:
    record(4, 'Assignment submission enrollment binding', 'Assignment submission already had an active-enrollment gate.', False)

# Round 5 — reminders must not be enabled for arbitrary courses with no learner relationship.
services = read('class-lsch-services.php')
rem_section = services.split('public static function set_reminder',1)[1].split('public static function related_links',1)[0]
if 'lsch_reminder_enrollment_required' not in rem_section:
    old = """\tpublic static function set_reminder( $course_id, $user_id, $enabled, $cadence, array $quiet_hours = array() ) {\n\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) ) { return new WP_Error( 'lsch_reminder_forbidden', __( 'Course reminder is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }\n\t\t$cadence = sanitize_key( $cadence ); if ( ! in_array( $cadence, array( 'daily', 'weekly', 'monthly' ), true ) ) { $cadence = 'weekly'; }\n"""
    new = """\tpublic static function set_reminder( $course_id, $user_id, $enabled, $cadence, array $quiet_hours = array() ) {\n\t\t$course_id = absint( $course_id ); $user_id = absint( $user_id );\n\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) || ! LSCH_Policy::can_read_post( $course_id, $user_id ) ) { return new WP_Error( 'lsch_reminder_forbidden', __( 'Course reminder is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }\n\t\tif ( $enabled ) { global $wpdb; $t = LSCH_Database::tables(); $status = (string) $wpdb->get_var( $wpdb->prepare( \"SELECT status FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1\", $user_id, $course_id ) ); if ( ! in_array( $status, array( 'enrolled', 'active', 'paused' ), true ) ) { return new WP_Error( 'lsch_reminder_enrollment_required', __( 'Enroll in this course before enabling learning reminders.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); } }\n\t\t$cadence = sanitize_key( $cadence ); if ( ! in_array( $cadence, array( 'daily', 'weekly', 'monthly' ), true ) ) { $cadence = 'weekly'; }\n"""
    replace_once('class-lsch-services.php', old, new)
    assert_has('class-lsch-services.php', 'lsch_reminder_enrollment_required')
    record(5, 'Reminder relationship and privacy boundary', 'Any approved account could enable reminders for any course ID regardless of enrollment. Enabling now requires a readable course plus enrolled/active/paused learner relationship; disabling remains possible for cleanup.', True)
else:
    record(5, 'Reminder relationship and privacy boundary', 'Reminder enabling already required a learner-course relationship.', False)

# Round 6 — governance POST must be strict allowlist, not arbitrary _lsch_* mass assignment.
admin = read('class-lsch-admin.php')
if '$allowed_keys = array(' not in admin:
    anchor = "\t\t$json_keys = array( '_lsch_required_components', '_lsch_questions', '_lsch_blueprint' );\n\t\tforeach ( $input as $key => $value ) {\n\t\t\t$key = sanitize_key( $key ); if ( 0 !== strpos( $key, '_lsch_' ) ) { continue; }\n"
    allowed = "\t\t$json_keys = array( '_lsch_required_components', '_lsch_questions', '_lsch_blueprint' );\n\t\t$allowed_keys = array( '_lsch_access', '_lsch_language', '_lsch_format', '_lsch_duration', '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_lesson_id', '_lsch_teacher_id', '_lsch_reviewer_id', '_lsch_objectives', '_lsch_prerequisites', '_lsch_equivalence', '_lsch_key_terms', '_lsch_examples', '_lsch_sources', '_lsch_accessibility', '_lsch_safety', '_lsch_required_components', '_lsch_questions', '_lsch_blueprint', '_lsch_rubric', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording', '_lsch_certificate_wording_approved' );\n\t\tforeach ( $input as $key => $value ) {\n\t\t\t$key = sanitize_key( $key ); if ( 0 !== strpos( $key, '_lsch_' ) || ! in_array( $key, $allowed_keys, true ) ) { continue; }\n"
    replace_once('class-lsch-admin.php', anchor, allowed)
    assert_has('class-lsch-admin.php', '! in_array( $key, $allowed_keys, true )')
    record(6, 'Governance metadata mass-assignment resistance', 'The admin save path accepted every crafted POST key beginning _lsch_, enabling unauthorized mutation of internal/version/governance metadata outside the visible form contract. A strict governance-field allowlist now blocks unknown internal keys.', True)
else:
    record(6, 'Governance metadata mass-assignment resistance', 'Governance POST handling already used a strict metadata allowlist.', False)

# Round 7 — Founder book slots must never be created with author 0 when Founder identity is unavailable.
content = read('class-lsch-content.php')
founder_anchor = "\t\t$founder = self::founder_id();\n\t\tfor ( $slot = 1; $slot <= 8; $slot++ ) {\n"
if founder_anchor in content:
    founder_new = "\t\t$founder = self::founder_id();\n\t\tif ( ! $founder ) { return; }\n\t\tfor ( $slot = 1; $slot <= 8; $slot++ ) {\n"
    replace_once('class-lsch-content.php', founder_anchor, founder_new)
    assert_has('class-lsch-content.php', 'if ( ! $founder ) { return; }')
    record(7, 'Founder catalog provenance integrity', 'When the public Founder provider/File00 validation was unavailable, the seeder still created Founder-book placeholders with post_author=0. Seeding now fails closed until a verified Founder identity is available.', True)
else:
    record(7, 'Founder catalog provenance integrity', 'Founder seed slots already failed closed when Founder identity was unavailable.', False)

# Round 8 — certificate governance fields must be registered with the canonical metadata contract.
content = read('class-lsch-content.php')
text_anchor = "\t\t\t\t'_lsch_citation_style', '_lsch_low_bandwidth', '_lsch_lifelong_learning',\n"
int_anchor = "\t\tforeach ( array( '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_teacher_id', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_lesson_id', '_lsch_reviewer_id', '_lsch_cohort_id' ) as $key ) {\n"
changed = False
if "'_lsch_certificate_jurisdiction'" not in content.split('private static function register_meta',1)[1].split('public static function can_edit_meta',1)[0]:
    replace_once('class-lsch-content.php', text_anchor, "\t\t\t\t'_lsch_citation_style', '_lsch_low_bandwidth', '_lsch_lifelong_learning', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording',\n")
    changed = True
content = read('class-lsch-content.php')
if "'_lsch_certificate_wording_approved'" not in content.split('private static function register_meta',1)[1].split('public static function can_edit_meta',1)[0]:
    replace_once('class-lsch-content.php', int_anchor, "\t\tforeach ( array( '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_teacher_id', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_lesson_id', '_lsch_reviewer_id', '_lsch_cohort_id', '_lsch_certificate_wording_approved' ) as $key ) {\n")
    changed = True
assert_has('class-lsch-content.php', "'_lsch_certificate_jurisdiction'")
assert_has('class-lsch-content.php', "'_lsch_certificate_wording_approved'")
record(8, 'Certificate metadata contract completeness', 'Certificate readiness fields were written by the admin UI but jurisdiction, wording and approval were absent from the canonical registered-meta contract. Registration now matches the executable certificate-readiness surface.' if changed else 'Certificate governance metadata was already fully registered.', changed)

# Round 9 — ordinary content/governance edits must advance the object version used by corrections/progress/evidence.
content = read('class-lsch-content.php')
changed = False
if 'private static $version_bumped' not in content:
    replace_once('class-lsch-content.php', "\tconst COMPETENCY = 'lsch_competency';\n", "\tconst COMPETENCY = 'lsch_competency';\n\tprivate static $version_bumped = array();\n")
    changed = True
content = read('class-lsch-content.php')
register_anchor = "\t\tself::register_meta();\n\t}\n\n\tprivate static function register_post_type"
if "add_action( 'post_updated', array( __CLASS__, 'bump_version_on_post_update' )" not in content:
    replace_once('class-lsch-content.php', register_anchor, "\t\tself::register_meta();\n\t\tadd_action( 'post_updated', array( __CLASS__, 'bump_version_on_post_update' ), 20, 3 );\n\t}\n\n\tprivate static function register_post_type")
    changed = True
content = read('class-lsch-content.php')
method_anchor = "\tpublic static function object_type( $post_id ) {\n"
if 'public static function bump_version( $post_id' not in content:
    methods = """\tpublic static function bump_version( $post_id, $reason = 'content_update' ) {\n\t\t$post_id = absint( $post_id );\n\t\tif ( ! $post_id || ! self::object_type( $post_id ) ) { return 0; }\n\t\tif ( isset( self::$version_bumped[ $post_id ] ) ) { return self::$version_bumped[ $post_id ]; }\n\t\t$raw = get_post_meta( $post_id, '_lsch_version', true );\n\t\t$next = '' === (string) $raw ? 1 : max( 1, absint( $raw ) ) + 1;\n\t\tupdate_post_meta( $post_id, '_lsch_version', $next );\n\t\tself::$version_bumped[ $post_id ] = $next;\n\t\tdo_action( 'lsch_content_version_bumped', $post_id, $next, sanitize_key( $reason ) );\n\t\treturn $next;\n\t}\n\n\tpublic static function bump_version_on_post_update( $post_id, $post_after, $post_before ) {\n\t\tif ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::object_type( $post_id ) ) { return; }\n\t\tforeach ( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_parent', 'menu_order' ) as $field ) {\n\t\t\tif ( (string) $post_after->$field !== (string) $post_before->$field ) { self::bump_version( $post_id, 'post_update' ); break; }\n\t\t}\n\t}\n\n"""
    replace_once('class-lsch-content.php', method_anchor, methods + method_anchor)
    changed = True
admin = read('class-lsch-admin.php')
if '$changed = false;' not in admin.split('public function save_governance_meta',1)[1].split('public function assets',1)[0]:
    replace_once('class-lsch-admin.php', "\t\t$allowed_keys = array( '_lsch_access', '_lsch_language', '_lsch_format', '_lsch_duration', '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_lesson_id', '_lsch_teacher_id', '_lsch_reviewer_id', '_lsch_objectives', '_lsch_prerequisites', '_lsch_equivalence', '_lsch_key_terms', '_lsch_examples', '_lsch_sources', '_lsch_accessibility', '_lsch_safety', '_lsch_required_components', '_lsch_questions', '_lsch_blueprint', '_lsch_rubric', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording', '_lsch_certificate_wording_approved' );\n", "\t\t$allowed_keys = array( '_lsch_access', '_lsch_language', '_lsch_format', '_lsch_duration', '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_lesson_id', '_lsch_teacher_id', '_lsch_reviewer_id', '_lsch_objectives', '_lsch_prerequisites', '_lsch_equivalence', '_lsch_key_terms', '_lsch_examples', '_lsch_sources', '_lsch_accessibility', '_lsch_safety', '_lsch_required_components', '_lsch_questions', '_lsch_blueprint', '_lsch_rubric', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording', '_lsch_certificate_wording_approved' );\n\t\t$changed = false;\n")
    replace_once('class-lsch-admin.php', "\t\t\tupdate_post_meta( $post_id, $key, $value );\n\t\t}\n\t\tif ( ! get_post_meta( $post_id, '_lsch_version', true ) ) { update_post_meta( $post_id, '_lsch_version', 1 ); }\n", "\t\t\t$current_value = get_post_meta( $post_id, $key, true );\n\t\t\tif ( (string) $current_value === (string) $value ) { continue; }\n\t\t\tupdate_post_meta( $post_id, $key, $value );\n\t\t\t$changed = true;\n\t\t}\n\t\tif ( $changed ) { LSCH_Content::bump_version( $post_id, 'governance_meta' ); }\n\t\telseif ( ! get_post_meta( $post_id, '_lsch_version', true ) ) { update_post_meta( $post_id, '_lsch_version', 1 ); }\n")
    changed = True
assert_has('class-lsch-content.php', 'public static function bump_version_on_post_update')
assert_has('class-lsch-admin.php', "LSCH_Content::bump_version( $post_id, 'governance_meta' )")
record(9, 'Object-version freshness and stale-correction prevention', 'Ordinary post body/title/governance edits did not advance _lsch_version, so correction proposals and learning evidence could compare against stale content without detecting intervening edits. A request-deduplicated version bump now covers post changes and governance-meta changes.' if changed else 'Ordinary content/governance changes already advanced canonical object version.', changed)

# Round 10 — AI citation approval must use canonical ID/URL, never title-only similarity.
f18 = read('class-lsch-future18.php')
old = """\t\t\t\t$source_url = esc_url_raw( (string) ( $source['url'] ?? '' ) );\n\t\t\t\t$source_title = sanitize_text_field( (string) ( $source['title'] ?? '' ) );\n\t\t\t\tif ( $item['url'] && $source_url && hash_equals( $source_url, $item['url'] ) ) { $approved = true; break; }\n\t\t\t\tif ( $item['title'] && $source_title && 0 === strcasecmp( trim( $source_title ), trim( $item['title'] ) ) ) { $approved = true; break; }\n"""
if old in f18:
    new = """\t\t\t\t$source_url = esc_url_raw( (string) ( $source['url'] ?? '' ) );\n\t\t\t\t$source_id = sanitize_text_field( (string) ( $source['object_id'] ?? '' ) );\n\t\t\t\t$source_owner = sanitize_text_field( (string) ( $source['owner_file'] ?? '' ) );\n\t\t\t\t$owner_match = '' === $item['owner_file'] || ( '' !== $source_owner && 0 === strcasecmp( trim( $source_owner ), trim( $item['owner_file'] ) ) );\n\t\t\t\t$object_match = '' !== $item['object_id'] && '' !== $source_id && hash_equals( $source_id, $item['object_id'] );\n\t\t\t\t$url_match = '' !== $item['url'] && '' !== $source_url && hash_equals( $source_url, $item['url'] );\n\t\t\t\tif ( $owner_match && ( $object_match || $url_match ) ) { $approved = true; break; }\n"""
    replace_once('class-lsch-future18.php', old, new)
    assert_not('class-lsch-future18.php', "0 === strcasecmp( trim( $source_title ), trim( $item['title'] ) )")
    assert_has('class-lsch-future18.php', '$object_match')
    record(10, 'Source-grounded Socratic AI citation integrity', 'A provider citation with a correct-looking title but a mismatched URL/object could pass source approval. Citation approval now requires an exact canonical object ID or URL match, with owner-file consistency when supplied; title is informational only.', True)
else:
    record(10, 'Source-grounded Socratic AI citation integrity', 'No title-only AI citation trust path detected.', False)

# Permanent Cycle-6 regression invariants.
test = ROOT / 'tests' / 'static-invariants.py'
t = test.read_text(encoding='utf-8')
marker = "\nif errors:\n"
block = r'''
# Sixth independent ten-round review regression invariants (2026-08-11).
policy_cycle6 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
for token in ["'account' === $access", "'restricted' !== $access", 'lsch_restricted_learning_access']:
    if token not in policy_cycle6:
        errors.append(f'Missing Cycle6 restricted-access invariant: {token}')

services_cycle6 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
for token in ['active_course_enrollment', 'active_child_enrollment', '$enrollment_gate = self::active_course_enrollment( $course_id, $user_id );', '$enrollment_gate = self::active_child_enrollment( $assessment_id, $user_id );', '$enrollment_gate = self::active_child_enrollment( $assignment_id, $user_id );', 'lsch_reminder_enrollment_required']:
    if token not in services_cycle6:
        errors.append(f'Missing Cycle6 enrollment-bound learning invariant: {token}')

admin_cycle6 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php', '')
for token in ['$allowed_keys = array(', '! in_array( $key, $allowed_keys, true )', "LSCH_Content::bump_version( $post_id, 'governance_meta' )"]:
    if token not in admin_cycle6:
        errors.append(f'Missing Cycle6 governance-meta invariant: {token}')

content_cycle6 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php', '')
for token in ['if ( ! $founder ) { return; }', "'_lsch_certificate_jurisdiction'", "'_lsch_certificate_wording_approved'", 'private static $version_bumped', 'bump_version_on_post_update']:
    if token not in content_cycle6:
        errors.append(f'Missing Cycle6 provenance/version invariant: {token}')

future_cycle6 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php', '')
for token in ['$object_match', '$url_match', '$owner_match']:
    if token not in future_cycle6:
        errors.append(f'Missing Cycle6 Socratic citation invariant: {token}')
if "0 === strcasecmp( trim( $source_title ), trim( $item['title'] ) )" in future_cycle6:
    errors.append('Cycle6 regression: Socratic tutor trusts citation title without canonical ID/URL match.')
'''
if '# Sixth independent ten-round review regression invariants' not in t:
    if marker not in t:
        raise SystemExit('static invariant insertion marker missing')
    t = t.replace(marker, '\n' + block + marker, 1)
    test.write_text(t, encoding='utf-8', newline='\n')

# Permanent review ledger, based on actual per-round detector outcome.
ledger = ROOT / 'REVIEW-10-CYCLE-6-2026-08-11.md'
lines = [
    '# File 05 — Sixth Independent 10-Round Sequential Review & Corrective Closure — 2026-08-11',
    '',
    f'Starting product-source HEAD: `{START_PRODUCT_HEAD}`. Temporary review transport commits are not product completion evidence. Each round inspected the cumulatively corrected source from the preceding round; any detected defect was fixed and asserted before the next round.',
    '',
    '| Round | Result | Review lens | Finding / correction |',
    '|---:|---|---|---|',
]
for r, result, lens, finding in rows:
    lines.append(f'| {r} | {result} | {lens} | {finding} |')
lines += [
    '',
    '## Defect-bearing rounds',
    '',
    (', '.join(str(x) for x in defects) if defects else 'None'),
    '',
    f'Total: **{len(defects)}/10 defect-bearing**, **{10-len(defects)}/10 clean**.',
    '',
    '## Truth boundary',
    '',
    'This ledger is repository/source evidence only. It does not establish Hostinger staging acceptance, deployed artifact parity, live database/schema/migration parity, Founder acceptance, live deployment or operational acceptance.',
]
ledger.write_text('\n'.join(lines) + '\n', encoding='utf-8', newline='\n')

print(f'Cycle6 complete: defects in rounds {defects if defects else "none"}; all detected defects corrected before subsequent rounds.')
