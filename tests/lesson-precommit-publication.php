<?php
/** WordPress precommit lesson publication: pending before a risky post-status write. */
define('ABSPATH','/unit-wp/');
class WP_Error {public $code; public function __construct($c){$this->code=$c;}}
class LSCH_Content {const LESSON='lsch_lesson';const TOPIC='lsch_topic';}
class LSCH_Capabilities {
 const MANAGE_CURRICULUM='manage_learning';const PUBLISH_LESSONS='publish_learning';
 public static function is_founder($id){return $id===1;}
 public static function can_author($id){return $id>0;}
 public static function can_review($id){return $id===10;}
}
class LSCH_Policy {
 public static $ready=true,$consent=true;
 public static function central_policy_ready(){return self::$ready;}
 public static function valid_case_consent($id){return self::$consent;}
}
class LSCH_Value {public static $valid_sources=true;public static function sources($id){return self::$valid_sources?array('verified-citation'):array();}}
class LSCH_Events {public static $audits=array();public static function audit($name,$type,$id,$data,$purpose){self::$audits[]=array($name,$data['reason']??'');}}
$GLOBALS['meta']=array('_lsch_objectives'=>'Objectives','_lsch_sources'=>'Verified source','_lsch_accessibility'=>'Transcript','_lsch_safety'=>'Safety rules','_lsch_reviewer_id'=>10);
$GLOBALS['topics']=array('foundations');
$GLOBALS['tag_present']=false;
$GLOBALS['tag_error']=false;
$GLOBALS['post_exists']=true;
$GLOBALS['publisher_permission']=true;
function absint($x){return abs((int)$x);}
function sanitize_key($v){return strtolower((string)$v);}
function get_post($id){return $GLOBALS['post_exists']?(object)array('ID'=>$id,'post_author'=>2):null;}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$key]??'';}
function wp_get_object_terms($id,$taxonomy,$opts){if(($opts['fields']??'')==='ids')return $GLOBALS['topic_ids']??array(31);return $GLOBALS['topics'];}
function has_term($tag,$tax,$id){return $GLOBALS['tag_present'];}
function wp_set_post_tags($id,$tags,$append){if($GLOBALS['tag_error'])return new WP_Error('tags_failed');$GLOBALS['tag_present']=true;return array(12);}
function wp_strip_all_tags($v){return strip_tags($v);}
function is_wp_error($v){return $v instanceof WP_Error;}
function current_user_can($cap){return false;}
function user_can($id,$cap){return $GLOBALS['publisher_permission'];}
function __( $v,$domain=''){return $v;}
function must($v,$message){if(!$v){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php';
$p=new LSCH_Plugin();
$base=array('post_type'=>'lsch_lesson','post_status'=>'publish','post_author'=>2,'post_title'=>'A source-linked lesson','post_content'=>'Learning goals with full source context');
function check_case($data,$expected,$reason='',$postarr=array('ID'=>13)){global $p;$r=$p->prepublish_lesson($data,$postarr);must($r['post_status']===$expected,'Unexpected pre-commit status: '.$reason);if($reason){$last=end(LSCH_Events::$audits);must($last[1]===$reason,'Expected pre-commit reason: '.$reason);}}
must($p->prepublish_lesson($base,array())['post_status']==='pending','New unsaved lesson cannot publish before persisted review');
check_case(array_merge($base,array('post_type'=>'post')),'publish');
check_case(array_merge($base,array('post_status'=>'draft')),'draft');
LSCH_Policy::$ready=false;check_case($base,'pending','central_policy_unavailable');LSCH_Policy::$ready=true;
$GLOBALS['publisher_permission']=false;check_case($base,'pending','unauthorized_publication_actor');$GLOBALS['publisher_permission']=true;
check_case(array_merge($base,array('post_content'=>' ')),'pending','lesson_body_or_title_missing');
$GLOBALS['meta']['_lsch_safety']='';check_case($base,'pending','missing__lsch_safety');$GLOBALS['meta']['_lsch_safety']='Safety rules';
LSCH_Value::$valid_sources=false;check_case($base,'pending','source_record_required');LSCH_Value::$valid_sources=true;
$GLOBALS['meta']['_lsch_reviewer_id']=2;check_case($base,'pending','independent_reviewer_required');$GLOBALS['meta']['_lsch_reviewer_id']=10;
$GLOBALS['topics']=new WP_Error('read_failed');check_case($base,'pending','patient_case_taxonomy_unavailable');
$GLOBALS['topics']=null;check_case($base,'pending','patient_case_taxonomy_unavailable');
$GLOBALS['topics']=array('patient-case-learning');LSCH_Policy::$consent=false;check_case($base,'pending','patient_case_consent_required');LSCH_Policy::$consent=true;
$GLOBALS['tag_error']=true;check_case($base,'pending','patient_case_tag_unverified');$GLOBALS['tag_error']=false;
check_case($base,'publish');must($GLOBALS['tag_present'],'Mandatory case tag present before status publish');
$GLOBALS['topics']=array('foundations');check_case($base,'publish');
// Adversarial same-save mutations must not be approved using stale saved evidence.
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tax_input'=>array(LSCH_Content::TOPIC=>array('patient-case-learning'))));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tax_input'=>'invalid-tax-input'));
check_case($base,'publish','',array('ID'=>13,'tax_input'=>array(LSCH_Content::TOPIC=>array(31))));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tax_input'=>array(LSCH_Content::TOPIC=>array(32))));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tax_input'=>array(LSCH_Content::TOPIC=>(object)array('slug'=>'patient-case-learning'))));
check_case($base,'publish','',array('ID'=>13,'tags_input'=>''));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tags_input'=>array((object)array('tag'=>'other'))));

check_case($base,'publish','',array('ID'=>13,'tax_input'=>array('unrelated_taxonomy'=>array('existing'))));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'tags_input'=>array('remove-case-tag')));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'meta_input'=>array('_lsch_safety'=>'')));
check_case($base,'pending','uncommitted_lesson_governance',array('ID'=>13,'meta_input'=>'malformed'));
check_case($base,'publish','',array('ID'=>13,'meta_input'=>array('_lsch_safety'=>'Safety rules')));
check_case($base,'publish','',array('ID'=>13,'meta_input'=>array('_other_unrelated_key'=>'safe')));
$_POST['lsch_meta']=array('_lsch_sources'=>'Changed references');
check_case($base,'pending','uncommitted_lesson_governance');
$_POST['lsch_meta']=array('_lsch_sources'=>'Verified source');
check_case($base,'publish');
$_POST['lsch_meta']='malformed';
check_case($base,'pending','uncommitted_lesson_governance');
unset($_POST['lsch_meta']);
echo "PASS: WordPress precommit blocks unsafe same-save topic, tags, metadata and editorial governance mutations.\n";echo "PASS: WordPress precommit prevents unsafe lesson publication, permits verified course content.\n";
