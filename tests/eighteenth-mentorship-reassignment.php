<?php
define('ABSPATH','/unit/');define('ARRAY_A','ARRAY_A');
class WP_Error{public $code;function __construct($c,$m='',$d=array()){$this->code=$c;}}
class LSCH_Policy{static function can_use_learning_actions(){return true;}static function sanitize_json($value,$max=65535){return json_encode($value);}}
class LSCH_Capabilities{const MANAGE_CURRICULUM='manage';const TEACH='teach';}
class LSCH_Database{static function uuid(){return 'id';}}
class LSCH_Events{static $events=array();static function publish($n,$t,$id,$data){self::$events[]=$n;}}
class MockDB{public $prefix='wp_',$last_error='',$mode='ended',$writes=0;
function prepare($q,...$args){return vsprintf($q,$args);}
function get_row($q,$fmt=null){$this->last_error=$this->mode==='error'?'sql outage':'';if($this->mode==='error'||$this->mode==='empty')return null;return array('id'=>11,'status'=>$this->mode==='ended'?'ended':'active','version'=>3);}
function insert($t,$d,$f){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new MockDB();
function absint($n){return abs((int)$n);}function __($v,$d=''){return $v;}
function sanitize_key($v){return strtolower((string)$v);}
function current_time($fmt,$gmt=true){return '2026-10-13 08:00:00';}
function user_can($u,$cap){return true;}function current_user_can($cap){return true;}
function is_wp_error($x){return $x instanceof WP_Error;}
function checkit($c,$m){if(!$c){fwrite(STDERR,"FAIL: $m\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
foreach(array('ended'=>'lsch_future18_mentorship_reassignment_required','active'=>'lsch_future18_mentorship_reassignment_required','error'=>'lsch_future18_mentorship_history_read_failed') as $mode=>$error){$GLOBALS['wpdb']->mode=$mode;$r=LSCH_Future18::assign_mentor(7,8,9,array('goal'=>'test'));checkit($r instanceof WP_Error&&$r->code===$error,$mode.' fail-closed');checkit($GLOBALS['wpdb']->writes===0,'never mutate prior mentorship');}
$GLOBALS['wpdb']->mode='empty';$r=LSCH_Future18::assign_mentor(7,8,9,array('goal'=>'test'));checkit($r===true&&$GLOBALS['wpdb']->writes===1,'genuinely new mentorship assigned');checkit(count(LSCH_Events::$events)===1,'only new record sends event');
echo "PASS: mentorship historical state cannot be silently reactivated.\n";
