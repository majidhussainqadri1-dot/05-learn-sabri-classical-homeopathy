<?php
/** Tenth independent File05 audit: all private dashboard and summary callers handle failed owner DB reads. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$text='',$data=array()){$this->code=$code;}}
class WP_REST_Response {
	public $data,$headers=array();
	public function __construct($data){$this->data=$data;}
	public function header($name,$value){$this->headers[$name]=$value;}
}
class LSCH_Database {public static function tables(){
	return array('enrollments'=>'wp_enrollments','progress'=>'wp_progress','bookmarks'=>'wp_bookmarks','attempts'=>'wp_attempts','submissions'=>'wp_submissions','reminders'=>'wp_reminders','completions'=>'wp_completions');
}}
class LSCH_State {public static function tables(){return array('value_events'=>'wp_value_events');}}
class LSCH_Policy {
	public static function access_model(){return 'single-free-tier-v2';}
	public static function can_use_learning_actions($u=0){return true;}
}
class DashDB {
	public $fail='wp_progress',$failEvents=false;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_results($sql,$format=null){
		if($this->fail!==''&&strpos($sql,$this->fail)!==false)return false;
		if($this->failEvents&&strpos($sql,'wp_value_events')!==false)return null;
		return array();
	}
}
$GLOBALS['wpdb']=new DashDB();
function absint($v){return abs((int)$v);}
function get_current_user_id(){return 44;}
function is_user_logged_in(){return true;}
function is_wp_error($x){return $x instanceof WP_Error;}
function __($s,$d=''){return $s;}
function esc_html__($s,$d=''){return $s;}
function rest_ensure_response($value){return $value instanceof WP_Error?$value:new WP_REST_Response($value);}
function check_d($ok,$message){if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-frontend.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php';

$failed=LSCH_Services::dashboard(44);
check_d($failed instanceof WP_Error&&$failed->code==='lsch_dashboard_read_failed','failed private learner dataset cannot become false/empty dashboard');
$api=(new LSCH_REST())->dashboard();
check_d($api instanceof WP_Error&&$api->code==='lsch_dashboard_read_failed','REST returns error without invalid header() call');
$export=LSCH_Value::learning_record_export(44);
check_d($export instanceof WP_Error&&$export->code==='lsch_dashboard_read_failed','learning-record export cannot embed WP_Error as successful records');
$html=(new LSCH_Frontend())->dashboard();
check_d(is_string($html)&&strpos($html,'temporarily unavailable')!==false,'frontend shows private DB failure without PHP warning or false empty dashboard');

$GLOBALS['wpdb']->fail='';$healthy=LSCH_Services::dashboard(44);
check_d(is_array($healthy)&&isset($healthy['progress'])&&$healthy['progress']===array(),'healthy truly empty learner dataset remains a valid dashboard');
$ok=(new LSCH_REST())->dashboard();
check_d($ok instanceof WP_REST_Response&&$ok->headers['Cache-Control']==='private, no-store','healthy authenticated dashboard remains private/no-store');

$GLOBALS['wpdb']->failEvents=true;
$summary=LSCH_Value::rest_value_summary();
check_d($summary instanceof WP_Error&&$summary->code==='lsch_value_summary_read_failed','failed public aggregate value query cannot be published as truthful metrics');
$GLOBALS['wpdb']->failEvents=false;
$goodSummary=LSCH_Value::rest_value_summary();
check_d($goodSummary instanceof WP_REST_Response&&$goodSummary->data['events']===array(),'verified empty aggregate response is valid');
echo "PASS: File05 tenth audit private dashboard/REST/export and aggregated metric DB-failure responses.\n";
