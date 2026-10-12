<?php
/** Cycle L: corrections must not masquerade DB errors as missing, and replay guard must not allocate on failed lookup. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');define('HOUR_IN_SECONDS',3600);
class WP_Error {public $code;public function __construct($code,$m='',$data=array()){$this->code=$code;}}
class WP_REST_Request implements ArrayAccess {
	public function get_param($key){return $key==='decision'?'accepted':($key==='reason'?'substantial evidence correction reason':null);}
	public function get_route(){return '/learn-sabri-classical-homeopathy/v2/progress';}
	public function get_method(){return 'POST';}
	public function get_header($name){return 'cycle-l-request-key';}
	public function get_params(){return array('lesson_id'=>7);}
	public function offsetExists($key){return true;}
	public function offsetGet($key){return 41;}
	public function offsetSet($key,$value){}
	public function offsetUnset($key){}
}
class LSCH_Events {public static function reset_request_integrity(){} }
class LSCH_Database {public static function tables(){return array('request_keys'=>'wp_request_keys');}public static function now(){return '2026-10-12 12:00:00';}}
class LSCH_Policy {
	public static function rate_limit($k,$subject,$count,$period){return true;}
	public static function idempotency_key($key,$uid,$action){return 'hashed-key';}
	public static function request_id(){return 'test-trace';}
}
class AuditDB {
	public $prefix='wp_',$last_error='',$writes=0,$lockReleased=false;
	public function prepare($query,...$args){return vsprintf($query,$args);}
	public function get_row($query,$format=null){$this->last_error='forced SQL outage';return null;}
	public function get_var($sql){if(strpos($sql,'RELEASE_LOCK')!==false){$this->lockReleased=true;return 1;}return 1;}
	public function query($sql){$this->writes++;return 1;}
	public function insert($table,$row,$fmt){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new AuditDB();
function get_current_user_id(){return 19;}
function sanitize_key($v){return strtolower((string)$v);}
function sanitize_textarea_field($v){return trim((string)$v);}
function __($v,$domain=''){return $v;}
function absint($v){return abs((int)$v);}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function get_transient($k){return true;}
function set_transient($k,$value,$seconds){return true;}
function is_wp_error($x){return $x instanceof WP_Error;}
function check_l($cond,$msg){if(!$cond){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php';
$request=new WP_REST_Request();
foreach(array('rest_decide_correction','rest_resubmit_correction','rest_withdraw_correction') as $method){
	$result=LSCH_State::$method($request);
	check_l($result instanceof WP_Error&&$result->code==='lsch_correction_read_failed',"failed $method ledger query must not appear as 404");
}
$guard=LSCH_Idempotency::before_callbacks(null,null,$request);
check_l($guard instanceof WP_Error&&$guard->code==='lsch_idempotency_ledger_read_failed','replay guard must return 503 on failed original-request read');
check_l($GLOBALS['wpdb']->writes===0,'failed ledger read does not allocate a new request-key row');
check_l($GLOBALS['wpdb']->lockReleased,'database named lock released after failed replay lookup');
echo "PASS: Cycle L correction decisions/resubmit/withdraw and private request replay DB failure.\n";
