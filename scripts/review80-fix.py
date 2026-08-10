#!/usr/bin/env python3
from pathlib import Path

p = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = p.read_text(encoding='utf-8')
old = """\t\t$review_days = $new_score >= 90 ? 60 : ( $new_score >= 80 ? 30 : ( $new_score >= 65 ? 14 : ( $new_score >= 50 ? 7 : 2 ) ) );
\t\t$now = current_time( 'mysql', true );
\t\t$next = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * $review_days );
"""
new = """\t\t$review_days = $new_score >= 90 ? 60 : ( $new_score >= 80 ? 30 : ( $new_score >= 65 ? 14 : ( $new_score >= 50 ? 7 : 2 ) ) );
\t\t$now = current_time( 'mysql', true );
\t\t$next = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * $review_days );
\t\tif ( $schedule_review ) {
\t\t\t$scheduled_due = $wpdb->get_var( $wpdb->prepare( \"SELECT due_at FROM {$t['review']} WHERE user_id=%d AND item_type='spaced' AND competency_key=%s LIMIT 1\", $user_id, $competency ) );
\t\t\tif ( is_string( $scheduled_due ) && '' !== $scheduled_due ) {
\t\t\t\t$next = $scheduled_due;
\t\t\t}
\t\t}
"""
if old not in s:
    raise SystemExit('Round 04 mastery schedule target not found')
s = s.replace(old, new, 1)
old2 = """\t\t$id = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['review']} WHERE user_id=%d AND item_type='spaced' AND competency_key=%s LIMIT 1\", $user_id, $competency ) );
\t\t$now = current_time( 'mysql', true );
\t\tif ( $id ) {
\t\t\t$wpdb->update( $t['review'], array( 'due_at' => $due_at, 'updated_at' => $now ), array( 'id' => absint( $id ) ), array( '%s', '%s' ), array( '%d' ) );
\t\t\treturn;
\t\t}
"""
new2 = """\t\t$id = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['review']} WHERE user_id=%d AND item_type='spaced' AND competency_key=%s LIMIT 1\", $user_id, $competency ) );
\t\t$now = current_time( 'mysql', true );
\t\tif ( $id ) {
\t\t\t/* Once a spaced-review item exists, record_review_result owns its due schedule. */
\t\t\treturn;
\t\t}
"""
if old2 not in s:
    raise SystemExit('Round 04 review overwrite target not found')
s = s.replace(old2, new2, 1)
p.write_text(s, encoding='utf-8')

t = Path('tests/future18-invariants.py')
x = t.read_text(encoding='utf-8')
old_check = """# Review scheduling must not be overwritten by mastery evidence recalculation.
review_result = f.split('public static function record_review_result',1)[-1].split('public static function add_mistake',1)[0]
if \"'review_item', $id, false\" not in review_result:
    errors.append('Spaced review result does not preserve its own scheduling interval.')
"""
new_check = """# Review scheduling must not be overwritten by later mastery evidence recalculation.
review_result = f.split('public static function record_review_result',1)[-1].split('public static function add_mistake',1)[0]
if \"'review_item', $id, false\" not in review_result:
    errors.append('Spaced review result does not preserve its own scheduling interval.')
ensure_review = f.split('private static function ensure_competency_review_item',1)[-1].split('public static function mastery_snapshot',1)[0]
if \"$wpdb->update( $t['review'], array( 'due_at' => $due_at\" in ensure_review:
    errors.append('Mastery evidence still overwrites an established spaced-review due schedule.')
if \"SELECT due_at FROM {$t['review']}\" not in f or '$next = $scheduled_due;' not in f:
    errors.append('Mastery state does not preserve the authoritative existing spaced-review due date.')
"""
if old_check not in x:
    raise SystemExit('Round 04 invariant target not found')
x = x.replace(old_check, new_check, 1)
t.write_text(x, encoding='utf-8')
