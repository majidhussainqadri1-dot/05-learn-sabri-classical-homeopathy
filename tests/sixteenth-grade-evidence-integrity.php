<?php
/** Cycle P: completed academic transitions must not claim successful downstream competency/portfolio evidence. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Policy {
 public static function can_use_learning_actions($user=0){return true;}
 public static function sanitize_json($v,$limit){return json_encode($v);}
}
class LSCH_Capabilities {const MANAGE_CURRICULUM='manage';const ASSESS='assess';}
class LSCH_Database {
 public static function uuid(){return 'cycle-p-uuid';}
 public static function tables(){return array('staff'=>'wp_staff');}
}
class LSCH_Events {
 public static $pub=array(),$audit=array();
 public static function publish($event,$owner,$id,$payload){self::$pub[]=$event;return true;}
 public static function audit($event,$owner,$id,$payload,$scope){self::$audit[]=$event;return true;}
}
class DB {
 public $prefix='wp_',$last_error='',$mode='mastery_fail',$academicWrites=0,$masteryWrites=0,$portfolioWrites=0;
 public function prepare($sql,...$args){return vsprintf($sql,$args);}
 public function get_row($sql,$format=null){
  $this->last_error='';
  if(strpos($sql,'f18_review_queue')!==false)return array('id'=>3,'user_id'=>21,'version'=>1,'interval_days'=>3,'ease'=>2.5,'competency_key'=>'case_taking');
  if(strpos($sql,'f18_practice')!==false)return array('id'=>5,'user_id'=>21,'version'=>1,'status'=>'awaiting_assessor','source_type'=>'lesson','source_id'=>8,'competency_key'=>'case_taking','mode'=>'oral_viva','public_id'=>'practice-5');
  if(strpos($sql,'f18_mastery')!==false)return array('id'=>44,'user_id'=>21,'mastery_score'=>68,'confidence'=>18,'evidence_count'=>1,'version'=>1);
  return null;
 }
 public function get_results($sql,$format=null){return array();}
 public function update($table,$values,$where,$format,$whereFormat){
  if(strpos($table,'f18_mastery')!==false){$this->masteryWrites++;return $this->mode==='mastery_fail'?false:1;}
  $this->academicWrites++;return 1;
 }
 public function insert($table,$values,$format){
  $this->portfolioWrites++;
  return $this->mode==='portfolio_fail'?false:1;
 }
}
$GLOBALS['wpdb']=new DB();
function absint($v){return abs((int)$v);}function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return trim((string)$v);}function sanitize_textarea_field($v){return trim((string)$v);}
function current_time($format,$gmt=true){return '2026-10-12 16:00:00';}
function __($text,$domain=''){return $text;}
function is_wp_error($v){return $v instanceof WP_Error;}
function user_can($user,$cap){return true;}
function checkp($cond,$message){if(!$cond){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$review=LSCH_Future18::record_review_result(21,3,4,1);
checkp($review instanceof WP_Error&&$review->code==='lsch_future18_review_mastery_pending','saved spaced-review without mastery fails with partial-state reconciliation 503');
checkp(in_array('future18_review_mastery_pending',LSCH_Events::$audit,true),'review post-write missing mastery audit');
LSCH_Events::$pub=array();
$grade=LSCH_Future18::grade_practice(5,30,86,array('ok'=>true),1);
checkp($grade instanceof WP_Error&&$grade->code==='lsch_future18_practice_mastery_pending','graded practice with mastery DB failure is not reported as success');
checkp(!in_array('LearningPracticeGraded.v1',LSCH_Events::$pub,true),'incomplete graded practice does not broadcast completed event');
$GLOBALS['wpdb']->mode='portfolio_fail';LSCH_Events::$pub=array();
$grade=LSCH_Future18::grade_practice(5,30,86,array('ok'=>true),1);
checkp($grade instanceof WP_Error&&$grade->code==='lsch_future18_practice_portfolio_pending','graded practice and mastery do not hide missing portfolio record');
checkp(!in_array('LearningPracticeGraded.v1',LSCH_Events::$pub,true),'missing portfolio does not publish misleading success event');
checkp($GLOBALS['wpdb']->academicWrites>=3,'test exercises academic write-before-failure partial states');
echo "PASS: Cycle P failed post-grading mastery and portfolio evidence do not falsely return success.\n";
