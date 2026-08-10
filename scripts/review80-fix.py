#!/usr/bin/env python3
from pathlib import Path

p = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = p.read_text(encoding='utf-8')
old = """\t\t$external = apply_filters( 'lsch_future18_blueprint', null, $mode, $source_type, $source_id, $user_id );
\t\tif ( is_array( $external ) ) {
\t\t\treturn $external;
\t\t}
"""
new = """\t\t$external = apply_filters( 'lsch_future18_blueprint', null, $mode, $source_type, $source_id, $user_id );
\t\tif ( is_array( $external ) ) {
\t\t\t$external_allowed = apply_filters( 'lsch_future18_blueprint_access', false, $mode, $source_type, $source_id, $user_id, $external );
\t\t\tif ( true !== $external_allowed ) {
\t\t\t\treturn new WP_Error( 'lsch_future18_external_blueprint_forbidden', __( 'The external learning owner did not authorize this governed practice object.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
\t\t\t}
\t\t\treturn $external;
\t\t}
"""
if old not in s:
    raise SystemExit('Round 06 external blueprint target not found')
s = s.replace(old, new, 1)
p.write_text(s, encoding='utf-8')

t = Path('tests/future18-invariants.py')
x = t.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# External canonical owners must explicitly authorize practice-blueprint access; adapters fail closed.\nblueprint = f.split('private static function blueprint',1)[-1].split('private static function auto_score',1)[0]\nif \"lsch_future18_blueprint_access\" not in blueprint or \"true !== $external_allowed\" not in blueprint:\n    errors.append('External practice-blueprint access is not fail-closed through an owner authorization contract.')\n\n"
if marker not in x:
    raise SystemExit('Round 06 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
t.write_text(x, encoding='utf-8')
