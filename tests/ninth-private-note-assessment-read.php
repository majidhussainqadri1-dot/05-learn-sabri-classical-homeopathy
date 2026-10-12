<?php
/** Cycle I: no empty private note on DB outage, or fake assessment confirmation. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$message='',$ctx=array()){$this->code=$c;}}
class LSCH_Content {const LESSON='lsch_lesson';const COURSE='lsch_course';const ASSESSMENT='lsch_assessment';
	public static function version($id){return 2;}
}
class LSCH_Capabilities {}
class LSCH_Policy {
	public static function can_use_learning_actions($id){return true;}
	public static function can_read_post($id,$user){return true;}
	public static function access_model(){return 'single-free-tier-v2';}
	public static function idempotency_key($key,$uid,$action){return 'hash-'.$key;}
}
class LSCH_Database {
	public static function tables(){return array('notes'=>'wp_notes','attempts'=>'wp_attempts','enrollments'=>'wp_enroll');}
	public static function now(){return '2026-10-11 09:00:00';}
	public static function uuid(){return 'test-uuid';}
}
class LSCH_Events {
	public static $published=array(),$audits=array();
	public static function publish($name,$type,$id,$payload){self::$published[]=$name;}
	public static function audit($name,$type,$id,$details,$purpose){self::$audits[]=$name;}
}
class CycleDB {
	public $last_error='',$stage='note_error',$reads=0,$writes=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($sql,$kind=null){
		if(strpos($sql,'wp_notes')!==false){
			$this->last_error=$this->stage==='note_error'?'database disconnected':'';
			return null;
		}
		if(strpos($sql,'wp_enroll')!==false)return array('status'=>'active','course_version'=>2,'terms_version'=>'single-free-tier-v2');
		if(strpos($sql,'wp_attempts')!==false){
			if($this->stage==='submit_read_error'&&strpos($sql,'idempotency_key')!==false){
				return array('id'=>5,'assessment_id'=>30,'user_id'=>2,'item_version'=>2,'status'=>'started','expires_at'=>'','version'=>1,'attempt_number'=>1,'public_id'=>'attempt-one');
			}
			return null;
		}
		return null;
	}
	public function get_var($sql){return strpos($sql,'MAX(attempt_number)')!==false?0:1;}
	public function insert($table,$data,$formats){$this->writes++;return 1;}
	public function update($table,$data,$where,$formats,$whereFormats){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new CycleDB();
function absint($v){return abs((int)$v);}
function get_post_type($id){return (int)$id===30?'lsch_assessment':((int)$id===7?'lsch_lesson':'lsch_course');}
function get_post_meta($id,$key,$single=true){
	$vals=array('_lsch_lesson_id'=>7,'_lsch_course_id'=>9,'_lsch_max_attempts'=>3,'_lsch_time_limit'=>0,'_lsch_questions'=>isset($GLOBALS['quiz_blueprint'])?$GLOBALS['quiz_blueprint']:'[{"correct":"a"}]');
	return isset($vals[$key])?$vals[$key]:'';
}
function __( $s,$d=''){return $s;}
function is_wp_error($x){return $x instanceof WP_Error;}
function sanitize_text_field($s){return trim((string)$s);}
function wp_json_encode($x,$flags=0){return json_encode($x,$flags);}
function check_i($cond,$msg){if(!$cond){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$note=LSCH_Services::get_note(7,2);
check_i($note instanceof WP_Error&&$note->code==='lsch_note_read_failed','DB error cannot show empty note');
$GLOBALS['wpdb']->stage='note_absent';$empty=LSCH_Services::get_note(7,2);
check_i(is_array($empty)&&$empty['note']===''&&$empty['version']===0,'genuinely absent note is empty');
$GLOBALS['wpdb']->stage='start_read_error';
$start=LSCH_Services::start_assessment(30,2,'start-key');
check_i($start instanceof WP_Error&&$start->code==='lsch_assessment_read_failed','post-insert attempt read failure cannot confirm start');
check_i(in_array('assessment_postwrite_read_failed',LSCH_Events::$audits,true),'start read failure audited');
$GLOBALS['wpdb']->stage='submit_read_error';
$done=LSCH_Services::submit_assessment(30,2,array('a'),'submit-key');
check_i($done instanceof WP_Error&&$done->code==='lsch_assessment_submission_read_failed','graded attempt must be reverified before success event');
check_i(!in_array('AssessmentSubmitted.v1',LSCH_Events::$published,true),'no false assessment submitted event after failed DB reread');
$oldWrites=$GLOBALS['wpdb']->writes;
foreach(array('[{"correct":"a"},{"prompt":"no grading key"}]','[{"correct":"a"},{"correct":{"bad":true}}]','[{"correct":"a"},null]','{"1":{"correct":"a"}}','[{"correct":""}]') as $badBlueprint) {
	$GLOBALS['quiz_blueprint']=$badBlueprint;
	$result=LSCH_Services::submit_assessment(30,2,array('a'),'submit-key');
	check_i($result instanceof WP_Error&&$result->code==='lsch_assessment_invalid','malformed quiz cannot silently drop a required question');
}
check_i($GLOBALS['wpdb']->writes===$oldWrites,'malformed question list cannot write false academic grade');
echo "PASS: Cycle I/J private note, postwrite verification and malformed-quiz grading regressions.\n";
