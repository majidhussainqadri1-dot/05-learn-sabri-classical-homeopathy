<?php
/** File 05 core erasure: a failed query must never be reported as a completed request. */
define( 'ABSPATH', '/unit-wordpress/' );
$GLOBALS['core_hold'] = false;
function get_user_by( $field, $value ) { return 'email'===$field && 'test@example.test'===$value ? (object)array('ID'=>25) : false; }
function apply_filters( $name, $default, ...$args ) { return 'lsch_user_legal_hold'===$name ? $GLOBALS['core_hold'] : $default; }
function absint( $x ) { return abs((int)$x); }
function __( $s, $domain='' ) { return $s; }
class LSCH_Database { public static function tables() {
  return array_combine(array('progress','bookmarks','notes','reminders','request_keys','attempts','submissions','audit','consents','staff','enrollments','completions'),array_map(static function($x){ return 'wp_lsch_'.$x; },array('progress','bookmarks','notes','reminders','request_keys','attempts','submissions','audit','consents','staff','enrollments','completions')));
} }
class LSCH_State { public static function tables() {return array('saved_searches'=>'wp_lsch_saved_searches','value_events'=>'wp_lsch_value_events','corrections'=>'wp_lsch_corrections');} }
class LSCH_Events { public static function audit( $event, $object_type, $object_id, $data, $purpose ) {} }
class CoreDB {
 public $failDelete = false; public $failRetainedCount = false; public $statements = array();
 public function delete( $table, $data, $formats ) { return $this->failDelete && false!==strpos($table,'_progress') ? false : 0; }
 public function prepare( $sql, ...$arguments ) { return vsprintf($sql,$arguments); }
 public function query( $sql ) { $this->statements[]=$sql;return 0; }
 public function get_var( $sql ) { return $this->failRetainedCount ? false : 0; }
}
$GLOBALS['wpdb'] = new CoreDB();
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php';
function check_core( $condition,$name ){if(!$condition){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}}
$service=new LSCH_Privacy();
$GLOBALS['wpdb']->failDelete=true;
$failed=$service->erase('test@example.test',1);
check_core(false===$failed['done'],'partial failed erasure stays incomplete');
check_core(!empty($failed['messages']),'partial failure is disclosed to privacy operator');
$GLOBALS['wpdb']->failDelete=false;
$passed=$service->erase('test@example.test',1);
check_core(true===$passed['done'],'successful erasure can finish');
$GLOBALS['wpdb']->failRetainedCount=true;
$unverified=$service->erase('test@example.test',1);
check_core(false===$unverified['done'] && true===$unverified['items_retained'],'failed retained count requires retry and conservatively flags possible academic evidence');
$GLOBALS['wpdb']->failRetainedCount=false;
$GLOBALS['core_hold']=true;
$held=$service->erase('test@example.test',1);
check_core(true===$held['done'] && true===$held['items_retained'],'legal hold returns correctly retained state');
echo "PASS: File05 core privacy erasure failure/retry and legal-hold contract.\n";
