<?php
/** Seventh audit: failed operational metrics must not be falsely reported healthy. */
define('ABSPATH','/wp-stub/');define('LSCH_VERSION','4.0.0');define('LSCH_SCHEMA_VERSION',18);
class LSCH_Database {
	const OPTION='lsch_schema_version';
	public static function tables(){return array('outbox'=>'wp_lsch_outbox','jobs'=>'wp_lsch_jobs','notes'=>'wp_lsch_notes');}
}
class LSCH_State {const SCHEMA=3;const OPTION='lsch_state_schema_version';
	public static function tables(){return array('saved_searches'=>'wp_lsch_saved_searches');}
}
class LSCH_Future18 {const SCHEMA=2;const OPTION='lsch_future18_schema_version';
	public static function tables(){return array('mastery'=>'wp_lsch_mastery');}
}
class LSCH_Dependencies {public static function missing(){return array();}}
class LSCH_Policy {
	const NOTE_KEY_VERSION=2;
	public static function central_policy_ready(){return true;}
	public static function note_encryption_ready(){return true;}
	public static function note_write_key_version(){return 2;}
}
class LSCH_Events {public static function audit($a,$b,$c,$d,$e){}}
class HealthDB {
	public $failOutbox=false,$failJobs=false,$failNotes=false;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_var($sql){
		if(strpos($sql,'SHOW TABLES LIKE ')===0)return trim(substr($sql,strlen('SHOW TABLES LIKE ')));
		if(strpos($sql,'wp_lsch_outbox')!==false && strpos($sql,"status='dead'")!==false)return $this->failOutbox?false:0;
		if(strpos($sql,'wp_lsch_jobs')!==false && strpos($sql,"status='dead'")!==false)return $this->failJobs?false:0;
		if(strpos($sql,'wp_lsch_notes')!==false && strpos($sql,'key_version<>')!==false)return $this->failNotes?false:0;
		return null;
	}
}
$GLOBALS['wpdb']=new HealthDB();
function get_option($key,$default=false){
	$map=array('lsch_schema_version'=>18,'lsch_state_schema_version'=>3,'lsch_future18_schema_version'=>2,'lsch_page_map'=>array('home'=>10,'dashboard'=>11,'mastery'=>12),'lsch_safe_mode'=>false);
	return isset($map[$key])?$map[$key]:$default;
}
function wp_next_scheduled($name){return true;}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function check_h($yes,$message){if(!$yes){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
$ok=LSCH_Operations::system_check();
check_h($ok['status']==='pass','healthy metrics pass');
$GLOBALS['wpdb']->failOutbox=true;
$o=LSCH_Operations::system_check();
check_h($o['status']==='fail' && $o['checks']['Dead outbox']['status']==='fail','missing outbox count is failure');
$GLOBALS['wpdb']->failOutbox=false;$GLOBALS['wpdb']->failJobs=true;
$o=LSCH_Operations::system_check();
check_h($o['status']==='fail' && $o['checks']['Dead jobs']['status']==='fail','missing jobs count is failure');
$GLOBALS['wpdb']->failJobs=false;$GLOBALS['wpdb']->failNotes=true;
$o=LSCH_Operations::system_check();
check_h($o['status']==='fail' && $o['checks']['Private note key rotation']['status']==='fail','unverifiable key rotation is failed health');
echo "PASS: File05 health check never masks queue/rotation DB read errors as zero.\n";
