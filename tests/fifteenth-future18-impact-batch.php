<?php
/** Cycle O: knowledge-change batch is retriable after each authoritative database failure. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$msg='',$data=array()){$this->code=$c;}}
class LSCH_Policy {public static function can_use_learning_actions($u=0){return true;}}
class LSCH_Database {public static function tables(){return array('progress'=>'wp_progress');}public static function uuid(){return 'cycle-o-uuid';}}
class LSCH_Events {
 public static $published=array(),$audits=array();
 public static function publish($name,$type,$id,$data){self::$published[]=$name;return true;}
 public static function audit($name,$type,$id,$data,$purpose){self::$audits[]=$name;return true;}
 public static function enqueue(){return true;}
}
class DB {
 public $prefix='wp_',$last_error='',$mode='users_error',$writes=0,$insertCount=0;
 public function prepare($query,...$args){return vsprintf($query,$args);}
 public function get_col($sql){return $this->mode==='users_error'?false:array(21);}
 public function query($sql){$this->writes++;return $this->mode==='impact_write_error'?false:1;}
 public function get_var($sql){
  $this->last_error='';
  if($this->mode==='review_read_error'){$this->last_error='down';return false;}
  return null;
 }
 public function insert($table,$row,$formats){$this->insertCount++;return $this->mode==='review_write_error'?false:1;}
}
$GLOBALS['wpdb']=new DB();
function absint($v){return abs((int)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function sanitize_textarea_field($v){return trim((string)$v);}
function current_time($format,$gmt=true){return '2026-10-12 14:00:00';}
function __($text,$domain=''){return $text;}
function is_wp_error($v){return $v instanceof WP_Error;}
function check_o($yes,$msg){if(!$yes){fwrite(STDERR,"FAIL $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$payload=array('event_id'=>'change-canon-1','object_type'=>'lesson','object_id'=>7,'previous_version'=>1,'new_version'=>2,'competencies'=>array('case_taking'),'change_summary'=>'Updated lesson requires re-study');
foreach(array('users_error','impact_write_error','review_read_error','review_write_error') as $mode){
 $GLOBALS['wpdb']->mode=$mode;
 LSCH_Events::$published=array();
 $result=LSCH_Future18::run_job(null,'future18_impact_batch',$payload,'job-1');
 check_o(false===$result,'failed '.$mode.' cannot be acknowledged completed');
 check_o(!in_array('LearningRestudyRequired.v1',LSCH_Events::$published,true),'no false restudy summary on '.$mode);
}
$GLOBALS['wpdb']->mode='healthy';
LSCH_Events::$published=array();
$result=LSCH_Future18::run_job(null,'future18_impact_batch',$payload,'job-1');
check_o(true===$result,'verified valid full batch completes');
check_o(in_array('LearningRestudyRequired.v1',LSCH_Events::$published,true),'fully verified batch emits event');
echo "PASS: Cycle O Future18 batch read/write/review failure returns retriable false and verified processing succeeds.\n";
