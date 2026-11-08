<?php
/** Cycle M: an unavailable submitted assignment is not a conflict suitable for retrying a mutation. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Capabilities {const ASSESS='assess';const MANAGE_CURRICULUM='manage';}
class LSCH_Policy {public static function can_use_learning_actions($u){return true;}}
class LSCH_Database {public static function tables(){return array('submissions'=>'wp_submissions');}}
class CaseDB {
	public $last_error='',$writes=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($sql,$fmt=null){$this->last_error='database read failed';return null;}
	public function update($table,$a,$where,$f,$wf){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new CaseDB();
function user_can($uid,$cap){return true;}
function absint($v){return abs((int)$v);}
function __($value,$domain=''){return $value;}
function sanitize_textarea_field($str){return (string)$str;}
function is_wp_error($v){return $v instanceof WP_Error;}
function check_m($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$grade=LSCH_Services::grade_submission(36,29,85,'A valid review',1);
check_m($grade instanceof WP_Error&&$grade->code==='lsch_grade_submission_read_failed','assessor SQL error returns unavailable 503 not invented version conflict');
$appeal=LSCH_Services::appeal_submission(36,17,'This grade requires a complete review',1);
check_m($appeal instanceof WP_Error&&$appeal->code==='lsch_appeal_submission_read_failed','student appeal SQL error returns unavailable 503 not invented invalid state');
check_m($GLOBALS['wpdb']->writes===0,'failed academic submission reads must not mutate grading or appeal records');
echo "PASS: Cycle M assignment grading and learner appeal DB read failures.\n";
