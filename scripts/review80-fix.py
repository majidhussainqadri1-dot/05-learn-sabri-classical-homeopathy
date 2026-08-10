#!/usr/bin/env python3
from pathlib import Path
p=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s=p.read_text(encoding='utf-8')
old="\t\t$minutes = max( 1, min( 24 * 60, absint( $data['minutes'] ?? 0 ) ) );\n"
new="""\t\t$minutes = absint( $data['minutes'] ?? 0 );
\t\tif ( $minutes < 1 || $minutes > 24 * 60 ) {
\t\t\treturn new WP_Error( 'lsch_future18_cpd_minutes_invalid', __( 'CPD duration must be between 1 and 1440 minutes for one activity.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
\t\t}
"""
if old not in s: raise SystemExit('Round 10 CPD minutes target not found')
s=s.replace(old,new,1)
p.write_text(s,encoding='utf-8')
t=Path('tests/future18-invariants.py'); x=t.read_text(encoding='utf-8'); marker="# Read paths must not mutate personalized pathway state.\n"
check="""# CPD must reject missing/zero/implausibly long activity duration instead of fabricating one minute.
cpd = f.split('public static function record_cpd',1)[-1].split('public static function verify_cpd',1)[0]
if 'lsch_future18_cpd_minutes_invalid' not in cpd or '$minutes < 1 || $minutes > 24 * 60' not in cpd:
    errors.append('CPD duration validation is not fail-closed.')

"""
if marker not in x: raise SystemExit('Round 10 invariant marker missing')
if check.strip() not in x: x=x.replace(marker,check+marker,1)
t.write_text(x,encoding='utf-8')
