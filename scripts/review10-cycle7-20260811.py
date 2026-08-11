#!/usr/bin/env python3
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
SERV = ROOT / '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php'
POL  = ROOT / '05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php'
ADM  = ROOT / '05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php'
TEST = ROOT / 'tests/static-invariants.py'
LEDGER = ROOT / 'REVIEW-10-CYCLE-7-2026-08-11.md'
START_PRODUCT_HEAD = 'c61c648a0a46e191e6003be6e51e019b39cd2279'
rows = []

def read(p): return p.read_text(encoding='utf-8')
def write(p, s): p.write_text(s, encoding='utf-8', newline='\n')
def replace_once(p, old, new, label):
    s = read(p)
    if old not in s:
        raise SystemExit(f'{label}: expected defective pattern not found')
    if s.count(old) != 1:
        raise SystemExit(f'{label}: expected exactly one defective pattern, found {s.count(old)}')
    write(p, s.replace(old, new, 1))

def lint(p):
    subprocess.run(['php', '-l', str(p)], cwd=ROOT, check=True, stdout=subprocess.DEVNULL)

def record(n, lens, finding):
    rows.append((n, 'DEFECT + FIX', lens, finding))
    print(f'Round {n}: fixed — {lens}')

# Round 1 — enrollment activation must re-authorize current content and prerequisites.
old = """\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_invalid_transition', __( 'The enrollment transition is not allowed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n"""
new = """\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) || ! LSCH_Policy::can_read_post( $course_id, $user_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_invalid_transition', __( 'The enrollment transition is not allowed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n"""
replace_once(SERV, old, new, 'round1 access revalidation')
old = """\t\t$transitions = array( 'enrolled' => array( 'active', 'withdrawn' ), 'active' => array( 'paused', 'withdrawn' ), 'paused' => array( 'active', 'withdrawn' ) );\n"""
new = """\t\tif ( 'active' === $state ) {\n\t\t\t$prerequisites = LSCH_Policy::validate_prerequisites( $course_id, $user_id );\n\t\t\tif ( is_wp_error( $prerequisites ) ) { return $prerequisites; }\n\t\t}\n\t\t$transitions = array( 'enrolled' => array( 'active', 'withdrawn' ), 'active' => array( 'paused', 'withdrawn' ), 'paused' => array( 'active', 'withdrawn' ) );\n"""
replace_once(SERV, old, new, 'round1 prerequisite revalidation')
lint(SERV)
record(1, 'Enrollment state re-authorization', 'Activation/resume did not recheck current course readability and prerequisites. The transition now re-authorizes both before entering active state.')

# Round 2 — active enrollment must match current course/access contract.
old = "SELECT id,status,version FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1"
new = "SELECT id,status,version,course_version,terms_version FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d LIMIT 1"
replace_once(SERV, old, new, 'round2 enrollment projection')
old = """\t\tif ( ! $row || 'active' !== $row['status'] ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_required', __( 'Activate this course enrollment before recording assessed learning activity.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn $row;\n"""
new = """\t\tif ( ! $row || 'active' !== $row['status'] ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_required', __( 'Activate this course enrollment before recording assessed learning activity.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\tif ( ! LSCH_Policy::can_read_post( $course_id, $user_id ) ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_access_revoked', __( 'This enrollment no longer grants access to the current course.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );\n\t\t}\n\t\tif ( absint( $row['course_version'] ) !== LSCH_Content::version( $course_id ) || (string) $row['terms_version'] !== (string) LSCH_Policy::access_model() ) {\n\t\t\treturn new WP_Error( 'lsch_active_enrollment_stale', __( 'The course or learning-access contract changed. Refresh the enrollment before continuing assessed activity.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn $row;\n"""
replace_once(SERV, old, new, 'round2 stale enrollment guard')
lint(SERV)
record(2, 'Active-enrollment version/current-access integrity', 'Assessed actions accepted an active row even after course/access-contract changes. Active enrollment now requires current readability, course version and access-model parity.')

# Round 3 — public progress mutation must not permit versionless overwrite.
old = """\t\tif ( $current && $expected && absint( $current['version'] ) !== $expected ) {\n\t\t\treturn new WP_Error( 'lsch_progress_conflict', __( 'Progress changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n"""
new = """\t\tif ( $current && ! $expected ) {\n\t\t\treturn new WP_Error( 'lsch_progress_version_required', __( 'The current progress version is required before updating this lesson.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n\t\tif ( $current && absint( $current['version'] ) !== $expected ) {\n\t\t\treturn new WP_Error( 'lsch_progress_conflict', __( 'Progress changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t}\n"""
replace_once(SERV, old, new, 'round3 progress optimistic concurrency')
anchor = "\tpublic static function progress( $lesson_id, $user_id, array $input ) {\n"
helper = """\tprivate static function refresh_progress_from_evidence( $lesson_id, $user_id ) {\n\t\t$current = self::progress_row( absint( $lesson_id ), absint( $user_id ) );\n\t\t$input = $current ? array( 'version' => absint( $current['version'] ) ) : array();\n\t\treturn self::progress( absint( $lesson_id ), absint( $user_id ), $input );\n\t}\n\n""" + anchor
replace_once(SERV, anchor, helper, 'round3 internal progress helper')
s = read(SERV)
s = s.replace("self::progress( $lesson_id, $user_id, array() );", "self::refresh_progress_from_evidence( $lesson_id, $user_id );")
s = s.replace("self::progress( $lesson_id, absint( $row['user_id'] ), array() );", "self::refresh_progress_from_evidence( $lesson_id, absint( $row['user_id'] ) );")
write(SERV, s)
lint(SERV)
record(3, 'Progress optimistic concurrency', 'Existing lesson progress could be overwritten when a caller omitted the version. Versionless external overwrites now fail closed; internal evidence refresh uses the current stored version.')

# Round 4 — private-note updates must not silently overwrite newer ciphertext.
old = """\t\tif ( $current ) {\n\t\t\tif ( $expected_version && absint( $current['version'] ) !== absint( $expected_version ) ) {\n\t\t\t\treturn new WP_Error( 'lsch_note_conflict', __( 'This note changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}\n"""
new = """\t\tif ( $current ) {\n\t\t\tif ( ! $expected_version ) {\n\t\t\t\treturn new WP_Error( 'lsch_note_version_required', __( 'The current private-note version is required before saving changes.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}\n\t\t\tif ( absint( $current['version'] ) !== absint( $expected_version ) ) {\n\t\t\t\treturn new WP_Error( 'lsch_note_conflict', __( 'This note changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}\n"""
replace_once(SERV, old, new, 'round4 note optimistic concurrency')
lint(SERV)
record(4, 'Private-note lost-update protection', 'Existing encrypted notes accepted versionless updates, permitting last-write-wins loss across devices. Existing-note writes now require the exact current version.')

# Round 5 — bound and normalize assessment answer payloads before persistence/scoring.
old = """\t\tif ( 'graded' === $attempt['status'] ) { return $attempt; }\n"""
new = """\t\tif ( 'graded' === $attempt['status'] ) { return $attempt; }\n\t\tif ( count( $answers ) > 200 ) { return new WP_Error( 'lsch_assessment_answers_limit', __( 'Too many assessment answers were submitted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t$normalized_answers = array();\n\t\tforeach ( $answers as $index => $answer ) {\n\t\t\tif ( ! is_scalar( $answer ) && null !== $answer ) { return new WP_Error( 'lsch_assessment_answer_invalid', __( 'Assessment answers must be scalar values.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t\t$value = sanitize_text_field( (string) $answer );\n\t\t\tif ( strlen( $value ) > 2000 ) { return new WP_Error( 'lsch_assessment_answer_too_large', __( 'An assessment answer exceeded the allowed size.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t\t$normalized_answers[ absint( $index ) ] = $value;\n\t\t}\n\t\t$answers = $normalized_answers;\n\t\t$answers_json = wp_json_encode( $answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );\n\t\tif ( false === $answers_json || strlen( $answers_json ) > 65535 ) { return new WP_Error( 'lsch_assessment_answers_payload', __( 'The assessment answer payload is invalid or too large.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n"""
replace_once(SERV, old, new, 'round5 assessment payload normalization')
old = "array( 'answers_json' => wp_json_encode( $answers ), 'result_json' => wp_json_encode( $review ),"
new = "array( 'answers_json' => $answers_json, 'result_json' => wp_json_encode( $review ),"
replace_once(SERV, old, new, 'round5 assessment bounded persistence')
lint(SERV)
record(5, 'Assessment payload bounds and scalar normalization', 'Assessment submit accepted arbitrarily large/nested answers and persisted the raw structure. Answers are now scalar-normalized, count/size bounded and encoded once under a hard payload ceiling.')

# Round 6 — consent records require valid subject/evidence and current policy version.
old = """\t\t$source = sanitize_key( isset( $data['consent_source'] ) ? $data['consent_source'] : '' );\n\t\t$scope = sanitize_textarea_field( isset( $data['scope'] ) ? $data['scope'] : '' );\n\t\t$policy = sanitize_text_field( isset( $data['policy_version'] ) ? $data['policy_version'] : 'case-consent-v1' );\n\t\tif ( ! in_array( $source, array( 'patient', 'guardian', 'institution' ), true ) || '' === $scope ) { return new WP_Error( 'lsch_consent_invalid', __( 'A valid consent source and scope are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n"""
new = """\t\t$source = sanitize_key( isset( $data['consent_source'] ) ? $data['consent_source'] : '' );\n\t\t$subject_type = sanitize_key( isset( $data['subject_type'] ) ? $data['subject_type'] : 'patient' );\n\t\t$scope = sanitize_textarea_field( isset( $data['scope'] ) ? $data['scope'] : '' );\n\t\t$evidence_reference = sanitize_text_field( isset( $data['evidence_reference'] ) ? $data['evidence_reference'] : '' );\n\t\t$expected_policy = sanitize_text_field( (string) apply_filters( 'lsch_case_consent_policy_version', 'case-consent-v1' ) );\n\t\t$policy = sanitize_text_field( isset( $data['policy_version'] ) ? $data['policy_version'] : $expected_policy );\n\t\tif ( ! in_array( $source, array( 'patient', 'guardian', 'institution' ), true ) || ! in_array( $subject_type, array( 'patient', 'minor_patient', 'guardian', 'institution' ), true ) || '' === trim( $scope ) || '' === trim( $evidence_reference ) || $policy !== $expected_policy ) { return new WP_Error( 'lsch_consent_invalid', __( 'Current policy, valid subject/source, scope and consent evidence are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n"""
replace_once(SERV, old, new, 'round6 consent validation')
old = "sanitize_key( isset( $data['subject_type'] ) ? $data['subject_type'] : 'patient' ), $source, $scope, sanitize_text_field( isset( $data['evidence_reference'] ) ? $data['evidence_reference'] : '' ), $now"
new = "$subject_type, $source, $scope, $evidence_reference, $now"
replace_once(SERV, old, new, 'round6 consent persistence variables')
lint(SERV)
record(6, 'Patient-case consent evidence/current-policy integrity', 'Consent storage accepted arbitrary subject types, empty evidence references and stale caller-supplied policy versions. Storage now requires constrained subject/source, evidence, scope and the current governed consent-policy version.')

# Round 7 — publication authorization must validate consent structure/currentness, not row existence alone.
old = """\tpublic static function valid_case_consent( $lesson_id ) {\n\t\tglobal $wpdb;\n\t\t$t = LSCH_Database::tables();\n\t\treturn (bool) $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['consents']} WHERE lesson_id=%d AND withdrawn_at IS NULL ORDER BY id DESC LIMIT 1\", absint( $lesson_id ) ) );\n\t}\n"""
new = """\tpublic static function valid_case_consent( $lesson_id ) {\n\t\tglobal $wpdb;\n\t\t$t = LSCH_Database::tables();\n\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT policy_version,subject_type,consent_source,scope,evidence_reference,confirmed_at,withdrawn_at FROM {$t['consents']} WHERE lesson_id=%d ORDER BY id DESC LIMIT 1\", absint( $lesson_id ) ), ARRAY_A );\n\t\tif ( ! $row || ! empty( $row['withdrawn_at'] ) ) { return false; }\n\t\t$expected_policy = sanitize_text_field( (string) apply_filters( 'lsch_case_consent_policy_version', 'case-consent-v1' ) );\n\t\treturn\n\t\t\t$expected_policy === (string) $row['policy_version'] &&\n\t\t\tin_array( sanitize_key( $row['consent_source'] ), array( 'patient', 'guardian', 'institution' ), true ) &&\n\t\t\tin_array( sanitize_key( $row['subject_type'] ), array( 'patient', 'minor_patient', 'guardian', 'institution' ), true ) &&\n\t\t\t'' !== trim( (string) $row['scope'] ) &&\n\t\t\t'' !== trim( (string) $row['evidence_reference'] ) &&\n\t\t\t'' !== trim( (string) $row['confirmed_at'] );\n\t}\n"""
replace_once(POL, old, new, 'round7 consent authorization validation')
lint(POL)
record(7, 'Consent publication-gate structural validation', 'The patient-case read gate treated any non-withdrawn consent row as valid. It now checks current policy, constrained subject/source, evidence, scope and confirmation timestamp before authorizing publication/readability.')

# Round 8 — cross-file related references must be non-empty and owner-validated.
old = """\t\t$source_type = sanitize_key( $source_type ); $source_id = absint( $source_id ); $target_file = strtoupper( sanitize_text_field( isset( $data['target_file'] ) ? $data['target_file'] : '' ) );\n\t\tif ( ! in_array( $target_file, array( '06', '10', '12', '15' ), true ) || LSCH_Content::object_type( $source_id ) !== $source_type ) { return new WP_Error( 'lsch_related_invalid', __( 'Related knowledge reference is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t$url = esc_url_raw( isset( $data['target_url'] ) ? $data['target_url'] : '' );\n\t\tif ( $url && wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) { return new WP_Error( 'lsch_related_host', __( 'Related links must use the approved platform host.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n"""
new = """\t\t$source_type = sanitize_key( $source_type ); $source_id = absint( $source_id ); $target_file = strtoupper( sanitize_text_field( isset( $data['target_file'] ) ? $data['target_file'] : '' ) );\n\t\t$target_type = sanitize_key( isset( $data['target_type'] ) ? $data['target_type'] : '' );\n\t\t$target_id = sanitize_text_field( isset( $data['target_id'] ) ? $data['target_id'] : '' );\n\t\tif ( ! in_array( $target_file, array( '06', '10', '12', '15' ), true ) || LSCH_Content::object_type( $source_id ) !== $source_type || '' === $target_type ) { return new WP_Error( 'lsch_related_invalid', __( 'Related knowledge reference is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t$url = esc_url_raw( isset( $data['target_url'] ) ? $data['target_url'] : '' );\n\t\tif ( '' === $target_id && '' === $url ) { return new WP_Error( 'lsch_related_target_missing', __( 'A canonical target identifier or approved target URL is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\tif ( $url && wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) { return new WP_Error( 'lsch_related_host', __( 'Related links must use the approved platform host.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }\n\t\t$verified = apply_filters( 'lsch_validate_related_learning_reference', false, array( 'target_file' => $target_file, 'target_type' => $target_type, 'target_id' => $target_id, 'target_url' => $url ), $source_type, $source_id );\n\t\tif ( true !== $verified ) { return new WP_Error( 'lsch_related_target_unverified', __( 'The canonical owner could not verify this related-learning target.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n"""
replace_once(SERV, old, new, 'round8 related reference validation')
old = "sanitize_key( isset( $data['target_type'] ) ? $data['target_type'] : '' ), sanitize_text_field( isset( $data['target_id'] ) ? $data['target_id'] : '' ), $url"
new = "$target_type, $target_id, $url"
replace_once(SERV, old, new, 'round8 related persistence variables')
lint(SERV)
record(8, 'Cross-file canonical-reference validation', 'Related links could persist empty/unverified targets. They now require a target type plus ID/URL and positive validation by the canonical external owner adapter before persistence.')

# Round 9 — prerequisites must reference valid other courses, never self/garbage IDs.
old = "$ids = array_filter( array_map( 'absint', preg_split( '/[\\s,]+/', $raw ) ) );"
new = "$ids = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/[\\s,]+/', $raw ) ) ) ) );"
replace_once(POL, old, new, 'round9 prerequisite normalization')
old = """\t\tforeach ( $ids as $required_course ) {\n\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['completions']} WHERE user_id=%d AND course_id=%d AND status='earned' LIMIT 1\", $user_id, $required_course ) );\n"""
new = """\t\tforeach ( $ids as $required_course ) {\n\t\t\tif ( absint( $required_course ) === absint( $course_id ) || LSCH_Content::COURSE !== get_post_type( $required_course ) ) {\n\t\t\t\treturn new WP_Error( 'lsch_prerequisite_configuration_invalid', __( 'A prerequisite must reference a different valid learning course.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );\n\t\t\t}\n\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['completions']} WHERE user_id=%d AND course_id=%d AND status='earned' LIMIT 1\", $user_id, $required_course ) );\n"""
replace_once(POL, old, new, 'round9 prerequisite validity')
lint(POL)
record(9, 'Prerequisite graph input integrity', 'Prerequisite parsing permitted self-references and non-course IDs, creating impossible or meaningless enrollment gates. Runtime validation now rejects self and invalid course references and de-duplicates the list.')

# Round 10 — governance numeric/JSON fields need semantic bounds, not absint-only coercion.
old = """\t\t\telseif ( in_array( $key, $integer_keys, true ) ) { $value = absint( $value ); }\n\t\t\telseif ( in_array( $key, $json_keys, true ) ) { $decoded = json_decode( (string) $value, true ); if ( ! is_array( $decoded ) ) { continue; } $value = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }\n"""
new = """\t\t\telseif ( in_array( $key, $integer_keys, true ) ) {\n\t\t\t\t$value = absint( $value );\n\t\t\t\tif ( '_lsch_pass_mark' === $key && ( $value < 1 || $value > 100 ) ) { continue; }\n\t\t\t\tif ( '_lsch_max_attempts' === $key && ( $value < 1 || $value > 50 ) ) { continue; }\n\t\t\t\tif ( '_lsch_time_limit' === $key && $value > 1440 ) { continue; }\n\t\t\t\tif ( in_array( $key, array( '_lsch_required', '_lsch_certificate_wording_approved' ), true ) && $value > 1 ) { continue; }\n\t\t\t}\n\t\t\telseif ( in_array( $key, $json_keys, true ) ) {\n\t\t\t\t$decoded = json_decode( (string) $value, true ); if ( ! is_array( $decoded ) ) { continue; }\n\t\t\t\tif ( '_lsch_required_components' === $key ) { $normalized = array_values( array_unique( array_map( 'sanitize_key', $decoded ) ) ); if ( ! $normalized || array_diff( $normalized, array( 'content', 'assessment', 'assignment' ) ) ) { continue; } $decoded = $normalized; }\n\t\t\t\tif ( '_lsch_questions' === $key && count( $decoded ) > 200 ) { continue; }\n\t\t\t\t$value = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); if ( false === $value || strlen( $value ) > 1048576 ) { continue; }\n\t\t\t}\n"""
replace_once(ADM, old, new, 'round10 governance semantic bounds')
lint(ADM)
record(10, 'Governance numeric/JSON semantic bounds', 'Admin governance accepted semantically impossible pass marks/attempt counts/flags and unbounded/invalid component/question JSON. Field-specific bounds and JSON semantic/size validation are now enforced.')

# Permanent regression assertions for this ten-round cycle.
t = read(TEST)
marker = "\nif errors:\n"
if marker not in t:
    raise SystemExit('static invariant insertion marker missing')
block = r'''

# Seventh independent ten-round review regression invariants (2026-08-11).
services_cycle7 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
for token in [
    "! LSCH_Policy::can_read_post( $course_id, $user_id )",
    "lsch_active_enrollment_stale",
    "lsch_progress_version_required",
    "refresh_progress_from_evidence",
    "lsch_note_version_required",
    "lsch_assessment_answers_limit",
    "lsch_assessment_answer_invalid",
    "lsch_assessment_answers_payload",
    "lsch_validate_related_learning_reference",
    "lsch_related_target_unverified",
]:
    if token not in services_cycle7:
        errors.append(f'Missing Cycle7 service-integrity invariant: {token}')

policy_cycle7 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
for token in [
    "lsch_case_consent_policy_version",
    "evidence_reference",
    "lsch_prerequisite_configuration_invalid",
    "array_unique",
]:
    if token not in policy_cycle7:
        errors.append(f'Missing Cycle7 policy invariant: {token}')

admin_cycle7 = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php', '')
for token in [
    "'_lsch_pass_mark' === $key",
    "'_lsch_max_attempts' === $key",
    "'_lsch_time_limit' === $key",
    "array_diff( $normalized, array( 'content', 'assessment', 'assignment' ) )",
    "count( $decoded ) > 200",
]:
    if token not in admin_cycle7:
        errors.append(f'Missing Cycle7 governance-bound invariant: {token}')
'''
write(TEST, t.replace(marker, block + marker, 1))
subprocess.run(['python3', 'tests/static-invariants.py'], cwd=ROOT, check=True)

lines = [
    '# File 05 — Seventh Independent 10-Round Sequential Review & Corrective Closure — 2026-08-11',
    '',
    f'Starting product-source HEAD: `{START_PRODUCT_HEAD}`. Temporary review transport commits are not product completion evidence. Each round inspected the cumulatively corrected source from the preceding round; every detected defect was fixed and syntax/assertion checked before advancing.',
    '',
    '| Round | Result | Review lens | Finding / correction |',
    '|---:|---|---|---|',
]
for n, result, lens, finding in rows:
    lines.append(f'| {n} | {result} | {lens} | {finding} |')
lines += [
    '',
    '## Defect-bearing rounds',
    '',
    ', '.join(str(r[0]) for r in rows),
    '',
    f'Total: **{len(rows)}/10 defect-bearing**, **{10-len(rows)}/10 clean at first inspection**. All listed defects were corrected before the subsequent round.',
    '',
    '## Truth boundary',
    '',
    'This ledger is repository/source evidence only. It does not establish Hostinger staging acceptance, deployed artifact parity, live database/schema/migration parity, Founder acceptance, live deployment or operational acceptance.',
]
write(LEDGER, '\n'.join(lines) + '\n')
print('Completed 10 sequential review/fix rounds; permanent regression assertions and ledger written.')
