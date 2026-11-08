<?php
/** Cycle H: fail closed on corrupt lesson requirements and missing assessment attempt history. */
define('ABSPATH','/unit-wp/'); define('ARRAY_A','ARRAY_A');
class WP_Error { public $code; public function __construct($code,$message='',$data=array()){$this->code=$code;} }
class LSCH_Content {
	const LESSON='lsch_lesson';const COURSE='lsch_course';const ASSESSMENT='lsch_assessment';const ASSIGNMENT='lsch_assignment';
	public static function version($id){return 3;}
}
class LSCH_Policy {
	public static function can_use_learning_actions($user){return true;}
	public static function can_read_post($id,$user){return true;}
	public static function access_model(){return 'single-free-tier-v2';}
	public static function idempotency_key($key,$user,$action){return 'hashed-key';}
}
class LSCH_Database {
	public static function tables(){return array('enrollments'=>'wp_enrollments','progress'=>'wp_progress','attempts'=>'wp_attempts','submissions'=>'wp_submissions');}
	public static function now(){return '2026-10-11 10:00:00';}
}
class DBH {
	public $reads=0,$writes=0,$maxFailure=true;
	public function prepare($q,...$args){if(count($args)===1 && is_array($args[0]))$args=$args[0];return vsprintf($q,$args);}
	public function get_row($q,$mode=null){
		if(strpos($q,'wp_enrollments')!==false)return array('id'=>1,'status'=>'active','course_version'=>3,'terms_version'=>'single-free-tier-v2');
		return null;
	}
	public function get_var($q){
		if(strpos($q,'GET_LOCK(')!==false || strpos($q,'RELEASE_LOCK(')!==false)return 1;
		if(strpos($q,'MAX(attempt_number)')!==false)return $this->maxFailure?false:0;
		if(strpos($q,'MAX(score)')!==false)return false;
		return null;
	}
	public function insert($table,$row,$types){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new DBH();$GLOBALS['required_components']='["content","bogus"]';$GLOBALS['evidence']='';
function absint($v){return abs((int)$v);}
function get_post_type($id){if($id==7)return LSCH_Content::LESSON;if($id==9)return LSCH_Content::COURSE;return LSCH_Content::ASSESSMENT;}
function get_post_meta($id,$key,$single=true){
	if($key==='_lsch_required_components')return $GLOBALS['required_components'];
	if($key==='_lsch_course_id')return 9;
	if($key==='_lsch_lesson_id')return 7;
	if($key==='_lsch_max_attempts')return 3;
	return '';
}
function get_posts($args){if($GLOBALS['evidence']==='assessment'&&$args['post_type']===LSCH_Content::ASSESSMENT)return array(22);if($GLOBALS['evidence']==='assignment'&&$args['post_type']===LSCH_Content::ASSIGNMENT)return array(23);return array();}
function is_wp_error($obj){return $obj instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function __($s,$domain=''){return $s;}
function check_h($truth,$message){if(!$truth){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
foreach (array('["content","bogus"]','["content","content"]','[123]','{"content":true}','not-json','[]') as $malformed) {
	$GLOBALS['required_components']=$malformed;
	$result=LSCH_Services::progress(7,42,array('complete'=>true));
	check_h($result instanceof WP_Error && $result->code==='lsch_required_components_invalid',"malformed requirements rejected: $malformed");
}
check_h($GLOBALS['wpdb']->writes===0,'invalid requirements must cause no learning progress write');
$result=LSCH_Services::start_assessment(22,42,'request-key');
check_h($result instanceof WP_Error && $result->code==='lsch_assessment_attempt_count_unverified','attempt allocation denied if MAX history read fails');
check_h($GLOBALS['wpdb']->writes===0,'DB failure must not allocate an attempt');
$GLOBALS['wpdb']->maxFailure=false;
foreach(array('assessment','assignment') as $kind){
	$GLOBALS['evidence']=$kind;$GLOBALS['required_components']='["content","'.$kind.'"]';
	$res=LSCH_Services::progress(7,42,array('complete'=>true));
	check_h($res instanceof WP_Error&&$res->code==='lsch_progress_evidence_unverified','failed '.$kind.' grade read must not award learning credit');
}
check_h($GLOBALS['wpdb']->writes===0,'no progress writes from unverified assessment or assignment evidence');
echo "PASS: cycle H/J invalid lesson requirements, failed assessment/assignment scores and attempt count.\n";
