<?php
/** Cycle H: a failed competency taxonomy may not earn a credential; old assessment scores cannot qualify. */
define('ABSPATH','/stub-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$msg='',$data=array()){$this->code=$code;}}
class LSCH_Content {const COURSE='lsch_course';const LESSON='lsch_lesson';const ASSESSMENT='lsch_assessment';const COMPETENCY='lsch_competency';
	public static function version($id){return 3;}
}
class LSCH_Policy {
	public static function can_use_learning_actions($u){return true;}
	public static function can_read_post($obj,$u){return true;}
	public static function request_id(){return 'test-request-id';}
}
class LSCH_Dependencies {public static function claims($user){return array('identity_verified'=>true);} }
class LSCH_Database {
	public static function tables(){return array('progress'=>'wp_progress','enrollments'=>'wp_enroll','completions'=>'wp_completions','attempts'=>'wp_attempts');}
	public static function now(){return '2026-10-11 10:00:00';}
}
class LSCH_Events {public static function audit($a,$b,$c,$d,$e){}}
class DBH {
	public $writes=0,$assessmentQuery='',$last_error='',$failRead='';
	public function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return vsprintf($sql,$args);}
	public function get_results($sql,$mode=null){
		if(strpos($sql,'wp_progress')!==false)return array(array('lesson_id'=>7,'state'=>'completed','lesson_version'=>3,'needs_review'=>0));
		return array();
	}
	public function get_row($sql,$type=null) {
		if($this->failRead==='enrollment'&&strpos($sql,'wp_enroll')!==false){$this->last_error='enrollment ledger DB failure';return null;}
		if($this->failRead==='completion'&&strpos($sql,'wp_completions')!==false){$this->last_error='completion ledger DB failure';return null;}
		$this->last_error='';
		if(strpos($sql,'wp_enroll')!==false)return array('id'=>5,'status'=>$GLOBALS['ready_stage']?'completed':'active','version'=>1);
		if(strpos($sql,'wp_completions')!==false)return $GLOBALS['ready_stage']?array('status'=>'earned','identity_assurance'=>'verified','integrity_status'=>'clear'):null;
		return null;
	}
	public function get_var($sql){
		if(strpos($sql,'wp_attempts')!==false){$this->assessmentQuery=$sql;return strpos($sql,'item_version=3')!==false?-1:95;}
		return 0;
	}
	public function query($sql){$this->writes++;return 1;}
	public function update($t,$x,$where,$f,$wf){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new DBH();$GLOBALS['ready_stage']=false;$GLOBALS['competency_error']=true;
function get_post_type($id){return $id==9?'lsch_course':($id==7?'lsch_lesson':'lsch_assessment');}
function absint($v){return abs((int)$v);}function __($s,$d=''){return $s;}
function is_wp_error($x){return $x instanceof WP_Error;}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function get_post_meta($id,$key,$single=true){
	$map=array('_lsch_required'=>'1','_lsch_pass_mark'=>'50','_lsch_certificate_jurisdiction'=>'PK','_lsch_certificate_wording'=>'Approved educational certificate','_lsch_certificate_wording_approved'=>'1');
	return $map[$key]??'';
}
function get_posts($args){return $args['post_type']===LSCH_Content::LESSON?array(7):array(22);}
function wp_get_object_terms($id,$taxonomy,$args){return $GLOBALS['competency_error']?new WP_Error('taxonomy_db_unavailable'):array('foundation');}
function sanitize_text_field($s){return trim((string)$s);}
function sanitize_textarea_field($s){return trim((string)$s);}
function check_h($ok,$why){if(!$ok){fwrite(STDERR,"FAIL: $why\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$method=new ReflectionMethod('LSCH_Services','recalculate_completion');$method->setAccessible(true);
$blocked=$method->invoke(null,9,42);
check_h($blocked instanceof WP_Error && $blocked->code==='lsch_completion_competencies_unverified','taxonomy read error blocks earned completion');
check_h($GLOBALS['wpdb']->writes===0,'no completion writes after unverified competency taxonomy');
$GLOBALS['ready_stage']=true;
$readiness=LSCH_Services::certificate_readiness(9,42);
check_h(is_array($readiness)&&$readiness['ready']===false,'legacy assessment version score cannot qualify for certificate readiness');
check_h(in_array('assessment_22_not_passed',$readiness['blockers'],true),'new assessment version requires new successful attempt');
check_h(strpos($GLOBALS['wpdb']->assessmentQuery,'item_version=3')!==false,'current assessment item version used in evidence query');
$GLOBALS['ready_stage']=false;$GLOBALS['competency_error']=false;
$beforeWrites=$GLOBALS['wpdb']->writes;
foreach(array('enrollment'=>'lsch_completion_enrollment_read_failed','completion'=>'lsch_completion_history_read_failed') as $failedSource=>$expectedCode){
	$GLOBALS['wpdb']->failRead=$failedSource;
	$result=$method->invoke(null,9,42);
	check_h($result instanceof WP_Error&&$result->code===$expectedCode,'failed '.$failedSource.' SQL lookup cannot claim successful current course completion');
	check_h($GLOBALS['wpdb']->writes===$beforeWrites,'failed '.$failedSource.' SQL lookup cannot insert/update or silently revive historic completion');
}
echo "PASS: cycle H/M competency evidence, current-version certificates and historical completion read failures.\n";
