#!/usr/bin/env python3
from pathlib import Path

p = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = p.read_text(encoding='utf-8')
if "\tconst SCHEMA = 1;" not in s:
    raise SystemExit('Round 07 Future-18 schema target not found')
s = s.replace("\tconst SCHEMA = 1;", "\tconst SCHEMA = 2;", 1)
old_schema = """\t\t\tblueprint_version bigint(20) unsigned NOT NULL DEFAULT 1,
\t\t\tresponse_json longtext NOT NULL,
"""
new_schema = """\t\t\tblueprint_version bigint(20) unsigned NOT NULL DEFAULT 1,
\t\t\tcompetency_key varchar(96) NOT NULL DEFAULT '',
\t\t\tresponse_json longtext NOT NULL,
"""
if old_schema not in s:
    raise SystemExit('Round 07 practice schema target not found')
s = s.replace(old_schema, new_schema, 1)
old_insert = """\t\t$public_id = LSCH_Database::uuid();
\t\t$ok = $wpdb->insert( $t['practice'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'mode' => $mode, 'source_type' => sanitize_key( $source_type ), 'source_id' => absint( $source_id ), 'blueprint_version' => max( 1, absint( $blueprint['version'] ?? 1 ) ), 'response_json' => $response_json, 'feedback_json' => $feedback_json, 'score' => (float) $scored['score'], 'status' => $status, 'assessor_id' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%s' ) );
"""
new_insert = """\t\t$public_id = LSCH_Database::uuid();
\t\t$competency = self::competency_key( $blueprint['competency_key'] ?? $mode );
\t\t$ok = $wpdb->insert( $t['practice'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'mode' => $mode, 'source_type' => sanitize_key( $source_type ), 'source_id' => absint( $source_id ), 'blueprint_version' => max( 1, absint( $blueprint['version'] ?? 1 ) ), 'competency_key' => $competency, 'response_json' => $response_json, 'feedback_json' => $feedback_json, 'score' => (float) $scored['score'], 'status' => $status, 'assessor_id' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%s' ) );
"""
if old_insert not in s:
    raise SystemExit('Round 07 practice insert target not found')
s = s.replace(old_insert, new_insert, 1)
old_dup = "\t\t$competency = self::competency_key( $blueprint['competency_key'] ?? $mode );\n\t\tif ( 'graded' === $status ) {"
if old_dup not in s:
    raise SystemExit('Round 07 duplicate competency target not found')
s = s.replace(old_dup, "\t\tif ( 'graded' === $status ) {", 1)
old_grade = """\t\t$blueprint = self::blueprint( $row['source_type'], absint( $row['source_id'] ), $row['mode'], absint( $row['user_id'] ) );
\t\t$competency = is_wp_error( $blueprint ) ? self::competency_key( $row['mode'] ) : self::competency_key( $blueprint['competency_key'] ?? $row['mode'] );
"""
new_grade = """\t\t/* Grade against the competency snapshot captured at submission, never the mutable current blueprint. */
\t\t$competency = self::competency_key( ! empty( $row['competency_key'] ) ? $row['competency_key'] : $row['mode'] );
"""
if old_grade not in s:
    raise SystemExit('Round 07 manual-grade blueprint drift target not found')
s = s.replace(old_grade, new_grade, 1)
p.write_text(s, encoding='utf-8')

t = Path('tests/future18-invariants.py')
x = t.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# Manual practice grading must use the competency snapshot captured with the submitted blueprint version.\nif 'const SCHEMA = 2' not in f or \"competency_key varchar(96) NOT NULL DEFAULT ''\" not in f:\n    errors.append('Future-18 practice competency snapshot migration is missing.')\ngrade = f.split('public static function grade_practice',1)[-1].split('public static function build_learning_path',1)[0]\nif \"$row['competency_key']\" not in grade or 'self::blueprint(' in grade:\n    errors.append('Manual practice grading can drift to a later mutable blueprint competency.')\n\n"
if marker not in x:
    raise SystemExit('Round 07 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
t.write_text(x, encoding='utf-8')
