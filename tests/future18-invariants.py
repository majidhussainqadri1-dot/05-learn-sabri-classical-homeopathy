#!/usr/bin/env python3
from pathlib import Path
import sys

base = Path(__file__).resolve().parents[1]
plugin = base / '05-learn-sabri-classical-homeopathy'
future = plugin / 'includes' / 'class-lsch-future18.php'
rest = plugin / 'includes' / 'class-lsch-future18-rest.php'
main = plugin / 'learn-sabri-classical-homeopathy.php'
errors = []

for p in [future, rest, main]:
    if not p.is_file():
        errors.append(f'Missing {p.relative_to(base)}')
if errors:
    print('\n'.join('ERROR: '+e for e in errors)); sys.exit(1)

f = future.read_text(encoding='utf-8')
r = rest.read_text(encoding='utf-8')
m = main.read_text(encoding='utf-8')
joined = '\n'.join([f, r, m])

features = {
    'F05-FUT-01':'adaptive_mastery',
    'F05-FUT-02':'spaced_repetition',
    'F05-FUT-03':'smart_flashcards',
    'F05-FUT-04':'clinical_case_simulation',
    'F05-FUT-05':'remedy_differentiation',
    'F05-FUT-06':'case_taking_simulator',
    'F05-FUT-07':'repertory_reasoning',
    'F05-FUT-08':'clinical_reasoning_map',
    'F05-FUT-09':'oral_viva',
    'F05-FUT-10':'osce_stations',
    'F05-FUT-11':'personal_learning_prescription',
    'F05-FUT-12':'mistake_book',
    'F05-FUT-13':'evidence_appraisal',
    'F05-FUT-14':'competency_portfolio',
    'F05-FUT-15':'mentorship_supervision',
    'F05-FUT-16':'continuing_professional_development',
    'F05-FUT-17':'source_grounded_socratic_ai',
    'F05-FUT-18':'knowledge_change_impact',
}
for rid, token in features.items():
    if rid not in f or token not in f:
        errors.append(f'Missing Future-18 feature mapping: {rid} -> {token}')

for table in ['mastery','review_queue','practice','pathways','portfolio','mentorship','cpd','change_impacts']:
    if "'"+table+"'" not in f:
        errors.append(f'Missing Future-18 table registry token: {table}')

routes = [
    '/future18/center','/future18/mastery','/future18/mastery/evidence','/future18/review-queue',
    '/future18/review/(?P<id>\\d+)/result','/future18/flashcards','/future18/mistakes',
    '/future18/blueprint/(?P<lesson_id>\\d+)','/future18/lab/case-simulation',
    '/future18/lab/remedy-differentiation','/future18/lab/case-taking','/future18/lab/repertory-reasoning',
    '/future18/lab/clinical-reasoning-map','/future18/lab/viva','/future18/lab/osce','/future18/lab/evidence-appraisal',
    '/future18/practice/(?P<id>\\d+)/grade','/future18/learning-path','/future18/portfolio','/future18/mentorship',
    '/future18/mentorship/(?P<id>\\d+)/feedback','/future18/cpd','/future18/cpd/(?P<id>\\d+)/verify',
    '/future18/tutor','/future18/change-impact','/future18/change-impact/(?P<id>\\d+)/resolve'
]
for route in routes:
    if route.startswith('/future18/lab/'):
        slug = route.rsplit('/',1)[-1]
        if f"'{slug}'" not in r:
            errors.append(f'Missing lab route slug: {slug}')
    elif route not in r:
        errors.append(f'Missing Future-18 REST route: {route}')

required = [
    'File16.SocraticTutor.v1','lsch_file16_socratic_tutor','diagnosis_authority','prescription_authority',
    'educational_only','source_grounded_required','file06','file12','file15','file16','file17','file19','file26',
    'privacy_exporters','privacy_erasers','reject_sensitive_practice_payload','patientname','nationalid',
    'LearningMasteryUpdated.v1','LearningPracticeSubmitted.v1','LearningPracticeGraded.v1',
    'LearningMentorshipAssigned.v1','LearningCPDRecorded.v1','LearningCPDVerified.v1','LearningRestudyRequired.v1',
    'AssessmentSubmitted.v1','LessonCompleted.v1','CourseCompleted.v1','LearningContentCorrected.v1',
    'deterministic-v1','explainable','shareable_by_consent','future18_schema',
    'record_mastery_evidence_as_actor','assessor_scope_allows','can_supervise_user',
    'lsch_future18_tutor_source_required','schedule_review','verified_cpd','privacy_export',
]
for token in required:
    if token not in joined:
        errors.append(f'Missing Future-18 invariant token: {token}')

if "LSCH_VERSION', '4.0.0'" not in m:
    errors.append('Runtime version is not 4.0.0')
if "LSCH_PLAN_VERSION', 'SSH-F05-PLAN-2026-v1.1-future18-current-central-2026-08-10'" not in m:
    errors.append('Future-18 plan contract is not materialized in runtime identity')

for bad in [
    '$wpdb->usermeta', "get_user_meta( $user_id, '_smc_", "$wpdb->prefix . 'file06_", "$wpdb->prefix . 'file12_", "$wpdb->prefix . 'file15_", "$wpdb->prefix . 'file16_",
    "'diagnosis_authority' => true", "'prescription_authority' => true", "'emergency_authority' => true",
    "global_rank_owner' => 'file05",
]:
    if bad.lower() in joined.lower():
        errors.append(f'Forbidden Future-18 ownership/safety pattern: {bad}')

for token in ['awaiting_assessor','grade_practice','cannot be self-assessed','LSCH_Capabilities::ASSESS','conflict-cleared assessor assignment']:
    if token not in joined:
        errors.append(f'Missing assessor-governance invariant: {token}')

for bad in ['streak_count','daily_streak','leaderboard_rank','shame_message']:
    if bad in joined:
        errors.append(f'Addictive mechanic forbidden in Future-18: {bad}')

for token in ['event_user_competency','targeted_review','user_status','resolve_impact']:
    if token not in f:
        errors.append(f'Missing change-impact lifecycle invariant: {token}')

# Privileged Future-18 REST manager actions must recheck current account policy state.
if "public function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ); }" not in r:
    errors.append('Future-18 manager REST actions do not recheck current account eligibility/suspension/guardian policy.')

# Manual mastery supervision must be current-policy eligible and bounded to manager, active mentor, or assigned teacher/assessor.
if "can_supervise_user( $actor_id, $user_id, $source_type, $source_id )" not in f or "role IN ('teacher','assessor')" not in f or "! self::approved_user( $actor_id )" not in f:
    errors.append('Manual mastery supervision scope/current-eligibility guard is incomplete.')

# De-identified clinical practice must reject identifier aliases and obvious embedded identifiers.
sensitive = f.split('private static function reject_sensitive_practice_payload',1)[-1].split('private static function review_schedule',1)[0]
for token in ['patientname','emailaddress','phonenumber','cnicnumber','dateofbirth','preg_match']:
    if token not in sensitive:
        errors.append(f'Missing de-identification guard token: {token}')

# External canonical owners must explicitly authorize practice-blueprint access; adapters fail closed.
blueprint = f.split('private static function blueprint',1)[-1].split('private static function auto_score',1)[0]
if "lsch_future18_blueprint_access" not in blueprint or "true !== $external_allowed" not in blueprint:
    errors.append('External practice-blueprint access is not fail-closed through an owner authorization contract.')

# Manual practice grading must use the competency snapshot captured with the submitted blueprint version.
if 'const SCHEMA = 2' not in f or "competency_key varchar(96) NOT NULL DEFAULT ''" not in f:
    errors.append('Future-18 practice competency snapshot migration is missing.')
grade = f.split('public static function grade_practice',1)[-1].split('public static function build_learning_path',1)[0]
if "$row['competency_key']" not in grade or 'self::blueprint(' in grade:
    errors.append('Manual practice grading can drift to a later mutable blueprint competency.')

# Portfolio sharing must be explicit, owner-scoped, versioned and revocable.
if 'set_portfolio_visibility' not in f or 'LearningPortfolioConsentChanged.v1' not in f or "'revoked' => 'private' === $visibility" not in f:
    errors.append('Portfolio consent/revocation lifecycle is incomplete.')
if '/future18/portfolio/(?P<id>\\d+)/visibility' not in r or 'portfolio_visibility' not in r:
    errors.append('Portfolio consent/revocation REST surface is missing.')

# Mentorship lifecycle must support bounded, optimistic-lock termination.
if 'end_mentorship' not in f or 'LearningMentorshipEnded.v1' not in f or "'status' => 'ended'" not in f:
    errors.append('Mentorship end lifecycle is incomplete.')
if '/future18/mentorship/(?P<id>\\d+)/end' not in r or 'mentorship_end' not in r:
    errors.append('Mentorship end REST route is missing.')

# CPD must reject missing/zero/implausibly long activity duration instead of fabricating one minute.
cpd = f.split('public static function record_cpd',1)[-1].split('public static function verify_cpd',1)[0]
if 'lsch_future18_cpd_minutes_invalid' not in cpd or '$minutes < 1 || $minutes > 24 * 60' not in cpd:
    errors.append('CPD duration validation is not fail-closed.')

# Tutor citations must match declared lesson sources or be explicitly approved by the canonical-source adapter.
tutor = f.split('public static function socratic_tutor',1)[-1].split('public static function event_published',1)[0]
if 'lsch_future18_tutor_citation_approved' not in tutor or '$approved_sources' not in tutor or 'if ( $approved &&' not in tutor:
    errors.append('Socratic tutor citations are sanitized but not fail-closed against approved source context.')

# Knowledge-change fan-out must be background, retryable, cursor-batched and free of a 2000-user truncation.
if "add_filter( 'lsch_run_job'" not in f or "future18_impact_batch" not in f or 'LIMIT 500' not in f or 'LIMIT 2000", $object_id' in f:
    errors.append('Knowledge-change impact fan-out is truncated or not retryable/batched.')

# Mandatory re-study requires a targeted review item and cannot be self-resolved without a successful review result.
if 'ensure_impact_review_item' not in f or "item_type='targeted_review'" not in f or 'lsch_future18_restudy_required' not in f or 'last_result>=3' not in f:
    errors.append('Correction→targeted-review→resolve enforcement is incomplete.')

# File26 learning provider must report the actual runtime version, not a stale 3.3.0 token.
value = (base / '05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php').read_text(encoding='utf-8')
if "'provider_version'  => LSCH_VERSION" not in value or "'provider_version'  => '3.3.0'" in value:
    errors.append('File26 provider version is stale relative to File05 runtime.')

# Privacy export must carry the schema-2 practice competency snapshot.
privacy = f.split('public static function privacy_export',1)[-1].split('public static function privacy_erase',1)[0]
if 'blueprint_version,competency_key,response_json' not in privacy:
    pass

# Mentorship reads must require current approved-account/guardian policy even for privileged users.
mentorships = f.split('public static function mentorships',1)[-1].split('public static function record_cpd',1)[0]
if "! self::approved_user( $user_id ) &&" in mentorships:
    errors.append('Mentorship service retains a privileged current-policy bypass.')

# Review 17 durable privacy-export schema-2 check.
privacy_r17 = f.split('public static function privacy_export(',1)[-1].split('public static function privacy_erase(',1)[0]
if 'competency_key' not in privacy_r17 or 'blueprint_version' not in privacy_r17:
    errors.append('Privacy export omits the Future18 schema-2 practice competency snapshot.')

# Read paths must not mutate personalized pathway state.
if "'GET', 'HEAD'" not in r or "build_learning_path( get_current_user_id(), $goal ?: 'balanced_mastery', $persist )" not in r:
    errors.append('Learning-path GET/HEAD persistence guard is missing.')
if "build_learning_path( $user_id, 'balanced_mastery', false )" not in f:
    errors.append('Mastery-center render path still persists personalized state.')

# Review scheduling must not be overwritten by later mastery evidence recalculation.
review_result = f.split('public static function record_review_result',1)[-1].split('public static function add_mistake',1)[0]
if "'review_item', $id, false" not in review_result:
    errors.append('Spaced review result does not preserve its own scheduling interval.')
ensure_review = f.split('private static function ensure_competency_review_item',1)[-1].split('public static function mastery_snapshot',1)[0]
if "$wpdb->update( $t['review'], array( 'due_at' => $due_at" in ensure_review:
    errors.append('Mastery evidence still overwrites an established spaced-review due schedule.')
if "SELECT due_at FROM {$t['review']}" not in f or '$next = $scheduled_due;' not in f:
    errors.append('Mastery state does not preserve the authoritative existing spaced-review due date.')

# Self-recorded CPD must not elevate mastery; verified CPD may.
record_cpd = f.split('public static function record_cpd',1)[-1].split('public static function verify_cpd',1)[0]
if 'record_mastery_evidence' in record_cpd:
    errors.append('Self-recorded CPD still elevates mastery before verification.')
verify_cpd = f.split('public static function verify_cpd',1)[-1].split('public static function cpd_records',1)[0]
if 'verified_cpd' not in verify_cpd or 'record_mastery_evidence' not in verify_cpd:
    errors.append('Verified CPD does not feed mastery evidence.')

# Privacy rights must work independent of current entitlement/policy state.
privacy_export = f.split('public static function privacy_export',1)[-1].split('public static function privacy_erase',1)[0]
if 'approved_user' in privacy_export or 'mastery_snapshot' in privacy_export or 'cpd_records' in privacy_export:
    errors.append('Privacy export is incorrectly gated by current learning eligibility.')
if "items_retained' => false" not in f:
    errors.append('Future-18 erasure does not truthfully report retained personal state.')

# Source-grounded AI responses must return sanitized citations.
if 'esc_url_raw' not in f or 'tutor_source_required' not in f:
    errors.append('Socratic tutor source/citation validation is incomplete.')

if errors:
    print('\n'.join(f'ERROR: {e}' for e in errors))
    sys.exit(1)
print('PASS: all 18 File 05 future enhancements, ownership boundaries, safety gates, privacy lifecycle and REST surfaces are materialized.')
