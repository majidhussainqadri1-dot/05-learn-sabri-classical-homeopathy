#!/usr/bin/env python3
from pathlib import Path
import subprocess

ROOT=Path('.')
F=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
R=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php')
V=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php')
CORE_R=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php')
T=Path('tests/future18-invariants.py')
STATIC=Path('tests/static-invariants.py')
SH=Path('tests/source-invariants.sh')

def verify(n,label):
    print(f'ROUND {n:02d} VERIFY — {label}', flush=True)
    subprocess.run(['bash','tests/source-invariants.sh'],check=True)
    print(f'ROUND {n:02d} COMPLETE', flush=True)

def add_test(check):
    x=T.read_text(encoding='utf-8'); marker="# Read paths must not mutate personalized pathway state.\n"
    if marker not in x: raise SystemExit('Future18 invariant marker missing')
    if check.strip() not in x: x=x.replace(marker,check+marker,1)
    T.write_text(x,encoding='utf-8')

# ROUND 11 — tutor citations must be approved against lesson source context / explicit owner adapter.
s=F.read_text(encoding='utf-8')
old="""\t\t$citations = array();
\t\tforeach ( (array) ( $result['citations'] ?? array() ) as $citation ) {
"""
new="""\t\t$citations = array();
\t\t$approved_sources = (array) $context['sources'];
\t\tforeach ( (array) ( $result['citations'] ?? array() ) as $citation ) {
"""
if old not in s: raise SystemExit('Round 11 citation setup target not found')
s=s.replace(old,new,1)
old2="""\t\t\tif ( $item['object_id'] || $item['url'] ) { $citations[] = $item; }
"""
new2="""\t\t\t$approved = false;
\t\t\tforeach ( $approved_sources as $source ) {
\t\t\t\tif ( ! is_array( $source ) ) { continue; }
\t\t\t\t$source_url = esc_url_raw( (string) ( $source['url'] ?? '' ) );
\t\t\t\t$source_title = sanitize_text_field( (string) ( $source['title'] ?? '' ) );
\t\t\t\tif ( $item['url'] && $source_url && hash_equals( $source_url, $item['url'] ) ) { $approved = true; break; }
\t\t\t\tif ( $item['title'] && $source_title && 0 === strcasecmp( trim( $source_title ), trim( $item['title'] ) ) ) { $approved = true; break; }
\t\t\t}
\t\t\t$approved = (bool) apply_filters( 'lsch_future18_tutor_citation_approved', $approved, $item, $approved_sources, $lesson_id, $user_id );
\t\t\tif ( $approved && ( $item['object_id'] || $item['url'] ) ) { $citations[] = $item; }
"""
if old2 not in s: raise SystemExit('Round 11 citation approval target not found')
s=s.replace(old2,new2,1); F.write_text(s,encoding='utf-8')
add_test("""# Tutor citations must match declared lesson sources or be explicitly approved by the canonical-source adapter.
tutor = f.split('public static function socratic_tutor',1)[-1].split('public static function event_published',1)[0]
if 'lsch_future18_tutor_citation_approved' not in tutor or '$approved_sources' not in tutor or 'if ( $approved &&' not in tutor:
    errors.append('Socratic tutor citations are sanitized but not fail-closed against approved source context.')

""")
verify(11,'approved-source Socratic tutor citations')

# ROUND 12 — change-impact fan-out must not truncate at 2000; use retryable background batches.
s=F.read_text(encoding='utf-8')
hook="\t\tadd_action( 'lsch_event_published', array( __CLASS__, 'event_published' ), 10, 5 );\n"
if hook not in s: raise SystemExit('Round 12 hook target missing')
s=s.replace(hook,hook+"\t\tadd_filter( 'lsch_run_job', array( __CLASS__, 'run_job' ), 10, 4 );\n",1)
old_block="""\t\t$users = $wpdb->get_col( $wpdb->prepare( \"SELECT DISTINCT user_id FROM {$core['progress']} WHERE lesson_id=%d ORDER BY user_id ASC LIMIT 2000\", $object_id ) );
\t\t$now = current_time( 'mysql', true );
\t\tforeach ( $users as $user_id ) {
\t\t\tforeach ( $competencies as $competency ) {
\t\t\t\t$wpdb->query( $wpdb->prepare( \"INSERT IGNORE INTO {$t['impacts']} (event_id,user_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at) VALUES (%s,%d,%s,%d,%d,%d,%s,%s,'targeted_review','pending',%s)\", sanitize_text_field( $event_id ), absint( $user_id ), sanitize_key( $aggregate_type ), $object_id, $previous, $new, $competency, $summary, $now ) );
\t\t\t}
\t\t}
\t\tLSCH_Events::publish( 'LearningRestudyRequired.v1', sanitize_key( $aggregate_type ), $object_id, array( 'affected_user_count' => count( $users ), 'previous_version' => $previous, 'new_version' => $new ) );
"""
new_block="""\t\t$queued = LSCH_Events::enqueue( 'future18_impact_batch', array( 'event_id' => sanitize_text_field( $event_id ), 'object_type' => sanitize_key( $aggregate_type ), 'object_id' => $object_id, 'previous_version' => $previous, 'new_version' => $new, 'competencies' => $competencies, 'change_summary' => $summary, 'cursor_user_id' => 0, 'affected_user_count' => 0 ), null, 8, 'f18-impact-' . sanitize_text_field( $event_id ) . '-0' );
\t\tif ( ! $queued ) {
\t\t\tLSCH_Events::audit( 'future18_impact_enqueue_failed', sanitize_key( $aggregate_type ), $object_id, array( 'event_id' => sanitize_text_field( $event_id ) ), 'learning' );
\t\t}
"""
if old_block not in s: raise SystemExit('Round 12 synchronous impact block target missing')
s=s.replace(old_block,new_block,1)
insert_before="\tpublic static function impacts( $user_id, $status = 'pending' ) {"
worker=r'''	public static function run_job( $result, $job_type, $payload, $job_key ) {
		if ( 'future18_impact_batch' !== $job_type ) { return $result; }
		$payload = is_array( $payload ) ? $payload : array();
		$event_id = sanitize_text_field( (string) ( $payload['event_id'] ?? '' ) );
		$object_type = sanitize_key( (string) ( $payload['object_type'] ?? '' ) );
		$object_id = absint( $payload['object_id'] ?? 0 );
		if ( ! $event_id || ! $object_id ) { return false; }
		$previous = absint( $payload['previous_version'] ?? 0 );
		$new = absint( $payload['new_version'] ?? 0 );
		$summary = sanitize_textarea_field( (string) ( $payload['change_summary'] ?? '' ) );
		$competencies = array_slice( array_values( array_unique( array_map( array( __CLASS__, 'competency_key' ), (array) ( $payload['competencies'] ?? array( '' ) ) ) ) ), 0, 20 );
		if ( ! $competencies ) { $competencies = array( '' ); }
		$cursor = absint( $payload['cursor_user_id'] ?? 0 );
		global $wpdb;
		$core = LSCH_Database::tables();
		$t = self::tables();
		$users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$core['progress']} WHERE lesson_id=%d AND user_id>%d ORDER BY user_id ASC LIMIT 500", $object_id, $cursor ) );
		$now = current_time( 'mysql', true );
		foreach ( $users as $user_id ) {
			foreach ( $competencies as $competency ) {
				$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$t['impacts']} (event_id,user_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at) VALUES (%s,%d,%s,%d,%d,%d,%s,%s,'targeted_review','pending',%s)", $event_id, absint( $user_id ), $object_type, $object_id, $previous, $new, $competency, $summary, $now ) );
			}
		}
		$total = absint( $payload['affected_user_count'] ?? 0 ) + count( $users );
		if ( 500 === count( $users ) ) {
			$next_cursor = absint( end( $users ) );
			return LSCH_Events::enqueue( 'future18_impact_batch', array_merge( $payload, array( 'cursor_user_id' => $next_cursor, 'affected_user_count' => $total ) ), null, 8, 'f18-impact-' . $event_id . '-' . $next_cursor );
		}
		LSCH_Events::publish( 'LearningRestudyRequired.v1', $object_type, $object_id, array( 'event_id' => $event_id, 'affected_user_count' => $total, 'previous_version' => $previous, 'new_version' => $new ) );
		return true;
	}

'''
if insert_before not in s: raise SystemExit('Round 12 worker insertion target missing')
s=s.replace(insert_before,worker+insert_before,1); F.write_text(s,encoding='utf-8')
add_test("""# Knowledge-change fan-out must be background, retryable, cursor-batched and free of a 2000-user truncation.
if "add_filter( 'lsch_run_job'" not in f or "future18_impact_batch" not in f or 'LIMIT 500' not in f or 'LIMIT 2000\", $object_id' in f:
    errors.append('Knowledge-change impact fan-out is truncated or not retryable/batched.')

""")
verify(12,'retryable cursor-batched knowledge-change fan-out')

# ROUND 13 — mandatory restudy must materialize a targeted review and resolution must prove review completion.
s=F.read_text(encoding='utf-8')
worker_anchor="\tpublic static function run_job( $result, $job_type, $payload, $job_key ) {"
helper=r'''	private static function ensure_impact_review_item( $user_id, $event_id, $object_type, $object_id, $competency, $summary ) {
		global $wpdb;
		$t = self::tables();
		$source_ref = substr( sanitize_text_field( $event_id ) . ':' . absint( $object_id ), 0, 64 );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['review']} WHERE user_id=%d AND item_type='targeted_review' AND source_type='knowledge_change' AND source_id=%s AND competency_key=%s LIMIT 1", absint( $user_id ), $source_ref, self::competency_key( $competency ) ) );
		if ( $existing ) { return true; }
		$metadata = self::encode_json( array( 'event_id' => sanitize_text_field( $event_id ), 'object_type' => sanitize_key( $object_type ), 'object_id' => absint( $object_id ) ), 10000 );
		if ( is_wp_error( $metadata ) ) { return false; }
		$now = current_time( 'mysql', true );
		return 1 === $wpdb->insert( $t['review'], array( 'public_id' => LSCH_Database::uuid(), 'user_id' => absint( $user_id ), 'item_type' => 'targeted_review', 'source_type' => 'knowledge_change', 'source_id' => $source_ref, 'competency_key' => self::competency_key( $competency ), 'prompt' => sanitize_textarea_field( $summary ), 'answer' => '', 'metadata_json' => $metadata, 'interval_days' => 0, 'ease' => 2.5, 'due_at' => $now, 'last_result' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%f','%s','%d','%d','%s','%s' ) );
	}

'''
if worker_anchor not in s: raise SystemExit('Round 13 helper target missing')
s=s.replace(worker_anchor,helper+worker_anchor,1)
impact_insert="\t\t\t\t$wpdb->query( $wpdb->prepare( \"INSERT IGNORE INTO {$t['impacts']} (event_id,user_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at) VALUES (%s,%d,%s,%d,%d,%d,%s,%s,'targeted_review','pending',%s)\", $event_id, absint( $user_id ), $object_type, $object_id, $previous, $new, $competency, $summary, $now ) );\n"
if impact_insert not in s: raise SystemExit('Round 13 worker impact insertion target missing')
s=s.replace(impact_insert,impact_insert+"\t\t\t\tself::ensure_impact_review_item( absint( $user_id ), $event_id, $object_type, $object_id, $competency, $summary );\n",1)
old_resolve="""\t\tglobal $wpdb;
\t\t$t = self::tables();
\t\t$updated = $wpdb->update( $t['impacts'], array( 'status' => 'reviewed', 'resolved_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'user_id' => $user_id, 'status' => 'pending' ), array( '%s', '%s' ), array( '%d', '%d', '%s' ) );
"""
new_resolve="""\t\tglobal $wpdb;
\t\t$t = self::tables();
\t\t$impact = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$t['impacts']} WHERE id=%d AND user_id=%d AND status='pending' LIMIT 1\", $id, $user_id ), ARRAY_A );
\t\tif ( ! $impact ) {
\t\t\treturn new WP_Error( 'lsch_future18_impact_conflict', __( 'Impact item was not pending or was already changed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\t$source_ref = substr( sanitize_text_field( $impact['event_id'] ) . ':' . absint( $impact['object_id'] ), 0, 64 );
\t\t$reviewed = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$t['review']} WHERE user_id=%d AND item_type='targeted_review' AND source_type='knowledge_change' AND source_id=%s AND competency_key=%s AND last_result>=3 LIMIT 1\", $user_id, $source_ref, self::competency_key( $impact['competency_key'] ) ) );
\t\tif ( ! $reviewed ) {
\t\t\treturn new WP_Error( 'lsch_future18_restudy_required', __( 'Complete the targeted re-study review before resolving this knowledge-change item.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
\t\t}
\t\t$updated = $wpdb->update( $t['impacts'], array( 'status' => 'reviewed', 'resolved_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'user_id' => $user_id, 'status' => 'pending' ), array( '%s', '%s' ), array( '%d', '%d', '%s' ) );
"""
if old_resolve not in s: raise SystemExit('Round 13 resolve target missing')
s=s.replace(old_resolve,new_resolve,1); F.write_text(s,encoding='utf-8')
add_test("""# Mandatory re-study requires a targeted review item and cannot be self-resolved without a successful review result.
if 'ensure_impact_review_item' not in f or "item_type='targeted_review'" not in f or 'lsch_future18_restudy_required' not in f or 'last_result>=3' not in f:
    errors.append('Correction→targeted-review→resolve enforcement is incomplete.')

""")
verify(13,'mandatory targeted restudy proof')

# ROUND 14 — provider runtime version drift.
v=V.read_text(encoding='utf-8')
old="\t\t\t'provider_version'  => '3.3.0',\n"
new="\t\t\t'provider_version'  => LSCH_VERSION,\n"
if old not in v: raise SystemExit('Round 14 provider version target missing')
v=v.replace(old,new,1); V.write_text(v,encoding='utf-8')
add_test("""# File26 learning provider must report the actual runtime version, not a stale 3.3.0 token.
value = (root / '05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php').read_text(encoding='utf-8')
if "'provider_version'  => LSCH_VERSION" not in value or "'provider_version'  => '3.3.0'" in value:
    errors.append('File26 provider version is stale relative to File05 runtime.')

""")
verify(14,'File26 provider/runtime version alignment')

# ROUND 15 — trace-count reporting and generated-artifact regression guard.
st=STATIC.read_text(encoding='utf-8')
st=st.replace('30 requirement traces present.', '48 requirement traces present.')
STATIC.write_text(st,encoding='utf-8')
sh=SH.read_text(encoding='utf-8')
marker="if find 05-learn-sabri-classical-homeopathy -type l -print -quit | grep -q .; then\n"
clean="""if find . -type f \\( -name '*.pyc' -o -name '*.pyo' \\) -print -quit | grep -q . || find . -type d -name '__pycache__' -print -quit | grep -q .; then
  echo 'ERROR: generated Python cache artifact committed to repository' >&2
  exit 1
fi

"""
if marker not in sh: raise SystemExit('Round 15 clean-tree marker missing')
if 'generated Python cache artifact' not in sh: sh=sh.replace(marker,clean+marker,1)
SH.write_text(sh,encoding='utf-8')
verify(15,'trace reporting and clean repository artifacts')

# ROUND 16 — manual mastery authorization message must match actual teacher/assessor/mentor/manager scope.
s=F.read_text(encoding='utf-8')
oldmsg="Manual mastery evidence requires an assigned mentor or curriculum manager."
newmsg="Manual mastery evidence requires an active mentor, assigned teacher/assessor, or curriculum manager."
if oldmsg not in s: raise SystemExit('Round 16 message target missing')
s=s.replace(oldmsg,newmsg,1); F.write_text(s,encoding='utf-8')
verify(16,'authorization/error-message semantic alignment')

# ROUND 17 — privacy export must include immutable practice competency snapshot added in schema 2.
s=F.read_text(encoding='utf-8')
oldsel="SELECT public_id,mode,source_type,source_id,blueprint_version,response_json,feedback_json,score,status,assessor_id,version,created_at,updated_at FROM {$t['practice']}"
newsel="SELECT public_id,mode,source_type,source_id,blueprint_version,competency_key,response_json,feedback_json,score,status,assessor_id,version,created_at,updated_at FROM {$t['practice']}"
if oldsel not in s: raise SystemExit('Round 17 privacy practice export target missing')
s=s.replace(oldsel,newsel,1); F.write_text(s,encoding='utf-8')
add_test("""# Privacy export must carry the schema-2 practice competency snapshot.
privacy = f.split('public static function privacy_export',1)[-1].split('public static function privacy_erase',1)[0]
if 'blueprint_version,competency_key,response_json' not in privacy:
    errors.append('Privacy export omits the practice competency snapshot introduced by Future18 schema 2.')

""")
verify(17,'privacy export/schema-2 completeness')

# ROUND 18 — mentorship reads must never bypass current suspension/guardian policy merely because the user has manager capability.
s=F.read_text(encoding='utf-8')
old="\t\tif ( ! self::approved_user( $user_id ) && ! user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {\n"
new="\t\tif ( ! self::approved_user( $user_id ) ) {\n"
# target the first occurrence after mentorships declaration only.
pos=s.find('public static function mentorships')
if pos<0: raise SystemExit('Round 18 mentorships target missing')
tail=s[pos:]
if old not in tail: raise SystemExit('Round 18 policy bypass target missing')
tail=tail.replace(old,new,1); s=s[:pos]+tail; F.write_text(s,encoding='utf-8')
add_test("""# Mentorship reads must require current approved-account/guardian policy even for privileged users.
mentorships = f.split('public static function mentorships',1)[-1].split('public static function record_cpd',1)[0]
if "! self::approved_user( $user_id ) &&" in mentorships:
    errors.append('Mentorship service retains a privileged current-policy bypass.')

""")
verify(18,'mentorship current-policy enforcement')

# ROUND 19 — core REST privileged callbacks must recheck current account policy, like Future18.
r=CORE_R.read_text(encoding='utf-8')
repls={
"\tpublic function assessor() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::ASSESS ); }":"\tpublic function assessor() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::ASSESS ); }",
"\tpublic function reviewer() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::REVIEW_LESSONS ); }":"\tpublic function reviewer() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::REVIEW_LESSONS ); }",
"\tpublic function manager() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ); }":"\tpublic function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ); }",
"\tpublic function teacher() { return is_user_logged_in() && ( current_user_can( LSCH_Capabilities::TEACH ) || current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ); }":"\tpublic function teacher() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( current_user_can( LSCH_Capabilities::TEACH ) || current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ); }",
"\tpublic function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE ); }":"\tpublic function operator() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::OPERATE ); }",
}
for a,b in repls.items():
    if a not in r: raise SystemExit('Round 19 core REST permission target missing: '+a[:30])
    r=r.replace(a,b,1)
old_author="\tpublic function author_or_reviewer( WP_REST_Request $request ) { $id = absint( $request['id'] ); return is_user_logged_in() && ( LSCH_Policy::can_manage_object( $id ) || current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) ); }\n"
new_author="\tpublic function author_or_reviewer( WP_REST_Request $request ) { $id = absint( $request['id'] ); return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( LSCH_Policy::can_manage_object( $id ) || current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) ); }\n"
if old_author not in r: raise SystemExit('Round 19 author/reviewer target missing')
r=r.replace(old_author,new_author,1); CORE_R.write_text(r,encoding='utf-8')
# Static invariant for all protected callbacks.
st=STATIC.read_text(encoding='utf-8')
marker="# Policy must fail closed if current central plan is not verifiable.\n"
check="""# Privileged core REST callbacks must recheck current learning eligibility/suspension policy.
core_rest = read('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php')
for signature in [
    "public function assessor() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function reviewer() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function teacher() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
    "public function operator() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions()",
]:
    require(signature in core_rest, f'core REST privileged callback missing current-policy check: {signature}')

"""
if marker not in st: raise SystemExit('Round 19 static marker missing')
if check.strip() not in st: st=st.replace(marker,check+marker,1)
STATIC.write_text(st,encoding='utf-8')
verify(19,'core privileged REST current-policy enforcement')

# ROUND 20 — global idempotency middleware already guards all non-GET/HEAD/OPTIONS File05 v2 writes; verify, no code change.
idp=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php').read_text(encoding='utf-8')
required=['rest_pre_dispatch','Idempotency-Key','GET_LOCK','request_keys']
missing=[z for z in required if z not in idp]
if missing: raise SystemExit('ROUND 20 DEFECT — missing idempotency controls: '+','.join(missing))
verify(20,'write idempotency/concurrency middleware coverage')
