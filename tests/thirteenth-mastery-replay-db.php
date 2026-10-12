<?php
/** Cycle M: Future18 competency history and due-review schedule must be authoritative, never guessed. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Policy {public static function can_use_learning_actions($u=0){return true;}}
class LSCH_Database {public static function uuid(){return 'cycle-m';}}
class MasteryDB {
	public $prefix='wp_',$last_error='',$mode='history_error',$writes=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($sql,$format=null){
		if($this->mode==='history_error'){$this->last_error='failed to read prior competency row';return null;}
		$this->last_error='';
		return array('id'=>6,'mastery_score'=>68,'confidence'=>20,'evidence_count'=>3,'version'=>1);
	}
	public function get_var($sql){
		if($this->mode==='schedule_error'){$this->last_error='review schedule unavailable';return false;}
		$this->last_error='';return null;
	}
	public function insert($table,$data,$formats){$this->writes++;return 1;}
	public function update($table,$data,$where,$formats,$whereFormats){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new MasteryDB();
function absint($v){return abs((int)$v);}function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return (string)$v;}function current_time($fmt,$gmt=true){return '2026-10-12 12:00:00';}
function __($s,$domain=''){return $s;}function is_wp_error($x){return $x instanceof WP_Error;}
function check_m($pass,$message){if(!$pass){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$fail=LSCH_Future18::record_mastery_evidence(27,'case_taking',74,1.2,'lesson',44,false);
check_m($fail instanceof WP_Error && $fail->code==='lsch_future18_mastery_history_read_failed','failed old mastery read cannot be treated as absent competency');
check_m($GLOBALS['wpdb']->writes===0,'failed prior mastery read performs no academic evidence insert');
$GLOBALS['wpdb']->mode='schedule_error';
$fail=LSCH_Future18::record_mastery_evidence(27,'case_taking',74,1.2,'lesson',44,true);
check_m($fail instanceof WP_Error && $fail->code==='lsch_future18_mastery_schedule_read_failed','failed spaced-review due date cannot be silently recalculated');
check_m($GLOBALS['wpdb']->writes===0,'failed existing review schedule performs no mastery update');
echo "PASS: Cycle M Future18 mastery history and spaced-review schedule database failures.\n";
