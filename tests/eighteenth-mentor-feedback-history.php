<?php
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$msg='',$meta=array()){$this->code=$c;}}
class LSCH_Capabilities {const TEACH='teach';}
class LSCH_Policy {public static function can_use_learning_actions($u=0){return true;}}
class LSCH_Events {public static $actions=array();public static function audit($a,$t,$i,$data,$purpose){self::$actions[]=$a;}}
class AuditDB {
 public $prefix='wp_',$writes=0,$feedback='corrupt';
 public function prepare($sql,...$args){return vsprintf($sql,$args);}
 public function get_row($sql,$format=null){return array('id'=>15,'mentor_id'=>25,'learner_id'=>26,'version'=>1,'status'=>'active','course_id'=>4,'feedback_json'=>$this->feedback);}
 public function update($table,$data,$where,$fmt,$wfmt){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new AuditDB();
function absint($v){return abs((int)$v);}
function __( $v,$domain=''){return $v;}
function user_can($id,$cap){return true;}
function is_wp_error($x){return $x instanceof WP_Error;}
function sanitize_key($v){return strtolower((string)$v);}
function check_r($truth,$why){if(!$truth){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
foreach(array('corrupt','{"unexpected":"object"}','{"0":{"feedback":"historic"}}','{"0":{},"1":{}}','null','[1,') as $corrupt){
 $GLOBALS['wpdb']->feedback=$corrupt;
 $result=LSCH_Future18::mentor_feedback(25,15,array('feedback'=>'academic mentoring quality'),1);
 check_r($result instanceof WP_Error&&$result->code==='lsch_future18_mentor_feedback_history_invalid','invalid old feedback JSON is typed failure');
 check_r($GLOBALS['wpdb']->writes===0,'malformed prior feedback cannot be overwritten');
}
check_r(in_array('future18_mentor_feedback_history_invalid',LSCH_Events::$actions,true),'historical evidence failure is audited');
$GLOBALS['wpdb']->feedback=json_encode(array_fill(0,100,array('feedback'=>'historic')));
$result=LSCH_Future18::mentor_feedback(25,15,array('feedback'=>'newest'),1);
check_r($result instanceof WP_Error&&$result->code==='lsch_future18_mentor_feedback_history_full','bounded mentor history must fail closed rather than truncate old evidence');
check_r($GLOBALS['wpdb']->writes===0,'at-capacity feedback never rewrites or truncates history');
check_r(in_array('future18_mentor_feedback_history_capacity_reached',LSCH_Events::$actions,true),'capacity conflict is audited');
echo "PASS: Corrupt and at-capacity mentor feedback history is never silently erased.\n";
