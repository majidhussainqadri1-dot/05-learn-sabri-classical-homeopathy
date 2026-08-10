#!/usr/bin/env python3
from pathlib import Path

p = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = p.read_text(encoding='utf-8')
old = """\t/** Manager or the learner's active assigned mentor may supervise learning state. */
\tprivate static function can_supervise_user( $actor_id, $learner_id ) {
\t\t$actor_id = absint( $actor_id );
\t\t$learner_id = absint( $learner_id );
\t\tif ( ! $actor_id || ! $learner_id || $actor_id === $learner_id ) {
\t\t\treturn false;
\t\t}
\t\tif ( LSCH_Policy::central_policy_ready() && user_can( $actor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
\t\t\treturn true;
\t\t}
\t\tglobal $wpdb;
\t\t$t = self::tables();
\t\treturn (bool) $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['mentorship']} WHERE mentor_id=%d AND learner_id=%d AND status='active' LIMIT 1\", $actor_id, $learner_id ) );
\t}
"""
new = """\t/** Manager, active mentor, or conflict-cleared assigned teacher/assessor may supervise bounded learning state. */
\tprivate static function can_supervise_user( $actor_id, $learner_id, $source_type = '', $source_id = 0 ) {
\t\t$actor_id = absint( $actor_id );
\t\t$learner_id = absint( $learner_id );
\t\t$source_type = sanitize_key( $source_type );
\t\t$source_id = absint( $source_id );
\t\tif ( ! $actor_id || ! $learner_id || $actor_id === $learner_id || ! self::approved_user( $actor_id ) || ! self::approved_user( $learner_id ) ) {
\t\t\treturn false;
\t\t}
\t\tif ( user_can( $actor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
\t\t\treturn true;
\t\t}
\t\tglobal $wpdb;
\t\t$t = self::tables();
\t\tif ( user_can( $actor_id, LSCH_Capabilities::TEACH ) && $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['mentorship']} WHERE mentor_id=%d AND learner_id=%d AND status='active' LIMIT 1\", $actor_id, $learner_id ) ) ) {
\t\t\treturn true;
\t\t}
\t\tif ( ! $source_id || ! $source_type ) {
\t\t\treturn false;
\t\t}
\t\t$core = LSCH_Database::tables();
\t\treturn (bool) $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$core['staff']} WHERE user_id=%d AND object_type=%s AND object_id=%d AND role IN ('teacher','assessor') AND conflict_status='clear' AND active=1 LIMIT 1\", $actor_id, $source_type, $source_id ) );
\t}
"""
if old not in s:
    raise SystemExit('Round 03 can_supervise_user target not found')
s = s.replace(old, new, 1)
old2 = """\t\tif ( LSCH_Policy::central_policy_ready() && user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
\t\t\treturn true;
\t\t}
"""
new2 = """\t\tif ( self::approved_user( $assessor_id ) && user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
\t\t\treturn true;
\t\t}
"""
if old2 not in s:
    raise SystemExit('Round 03 assessor manager target not found')
s = s.replace(old2, new2, 1)
old3 = "\t\tif ( ! self::can_supervise_user( $actor_id, $user_id ) ) {"
new3 = "\t\tif ( ! self::can_supervise_user( $actor_id, $user_id, $source_type, $source_id ) ) {"
if old3 not in s:
    raise SystemExit('Round 03 mastery actor target not found')
s = s.replace(old3, new3, 1)
p.write_text(s, encoding='utf-8')

t = Path('tests/future18-invariants.py')
x = t.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# Manual mastery supervision must be current-policy eligible and bounded to manager, active mentor, or assigned teacher/assessor.\nif \"can_supervise_user( $actor_id, $user_id, $source_type, $source_id )\" not in f or \"role IN ('teacher','assessor')\" not in f or \"! self::approved_user( $actor_id )\" not in f:\n    errors.append('Manual mastery supervision scope/current-eligibility guard is incomplete.')\n\n"
if marker not in x:
    raise SystemExit('Round 03 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
t.write_text(x, encoding='utf-8')
