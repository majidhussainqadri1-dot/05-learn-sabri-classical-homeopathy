#!/usr/bin/env python3
from pathlib import Path
import re, sys
base=Path(__file__).resolve().parents[1]; plugin=base/'05-learn-sabri-classical-homeopathy'
errors=[]
files={p.relative_to(base).as_posix():p.read_text(encoding='utf-8') for p in plugin.rglob('*') if p.is_file()}
joined='\n'.join(files.values())
required_files=[
'05-learn-sabri-classical-homeopathy/learn-sabri-classical-homeopathy.php',
'05-learn-sabri-classical-homeopathy/includes/class-lsch-database.php',
'05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php',
'05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php',
'05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php',
'05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php',
'05-learn-sabri-classical-homeopathy/uninstall.php']
for f in required_files:
    if f not in files: errors.append(f'Missing {f}')
for token in ['LSCH_VERSION',"'2.0.0'",'LSCH_SCHEMA_VERSION','single-free-tier-v2','LearningEnrollmentCreated.v1','LessonCompleted.v1','AssessmentSubmitted.v1','CourseCompleted.v1','LearningContentCorrected.v1','LearningCaseConsentWithdrawn.v1','LearningReminderPreferenceChanged.v1','components_json','independent_reviewer_required','کامیاب کیس']:
    if token not in joined: errors.append(f'Missing invariant token: {token}')
for table in ['enrollments','progress','bookmarks','notes','attempts','submissions','staff_assignments','completions','related_links','case_consents','reminders','outbox','inbox','jobs','audit_log']:
    if f"'lsch_{table}'" not in joined and f"' {table}'" not in joined and table not in joined: errors.append(f'Missing table: {table}')
for route in [r'/catalog',r'/dashboard',r'/course/(?P<id>\d+)/enroll',r'/lesson/(?P<id>\d+)/progress',r'/lesson/(?P<id>\d+)/progress/reset',r'/lesson/(?P<id>\d+)/consent',r'/assessment/(?P<id>\d+)/start',r'/assessment/(?P<id>\d+)/submit',r'/assignment/(?P<id>\d+)/submit',r'/course/(?P<id>\d+)/reminder',r'/object/(?P<type>[a-z_]+)/(?P<id>\d+)/related',r'/staff',r'/system-check']:
    if route not in joined: errors.append(f'Missing REST route: {route}')
for bad in ["$percent = min( 100, absint( isset( $input['percent']",'innerHTML','eval(','$_REQUEST','wp_ajax_nopriv_lsch','http://','PKR 400/month']:
    if bad in joined: errors.append(f'Forbidden pattern: {bad}')
for path,text in files.items():
    if path.endswith('.php') and not path.endswith('learn-sabri-classical-homeopathy.php') and not path.endswith('uninstall.php') and "defined( 'ABSPATH' ) || exit;" not in text:
        errors.append(f'Missing ABSPATH guard: {path}')
    if 'sabri-learning' in text:
        errors.append(f'Legacy text domain in canonical package: {path}')
trace=(base/'REQUIREMENTS-TRACEABILITY.md').read_text(encoding='utf-8') if (base/'REQUIREMENTS-TRACEABILITY.md').exists() else ''
for i in range(1,19):
    rid=f'F05-FR-{i:03d}'
    if rid not in trace: errors.append(f'Missing trace {rid}')
for i in range(1,11):
    rid=f'F05-NFR-{i:03d}'
    if rid not in trace: errors.append(f'Missing trace {rid}')
if errors:
    print('\n'.join(f'ERROR: {e}' for e in errors)); sys.exit(1)
print(f'PASS: {len(files)} plugin files; source invariants and 28 requirement traces present.')
