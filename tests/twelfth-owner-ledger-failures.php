<?php
/** Cycle L: owner ledger query failure must not become empty enrollment, consent or certificate history. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;public function __construct($code,$msg='',$data=array()){$this->code=$code;}}
class LSCH_Content {const COURSE='lsch_course';const LESSON='lsch_lesson';public static function version($id){return 3;}}
class LSCH_Capabilities {const REVIEW_LESSONS='review_lessons';}
class LSCH_Dependencies {public static function claims($id){return array('identity_verified'=>true);}}
class LSCH_Policy {
	public static function can_use_learning_actions($id=0){return true;}
	public static function can_read_post($id,$user=0){return true;}
	public static function can_manage_object($id,$user=0){return true;}
	public static function validate_prerequisites($cid,$uid){return true;}
	public static function idempotency_key($key,$uid,$action){return 'mock-key';}
	public static function access_model(){return 'single-free-tier-v2';}
}
class LSCH_Database {public static function tables(){return array('enrollments'=>'wp_enrollments','consents'=>'wp_consents','completions'=>'wp_completions');}
	public static function now(){return '2026-10-12 12:00:00';}}
class ReadFailureDB {
	public $last_error='',$mode='enroll',$writes=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($sql,$format=null){
		if($this->mode==='certificate-enrollment'&&strpos($sql,'wp_completions')!==false){$this->last_error='';return array('status'=>'earned','identity_assurance'=>'verified','integrity_status'=>'clear');}
		$this->last_error='connection failure';return null;
	}
	public function insert($t,$a,$f){$this->writes++;return 1;}
	public function query($sql){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new ReadFailureDB();
function absint($v){return abs((int)$v);}function __($x,$d=''){return $x;}
function is_wp_error($v){return $v instanceof WP_Error;}
function get_post_type($id){return (int)$id===7?'lsch_lesson':'lsch_course';}
function get_post_meta($id,$key,$single=true){return '_lsch_course_id'===$key?9:'';}
function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function sanitize_textarea_field($v){return trim((string)$v);}
function apply_filters($name,$value,...$args){return $value;}
function user_can($id,$cap){return false;}
function check_l($yes,$label){if(!$yes){fwrite(STDERR,"FAIL: $label\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$before=$GLOBALS['wpdb']->writes;
$res=LSCH_Services::enroll(9,21,'fresh-key');
check_l($res instanceof WP_Error&&$res->code==='lsch_enrollment_prewrite_read_failed','enrollment lookup outage cannot create new row');
$res=LSCH_Services::progress(7,21,array('complete'=>true));
check_l($res instanceof WP_Error&&$res->code==='lsch_enrollment_gate_read_failed','failed active enrollment lookup must not become learner inactive verdict');
$GLOBALS['wpdb']->mode='consent';
$res=LSCH_Services::store_case_consent(7,21,array('consent_source'=>'patient','subject_type'=>'patient','scope'=>'publication','evidence_reference'=>'consent-record-1'));
check_l($res instanceof WP_Error&&$res->code==='lsch_consent_history_read_failed','consent history query failure prevents duplicate new patient consent');
$GLOBALS['wpdb']->mode='certificate-completion';
$res=LSCH_Services::certificate_readiness(9,21);
check_l($res instanceof WP_Error&&$res->code==='lsch_certificate_ledger_read_failed','failed credential ledger is not counted as no credit');
$GLOBALS['wpdb']->mode='certificate-enrollment';
$res=LSCH_Services::certificate_readiness(9,21);
check_l($res instanceof WP_Error&&$res->code==='lsch_certificate_enrollment_read_failed','failed enrollment ledger not interpreted as incomplete academic state');
check_l($GLOBALS['wpdb']->writes===$before,'five DB outage cases produced no writes');
echo "PASS: Cycle L File05 enrollment, consent and certificate private-ledger read failures.\n";
