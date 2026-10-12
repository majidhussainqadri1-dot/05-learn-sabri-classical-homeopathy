<?php
/** Cycle O: authoritative educational evidence must not be falsely missing when SQL fails. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Policy {public static function can_use_learning_actions($u=0){return true;}}
class LSCH_Capabilities {const ASSESS='assess';const MANAGE_CURRICULUM='manage';}
class LSCH_Database {public static function uuid(){return 'cycle-o-uuid';}public static function tables(){return array('staff'=>'wp_staff');}}
class LSCH_Events {
 public static $published=array();
 public static function publish($name,$type,$id,$payload){self::$published[]=$name;return true;}
}
class DB {
 public $prefix='wp_',$last_error='',$mode='review_read_error',$writes=0;
 public function prepare($sql,...$args){return vsprintf($sql,$args);}
 public function get_row($sql,$type=null){
  if(in_array($this->mode,array('review_read_error','practice_read_error','impact_read_error'),true)){
   $this->last_error='database inaccessible';return null;
  }
  $this->last_error='';
  if(strpos($sql,'f18_impacts')!==false)return array('event_id'=>'rev-1','object_id'=>7,'competency_key'=>'case_taking');
  if(strpos($sql,'f18_mastery')!==false)return array('id'=>1,'mastery_score'=>75,'confidence'=>10,'evidence_count'=>1,'version'=>1);
  return null;
 }
 public function get_var($sql){
  $this->last_error='';
  if($this->mode==='review_evidence_error' || ($this->mode==='mastery_review_lookup_error' && strpos($sql,'SELECT id')!==false)){
   $this->last_error='SQL read failed';return false;
  }
  if($this->mode==='review_evidence_missing')return null;
  return null;
 }
 public function update($table,$values,$where,$fmt,$wherefmt){$this->writes++;return 1;}
 public function insert($table,$values,$fmt){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new DB();
function absint($v){return abs((int)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function current_time($fmt,$gmt=true){return '2026-10-12 15:00:00';}
function __($s,$d=''){return $s;}
function is_wp_error($x){return $x instanceof WP_Error;}
function user_can($uid,$cap){return true;}
function check_o($pass,$msg){if(!$pass){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$r=LSCH_Future18::record_review_result(21,5,4,1);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_review_history_read_failed','review record outage must not impersonate stale version');
$GLOBALS['wpdb']->mode='practice_read_error';
$r=LSCH_Future18::grade_practice(5,30,85,array('assessment'=>'recheck'),1);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_practice_history_read_failed','assessor cannot grade after practice record SQL error');
$GLOBALS['wpdb']->mode='impact_read_error';
$r=LSCH_Future18::resolve_impact(21,5);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_impact_history_read_failed','knowledge impact read error is 503 not a stale conflict');
$GLOBALS['wpdb']->mode='review_evidence_error';
$r=LSCH_Future18::resolve_impact(21,5);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_impact_review_read_failed','failed re-study check is not a learner disqualification');
$GLOBALS['wpdb']->mode='review_evidence_missing';
$r=LSCH_Future18::resolve_impact(21,5);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_restudy_required','verified lack of restudy remains genuine conflict');
check_o($GLOBALS['wpdb']->writes===0,'no academic mutation while previous evidence unknown');
$GLOBALS['wpdb']->mode='mastery_review_lookup_error';
LSCH_Events::$published=array();
$r=LSCH_Future18::record_mastery_evidence(21,'case_taking',86,1.0,'lesson',7,true);
check_o($r instanceof WP_Error&&$r->code==='lsch_future18_mastery_review_unverified','mastery update without verified scheduled review is retryable error');
check_o(!in_array('LearningMasteryUpdated.v1',LSCH_Events::$published,true),'unverified review does not broadcast false complete mastery event');
echo "PASS: Cycle O practice/review/impact evidence read failures and mastery review verification.\n";
