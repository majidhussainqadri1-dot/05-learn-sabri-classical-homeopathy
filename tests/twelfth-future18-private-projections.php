<?php
/** Cycle L: private Future18 DB failures are errors, not bogus empty profiles or HTTP success objects. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$m='',$data=array()){$this->code=$c;}}
class WP_REST_Request {public function get_param($k){return $k==='limit'?15:($k==='status'?'pending':'');}public function get_method(){return 'GET';}}
class WP_REST_Response {public $data,$headers=array();function __construct($data){$this->data=$data;}function header($k,$v){$this->headers[$k]=$v;}}
class LSCH_Policy {
	public static function can_use_learning_actions($id=0){return true;}
	public static function sanitize_json($data,$size){return json_encode($data);}
}
class FutureDB {
	public $prefix='wp_',$failed=true,$failedTable='all';
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_results($sql,$fmt=null){
		if($this->failed && ($this->failedTable==='all'||strpos($sql,$this->failedTable)!==false))return false;
		return array();
	}
}
$GLOBALS['wpdb']=new FutureDB();
function absint($v){return abs((int)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function get_current_user_id(){return 18;}
function current_time($format,$gmt=true){return '2026-10-12 12:00:00';}
function __( $x,$d=''){return $x;}
function is_wp_error($x){return $x instanceof WP_Error;}
function rest_ensure_response($v){return is_wp_error($v)?$v:new WP_REST_Response($v);}
function check_l($truth,$message){if(!$truth){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php';
$tests=array(
	array('mastery_snapshot',array(18),'lsch_future18_mastery_read_failed'),
	array('review_queue',array(18),'lsch_future18_review_read_failed'),
	array('portfolio',array(18),'lsch_future18_portfolio_read_failed'),
	array('mentorships',array(18),'lsch_future18_mentorship_read_failed'),
	array('cpd_records',array(18),'lsch_future18_cpd_read_failed'),
	array('impacts',array(18),'lsch_future18_impact_read_failed'),
);
foreach($tests as $test){$result=call_user_func_array(array('LSCH_Future18',$test[0]),$test[1]);check_l($result instanceof WP_Error&&$result->code===$test[2],$test[0].' SQL outage');}
$routes=new LSCH_Future18_REST();$req=new WP_REST_Request();
foreach(array(
	array('mastery',array()),array('review_queue',array($req)),array('portfolio',array($req)),array('mentorships',array()),array('cpd_get',array()),array('change_impacts',array($req)),array('center',array()),array('learning_path',array($req)),array('mistakes',array($req))
) as $test) {
	$res=call_user_func_array(array($routes,$test[0]),$test[1]);
	check_l($res instanceof WP_Error,$test[0].' REST propagates WP_Error without invoking response header()');
}
$GLOBALS['wpdb']->failedTable='wp_lsch_f18_review_queue';
$result=LSCH_Future18::build_learning_path(18,'balanced_mastery',false);
check_l($result instanceof WP_Error&&$result->code==='lsch_future18_review_read_failed','learning-path ranking cannot mask failed review history');
$center=LSCH_Future18::center_snapshot(18);
check_l($center instanceof WP_Error,'center fails if one private child dependency has failed');
$GLOBALS['wpdb']->failed=false;
foreach($tests as $test){$res=call_user_func_array(array('LSCH_Future18',$test[0]),$test[1]);check_l(is_array($res),$test[0].' valid truly empty array');}
$res=$routes->mastery();
check_l($res instanceof WP_REST_Response && $res->headers['Cache-Control']==='private, no-store','healthy Future18 REST profile is private no-store');
$healthy=LSCH_Future18::center_snapshot(18);
check_l(is_array($healthy)&&is_array($healthy['portfolio']),'healthy center returns fully verified empty collections');
echo "PASS: Cycle L six Future18 owned private query failures, nine REST error pathways and healthy empty-state responses.\n";
