<?php
/** Cycle K: post-write verification must precede success events for course state, assignment, reminder. */
define('ABSPATH','/unit-wordpress/'); define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Content {
	const COURSE='lsch_course';const LESSON='lsch_lesson';const ASSIGNMENT='lsch_assignment';
	public static function version($id){return 3;}
}
class LSCH_Policy {
	public static function can_use_learning_actions($user=0){return true;}
	public static function can_read_post($id,$user=0){return true;}
	public static function access_model(){return 'single-free-tier-v2';}
	public static function validate_prerequisites($course,$user){return true;}
	public static function sanitize_json($value,$max=65535){return json_encode($value);}
}
class LSCH_Database {
	public static function now(){return '2026-10-12 09:00:00';}
	public static function uuid(){return '11111111-1111-4111-8111-111111111111';}
	public static function tables(){return array('enrollments'=>'wp_enrollments','submissions'=>'wp_submissions','reminders'=>'wp_reminders');}
}
class LSCH_Events {
	public static $published=array(),$audits=array();
	public static function publish($name,$type,$id,$data){self::$published[]=$name;}
	public static function audit($name,$type,$id,$data,$purpose){self::$audits[]=$name;}
}
class VerifyDB {
	public $mode='transition_fail',$verified=true,$writes=0,$insert_id=17;
	public function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return vsprintf($sql,$args);}
	public function get_row($sql,$format=null){
		if(strpos($sql,'wp_enrollments')!==false){
			if(strpos($sql,'WHERE id=')!==false)return $this->verified?array('id'=>4,'status'=>'active','version'=>2):null;
			return array('id'=>4,'status'=>'enrolled','version'=>1,'course_version'=>3,'terms_version'=>'single-free-tier-v2');
		}
		if(strpos($sql,'wp_submissions')!==false)return $this->verified?array('id'=>17,'status'=>'submitted','user_id'=>22,'assignment_id'=>33):null;
		if(strpos($sql,'wp_reminders')!==false)return $this->verified?array('enabled'=>1,'cadence'=>'weekly'):null;
		return null;
	}
	public function get_var($sql){return 'enrolled';}
	public function update($table,$data,$where,$formats,$whereformats){$this->writes++;return 1;}
	public function query($sql){$this->writes++;return 1;}
	public function insert($table,$data,$types){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new VerifyDB();
function __( $text,$domain=''){return $text;}
function absint($x){return abs((int)$x);}
function get_post_type($id){return (int)$id===33?LSCH_Content::ASSIGNMENT:((int)$id===7?LSCH_Content::LESSON:LSCH_Content::COURSE);}
function get_post_meta($id,$key,$single=true){if($key==='_lsch_lesson_id')return 7;if($key==='_lsch_course_id')return 9;return '';}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_kses_post($text){return $text;}
function wp_strip_all_tags($text){return strip_tags($text);}
function sanitize_key($value){return strtolower((string)$value);}
function check_k($yes,$msg){if(!$yes){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$GLOBALS['wpdb']->verified=false;
$res=LSCH_Services::change_enrollment_state(9,22,'active',1);
check_k($res instanceof WP_Error&&$res->code==='lsch_enrollment_transition_unverified','failed course transition re-read returns an error');
check_k(!in_array('LearningEnrollmentActivated.v1',LSCH_Events::$published,true),'unverified transition does not publish activated event');
$res=LSCH_Services::submit_assignment(33,22,'Substantive assignment body');
check_k($res instanceof WP_Error&&$res->code==='lsch_assignment_read_failed','failed submission post-write read never returns empty success');
check_k(!in_array('assignment_submitted',LSCH_Events::$audits,true),'unverified assignment cannot publish a submitted audit');
$res=LSCH_Services::set_reminder(9,22,true,'weekly');
check_k($res instanceof WP_Error&&$res->code==='lsch_reminder_read_failed','failed reminder post-write read returns error');
check_k(!in_array('LearningReminderPreferenceChanged.v1',LSCH_Events::$published,true),'unverified reminder does not publish change');
$GLOBALS['wpdb']->verified=true;
$res=LSCH_Services::change_enrollment_state(9,22,'active',1);
check_k(is_array($res)&&$res['status']==='active','verified transition returns actual record');
$res=LSCH_Services::submit_assignment(33,22,'Substantive assignment body');
check_k(is_array($res)&&$res['id']===17,'verified submission returns saved record');
$res=LSCH_Services::set_reminder(9,22,true,'weekly');
check_k(is_array($res)&&$res['enabled']===1,'verified reminder returns saved preference');
check_k(in_array('LearningEnrollmentActivated.v1',LSCH_Events::$published,true),'successful verified enrollment transition publishes');
check_k(in_array('assignment_submitted',LSCH_Events::$audits,true),'successful verified assignment is audited');
check_k(in_array('LearningReminderPreferenceChanged.v1',LSCH_Events::$published,true),'successful verified reminder publishes');
echo "PASS: File05 Cycle K assignment/reminder/enrollment post-write verification.\n";
