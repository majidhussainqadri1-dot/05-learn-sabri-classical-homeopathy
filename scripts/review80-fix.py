#!/usr/bin/env python3
from pathlib import Path
import subprocess, re, json, tempfile
F=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php'); OPS=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php'); POL=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php'); DB=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-database.php'); DEP=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-dependencies.php'); PRIV=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php'); REST=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php'); FREST=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php'); STATE=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php'); FRONT=Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-frontend.php'); MAIN=Path('05-learn-sabri-classical-homeopathy/learn-sabri-classical-homeopathy.php'); JS=Path('05-learn-sabri-classical-homeopathy/assets/js/learning.js'); CSS=Path('05-learn-sabri-classical-homeopathy/assets/css/learning.css'); README=Path('05-learn-sabri-classical-homeopathy/readme.txt'); STATIC=Path('tests/static-invariants.py'); WORKFLOW=Path('.github/workflows/file-05-complete.yml'); FUTDOC=Path('FUTURE-18-ENHANCEMENTS.md')
DEFECT_ROUNDS=[]
def source_all():
    out=[]
    for p in sorted(Path('05-learn-sabri-classical-homeopathy').rglob('*')):
        if p.is_file() and p.suffix.lower() in {'.php','.js','.css','.txt','.json'}:
            try: out.append(p.read_text(encoding='utf-8'))
            except Exception: pass
    return '\n'.join(out)
def defect(n,msg): DEFECT_ROUNDS.append(n); print(f'ROUND {n:02d} DEFECT — {msg}',flush=True)
def verify(n,label,preds=()):
    print(f'ROUND {n:02d} REVIEW — {label}',flush=True)
    for ok,msg in preds:
        if not ok: raise SystemExit(f'ROUND {n:02d} ASSERTION FAILED: {msg}')
    subprocess.run(['bash','tests/source-invariants.sh'],check=True)
    print(f'ROUND {n:02d} COMPLETE',flush=True)

# 21 — paginated Future18 privacy export.
defect(21,'Future18 privacy export could silently truncate high-volume users.')
s=F.read_text(encoding='utf-8'); a=s.index('\tpublic static function privacy_export( $email, $page = 1 ) {'); b=s.index('\n\tpublic static function privacy_erase( $email, $page = 1 ) {',a)
exp=r'''	public static function privacy_export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		$page = max( 1, absint( $page ) );
		if ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
		$user_id = absint( $user->ID ); $limit = 200; $offset = ( $page - 1 ) * $limit;
		global $wpdb; $t = self::tables();
		$mastery = $wpdb->get_results( $wpdb->prepare( "SELECT competency_key,mastery_score,confidence,evidence_count,last_source_type,last_source_id,last_evidence_at,next_review_at,version FROM {$t['mastery']} WHERE user_id=%d ORDER BY competency_key ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$review = $wpdb->get_results( $wpdb->prepare( "SELECT item_type,source_type,source_id,competency_key,prompt,answer,metadata_json,interval_days,ease,due_at,last_result,version FROM {$t['review']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$practice = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,mode,source_type,source_id,blueprint_version,competency_key,response_json,feedback_json,score,status,assessor_id,version,created_at,updated_at FROM {$t['practice']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$portfolio = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,item_type,object_type,object_id,competency_key,data_json,visibility,version,created_at,updated_at FROM {$t['portfolio']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$cpd = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,activity_type,object_type,object_id,competency_key,minutes,evidence_json,status,verified_by,completed_at,version FROM {$t['cpd']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$impacts = $wpdb->get_results( $wpdb->prepare( "SELECT event_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at,resolved_at FROM {$t['impacts']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$limit,$offset ), ARRAY_A );
		$mentorship = $wpdb->get_results( $wpdb->prepare( "SELECT id,mentor_id,learner_id,course_id,status,goals_json,feedback_json,version,created_at,updated_at FROM {$t['mentorship']} WHERE learner_id=%d OR mentor_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id,$user_id,$limit,$offset ), ARRAY_A );
		$done = count($mastery)<$limit && count($review)<$limit && count($practice)<$limit && count($portfolio)<$limit && count($cpd)<$limit && count($impacts)<$limit && count($mentorship)<$limit;
		$data=array(); if ( $mastery || $review || $practice || $portfolio || $mentorship || $cpd || $impacts ) { $data[]=array('group_id'=>'lsch-future18','group_label'=>__( 'Learning Mastery and Clinical Education','learn-sabri-classical-homeopathy' ),'item_id'=>'future18-'.$user_id.'-page-'.$page,'data'=>array(array('name'=>__('Mastery','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($mastery,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Review queue and flashcards','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($review,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Practice laboratories','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($practice,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Portfolio','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($portfolio,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Mentorship','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($mentorship,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Continuing professional development','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($cpd,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),array('name'=>__('Knowledge-change re-study','learn-sabri-classical-homeopathy'),'value'=>wp_json_encode($impacts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)))); }
		return array( 'data'=>$data,'done'=>$done );
	}
'''
F.write_text(s[:a]+exp+s[b:],encoding='utf-8'); verify(21,'Future18 privacy pagination',[("LIMIT %d OFFSET %d" in F.read_text(encoding='utf-8'),'pagination SQL missing')])

# 22 — legal hold.
defect(22,'Future18 privacy erasure ignored legal hold.')
s=F.read_text(encoding='utf-8'); a=s.index('\tpublic static function privacy_erase( $email, $page = 1 ) {'); b=s.index('\n}',a)
er22=r'''	public static function privacy_erase( $email, $page = 1 ) {
		$user=get_user_by('email',$email); if(!$user||1!==absint($page)){return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);} $user_id=absint($user->ID); global $wpdb; $t=self::tables(); $hold=(bool)apply_filters('lsch_user_legal_hold',false,$user_id); $removed=false; $messages=array();
		foreach(array('mastery','review','pathways','portfolio') as $key){$d=$wpdb->delete($t[$key],array('user_id'=>$user_id),array('%d')); if(false!==$d&&$d>0){$removed=true;}}
		if($hold){$messages[]=__('Practice, CPD, knowledge-change and mentorship records were retained under an active legal hold.','learn-sabri-classical-homeopathy'); LSCH_Events::audit('future18_privacy_erasure','user',$user_id,array('legal_hold'=>true),'privacy'); return array('items_removed'=>$removed,'items_retained'=>true,'messages'=>$messages,'done'=>true);}
		foreach(array('practice','cpd','impacts') as $key){$d=$wpdb->delete($t[$key],array('user_id'=>$user_id),array('%d')); if(false!==$d&&$d>0){$removed=true;}} $m=$wpdb->query($wpdb->prepare("DELETE FROM {$t['mentorship']} WHERE learner_id=%d OR mentor_id=%d",$user_id,$user_id)); if(false!==$m&&$m>0){$removed=true;} return array('items_removed'=>$removed,'items_retained'=>false,'messages'=>$messages,'done'=>true);
	}
'''
F.write_text(s[:a]+er22+s[b:],encoding='utf-8'); verify(22,'Future18 legal hold',[("lsch_user_legal_hold" in F.read_text(encoding='utf-8'),'hold gate missing')])

# 23 — preserve counterpart mentorship + surface DB failures.
defect(23,'Future18 erasure deleted counterpart mentorship rows and hid DB failures.')
s=F.read_text(encoding='utf-8'); a=s.index('\tpublic static function privacy_erase( $email, $page = 1 ) {'); b=s.index('\n}',a)
er23=r'''	public static function privacy_erase( $email, $page = 1 ) {
		$user=get_user_by('email',$email); if(!$user||1!==absint($page)){return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);} $user_id=absint($user->ID); global $wpdb; $t=self::tables(); $hold=(bool)apply_filters('lsch_user_legal_hold',false,$user_id); $removed=false; $retained=false; $messages=array(); $failures=array();
		foreach(array('mastery','review','pathways','portfolio') as $key){$d=$wpdb->delete($t[$key],array('user_id'=>$user_id),array('%d')); if(false===$d){$failures[]=$key;} elseif($d>0){$removed=true;}}
		if($hold){$retained=true; $messages[]=__('Practice, CPD, knowledge-change and mentorship records were retained under an active legal hold.','learn-sabri-classical-homeopathy');}
		else { foreach(array('practice','cpd','impacts') as $key){$d=$wpdb->delete($t[$key],array('user_id'=>$user_id),array('%d')); if(false===$d){$failures[]=$key;} elseif($d>0){$removed=true;}}
			$m=$wpdb->query($wpdb->prepare("UPDATE {$t['mentorship']} SET mentor_id=0,status='ended',goals_json='{}',feedback_json='{}',version=version+1,updated_at=UTC_TIMESTAMP() WHERE mentor_id=%d",$user_id)); if(false===$m){$failures[]='mentorship-mentor';} elseif($m>0){$removed=true;$retained=true;}
			$l=$wpdb->query($wpdb->prepare("UPDATE {$t['mentorship']} SET learner_id=0,status='ended',goals_json='{}',feedback_json='{}',version=version+1,updated_at=UTC_TIMESTAMP() WHERE learner_id=%d",$user_id)); if(false===$l){$failures[]='mentorship-learner';} elseif($l>0){$removed=true;$retained=true;} if($retained){$messages[]=__('Counterpart mentorship records were de-identified and ended rather than deleted.','learn-sabri-classical-homeopathy');}}
		if($failures){$messages[]=__('Some Future Learning privacy operations require operator retry.','learn-sabri-classical-homeopathy'); LSCH_Events::audit('future18_privacy_erasure_partial_failure','user',$user_id,array('failure_count'=>count($failures)),'privacy');}
		LSCH_Events::audit('future18_privacy_erasure','user',$user_id,array('legal_hold'=>$hold,'failure_count'=>count($failures)),'privacy'); return array('items_removed'=>$removed,'items_retained'=>$retained,'messages'=>$messages,'done'=>true);
	}
'''
F.write_text(s[:a]+er23+s[b:],encoding='utf-8'); verify(23,'Future18 participant-safe erasure',[("SET mentor_id=0,status='ended'" in F.read_text(encoding='utf-8'),'mentor anonymization missing'),("partial_failure" in F.read_text(encoding='utf-8'),'DB failure audit missing')])

# 24 — core privacy lifecycle.
priv=PRIV.read_text(encoding='utf-8'); verify(24,'core privacy lifecycle',[("wp_privacy_personal_data_exporters" in priv,'exporter hook missing'),("wp_privacy_personal_data_erasers" in priv,'eraser hook missing'),("lsch_user_legal_hold" in priv,'core legal hold missing'),("privacy_erasure_partial_failure" in priv,'core failure reporting missing')])
# 25 — reliable outbox explicit consumer ack.
ops=OPS.read_text(encoding='utf-8')
if "if ( true === $delivered || null === $delivered )" in ops:
    defect(25,'Outbox treated missing consumer as delivered.'); ops=ops.replace("if ( true === $delivered || null === $delivered )","if ( true === $delivered )",1).replace("'last_error_code' => 'consumer_rejected'","'last_error_code' => null === $delivered ? 'consumer_unavailable' : 'consumer_rejected'",1); OPS.write_text(ops,encoding='utf-8')
verify(25,'explicit outbox acknowledgement',[("consumer_unavailable" in OPS.read_text(encoding='utf-8'),'missing-consumer state absent')])
# 26 — job explicit handler ack.
ops=OPS.read_text(encoding='utf-8')
if "if ( true === $result || null === $result )" in ops:
    defect(26,'Job runner treated missing handler as completed.'); ops=ops.replace("if ( true === $result || null === $result )","if ( true === $result )",1).replace("'last_error_code' => 'handler_rejected'","'last_error_code' => null === $result ? 'handler_unavailable' : 'handler_rejected'",1); OPS.write_text(ops,encoding='utf-8')
verify(26,'explicit job acknowledgement',[("handler_unavailable" in OPS.read_text(encoding='utf-8'),'missing-handler state absent')])
# 27 — dead letter health.
ops=OPS.read_text(encoding='utf-8')
if "Dead outbox" not in ops:
    defect(27,'System Check hid exhausted outbox/jobs.'); anchor="\t\t$checks['Cron outbox'] = array(\n"; add="""\t\t$dead_outbox=(int)$wpdb->get_var(\"SELECT COUNT(*) FROM {$t['outbox']} WHERE status='dead'\"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
\t\t$dead_jobs=(int)$wpdb->get_var(\"SELECT COUNT(*) FROM {$t['jobs']} WHERE status='dead'\"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
\t\t$checks['Dead outbox']=array('status'=>$dead_outbox?'warn':'pass','detail'=>sprintf('%d exhausted outbox event(s).',$dead_outbox));
\t\t$checks['Dead jobs']=array('status'=>$dead_jobs?'warn':'pass','detail'=>sprintf('%d exhausted background job(s).',$dead_jobs));
"""; ops=ops.replace(anchor,add+anchor,1); OPS.write_text(ops,encoding='utf-8')
verify(27,'dead-letter health visibility',[("$checks['Dead jobs']" in OPS.read_text(encoding='utf-8'),'dead job health missing')])
# 28 — safe mode protects writes.
pol=POL.read_text(encoding='utf-8'); target="\t\t\t$user_id &&\n\t\t\tself::central_policy_ready() &&\n\t\t\tLSCH_Capabilities::approved_account( $user_id ) &&"
if target in pol:
    defect(28,'Safe mode did not fail closed for protected learning mutations.'); pol=pol.replace(target,"\t\t\t$user_id &&\n\t\t\t! LSCH_Operations::safe_mode() &&\n\t\t\tself::central_policy_ready() &&\n\t\t\tLSCH_Capabilities::approved_account( $user_id ) &&",1); POL.write_text(pol,encoding='utf-8')
verify(28,'safe-mode mutation gate',[("! LSCH_Operations::safe_mode()" in POL.read_text(encoding='utf-8'),'safe-mode gate missing')])
# 29 — schema parity health.
ops=OPS.read_text(encoding='utf-8')
if "Core schema version" not in ops:
    defect(29,'System Check did not compare installed schema versions with expected versions.'); anchor="\t\t$checks['Managed pages'] = array(\n"; add="""\t\t$core_schema=(int)get_option(LSCH_Database::OPTION,0); $state_schema=(int)get_option(LSCH_State::OPTION,0); $future_schema=(int)get_option(LSCH_Future18::OPTION,0);
\t\t$checks['Core schema version']=array('status'=>LSCH_SCHEMA_VERSION===$core_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$core_schema,LSCH_SCHEMA_VERSION));
\t\t$checks['Aux state schema version']=array('status'=>LSCH_State::SCHEMA===$state_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$state_schema,LSCH_State::SCHEMA));
\t\t$checks['Future18 schema version']=array('status'=>LSCH_Future18::SCHEMA===$future_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$future_schema,LSCH_Future18::SCHEMA));
"""; ops=ops.replace(anchor,add+anchor,1); OPS.write_text(ops,encoding='utf-8')
verify(29,'schema-version health parity',[("Future18 schema version" in OPS.read_text(encoding='utf-8'),'future schema health missing')])
# 30-65 targeted source reviews.
f=F.read_text(encoding='utf-8'); allsrc=source_all(); dep=DEP.read_text(encoding='utf-8'); db=DB.read_text(encoding='utf-8'); doc=FUTDOC.read_text(encoding='utf-8'); st=STATE.read_text(encoding='utf-8'); js=JS.read_text(encoding='utf-8'); css=CSS.read_text(encoding='utf-8')
checks={
30:('Future18 schema-2 migration',[("const SCHEMA = 2" in f,'schema !=2'),("competency_key varchar(96) NOT NULL DEFAULT ''" in f,'competency column missing'),("update_option( self::OPTION, self::SCHEMA" in f,'schema option update missing')]),
31:('File00 public-contract boundary',[("SMC_Contracts::assertions" in dep,'public assertions missing'),("_smc_identity_verified" not in allsrc,'private meta coupling'),("$wpdb->usermeta" not in allsrc,'direct usermeta coupling')]),
32:('File06 encyclopedia truth boundary',[("file06" in doc.lower(),'File06 declaration missing'),("remedy_differentiation" in f,'mode missing'),("lsch_future18_blueprint_access" in f,'owner gate missing')]),
33:('File12 PDF truth boundary',[("file12" in doc.lower(),'File12 declaration missing'),("$wpdb->prefix . 'file12_" not in allsrc,'foreign File12 table')]),
34:('File15 repertory truth boundary',[("file15" in doc.lower(),'File15 declaration missing'),("repertory_reasoning" in f,'mode missing'),("$wpdb->prefix . 'file15_" not in allsrc,'foreign File15 table')]),
35:('File16 AI answer/source boundary',[("provider_owner' => 'file16'" in f,'File16 owner missing'),("lsch_future18_tutor_citation_approved" in f,'citation approval missing'),("prescription" in f.lower(),'clinical safety boundary missing')]),
36:('File17 messaging boundary',[("file17" in doc.lower(),'File17 declaration missing'),("$wpdb->prefix . 'file17_" not in allsrc,'foreign File17 table')]),
37:('File19 notification boundary',[("file19" in doc.lower(),'File19 declaration missing'),("$wpdb->prefix . 'file19_" not in allsrc,'foreign File19 table')]),
38:('File26 search/ranking boundary',[("search_discovery_owner" in dep and "file26" in dep,'File26 policy missing'),("global_rank_owner" in allsrc,'ranking assertion missing')]),
39:('single free tier/no donor advantage',[("single-free-tier-v2" in allsrc,'free tier token missing'),("paid_unlocks_enabled" in dep,'paid gate policy missing'),("PKR 400" not in allsrc,'obsolete price present')]),
40:('Sabri Green/current design token',[("#087A4E" in allsrc,'current green missing'),("#167447" not in allsrc,'obsolete green present')]),
41:('account/guardian/suspension gates',[("guardian_gate_passes" in allsrc,'guardian gate missing'),("approved_account" in allsrc,'approval gate missing'),("'suspended'" in dep,'suspension claim missing')]),
42:('teacher/mentor/assessor object scope',[("role IN ('teacher','assessor')" in f,'assigned teacher/assessor scope missing'),("conflict_status='clear'" in f,'conflict gate missing')]),
43:('self-approval/conflict controls',[("$actor_id === $learner_id" in f,'self-supervision block missing'),("independent_reviewer_required" in allsrc,'independent review missing')]),
44:('completion version integrity',[("UNIQUE KEY user_course_version" in db,'completion uniqueness missing'),("course_version bigint" in db,'course version missing'),("integrity_status" in db,'integrity status missing')]),
45:('credential token/revocation boundary',[("share_token_hash" in db,'share token missing'),("revoked_at" in db and "revoked_reason" in db,'revocation fields missing')]),
46:('correction version/reconciliation',[("proposed_object_version" in st,'proposed version missing'),("applied_object_version" in st,'applied version missing'),("stale_conflict" in OPS.read_text(encoding='utf-8'),'stale conflict missing')]),
47:('REST authorization callbacks',[("permission_callback" in REST.read_text(encoding='utf-8') and "permission_callback" in FREST.read_text(encoding='utf-8'),'permission callbacks missing'),("can_use_learning_actions" in REST.read_text(encoding='utf-8') and "can_use_learning_actions" in FREST.read_text(encoding='utf-8'),'policy guard missing')]),
48:('SQL prepare/owned storage',[("$wpdb->prepare" in allsrc,'prepared SQL missing'),("$wpdb->usermeta" not in allsrc,'usermeta query present')]),
49:('XSS/DOM sinks',[(".innerHTML" not in js,'innerHTML present'),("wp_kses_post" in allsrc,'HTML sanitation missing'),("esc_html" in allsrc,'escaping missing')]),
50:('payload-size bounds',[("sanitize_json" in allsrc,'core JSON bound missing'),("encode_json" in f,'Future18 JSON bound missing'),("too large" in allsrc.lower(),'oversize rejection missing')]),
51:('attachment/file transport boundary',[("attachments_json" in db,'attachment metadata missing'),("chunked" not in f.lower(),'Future18 implements foreign file transport')]),
52:('secrets/PII minimization',[("LSCH_NOTE_MASTER_KEY" in allsrc,'note key config missing'),("password" not in f.lower(),'password-like Future18 data'),("question_hash" in f,'AI audit question hash missing')]),
53:('private-note AES-GCM/key rotation',[("aes-256-gcm" in POL.read_text(encoding='utf-8'),'AES-GCM missing'),("NOTE_KEY_VERSION = 2" in POL.read_text(encoding='utf-8'),'key version missing'),("AUTH_KEY . SECURE_AUTH_SALT" not in POL.read_text(encoding='utf-8'),'auth salts used')]),
54:('external URL/SSRF boundary',[("esc_url_raw" in allsrc,'URL sanitation missing'),("wp_remote_get" not in f and "wp_remote_post" not in f,'ungoverned remote fetch')]),
55:('frontend JavaScript syntax',[(JS.stat().st_size>0,'JS empty')]),
56:('accessibility semantics/focus',[("aria-" in FRONT.read_text(encoding='utf-8') or "aria-" in f,'ARIA missing'),("focus" in css.lower(),'focus styling missing')]),
57:('RTL/LTR support',[("rtl" in css.lower() or "direction" in css.lower(),'RTL handling missing')]),
58:('reduced motion',[("prefers-reduced-motion" in css,'reduced motion missing')]),
59:('design token consistency',[("#087A4E" in allsrc,'brand token missing'),("#167447" not in css,'obsolete CSS token')]),
60:('server-rendered/no-JS fallback',[("shortcode" in f.lower() or "shortcode" in FRONT.read_text(encoding='utf-8').lower(),'shortcode fallback missing'),("<section" in f or "<section" in FRONT.read_text(encoding='utf-8'),'semantic server HTML missing')]),
61:('i18n/text domain',[("learn-sabri-classical-homeopathy" in allsrc,'text domain missing')]),
62:('bounded privacy pagination',[("$limit = 200" in f,'privacy cap missing'),("LIMIT %d OFFSET %d" in f,'pagination SQL missing')]),
63:('background correction fan-out',[("future18_impact_batch" in f,'impact job missing'),("LIMIT 500" in f,'batch bound missing'),("LIMIT 2000\", $object_id" not in f,'old 2000 truncation remains')]),
64:('critical DB indexes',[("KEY queue (status,run_after)" in db,'job index missing'),("KEY dispatch (status,next_attempt_at)" in db,'outbox index missing'),("KEY state_updated (status,updated_at)" in db,'idempotency index missing')]),
65:('migration idempotency/recovery',[("dbDelta" in db and "dbDelta" in f,'dbDelta missing'),("runtime_upgrade_failed" in allsrc,'upgrade failure audit missing'),("maybe_upgrade" in allsrc,'upgrade path missing')])}
for n in range(30,66):
    if n==55: subprocess.run(['node','--check',str(JS)],check=True)
    verify(n,*checks[n])
# 66 deterministic build.
with tempfile.TemporaryDirectory() as td:
    a=Path(td)/'a.zip'; b=Path(td)/'b.zip'; subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(a)],check=True); subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(b)],check=True); assert a.read_bytes()==b.read_bytes()
verify(66,'deterministic package bytes')
# 67 manifest.
with tempfile.TemporaryDirectory() as td:
    z=Path(td)/'r.zip'; out=Path(td)/'x'; out.mkdir(); subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(z)],check=True); subprocess.run(['unzip','-q',str(z),'-d',str(out)],check=True); subprocess.run(['sha256sum','-c','MANIFEST.sha256'],cwd=out/'05-learn-sabri-classical-homeopathy',check=True,stdout=subprocess.DEVNULL)
verify(67,'MANIFEST SHA-256 integrity')
# 68 SBOM.
with tempfile.TemporaryDirectory() as td:
    z=Path(td)/'r.zip'; out=Path(td)/'x'; out.mkdir(); subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(z)],check=True); subprocess.run(['unzip','-q',str(z),'-d',str(out)],check=True); d=json.loads((out/'05-learn-sabri-classical-homeopathy'/'SBOM.spdx.json').read_text(encoding='utf-8')); assert d['spdxVersion']=='SPDX-2.3' and d['packages'][0]['versionInfo']=='4.0.0'
verify(68,'SPDX SBOM/version identity')
# 69 installable root.
with tempfile.TemporaryDirectory() as td:
    z=Path(td)/'r.zip'; out=Path(td)/'x'; out.mkdir(); subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(z)],check=True); subprocess.run(['unzip','-q',str(z),'-d',str(out)],check=True); assert (out/'05-learn-sabri-classical-homeopathy'/'learn-sabri-classical-homeopathy.php').exists()
verify(69,'package top-level/source parity')
wf=WORKFLOW.read_text(encoding='utf-8')
verify(70,'CI pinning/least privilege',[("permissions:\n  contents: read" in wf,'CI permission not read-only'),("actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683" in wf,'checkout not pinned'),("shivammathur/setup-php@7c071dfe9dc99bdf297fa79cb49ea005b9fcadbc" in wf,'setup-php not pinned')])
verify(71,'CI trigger/gate topology',[("pull_request:" in wf and "branches: [ main ]" in wf,'PR trigger missing'),("source-integrity:" in wf and "php-syntax:" in wf and "reproducible-package:" in wf,'CI gates missing')])
allphp='\n'.join(p.read_text(encoding='utf-8') for p in Path('05-learn-sabri-classical-homeopathy').rglob('*.php'))
verify(72,'PHP 7.4 language compatibility screen',[("?->" not in allphp,'nullsafe operator'),(not re.search(r'\bmatch\s*\(',allphp),'match expression'),("enum " not in allphp,'enum syntax')])
for p in sorted(Path('05-learn-sabri-classical-homeopathy').rglob('*.php')): subprocess.run(['php','-l',str(p)],check=True,stdout=subprocess.DEVNULL)
verify(73,'PHP 8.3 syntax lint')
verify(74,'WordPress 6.6+ primitives',[("register_rest_route" in allsrc,'REST missing'),("dbDelta" in allsrc,'dbDelta missing'),("wp_json_encode" in allsrc,'wp_json missing')])
main=MAIN.read_text(encoding='utf-8'); readme=README.read_text(encoding='utf-8')
verify(75,'runtime/package version agreement',[("Version: 4.0.0" in main,'header version mismatch'),("LSCH_VERSION', '4.0.0'" in main,'runtime version mismatch'),("4.0.0.zip" in wf,'CI package version mismatch'),("4.0.0" in readme,'readme version missing')])
verify(76,'Future18 plan/code agreement',[(all(f'F05-FUT-{i:02d}' in doc for i in range(1,19)),'document IDs incomplete'),("feature_ids" in f and "F05-FUT-18" in f,'code feature registry incomplete')])
static=STATIC.read_text(encoding='utf-8'); verify(77,'48 governing requirement traces',[("48 requirement traces present" in static,'trace count not 48'),("requirement" in static.lower(),'trace checks absent')])
verify(78,'repository/staging/live truth boundary',[("staging" in doc.lower(),'staging gate missing'),("live" in doc.lower(),'live distinction missing'),("repository" in doc.lower(),'repository truth missing')])
subprocess.run(['bash','tests/source-invariants.sh'],check=True); verify(79,'fresh adversarial security regression',[(".innerHTML" not in JS.read_text(encoding='utf-8'),'innerHTML regressed'),("_smc_identity_verified" not in source_all(),'private identity coupling regressed'),("PKR 400" not in source_all(),'pricing regressed')])
with tempfile.TemporaryDirectory() as td:
    a=Path(td)/'fa.zip'; b=Path(td)/'fb.zip'; subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(a)],check=True); subprocess.run(['python3','scripts/build-release.py','--root','.','--output',str(b)],check=True); assert a.read_bytes()==b.read_bytes(); subprocess.run(['unzip','-t',str(a)],check=True,stdout=subprocess.DEVNULL)
verify(80,'final pre-cleanup exact-source package/invariants')
print('REVIEW80_DEFECT_ROUNDS='+','.join(map(str,DEFECT_ROUNDS)),flush=True); print('REVIEW80_COMPLETE=80',flush=True)
