<?php
/** Cycle R: CPD canonical ID, portfolio privacy and supervised academic projection failures. */
define('ABSPATH','/unit/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($c,$m='',$d=array()){$this->code=$c;}}
class LSCH_Policy {static function can_use_learning_actions($u=0){return true;}static function sanitize_json($v,$max=30000){return json_encode($v);}}
class LSCH_Capabilities {
 const TEACH='teach';const MANAGE_CURRICULUM='manage';
 static function verified_doctor($u){return true;}static function is_founder($u){return false;}
}
class LSCH_Database {static function uuid(){return 'test-public-id';}}
class LSCH_Events {
 static $pub=array(),$audit=array();
 static function publish($name,$type,$id,$body){self::$pub[]=$name;return true;}
 static function audit($name,$type,$id,$meta,$purpose){self::$audit[]=$name;}
}
class CPD_DB {
 public $prefix='wp_',$mode='healthy',$last_error='',$insert_id=0,$writes=0;
 function prepare($sql,...$args){return vsprintf($sql,$args);}
 function get_row($sql,$format=null){
  if($this->mode==='privacy_read_fail'&&strpos($sql,'f18_portfolio')!==false){$this->last_error='connection error';return null;}
  $this->last_error='';
  if(strpos($sql,'f18_portfolio')!==false)return array('id'=>7,'public_id'=>'p7','user_id'=>21,'visibility'=>'private','version'=>1);
  if(strpos($sql,'f18_mentorship')!==false)return array('id'=>3,'version'=>1,'feedback_json'=>'[]','mentor_id'=>22,'learner_id'=>21,'course_id'=>9);
  if(strpos($sql,'f18_cpd')!==false)return array('id'=>55,'user_id'=>21,'version'=>1,'status'=>'self_recorded','public_id'=>'cpd55','competency_key'=>'case_taking','minutes'=>120);
  if(strpos($sql,'f18_mastery')!==false)return array('id'=>4,'mastery_score'=>55,'confidence'=>10,'evidence_count'=>1,'version'=>1);
  return null;
 }
 function get_var($sql){$this->last_error='';return null;}
 function update($table,$v,$where,$f,$wf){
  $this->writes++;
  if($this->mode==='mastery_fail' && strpos($table,'f18_mastery')!==false)return false;
  return 1;
 }
 function insert($table,$row,$fmt){
  $this->writes++;
  if(strpos($table,'f18_portfolio')!==false){$this->insert_id=99;return $this->mode==='portfolio_fail'?false:1;}
  $this->insert_id=55;return 1;
 }
}
function absint($v){return abs((int)$v);}function sanitize_key($v){return strtolower((string)$v);}
function sanitize_text_field($v){return trim((string)$v);}function sanitize_textarea_field($v){return trim((string)$v);}
function __($v,$d=''){return $v;}function user_can($uid,$cap){return true;}
function current_time($fmt,$gmt=true){return '2026-10-12 21:00:00';}
function is_wp_error($v){return $v instanceof WP_Error;}
function current_user_can($cap){return true;}
function checkr($ok,$msg){if(!$ok){fwrite(STDERR,'FAIL: '.$msg."\\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='privacy_read_fail';
$r=LSCH_Future18::set_portfolio_visibility(21,7,'shareable_by_consent',1);
checkr($r instanceof WP_Error&&$r->code==='lsch_future18_portfolio_visibility_read_failed','private portfolio DB read is typed outage');
checkr($GLOBALS['wpdb']->writes===0,'no privacy visibility mutation on unknown record');
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='portfolio_fail';LSCH_Events::$audit=array();
$r=LSCH_Future18::mentor_feedback(22,3,array('comment'=>'review'),1);
checkr($r instanceof WP_Error&&$r->code==='lsch_future18_mentor_portfolio_pending','saved mentor feedback cannot hide failed portfolio');
checkr(in_array('future18_mentor_portfolio_pending',LSCH_Events::$audit,true),'mentor partial state audited');
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='portfolio_fail';LSCH_Events::$pub=array();
$r=LSCH_Future18::record_cpd(21,array('minutes'=>80,'activity_type'=>'learning','competency_key'=>''));
checkr($r instanceof WP_Error&&$r->code==='lsch_future18_cpd_portfolio_pending','CPD portfolio write failure acknowledged');
checkr(!in_array('LearningCPDRecorded.v1',LSCH_Events::$pub,true),'no false complete CPD recorded event');
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='healthy';
$r=LSCH_Future18::record_cpd(21,array('minutes'=>80,'activity_type'=>'learning','competency_key'=>''));
checkr(is_array($r)&&$r['id']===55,'CPD uses original row ID not the subsequent portfolio insert ID');
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='portfolio_fail';LSCH_Events::$pub=array();
$r=LSCH_Future18::verify_cpd(55,22,1);
checkr($r instanceof WP_Error&&$r->code==='lsch_future18_verified_cpd_portfolio_pending','verified CPD cannot hide failed portfolio write');
checkr(!in_array('LearningCPDVerified.v1',LSCH_Events::$pub,true),'no verified CPD success event on incomplete portfolio');
$GLOBALS['wpdb']=new CPD_DB();$GLOBALS['wpdb']->mode='mastery_fail';LSCH_Events::$pub=array();
$r=LSCH_Future18::verify_cpd(55,22,1);
checkr($r instanceof WP_Error&&$r->code==='lsch_future18_verified_cpd_mastery_pending','verified CPD cannot hide mastery update failure');
checkr(!in_array('LearningCPDVerified.v1',LSCH_Events::$pub,true),'no verified CPD event with missing mastery');
echo "PASS: Cycle R portfolio private history, CPD ID and projected academic evidence failure guards.\\n";
