<?php
/** Cycle H: course analytics cannot disguise DB outages as zero counts. */
define('ABSPATH','/unit-wp/');
class WP_Error {public $code;function __construct($c,$m='',$data=array()){$this->code=$c;}}
class LSCH_Capabilities {const MANAGE_CURRICULUM='manage_curriculum';}
class LSCH_Policy {public static function can_use_learning_actions(){return true;}}
class LSCH_Database {public static function tables(){return array('enrollments'=>'wp_enroll','progress'=>'wp_progress');}}
class DBH {
	public $fail='',$enrollments=6,$completed=3,$active=3,$contributors=6,$avg=70.5;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_var($sql) {
		$key=strpos($sql,'AVG(percent)')!==false?'average':(strpos($sql,'COUNT(DISTINCT')!==false?'contributors':(strpos($sql,"status='completed'")!==false?'completed':(strpos($sql,"status='active'")!==false?'active':'enrollments')));
		if($this->fail===$key)return false;
		if($key==='average')return $this->avg;
		return $this->$key;
	}
}
$GLOBALS['wpdb']=new DBH();
function current_user_can($cap){return true;}function get_current_user_id(){return 8;}
function absint($id){return abs((int)$id);}function __($s,$d=''){return $s;}
function check_h($ok,$why){if(!$ok){fwrite(STDERR,"FAIL: $why\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
foreach(array('enrollments','completed','active','contributors','average') as $errorAt) {
	$GLOBALS['wpdb']->fail=$errorAt;
	$result=LSCH_Services::course_analytics(17);
	check_h($result instanceof WP_Error && $result->code==='lsch_analytics_read_failed',"failed $errorAt count/average must be error");
}
$GLOBALS['wpdb']->fail='';
$result=LSCH_Services::course_analytics(17);
check_h(is_array($result)&&$result['suppressed']===false && $result['enrollments']===6 && $result['average_progress']===70.5,'healthy analytics valid');
check_h($result['completed']===null&&$result['active']===null,'small subgroup counts are privacy-suppressed');
$GLOBALS['wpdb']->enrollments=4;
$result=LSCH_Services::course_analytics(17);
check_h(is_array($result)&&$result['suppressed']===true,'small total aggregate suppressed');
echo "PASS: cycle H course analytics verified counts, failure and small-cell privacy.\n";
