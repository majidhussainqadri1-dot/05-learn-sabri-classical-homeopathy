<?php
/** Seventh audit: post-write enrollment verification failure is not a success. */
define('ABSPATH','/test-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$message='',$context=array()){$this->code=$code;}}
class LSCH_Content {const COURSE='lsch_course';public static function version($id){return 1;}}
class LSCH_Policy {
	public static function can_use_learning_actions($id){return true;}
	public static function can_read_post($id,$user){return true;}
	public static function validate_prerequisites($course,$user){return true;}
	public static function idempotency_key($key,$user,$action){return 'stub-key';}
	public static function access_model(){return 'single-free-tier-v2';}
}
class LSCH_Database {
	public static function tables(){return array('enrollments'=>'wp_lsch_enrollments');}
	public static function now(){return '2026-10-11 09:00:00';}
	public static function uuid(){return '7cf3d749-5fe5-4f74-8621-e5f9d7aec29b';}
}
class LSCH_Events {
	public static $events=array(),$audits=array();
	public static function publish($name,$type,$id,$payload){self::$events[]=$name;}
	public static function audit($name,$type,$id,$ctx,$purpose){self::$audits[]=$name;}
}
class EnrollDB {
	public $readCount=0,$healthy=false,$writes=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($sql,$type=null){$this->readCount++;return $this->healthy&&$this->readCount>1?array('id'=>10,'status'=>'enrolled','terms_version'=>'single-free-tier-v2'):null;}
	public function insert($table,$fields,$fmt){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new EnrollDB();
function absint($n){return abs((int)$n);}function get_post_type($id){return 'lsch_course';}
function __($s,$d=''){return $s;}function is_wp_error($o){return $o instanceof WP_Error;}
function check_enroll($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$res=LSCH_Services::enroll(19,72,'enroll-request-key');
check_enroll($res instanceof WP_Error && $res->code==='lsch_enrollment_read_failed','unverified write returns explicit error');
check_enroll(empty(LSCH_Events::$events),'unverified write cannot publish confirmed enrollment event');
check_enroll(in_array('enrollment_postwrite_read_failed',LSCH_Events::$audits,true),'unverified write audited');
$GLOBALS['wpdb']=new EnrollDB();$GLOBALS['wpdb']->healthy=true;
$res=LSCH_Services::enroll(19,72,'enroll-request-key-new');
check_enroll(is_array($res)&&$res['terms_version']==='single-free-tier-v2','verified enrollment succeeds');
check_enroll(in_array('LearningEnrollmentCreated.v1',LSCH_Events::$events,true),'verified enrollment event published');
echo "PASS: File05 enrollment write verification and event integrity.\n";
