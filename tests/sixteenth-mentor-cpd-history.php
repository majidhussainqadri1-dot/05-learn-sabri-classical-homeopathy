<?php
/** Cycle P, F05-FUT-15/16: a broken prior authority read is never a real version conflict. */
define('ABSPATH','/unit-wp/'); define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Policy {public static function can_use_learning_actions($user=0){return true;}}
class LSCH_Capabilities {const TEACH='teach';const MANAGE_CURRICULUM='manage';}
class DB {
 public $prefix='wp_',$last_error='',$mode='fail',$writes=0;
 public function prepare($q,...$args){return vsprintf($q,$args);}
 public function get_row($sql,$format=null){$this->last_error=$this->mode==='fail'?'simulated database outage':'';return null;}
 public function update($t,$v,$w,$f,$wf){$this->writes++;return 1;}
 public function query($sql){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new DB();
function absint($v){return abs((int)$v);}function sanitize_key($v){return strtolower((string)$v);}
function __($v,$domain=''){return $v;}function user_can($user,$cap){return true;}
function is_wp_error($v){return $v instanceof WP_Error;}
function failp($pass,$message){if(!$pass){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$checks=array(
 array('mentor_feedback',array(9,22,array('note'=>'quality verified'),1),'lsch_future18_mentor_history_read_failed','lsch_future18_mentorship_conflict'),
 array('end_mentorship',array(9,22,1),'lsch_future18_mentorship_end_read_failed','lsch_future18_mentorship_end_conflict'),
 array('verify_cpd',array(22,9,1),'lsch_future18_cpd_history_read_failed','lsch_future18_cpd_verify_conflict'),
);
foreach($checks as $check){
 $GLOBALS['wpdb']->mode='fail';$result=call_user_func_array(array('LSCH_Future18',$check[0]),$check[1]);
 failp($result instanceof WP_Error&&$result->code===$check[2],$check[0].' DB outage uses explicit 503');
 $GLOBALS['wpdb']->mode='absent';$result=call_user_func_array(array('LSCH_Future18',$check[0]),$check[1]);
 failp($result instanceof WP_Error&&$result->code===$check[3],$check[0].' true missing record retains normal 409');
}
failp($GLOBALS['wpdb']->writes===0,'no mentorship or CPD mutation without verified old records');
echo "PASS: Cycle P mentoring, closure and CPD verification fail-closed history reads.\n";
