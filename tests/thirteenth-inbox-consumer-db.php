<?php
/** Cycle M: File19-style inbox replay must not process an event when prior identity history cannot be read. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c){$this->code=$c;}}
class LSCH_Database {
	public static function tables(){return array('inbox'=>'wp_lsch_inbox','audit'=>'wp_lsch_audit');}
	public static function uuid(){return 'c71349c8-cfb8-4525-9a92-03bdf2976112';}
	public static function now(){return '2026-10-12 14:00:00';}
}
class LSCH_Policy {public static function request_id(){return 'cycle-m';}}
class LSCH_Dependencies {public static $auditNames=array();public static function audit($name,$context){self::$auditNames[]=$name;}}
class InboundDB {
	public $last_error='',$mode='unavailable',$locks=0,$unlocks=0,$inserts=array(),$updates=0;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_var($sql){if(strpos($sql,'RELEASE_LOCK')!==false){$this->unlocks++;return 1;}if(strpos($sql,'GET_LOCK')!==false){$this->locks++;return 1;}return 0;}
	public function get_row($sql,$style=null){
		if($this->mode==='unavailable'){$this->last_error='database read error';return null;}
		$this->last_error='';
		return array('status'=>'processed','payload_hash'=>$GLOBALS['correctHash']);
	}
	public function insert($table,$row,$fmt){$this->inserts[]=$table;return 1;}
	public function query($sql){throw new Exception('unexpected inbox mutation');}
	public function update($table,$row,$where,$fmt,$wherefmt){$this->updates++;return 1;}
}
$GLOBALS['wpdb']=new InboundDB();
$GLOBALS['correctHash']=hash('sha256',json_encode(array('course'=>9),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
function __($t,$domain=''){return $t;}function absint($v){return abs((int)$v);}
function sanitize_key($t){return strtolower((string)$t);}
function sanitize_text_field($t){return trim((string)$t);}
function get_current_user_id(){return 19;}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function is_wp_error($v){return $v instanceof WP_Error;}
function check_m($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-events.php';
$calls=0;
$handler=function($payload)use(&$calls){$calls++;return true;};
$failed=LSCH_Events::consume('event-1','CourseCompleted.v1',array('course'=>9),$handler);
check_m($failed===false,'failed inbox prior row read cannot claim processed event');
check_m($calls===0 && $GLOBALS['wpdb']->updates===0,'failed DB read must not execute event handler or mutate inbox');
check_m($GLOBALS['wpdb']->unlocks===1,'failed inbox lookup releases named lock');
check_m(in_array('inbox_history_read_failed',LSCH_Dependencies::$auditNames,true),'failed inbox lookup audited with bounded event name');
$GLOBALS['wpdb']->mode='processed';
$ok=LSCH_Events::consume('event-1','CourseCompleted.v1',array('course'=>9),$handler);
check_m($ok===true && $calls===0,'confirmed processed event deduplicates idempotently');
check_m($GLOBALS['wpdb']->unlocks===2,'successful replay releases its named lock');
echo "PASS: Cycle M exact-once inbox consumer fails closed when original File19 event history cannot be verified.\n";
