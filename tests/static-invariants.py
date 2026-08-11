#!/usr/bin/env python3
from pathlib import Path
import re, sys

base = Path(__file__).resolve().parents[1]
plugin = base / '05-learn-sabri-classical-homeopathy'
errors = []
files = {p.relative_to(base).as_posix(): p.read_text(encoding='utf-8') for p in plugin.rglob('*') if p.is_file()}
joined = '\n'.join(files.values())

required_files = [
    '05-learn-sabri-classical-homeopathy/learn-sabri-classical-homeopathy.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-database.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php',
    '05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php',
    '05-learn-sabri-classical-homeopathy/uninstall.php',
]
for path in required_files:
    if path not in files:
        errors.append(f'Missing {path}')

required_docs = [
    'README.md', 'CHANGELOG.md', 'CHANGE-CONTROL.md', 'DECISION-LOG.md',
    'ARCHITECTURE.md', 'CONTRACTS.md', 'DATA-DICTIONARY.md', 'MIGRATION.md',
    'ROLLBACK.md', 'BACKUP-RESTORE.md', 'SECURITY-PRIVACY.md',
    'STAGING-ACCEPTANCE.md', 'REQUIREMENTS-TRACEABILITY.md', 'STATUS.md',
    'REVIEW-ROUND-1.md', 'REVIEW-ROUND-2.md',
]
for path in required_docs:
    if not (base / path).is_file():
        errors.append(f'Missing release/governance document: {path}')

required_tokens = [
    "LSCH_VERSION', '4.0.0'",
    'const SCHEMA = 2',
    'LSCH_SCHEMA_VERSION\', 18',
    'single-free-tier-v2',
    'SMC_Contracts',
    'assertions',
    'SMC_CONTRACT_VERSION',
    '1.2.2',
    '#087A4E',
    'file26',
    'global_rank_owner',
    'LearningEnrollmentCreated.v1',
    'LessonCompleted.v1',
    'AssessmentSubmitted.v1',
    'CourseCompleted.v1',
    'LearningContentCorrected.v1',
    'LearningCorrectionProposed.v1',
    'LearningCorrectionDecided.v1',
    'LearningCaseConsentWithdrawn.v1',
    'LearningReminderPreferenceChanged.v1',
    'components_json',
    'independent_reviewer_required',
    'LSCH_NOTE_MASTER_KEY',
    'NOTE_KEY_VERSION = 2',
    'const SCHEMA = 3',
    'note_write_key_version',
    'LearningCorrectionResubmitted.v1',
    'LearningCorrectionWithdrawn.v1',
    'citation_export',
    'bibtex',
    'ris',
    'saved_searches',
    'value_events',
    'request_keys',
    'rest_request_before_callbacks',
    'rest_request_after_callbacks',
    'GET_LOCK',
    'RELEASE_LOCK',
    'lsch_idempotency_payload_conflict',
    'JSON_INVALID_UTF8_SUBSTITUTE',
    'error_code',
    'lsch_correction_object_busy',
    'privacy-minimized',
    'F05-FUT-01',
    'F05-FUT-18',
    'lsch_future18_schema',
    'source_grounded_socratic_ai',
    'کامیاب کیس',
]
for token in required_tokens:
    if token not in joined:
        errors.append(f'Missing invariant token: {token}')

for table in [
    'enrollments', 'progress', 'bookmarks', 'notes', 'attempts', 'submissions',
    'staff_assignments', 'completions', 'related_links', 'case_consents',
    'reminders', 'outbox', 'inbox', 'jobs', 'audit_log', 'request_keys',
]:
    if table not in joined:
        errors.append(f'Missing core state table: {table}')
for table in ['saved_searches', 'corrections', 'value_events']:
    if table not in joined:
        errors.append(f'Missing continuous-value table: {table}')

for route in [
    r'/catalog', r'/dashboard', r'/course/(?P<id>\d+)/enroll',
    r'/lesson/(?P<id>\d+)/progress', r'/lesson/(?P<id>\d+)/progress/reset',
    r'/lesson/(?P<id>\d+)/consent', r'/assessment/(?P<id>\d+)/start',
    r'/assessment/(?P<id>\d+)/submit', r'/assignment/(?P<id>\d+)/submit',
    r'/course/(?P<id>\d+)/reminder', r'/object/(?P<type>[a-z_]+)/(?P<id>\d+)/related',
    r'/staff', r'/system-check', r'/search/saved',
    r'/corrections', r'/corrections/(?P<id>\d+)/resubmit', r'/citation/(?P<id>\d+)/export',
    r'/learning-record/export', r'/value/summary',
]:
    if route not in joined:
        errors.append(f'Missing REST route: {route}')

for bad in [
    "get_user_meta( $user_id, '_smc_",
    '$wpdb->usermeta',
    'wp_ajax_nopriv_lsch',
    'PKR 400',
    'premium tier',
    'paid unlock',
    '#167447',
    "global_rank_owner' => 'file05'",
]:
    if bad.lower() in joined.lower():
        errors.append(f'Forbidden current-plan pattern: {bad}')

# New-note encryption may preserve a read-only legacy v1 decrypt branch, but
# the encryption path must use the independent configured key generation.
policy = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php', '')
encrypt_section = policy.split('public static function encrypt_note', 1)[-1].split('public static function decrypt_note', 1)[0]
if 'AUTH_KEY' in encrypt_section or 'SECURE_AUTH_SALT' in encrypt_section:
    errors.append('New note encryption still depends on WordPress auth salts.')

content = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php', '')
if '$wpdb' in content or 'usermeta' in content.lower():
    errors.append('Content owner directly queries foreign membership storage.')

caps = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-capabilities.php', '')
for bad in ['two_factor', 'approval_version', '_smc_', 'get_user_meta']:
    if bad in caps:
        errors.append(f'Capabilities re-derive File 00 private state: {bad}')

for path, text in files.items():
    if path.endswith('.php') and not path.endswith('learn-sabri-classical-homeopathy.php') and not path.endswith('uninstall.php') and "defined( 'ABSPATH' ) || exit;" not in text:
        errors.append(f'Missing ABSPATH guard: {path}')
    if 'sabri-learning' in text:
        errors.append(f'Legacy text domain in canonical package: {path}')

trace_path = base / 'REQUIREMENTS-TRACEABILITY.md'
trace = trace_path.read_text(encoding='utf-8') if trace_path.exists() else ''
for i in range(1, 19):
    rid = f'F05-FR-{i:03d}'
    if rid not in trace:
        errors.append(f'Missing trace {rid}')
for i in range(1, 11):
    rid = f'F05-NFR-{i:03d}'
    if rid not in trace:
        errors.append(f'Missing trace {rid}')
for cid in ['F05-CEN-01', 'F05-CEN-02']:
    if cid not in trace:
        errors.append(f'Missing central-plan trace {cid}')
for i in range(1, 19):
    rid = f'F05-FUT-{i:02d}'
    if rid not in trace:
        errors.append(f'Missing Future-18 trace {rid}')

if (base / 'materialize-v3').exists():
    errors.append('Corrupt historical materialization transport must not remain in the release branch.')
if (base / '.github/workflows/materialize-file05-v3.yml').exists():
    errors.append('Obsolete materialization workflow must not remain in the release branch.')

rest_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php', '')
if "/lesson/(?P<id>\\d+)/correct" in rest_source or "array( $this, 'correct' )" in rest_source:
    errors.append('Legacy direct correction bypass remains REST-accessible.')

idempotency_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php', '')
if 'rest_pre_dispatch' in idempotency_source or 'rest_post_dispatch' in idempotency_source:
    errors.append('Idempotency guard still executes before REST permission callbacks.')
if 'is_wp_error( $response )' not in idempotency_source or 'response_status( $response )' not in idempotency_source:
    errors.append('Idempotency response finalization is not WP_Error-safe.')
services_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
if 'mark_content_corrected' in services_source:
    errors.append('Legacy direct correction service bypass remains callable.')

# Privileged core REST callbacks must recheck current learning eligibility/suspension policy.
core_rest = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php', '')
for signature in [
    "public function assessor() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function reviewer() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function teacher() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE )",
]:
    if signature not in core_rest:
        errors.append(f'core REST privileged callback missing current-policy check: {signature}')


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

if errors:
    print('\n'.join(f'ERROR: {e}' for e in errors))
    sys.exit(1)

print(f'PASS: {len(files)} plugin files; current-plan ownership/security/value invariants and 48 requirement traces present.')
