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
    'privacy_exporters','privacy_erasers','reject_sensitive_practice_payload','patient_name','national_id',
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

# Read paths must not mutate personalized pathway state.
if "'GET', 'HEAD'" not in r or "build_learning_path( get_current_user_id(), $goal ?: 'balanced_mastery', $persist )" not in r:
    errors.append('Learning-path GET/HEAD persistence guard is missing.')
if "build_learning_path( $user_id, 'balanced_mastery', false )" not in f:
    errors.append('Mastery-center render path still persists personalized state.')

# Review scheduling must not be overwritten by mastery evidence recalculation.
review_result = f.split('public static function record_review_result',1)[-1].split('public static function add_mistake',1)[0]
if "'review_item', $id, false" not in review_result:
    errors.append('Spaced review result does not preserve its own scheduling interval.')

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
