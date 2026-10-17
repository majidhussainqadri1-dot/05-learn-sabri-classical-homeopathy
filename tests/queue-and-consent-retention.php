<?php
/** Reliability and audit-retention regression without a deployed WordPress database. */
define( 'ABSPATH', '/unit-wordpress/' );
define( 'ARRAY_A', 'ARRAY_A' );
class LSCH_Database {
	public static function tables() {
		return array('outbox'=>'wp_lsch_outbox','jobs'=>'wp_lsch_jobs','bookmarks'=>'wp_lsch_bookmarks',
			'progress'=>'wp_lsch_progress','notes'=>'wp_lsch_notes','consents'=>'wp_lsch_consents',
			'reminders'=>'wp_lsch_reminders','staff'=>'wp_lsch_staff');
	}
}
class LSCH_State { public static function tables() { return array( 'corrections'=>'wp_lsch_corrections', 'value_events'=>'wp_lsch_value_events' ); } }
class LSCH_Events { public static $audit=array(); public static function audit($event,$type,$object,$details,$purpose){self::$audit[]=$event;} }
class QueueFailDB {
	public $posts='wp_posts'; public $users='wp_users'; public $queries=array();
	public function get_results($sql,$mode=null) {
		if (false!==strpos($sql,'wp_lsch_outbox') && false!==strpos($sql,"status IN ('pending','retry')"))return null;
		if (false!==strpos($sql,'wp_lsch_jobs') && false!==strpos($sql,"status IN ('pending','retry')"))return null;
		return array();
	}
	public function query($sql){$this->queries[]=$sql;return 0;}
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_var($sql){return 0;}
}
$GLOBALS['wpdb']=new QueueFailDB();
function get_option($key,$default=false){return $default;}
function apply_filters($hook,$default,...$args){return $default;}
function is_wp_error($value){return $value instanceof WP_Error;}
function absint($v){return abs((int)$v);}
function update_option($name,$value,$autoload=false){return true;}
function check_op($ok,$what){if(!$ok){fwrite(STDERR,"FAIL: ".$what."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
LSCH_Operations::process_outbox();
LSCH_Operations::process_jobs();
check_op(in_array('outbox_queue_read_failed',LSCH_Events::$audit,true),'outbox query failure audited safely');
check_op(in_array('jobs_queue_read_failed',LSCH_Events::$audit,true),'job queue query failure audited safely');
check_op(count($GLOBALS['wpdb']->queries)===0,'queue reads with DB failure make no state mutations');
LSCH_Operations::daily_reconcile();
$sql=implode("\n",$GLOBALS['wpdb']->queries);
check_op(false!==strpos($sql,'DELETE b FROM'),'regular orphan bookmark housekeeping still executes');
check_op(false===strpos($sql,'DELETE c FROM wp_lsch_consents'),'archived consent evidence survives daily reconcile');
echo "PASS: File05 queue read outage is fail-safe; historical consent evidence remains retained.\n";
