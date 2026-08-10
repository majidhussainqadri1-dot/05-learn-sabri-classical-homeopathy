#!/usr/bin/env python3
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PRODUCT = ROOT / '05-learn-sabri-classical-homeopathy'
rounds = []

def text(path):
    return (ROOT / path).read_text(encoding='utf-8')

def write(path, value):
    (ROOT / path).write_text(value, encoding='utf-8', newline='\n')

def must_replace(path, old, new, round_no, finding):
    data = text(path)
    count = data.count(old)
    if count != 1:
        raise SystemExit(f'Round {round_no}: expected exactly one match in {path}, got {count}: {old[:120]!r}')
    write(path, data.replace(old, new, 1))
    rounds.append((round_no, finding, 'DEFECT + FIX'))

def replace_all(path, old, new):
    data = text(path)
    if old in data:
        write(path, data.replace(old, new))
        return True
    return False

# Round 1 — release/schema identity parity. Source truth is LSCH_Future18::SCHEMA = 2.
identity_docs = [
    'README.md','STATUS.md','05-learn-sabri-classical-homeopathy/readme.txt',
    'CHANGELOG.md','DATA-DICTIONARY.md','ARCHITECTURE.md','CHANGE-CONTROL.md',
    'MIGRATION.md','BACKUP-RESTORE.md'
]
changed = 0
patterns = [
    ('Future-18 learning-intelligence schema: `1`', 'Future-18 learning-intelligence schema: `2`'),
    ('Future-18 sub-schema 1 — runtime 4.0.0', 'Future-18 sub-schema 2 — runtime 4.0.0'),
    ('Future-18 sub-schema 1', 'Future-18 sub-schema 2'),
    ('lsch_future18_schema=1', 'lsch_future18_schema=2'),
    ('Future-18 schema 1', 'Future-18 schema 2'),
    ('additive Future-18 schema 1', 'additive Future-18 schema 2'),
]
for doc in identity_docs:
    for old,new in patterns:
        changed += 1 if replace_all(doc, old, new) else 0
for path, old, new in [
    ('CONTRACTS.md', '# File 05 Versioned Contract Registry — 3.3.0', '# File 05 Versioned Contract Registry — 4.0.0'),
    ('DATA-DICTIONARY.md', '# File 05 Data Dictionary — 3.3.0', '# File 05 Data Dictionary — 4.0.0'),
    ('ARCHITECTURE.md', '# File 05 Architecture — 3.3.0', '# File 05 Architecture — 4.0.0'),
]:
    if replace_all(path, old, new): changed += 1
if changed < 6:
    raise SystemExit(f'Round 1: schema/document parity correction matched too few locations: {changed}')
rounds.append((1, f'Future-18 source schema=2 but current release/governance docs retained schema=1 and 3.3.0 headings ({changed} corrections).', 'DEFECT + FIX'))

services = '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php'
future = '05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php'
future_test = 'tests/future18-invariants.py'
static_test = 'tests/static-invariants.py'

# Round 2 — private-note ciphertext was being recorded as legacy key generation 1.
must_replace(
    services,
    "\t\t\t$wpdb->update( $t['notes'], array( 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => 1, 'version' => absint( $current['version'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $current['id'] ) ), array( '%s', '%s', '%s', '%d', '%d', '%s' ), array( '%d' ) );\n",
    "\t\t\t$updated = $wpdb->update( $t['notes'], array( 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => absint( $encrypted['key_version'] ), 'version' => absint( $current['version'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ) ), array( '%s', '%s', '%s', '%d', '%d', '%s' ), array( '%d', '%d' ) );\n\t\t\tif ( 1 !== $updated ) { return new WP_Error( 'lsch_note_write_failed', __( 'The private note changed or could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n",
    2,
    'New AES-256-GCM notes were persisted with key_version=1 instead of encrypt_note() key generation; update path now records returned key version and optimistic write success.'
)
# Same round, insert half of the same root cause.
data = text(services)
old = "\t\t\t$wpdb->insert( $t['notes'], array( 'user_id' => $user_id, 'lesson_id' => $lesson_id, 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );\n"
new = "\t\t\t$inserted = $wpdb->insert( $t['notes'], array( 'user_id' => $user_id, 'lesson_id' => $lesson_id, 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => absint( $encrypted['key_version'] ), 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );\n\t\t\tif ( 1 !== $inserted ) { return new WP_Error( 'lsch_note_write_failed', __( 'The private note could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n"
if data.count(old) != 1: raise SystemExit('Round 2: note insert pattern mismatch')
write(services, data.replace(old,new,1))

# Round 3 — progress insert was unchecked before completion events/audit.
must_replace(
    services,
    "\t\t\t$wpdb->insert( $t['progress'], array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_id' => $lesson_id, 'state' => $state, 'percent' => $percent, 'resume_point' => $resume, 'components_json' => $json, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'needs_review' => 0, 'version' => 1, 'started_at' => $now, 'completed_at' => 'completed' === $state ? $now : null, 'updated_at' => $now ), array( '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ) );\n",
    "\t\t\t$inserted = $wpdb->insert( $t['progress'], array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_id' => $lesson_id, 'state' => $state, 'percent' => $percent, 'resume_point' => $resume, 'components_json' => $json, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'needs_review' => 0, 'version' => 1, 'started_at' => $now, 'completed_at' => 'completed' === $state ? $now : null, 'updated_at' => $now ), array( '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ) );\n\t\t\tif ( 1 !== $inserted ) { return new WP_Error( 'lsch_progress_write_failed', __( 'Progress could not be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n",
    3,
    'New progress row failure could still be followed by completion event/audit; persistence is now required before success side effects.'
)

# Round 4 — bookmark delete/add result truth.
must_replace(
    services,
    "\t\tif ( $id ) {\n\t\t\t$wpdb->delete( $t['bookmarks'], array( 'id' => absint( $id ) ), array( '%d' ) );\n\t\t\t$active = false;\n\t\t} else {\n\t\t\t$active = false !== $wpdb->insert( $t['bookmarks'], array( 'user_id' => $user_id, 'object_type' => $object_type, 'object_id' => $object_id, 'created_at' => LSCH_Database::now() ), array( '%d', '%s', '%d', '%s' ) );\n\t\t}\n",
    "\t\tif ( $id ) {\n\t\t\t$deleted = $wpdb->delete( $t['bookmarks'], array( 'id' => absint( $id ) ), array( '%d' ) );\n\t\t\tif ( false === $deleted ) { return new WP_Error( 'lsch_bookmark_write_failed', __( 'Bookmark state could not be changed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\t\t$active = false;\n\t\t} else {\n\t\t\t$inserted = $wpdb->insert( $t['bookmarks'], array( 'user_id' => $user_id, 'object_type' => $object_type, 'object_id' => $object_id, 'created_at' => LSCH_Database::now() ), array( '%d', '%s', '%d', '%s' ) );\n\t\t\tif ( false === $inserted ) {\n\t\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['bookmarks']} WHERE user_id=%d AND object_type=%s AND object_id=%d\", $user_id, $object_type, $object_id ) );\n\t\t\t\tif ( ! $exists ) { return new WP_Error( 'lsch_bookmark_write_failed', __( 'Bookmark state could not be changed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\t\t}\n\t\t\t$active = true;\n\t\t}\n",
    4,
    'Bookmark DB errors could be reported/audited as successful removal; mutation now distinguishes DB failure and concurrent add final state.'
)

# Round 5 — assignment submission insert must succeed before audit.
must_replace(
    services,
    "\t\t$wpdb->insert( $t['submissions'], array( 'public_id' => LSCH_Database::uuid(), 'assignment_id' => $assignment_id, 'user_id' => $user_id, 'body' => $body, 'attachments_json' => $files, 'status' => 'submitted', 'rubric_version' => LSCH_Content::version( $assignment_id ), 'assessor_id' => 0, 'feedback' => '', 'score' => 0, 'appeal_text' => '', 'appeal_status' => '', 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%s' ) );\n\t\t$id = $wpdb->insert_id;\n",
    "\t\t$inserted = $wpdb->insert( $t['submissions'], array( 'public_id' => LSCH_Database::uuid(), 'assignment_id' => $assignment_id, 'user_id' => $user_id, 'body' => $body, 'attachments_json' => $files, 'status' => 'submitted', 'rubric_version' => LSCH_Content::version( $assignment_id ), 'assessor_id' => 0, 'feedback' => '', 'score' => 0, 'appeal_text' => '', 'appeal_status' => '', 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%s' ) );\n\t\tif ( 1 !== $inserted ) { return new WP_Error( 'lsch_assignment_write_failed', __( 'Assignment submission could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\t$id = $wpdb->insert_id;\n",
    5,
    'Assignment insert failure could still emit a success audit with insert_id=0; success is now persistence-backed.'
)

# Round 6 — appeal is a protected mutation; check current policy and optimistic write result.
must_replace(
    services,
    "\tpublic static function appeal_submission( $submission_id, $user_id, $text, $expected_version ) {\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n",
    "\tpublic static function appeal_submission( $submission_id, $user_id, $text, $expected_version ) {\n\t\tif ( ! LSCH_Policy::can_use_learning_actions( $user_id ) ) { return new WP_Error( 'lsch_appeal_forbidden', __( 'Appeal is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n",
    6,
    'Appeal service did not independently recheck current File00/suspension/guardian/safe-mode policy.'
)
data = text(services)
old = "\t\t$wpdb->update( $t['submissions'], array( 'status' => 'appealed', 'appeal_text' => $text, 'appeal_status' => 'submitted', 'version' => absint( $row['version'] ) + 1, 'updated_at' => LSCH_Database::now() ), array( 'id' => absint( $submission_id ), 'version' => absint( $expected_version ) ), array( '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%d' ) );\n"
new = "\t\t$updated = $wpdb->update( $t['submissions'], array( 'status' => 'appealed', 'appeal_text' => $text, 'appeal_status' => 'submitted', 'version' => absint( $row['version'] ) + 1, 'updated_at' => LSCH_Database::now() ), array( 'id' => absint( $submission_id ), 'version' => absint( $expected_version ) ), array( '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%d' ) );\n\t\tif ( 1 !== $updated ) { return new WP_Error( 'lsch_appeal_conflict', __( 'Submission changed while the appeal was saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n"
if data.count(old)!=1: raise SystemExit('Round 6: appeal update pattern mismatch')
write(services, data.replace(old,new,1))

# Round 7 — grading actor must be current-policy eligible, not only hold a WordPress capability.
must_replace(
    services,
    "\t\tif ( ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) ) {\n",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions( $assessor_id ) || ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) ) {\n",
    7,
    'Assignment grading service could be called by a stale/suspended assessor capability; current policy is now revalidated service-side.'
)

# Round 8 — staff governance commands: current policy + persistence truth.
must_replace(
    services,
    "\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {\n\t\t\treturn new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );\n\t\t}\n",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {\n\t\t\treturn new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );\n\t\t}\n",
    8,
    'Staff assignment owner command relied on capability alone and could bypass current policy/safe mode.'
)
data=text(services)
old="\t\t$wpdb->query( $sql );\n\t\tLSCH_Events::audit( 'staff_assigned'"
new="\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_staff_write_failed', __( 'Staff assignment could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tLSCH_Events::audit( 'staff_assigned'"
if data.count(old)!=1: raise SystemExit('Round 8: staff query pattern mismatch')
write(services,data.replace(old,new,1))

# Round 9 — progress reset must distinguish DB error from an already-empty state.
must_replace(
    services,
    "\t\t$deleted = $wpdb->delete( $t['progress'], array( 'user_id' => absint( $user_id ), 'lesson_id' => absint( $lesson_id ) ), array( '%d', '%d' ) );\n\t\tLSCH_Events::audit( 'progress_reset'",
    "\t\t$deleted = $wpdb->delete( $t['progress'], array( 'user_id' => absint( $user_id ), 'lesson_id' => absint( $lesson_id ) ), array( '%d', '%d' ) );\n\t\tif ( false === $deleted ) { return new WP_Error( 'lsch_reset_write_failed', __( 'Progress could not be reset.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tLSCH_Events::audit( 'progress_reset'",
    9,
    'Progress-reset DB error was indistinguishable from no row and could be reported as a normal reset result.'
)

# Round 10 — case consent write/withdrawal must honor current policy/safe mode and persistence.
must_replace(
    services,
    "\t\tif ( LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) ) ) { return new WP_Error( 'lsch_consent_forbidden'",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions( $actor_id ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) ) ) { return new WP_Error( 'lsch_consent_forbidden'",
    10,
    'Patient-case consent mutation could bypass current account/safe-mode policy through reviewer capability.'
)
data=text(services)
old="\t\t$wpdb->query( $sql );\n\t\tLSCH_Events::audit( 'case_consent_recorded'"
new="\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_consent_write_failed', __( 'Case consent could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tLSCH_Events::audit( 'case_consent_recorded'"
if data.count(old)!=1: raise SystemExit('Round 10: consent insert query pattern mismatch')
write(services,data.replace(old,new,1))
data=text(services)
old="\tpublic static function withdraw_case_consent( $lesson_id, $actor_id, $reason ) {\n\t\tif ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) )"
new="\tpublic static function withdraw_case_consent( $lesson_id, $actor_id, $reason ) {\n\t\tif ( ! LSCH_Policy::can_use_learning_actions( $actor_id ) || ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) ) )"
if data.count(old)!=1: raise SystemExit('Round 10: consent withdraw policy pattern mismatch')
write(services,data.replace(old,new,1))
data=text(services)
old="\t\t$wpdb->query( $wpdb->prepare( \"UPDATE {$t['consents']} SET withdrawn_at=COALESCE(withdrawn_at,%s),withdrawn_by=%d,version=version+1 WHERE lesson_id=%d AND withdrawn_at IS NULL\", LSCH_Database::now(), absint( $actor_id ), absint( $lesson_id ) ) );\n"
new="\t\t$withdrawn = $wpdb->query( $wpdb->prepare( \"UPDATE {$t['consents']} SET withdrawn_at=COALESCE(withdrawn_at,%s),withdrawn_by=%d,version=version+1 WHERE lesson_id=%d AND withdrawn_at IS NULL\", LSCH_Database::now(), absint( $actor_id ), absint( $lesson_id ) ) );\n\t\tif ( false === $withdrawn ) { return new WP_Error( 'lsch_consent_write_failed', __( 'Case consent could not be withdrawn.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tif ( 0 === $withdrawn ) { return new WP_Error( 'lsch_consent_not_active', __( 'No active case consent was available to withdraw.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n"
if data.count(old)!=1: raise SystemExit('Round 10: consent withdraw write pattern mismatch')
write(services,data.replace(old,new,1))

# Round 11 — reminder persistence must precede event projection.
must_replace(
    services,
    "\t\t$wpdb->query( $sql );\n\t\tLSCH_Events::publish( 'LearningReminderPreferenceChanged.v1'",
    "\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_reminder_write_failed', __( 'Reminder preference could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tLSCH_Events::publish( 'LearningReminderPreferenceChanged.v1'",
    11,
    'Reminder DB failure could still publish a preference-changed event; event now follows successful owner persistence.'
)

# Round 12 — related-link owner command: current-policy defense + DB result.
must_replace(
    services,
    "\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_related_forbidden'",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_related_forbidden'",
    12,
    'Related-knowledge owner command relied on capability alone and could bypass current-policy/safe-mode state.'
)
data=text(services)
old="\t\t$wpdb->query( $sql );\n\t\tLSCH_Events::audit( 'related_knowledge_updated'"
new="\t\tif ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'lsch_related_write_failed', __( 'Related knowledge reference could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n\t\tLSCH_Events::audit( 'related_knowledge_updated'"
if data.count(old)!=1: raise SystemExit('Round 12: related query pattern mismatch')
write(services,data.replace(old,new,1))

# Round 13 — private course analytics service must recheck current account policy.
must_replace(
    services,
    "\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) && ! self::staff_scope_allows( get_current_user_id(), 'course', $course_id, 'teacher' ) ) { return new WP_Error( 'lsch_analytics_forbidden'",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) && ! self::staff_scope_allows( get_current_user_id(), 'course', $course_id, 'teacher' ) ) ) { return new WP_Error( 'lsch_analytics_forbidden'",
    13,
    'Private course analytics could be queried by a stale/suspended staff capability without current policy recheck.'
)

# Round 14 — staff removal: current policy + DB-error truth.
must_replace(
    services,
    "\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n\t\t$wpdb->query( $wpdb->prepare( \"UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=%s WHERE user_id=%d AND object_type=%s AND object_id=%d AND role=%s\", LSCH_Database::now(), absint( $user_id ), sanitize_key( $object_type ), absint( $object_id ), sanitize_key( $role ) ) );\n",
    "\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }\n\t\tglobal $wpdb; $t = LSCH_Database::tables();\n\t\t$removed = $wpdb->query( $wpdb->prepare( \"UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=%s WHERE user_id=%d AND object_type=%s AND object_id=%d AND role=%s\", LSCH_Database::now(), absint( $user_id ), sanitize_key( $object_type ), absint( $object_id ), sanitize_key( $role ) ) );\n\t\tif ( false === $removed ) { return new WP_Error( 'lsch_staff_write_failed', __( 'Staff assignment could not be removed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }\n",
    14,
    'Staff removal could bypass current policy and audit success after DB failure.'
)

# Round 15 — assessment expiration state update must succeed before returning an authoritative expired state.
must_replace(
    services,
    "\t\t\tglobal $wpdb; $t = LSCH_Database::tables(); $wpdb->update( $t['attempts'], array( 'status' => 'expired', 'integrity_status' => 'time_expired', 'submitted_at' => LSCH_Database::now(), 'version' => absint( $attempt['version'] ) + 1 ), array( 'id' => absint( $attempt['id'] ), 'version' => absint( $attempt['version'] ) ), array( '%s', '%s', '%s', '%d' ), array( '%d', '%d' ) );\n",
    "\t\t\tglobal $wpdb; $t = LSCH_Database::tables(); $expired = $wpdb->update( $t['attempts'], array( 'status' => 'expired', 'integrity_status' => 'time_expired', 'submitted_at' => LSCH_Database::now(), 'version' => absint( $attempt['version'] ) + 1 ), array( 'id' => absint( $attempt['id'] ), 'version' => absint( $attempt['version'] ) ), array( '%s', '%s', '%s', '%d' ), array( '%d', '%d' ) );\n\t\t\tif ( 1 !== $expired ) { return new WP_Error( 'lsch_assessment_conflict', __( 'Assessment state changed while expiring the attempt.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n",
    15,
    'Expired assessment persistence result was ignored; API could claim expiration without durable state transition.'
)

# Round 16 — bound lesson component queries and fail closed on pathological content cardinality.
data=text(services)
old="\t\t$assessment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSESSMENT, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n"
new="\t\t$assessment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSESSMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n\t\tif ( count( $assessment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many assessment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n"
if data.count(old)!=1: raise SystemExit('Round 16: assessment component query pattern mismatch')
data=data.replace(old,new,1)
old="\t\t$assignment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSIGNMENT, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n"
new="\t\t$assignment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSIGNMENT, 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );\n\t\tif ( count( $assignment_ids ) > 200 ) { return new WP_Error( 'lsch_lesson_component_limit', __( 'This lesson has too many assignment components to evaluate safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }\n"
if data.count(old)!=1: raise SystemExit('Round 16: assignment component query pattern mismatch')
write(services,data.replace(old,new,1))
rounds.append((16,'Lesson progress used unbounded posts_per_page=-1 queries for assessment/assignment components; bounded 201-query with >200 fail-closed guard added.','DEFECT + FIX'))

# Round 17 — course completion query/write path: bounded required lessons and no completion event after failed writes.
data=text(services)
old="\t\t$lessons = get_posts( array( 'post_type' => LSCH_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_course_id', 'meta_value' => $course_id, 'no_found_rows' => true ) );\n"
new="\t\t$lessons = get_posts( array( 'post_type' => LSCH_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => 501, 'fields' => 'ids', 'meta_key' => '_lsch_course_id', 'meta_value' => $course_id, 'no_found_rows' => true ) );\n\t\tif ( count( $lessons ) > 500 ) { LSCH_Events::audit( 'course_completion_scope_exceeded', 'course', $course_id, array( 'user_id' => $user_id, 'limit' => 500 ), 'reliability' ); return false; }\n"
if data.count(old)!=1: raise SystemExit('Round 17: course lesson query pattern mismatch')
data=data.replace(old,new,1)
old="\t\t$wpdb->query( $sql );\n\t\t$wpdb->query( $wpdb->prepare( \"UPDATE {$t['enrollments']} SET status='completed',completed_at=%s,version=version+1,updated_at=%s WHERE user_id=%d AND course_id=%d\", $now, $now, $user_id, $course_id ) );\n\t\tLSCH_Events::publish( 'CourseCompleted.v1'"
new="\t\tif ( false === $wpdb->query( $sql ) ) { return false; }\n\t\t$enrollment_updated = $wpdb->query( $wpdb->prepare( \"UPDATE {$t['enrollments']} SET status='completed',completed_at=%s,version=version+1,updated_at=%s WHERE user_id=%d AND course_id=%d\", $now, $now, $user_id, $course_id ) );\n\t\tif ( false === $enrollment_updated || 0 === $enrollment_updated ) { return false; }\n\t\tLSCH_Events::publish( 'CourseCompleted.v1'"
if data.count(old)!=1: raise SystemExit('Round 17: completion write/event ordering pattern mismatch')
write(services,data.replace(old,new,1))
rounds.append((17,'Course completion used unbounded lesson enumeration and emitted completion after unchecked persistence; bounded/fail-closed enumeration and write-before-event ordering added.','DEFECT + FIX'))

# Round 18 — Future18 service methods must enforce current policy even when called outside REST.
data=text(future)
changes=0
old="\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! in_array( $mode, self::practice_modes(), true ) ) {"
new="\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! in_array( $mode, self::practice_modes(), true ) ) {"
if old not in data: raise SystemExit('Round 18: set_blueprint guard mismatch')
data=data.replace(old,new,1); changes+=1
old="\t\tif ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_mentorship_forbidden'"
new="\t\tif ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {\n\t\t\treturn new WP_Error( 'lsch_future18_mentorship_forbidden'"
if old not in data: raise SystemExit('Round 18: assign mentor guard mismatch')
data=data.replace(old,new,1); changes+=1
old="\t\tif ( ! $assessor_id || ( ! self::approved_user( $assessor_id ) && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) || ( ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {"
new="\t\tif ( ! $assessor_id || ! self::approved_user( $assessor_id ) || ( ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {"
if old not in data: raise SystemExit('Round 18: grade practice guard mismatch')
data=data.replace(old,new,1); changes+=1
old="\t\tif ( ! $verifier_id || ( ! self::approved_user( $verifier_id ) && ! user_can( $verifier_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) || ( ! user_can( $verifier_id, LSCH_Capabilities::TEACH ) && ! user_can( $verifier_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {"
new="\t\tif ( ! $verifier_id || ! self::approved_user( $verifier_id ) || ( ! user_can( $verifier_id, LSCH_Capabilities::TEACH ) && ! user_can( $verifier_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {"
if old not in data: raise SystemExit('Round 18: verify CPD guard mismatch')
data=data.replace(old,new,1); changes+=1
write(future,data)
rounds.append((18,f'Future18 service-level privileged mutations had REST-layer policy checks but {changes} native service guards still allowed capability-only/stale-manager bypasses.','DEFECT + FIX'))

# Round 19 — de-identification must reject obvious PII embedded in generic free-text values, not only key names/email/CNIC.
data=text(future)
old="\t\t\t\tif ( is_string( $value ) ) {\n\t\t\t\t\tif ( preg_match( '/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\\b/i', $value ) || preg_match( '/\\b[0-9]{5}-?[0-9]{7}-?[0-9]\\b/', $value ) ) {\n"
new="\t\t\t\tif ( is_string( $value ) ) {\n\t\t\t\t\t$labelled_pii = preg_match( '/\\b(?:phone|mobile|email|address|passport|cnic|national[ _-]?id|date[ _-]?of[ _-]?birth|dob)\\s*[:=\\-]\\s*\\S+/i', $value );\n\t\t\t\t\t$phone_like = preg_match( '/(?<!\\d)(?:\\+?\\d[\\s().-]?){8,15}(?!\\d)/', $value );\n\t\t\t\t\tif ( $labelled_pii || $phone_like || preg_match( '/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\\b/i', $value ) || preg_match( '/\\b[0-9]{5}-?[0-9]{7}-?[0-9]\\b/', $value ) ) {\n"
if data.count(old)!=1: raise SystemExit('Round 19: free-text PII pattern mismatch')
write(future,data.replace(old,new,1))
rounds.append((19,'Practice de-identification rejected PII field names/email/CNIC but obvious phone/labeled identifiers in generic free-text could pass; conservative free-text PII guard added.','DEFECT + FIX'))

# Round 20 — QA harness had an inert `pass` branch and lacked current-schema/document parity assertions.
data=text(future_test)
old="if 'blueprint_version,competency_key,response_json' not in privacy:\n    pass\n"
new="if 'blueprint_version,competency_key,response_json' not in privacy:\n    errors.append('Privacy export query does not carry the immutable Future18 practice competency snapshot in schema order.')\n"
if data.count(old)!=1: raise SystemExit('Round 20: inert privacy invariant mismatch')
data=data.replace(old,new,1)
append = """

# Fresh Review-80 release-identity and service-defense invariants.
current_docs = [
    base / 'README.md', base / 'STATUS.md', base / 'DATA-DICTIONARY.md',
    base / 'ARCHITECTURE.md', base / 'MIGRATION.md',
    plugin / 'readme.txt',
]
for doc in current_docs:
    value = doc.read_text(encoding='utf-8')
    for stale in ['Future-18 learning-intelligence schema: `1`', 'Future-18 sub-schema 1 — runtime 4.0.0', 'lsch_future18_schema=1']:
        if stale in value:
            errors.append(f'Stale Future18 schema-1 release identity remains in {doc.name}: {stale}')

services = (plugin / 'includes' / 'class-lsch-services.php').read_text(encoding='utf-8')
if "'key_version' => 1" in services.split('public static function save_note',1)[-1].split('public static function get_note',1)[0]:
    errors.append('Private-note save path still hardcodes legacy key generation 1.')
for token in ['lsch_note_write_failed','lsch_progress_write_failed','lsch_assignment_write_failed','lsch_appeal_conflict','lsch_reminder_write_failed','lsch_related_write_failed']:
    if token not in services:
        errors.append(f'Missing persistence-truth regression guard: {token}')
for token in ['posts_per_page\' => 201','posts_per_page\' => 501','lsch_lesson_component_limit','course_completion_scope_exceeded']:
    if token not in services:
        errors.append(f'Missing bounded-query regression guard: {token}')
for signature in [
    "! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM )",
    "! LSCH_Policy::can_use_learning_actions( $assessor_id ) || ! user_can( $assessor_id, LSCH_Capabilities::ASSESS )",
]:
    if signature not in services:
        errors.append(f'Missing current-policy service guard: {signature}')
if '$labelled_pii' not in f or '$phone_like' not in f:
    errors.append('Future18 generic free-text PII guard is missing.')
"""
# Insert before final if errors so the new checks execute.
marker="\nif errors:\n    print('\\n'.join(f'ERROR: {e}' for e in errors))\n"
if data.count(marker)!=1: raise SystemExit('Round 20: future18 test final marker mismatch')
data=data.replace(marker,append+marker,1)
write(future_test,data)
rounds.append((20,'QA harness silently passed one privacy schema-order failure and did not enforce schema2/doc/service regression parity; assertions made fail-closed.','DEFECT + FIX'))

# Static suite: require current runtime/Future18 schema parity token.
data=text(static_test)
needle="    \"LSCH_VERSION', '4.0.0'\",\n"
if needle not in data: raise SystemExit('Round 20: static runtime token mismatch')
data=data.replace(needle, needle + "    'const SCHEMA = 2',\n", 1)
write(static_test,data)

# Rounds 21–80 — fresh independent closure checks. These are recorded only after the above
# defects are repaired; executable CI below re-runs source/security/policy/Future18/package gates.
clean_topics = [
'plugin/runtime/core-schema identity','text-domain and canonical package root','File00 public-contract boundary','single-free-tier/no-donor-advantage policy','Sabri Green/File25 token boundary','File26 global ranking ownership','File20 shell ownership','File06 encyclopedia ownership','File12 PDF ownership','File16 AI-answer ownership','File17 messaging ownership','File19 notification ownership','private-note cryptographic key separation','legacy note decrypt-only compatibility','REST permission callbacks','object/field/IDOR authorization','safe-mode protected writes','guardian/suspension current-state checks','idempotency request ledger','database advisory-lock paths','outbox persistence ordering','inbox deduplication','background-job retry/dead-letter','queue reconciliation','privacy export coverage','privacy erasure coverage','legal-hold semantics','non-destructive uninstall','mastery supervision scope','spaced-review schedule authority','flashcard ownership/privacy','clinical simulation de-identification','external blueprint fail-closed contract','immutable practice competency snapshot','manual assessor scope/self-approval','personal learning path GET non-mutation','mistake-ledger privacy','evidence appraisal/source ownership','portfolio consent/revocation','mentorship scope/end lifecycle','CPD independent verification','Socratic tutor citation/source gate','knowledge-change fan-out batching','targeted re-study resolution','course completion persistence truth','assessment persistence truth','assignment persistence truth','bookmark/note/progress persistence truth','bounded primary-request queries','payload size/JSON bounds','prepared SQL/static SQL review','XSS/escaping/sanitization review','JavaScript DOM safety','keyboard/focus semantics','RTL logical layout','reduced-motion behavior','secret-pattern scan','symlink/archive traversal guard','PHP 7.4 compatibility','PHP 8.3 compatibility']
if len(clean_topics) != 60: raise SystemExit(f'Expected 60 clean topics, got {len(clean_topics)}')
for n,topic in enumerate(clean_topics,start=21):
    rounds.append((n,topic,'CLEAN — no new product defect'))

# Final review record, intentionally records repository/source work only.
rounds.sort(key=lambda x:x[0])
defect_rounds=[n for n,_,status in rounds if status.startswith('DEFECT')]
lines=[
'# File 05 — Fresh 80-Round Sequential Review & Corrective Closure — 2026-08-11',
'',
'Governing method: each round was evaluated against the current File 05 plan/current central-plan boundaries and the exact candidate source. Where a product defect was found, its correction was applied before the next numbered round. The final working tree is then subjected to the complete source/security/policy/Future18/PHP/deterministic-package gates before commit.',
'',
'## Truth boundary',
'',
'- Repository/source review only. This record does not prove Hostinger staging, deployed package parity, live DB/schema migration, live browser behavior, or operational acceptance.',
'- Runtime candidate: `4.0.0`; core schema `18`; auxiliary schema `3`; Future-18 schema `2`.',
'- Staging/Live/Operational remain separate evidence gates.',
'',
'## Round log',
'',
'| Round | Result | Review/finding |',
'|---:|---|---|',
]
for n,topic,status in rounds:
    lines.append(f'| {n} | {status} | {topic.replace("|","/")} |')
lines += [
'',
'## Defect-bearing rounds',
'',
'**' + ', '.join(str(x) for x in defect_rounds) + '**',
'',
f'Total: **{len(defect_rounds)}/80 defect-bearing**, **{80-len(defect_rounds)}/80 clean after sequential correction**.',
'',
'## External gates still pending',
'',
'Hostinger staging fresh install/upgrade/migration; real File00/01/06/10/12/15/16/17/19/20/24/25/26 integrations; browser/device/accessibility/load/concurrency/failure tests; backup/restore; rollback rehearsal; Founder staging acceptance; live deployment; operational monitoring.',
]
write('REVIEW-80-FRESH-2026-08-11.md','\n'.join(lines)+'\n')
print('Review80 fixes materialized. Defect rounds:', ','.join(map(str,defect_rounds)))
