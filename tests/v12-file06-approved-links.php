<?php
/** File 05 → File 06 lessons: exact source version, independent rights/ACL. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
class LSCH_Content {
	const LESSON='lsch_lesson';
	public static function version($id){return $GLOBALS['lesson_version'];}
}
class LSCH_Policy {
	public static function central_policy_ready(){return true;}
	public static function publicly_visible($id){return !empty($GLOBALS['public_lesson']);}
	public static function can_use_learning_actions($id=0){return empty($GLOBALS['safe_mode']);}
}
class LSCH_Capabilities {
	const REVIEW_LESSONS='lsch_review_lessons';
	public static function can_review($id){return $GLOBALS['reviewer_authorized']??true;}
}
class LSCH_REST {const NS='lsch/v1';}
class WP_Error {private $c;public function __construct($c,$m='',$d=array()){$this->c=$c;}public function get_error_code(){return $this->c;}}
function absint($v){return abs((int)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function get_post($id){return (object)array('ID'=>$id,'post_type'=>'lsch_lesson','post_status'=>'publish','post_author'=>7);}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$key]??null;}
function update_post_meta($id,$key,$value){if($key===($GLOBALS['fail_meta_key']??'')){return false;}$GLOBALS['meta'][$key]=$value;return true;}
function rest_ensure_response($v){return $v;}
class MockRequest extends ArrayObject {public function get_header($n){return 'nonce';}public function get_json_params(){return $this->getArrayCopy();}}
function get_posts($args){return array(9);}
function get_permalink($id){return 'https://example.org/learn/lesson/9';}
function get_the_title($id){return 'Reviewed educational lesson';}
function wp_http_validate_url($v){return 0===strpos($v,'https://');}
function user_can($uid,$cap){return $GLOBALS['reviewer_active']??true;}
function get_current_user_id(){return 3;}
function wp_verify_nonce($n,$v){return true;}
function current_user_can($cap){return $GLOBALS['live_reviewer_cap']??true;}
function apply_filters($name,$value,...$params){
	if($name==='he_v12_file06_concept_assertion_v1'&&!empty($GLOBALS['owner_exception']))throw new RuntimeException('owner_offline');
	if($name==='he_v12_file06_concept_assertion_v1'&&($GLOBALS['canonical']??true))return array('owner_file'=>'file-06','published'=>true,'public_id'=>$params[0],'version'=>($GLOBALS['owner_version']??$params[1]));
	return null;
}
function must($ok,$label){$GLOBALS['checks']++;if(!$ok){fwrite(STDERR,"FAILED {$label}\n");exit(1);}}
$GLOBALS['checks']=0;$GLOBALS['public_lesson']=true;$GLOBALS['lesson_version']=8;
$id='11111111-1111-4111-8111-111111111111';$dto=array('id'=>$id,'version'=>3);
$GLOBALS['meta']=array(
	'_lsch_file06_concept_public_id'=>$id,
	'_lsch_file06_source_approval_v1'=>array('approved'=>true,'concept_public_id'=>$id,'concept_version'=>3,
		'lesson_version'=>8,'rights_scope'=>'permission-to-link','rights_evidence_ref'=>'rights:2026-approved',
		'reviewer_user_id'=>4),
);
require dirname(__DIR__).'/05-learn-sabri-classical-homeopathy/includes/class-lsch-file06-source-links.php';
$links=LSCH_File06_Source_Links::collect(array(),$dto);
must(count($links)===1,'Exact qualified lesson returned');
must(LSCH_File06_Source_Links::attest(false,$links[0],$dto)===true,'Independent owner reattestation');
$GLOBALS['canonical']=false;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'Retracted/absent File06 concept never displays previously approved link');
must(LSCH_File06_Source_Links::attest(false,$links[0],$dto)===false,'Retracted File06 concept revokes previous attestation');
$GLOBALS['canonical']=true;
$GLOBALS['owner_version']=4;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'File06 source-version change invalidates old lesson source');
$GLOBALS['owner_version']=3;
$GLOBALS['owner_exception']=true;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'File06 lookup exception denies cached relation');
$GLOBALS['owner_exception']=false;
$other=$dto;$other['id']='22222222-2222-4222-8222-222222222222';
must(LSCH_File06_Source_Links::collect(array(),$other)===array(),'No lexical matching or wrong concept identity');
$GLOBALS['public_lesson']=false;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'Private or withdrawn lesson omitted');
$GLOBALS['public_lesson']=true;
$GLOBALS['lesson_version']=9;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'Changed lesson requires new review');
$GLOBALS['lesson_version']=8;
$GLOBALS['meta']['_lsch_file06_source_approval_v1']['reviewer_user_id']=7;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'Self review blocked');
$GLOBALS['meta']['_lsch_file06_source_approval_v1']['reviewer_user_id']=4;
$GLOBALS['meta']['_lsch_file06_source_approval_v1']['rights_evidence_ref']='';
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'No rights evidence no link');
$GLOBALS['meta']['_lsch_file06_source_approval_v1']['rights_evidence_ref']='rights:2026-approved';
$GLOBALS['reviewer_active']=false;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'Revoked reviewer rights disables projection');
$GLOBALS['reviewer_active']=true;
$GLOBALS['reviewer_authorized']=false;
must(LSCH_File06_Source_Links::collect(array(),$dto)===array(),'File00 suspended reviewer disables old source relation');
$GLOBALS['reviewer_authorized']=true;
$GLOBALS['reviewer_active']=true;
$request=new MockRequest(array('id'=>9,'concept_public_id'=>$id,'concept_version'=>3,
  'lesson_version'=>8,'rights_scope'=>'permission-to-link','rights_evidence_ref'=>'rights:2026-approved'));
$GLOBALS['owner_version']=2;
$rejected=LSCH_File06_Source_Links::approve($request);
must($rejected instanceof WP_Error && $rejected->get_error_code()==='lsch_f06_owner_unavailable',
  'Stale File06 owner attestation must reject requested version');
$GLOBALS['owner_version']=3;
$GLOBALS['live_reviewer_cap']=false;
$revoked=LSCH_File06_Source_Links::approve($request);
must($revoked instanceof WP_Error && $revoked->get_error_code()==='lsch_f06_review_denied',
  'Revoked reviewer capability must be rechecked before mutation');
$GLOBALS['live_reviewer_cap']=true;
$GLOBALS['safe_mode']=true;
$blocked=LSCH_File06_Source_Links::approve($request);
must($blocked instanceof WP_Error && $blocked->get_error_code()==='lsch_f06_review_denied',
  'Safe-mode blocks approval even if WordPress capability is present');
$GLOBALS['safe_mode']=false;
// A failed index write must not pre-commit the new approval.
$old_record=$GLOBALS['meta'][LSCH_File06_Source_Links::META_APPROVAL];
$GLOBALS['meta'][LSCH_File06_Source_Links::META_INDEX]='stale-other-uuid';
$GLOBALS['fail_meta_key']=LSCH_File06_Source_Links::META_INDEX;
$index_error=LSCH_File06_Source_Links::approve($request);
must($index_error instanceof WP_Error && $index_error->get_error_code()==='lsch_f06_link_index',
  'Failed relation index blocks approval');
must($GLOBALS['meta'][LSCH_File06_Source_Links::META_APPROVAL]===$old_record,
  'Failed relation index does not mutate reviewed approval');
$GLOBALS['fail_meta_key']='';
$accepted=LSCH_File06_Source_Links::approve($request);
must(is_array($accepted)&&$accepted['approved']===true,
  'Exact File06 current version can be independently approved');
// A failed approval write leaves only an index, never a new academic decision.
$prior=$GLOBALS['meta'][LSCH_File06_Source_Links::META_APPROVAL];
// Change an actual reviewed field: same-second identical metadata is a successful
// idempotent no-op and must not be counted as an artificial failed write.
$request['rights_evidence_ref']='rights:2026-new-audit';
$GLOBALS['fail_meta_key']=LSCH_File06_Source_Links::META_APPROVAL;
$approval_error=LSCH_File06_Source_Links::approve($request);
must($approval_error instanceof WP_Error && $approval_error->get_error_code()==='lsch_f06_review_store',
  'Approval write failure denies success');
must($GLOBALS['meta'][LSCH_File06_Source_Links::META_APPROVAL]===$prior,
  'Failed approval retains only the previous committed decision');
$GLOBALS['fail_meta_key']='';
echo "File05→File06 approved educational links: {$GLOBALS['checks']} isolated checks PASS.\n";
