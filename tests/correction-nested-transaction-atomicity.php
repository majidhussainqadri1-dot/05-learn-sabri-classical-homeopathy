<?php
/** Independent source-level regression: REST owns exactly one correction transaction. */
define('ABSPATH','/unit-wordpress/');
define('ARRAY_A','ARRAY_A');
class WP_Error {
    public $code;
    public function __construct($code,$message='',$data=array()) { $this->code=$code; }
}
class LSCH_Content {
    const LESSON='lsch_lesson';
    public static function object_type($id) {return 'lsch_lesson';}
    public static function version($id) {return 3;}
}
class LSCH_Database {
    public static function tables(){return array('progress'=>'wp_lsch_progress');}
    public static function now(){return '2026-10-15 17:00:00';}
}
class LSCH_Events {
    public static $events=array();
    public static function publish($name,$kind,$id,$payload){self::$events[]=$name;return 'event-1';}
    public static function audit($name,$kind,$id,$payload,$purpose){}
}
class AuditDB {
    public $queries=array(),$fail_progress=false,$locks_released=0;
    public function prepare($sql,...$args){return vsprintf($sql,$args);}
    public function get_var($query){if(strpos($query,'RELEASE_LOCK')!==false){$this->locks_released++;}return 1;}
    public function query($query){$this->queries[]=$query;if($this->fail_progress&&strpos($query,'UPDATE wp_lsch_progress')!==false){return false;}return 1;}
}
$GLOBALS['wpdb']=new AuditDB();
$GLOBALS['meta']=array();
function absint($v){return abs((int)$v);}
function sanitize_textarea_field($v){return trim((string)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function update_post_meta($id,$name,$value){if($name==='_lsch_correction_note'&&!empty($GLOBALS['fail_note_write']))return false;$GLOBALS['meta'][$name]=$value;return true;}
function get_post_meta($id,$name,$single=true){return $GLOBALS['meta'][$name]??'';}
function clean_post_cache($id){}
function __( $v,$domain=''){return $v;}
function is_wp_error($x){return $x instanceof WP_Error;}
function require_true($test,$why){if(!$test){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php';
$method=new ReflectionMethod('LSCH_State','apply_correction');
$method->setAccessible(true);
$ok=$method->invoke(null,19,'Review source and correct learning objectives',3);
require_true(4===$ok,'Correction increments canonical version');
require_true(in_array('LearningContentCorrected.v1',LSCH_Events::$events,true),'Versioned event queued after correction');
require_true(1===$GLOBALS['wpdb']->locks_released,'Advisory lock released after success');
foreach($GLOBALS['wpdb']->queries as $query){
  require_true(!preg_match('/^(START TRANSACTION|COMMIT|ROLLBACK)$/i',trim($query)),'Nested transaction boundary was not opened');
}
$GLOBALS['wpdb']->queries=array();
$GLOBALS['wpdb']->fail_progress=true;
$events_before=count(LSCH_Events::$events);
$bad=$method->invoke(null,19,'A follow-up evidence correction',3);
require_true($bad instanceof WP_Error&&$bad->code==='lsch_correction_apply_failed','Bad progress write fails and delegates rollback to request owner');
require_true(count(LSCH_Events::$events)===$events_before,'Failure does not publish correction event');
require_true(2===$GLOBALS['wpdb']->locks_released,'Advisory lock released on failure');
foreach($GLOBALS['wpdb']->queries as $query){
 require_true(!preg_match('/^(START TRANSACTION|COMMIT|ROLLBACK)$/i',trim($query)),'Failure path does not interfere with outer transaction');
}
$GLOBALS['wpdb']->fail_progress=false;
$GLOBALS['fail_note_write']=true;
$GLOBALS['wpdb']->queries=array();
$events_before=count(LSCH_Events::$events);
$previous_version=$GLOBALS['meta']['_lsch_version']??null;
$failed_note=$method->invoke(null,19,'A new note must persist independently',3);
require_true($failed_note instanceof WP_Error&&$failed_note->code==='lsch_correction_apply_failed','Failed source correction note returns error');
require_true(($GLOBALS['meta']['_lsch_version']??null)===$previous_version,'Failed correction note must not advance version');
require_true(count(LSCH_Events::$events)===$events_before,'Failed correction note cannot emit correction event');
require_true($GLOBALS['wpdb']->queries===array(),'Failed note must stop before dependent progress update');
echo "PASS: Correction avoids nested transactions and refuses unverified note provenance.\n";
