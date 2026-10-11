<?php
/** Cycle I: reconciliation is never marked finished on partial failed cleanup or correction scan. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$m='',$data=array()){$this->code=$c;}}
class LSCH_Database {public static function tables(){return array(
	'bookmarks'=>'wp_lsch_bookmarks','progress'=>'wp_lsch_progress','notes'=>'wp_lsch_notes',
	'reminders'=>'wp_lsch_reminders','staff'=>'wp_lsch_staff','jobs'=>'wp_lsch_jobs'
);}}
class LSCH_State {public static function tables(){return array('corrections'=>'wp_lsch_corrections','value_events'=>'wp_lsch_value_events');}}
class LSCH_Events {public static $audits=array();public static function audit($event,$type,$id,$data,$purpose){self::$audits[]=$event;}}
class ReconcileDB {
	public $posts='wp_posts',$users='wp_users',$failStep=-1,$step=0,$failRead=false,$queryLog=array();
	public function query($sql){
		$this->queryLog[]=$sql;
		$result=$this->failStep===$this->step?false:0;
		$this->step++;
		return $result;
	}
	public function get_results($sql,$format=null){return $this->failRead?false:array();}
}
$GLOBALS['wpdb']=new ReconcileDB();$GLOBALS['reconcileUpdates']=0;
function update_option($name,$value,$autoload=false){$GLOBALS['reconcileUpdates']++;return true;}
function __( $s,$d=''){return $s;}
function is_wp_error($x){return $x instanceof WP_Error;}
function check_i($x,$message){if(!$x){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
$GLOBALS['wpdb']->failStep=2;
$res=LSCH_Operations::daily_reconcile();
check_i($res instanceof WP_Error&&$res->code==='lsch_reconcile_failed','failed maintenance SQL cannot be successful');
check_i($GLOBALS['reconcileUpdates']===0,'failed housekeeping does not update last reconcile');
check_i(strpos(implode("\n",$GLOBALS['wpdb']->queryLog),'lsch_consents')===false,'automatic maintenance preserves historical patient consent');
$GLOBALS['wpdb']=new ReconcileDB();$GLOBALS['wpdb']->failRead=true;
$res=LSCH_Operations::daily_reconcile();
check_i($res instanceof WP_Error&&$res->code==='lsch_reconcile_failed','failed corrections queue read aborts reconciliation');
check_i($GLOBALS['reconcileUpdates']===0,'failed correction read does not mark completion');
$GLOBALS['wpdb']=new ReconcileDB();
$res=LSCH_Operations::daily_reconcile();
check_i($res===true&&$GLOBALS['reconcileUpdates']===1,'verified no-error maintenance completes and updates timestamp');
echo "PASS: Cycle I reconciliation errors, consent retention and success marker integrity.\n";
