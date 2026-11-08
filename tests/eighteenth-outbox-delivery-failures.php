<?php
/** Cycle R: File19 outbox failure injection, bounded retry and lock safety. */
define('ABSPATH','/unit/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Database {static function tables(){return array('outbox'=>'wp_outbox');}static function now(){return '2026-10-12 17:00:00';}}
class LSCH_Events {static $audits=array();static function audit($name,$type,$id,$data,$purpose){self::$audits[]=$name;}}
class ODB {
 public $mode='healthy',$last_error='',$unlocks=0,$writes=array(),$dispatchCalls=0;
 function prepare($sql,...$args){return vsprintf($sql,$args);}
 function get_results($sql,$format=null){return array(array('id'=>7));}
 function get_var($sql){if(strpos($sql,'RELEASE_LOCK')!==false){$this->unlocks++;return 1;}return 1;}
 function get_row($sql,$format=null){
  if($this->mode==='unreadable'){$this->last_error='temporary SQL outage';return false;}
  $this->last_error='';
  return array('id'=>7,'event_name'=>'LearningChanged.v1','event_id'=>'test7','status'=>'pending','attempts'=>0,'payload_json'=>$this->mode==='invalid'?'BROKEN': '{"lesson_id":7}');
 }
 function update($table,$row,$where,$formats,$whereformats){$this->writes[]=$row;return $this->mode==='write_fail'?false:1;}
}
function get_option($k,$default=false){return $default;}
function absint($n){return abs((int)$n);}
function sanitize_key($v){return strtolower((string)$v);}
function __($v,$d=''){return $v;}
function apply_filters($name,$value,...$args){if($name==='lsch_dispatch_event'){
 $GLOBALS['wpdb']->dispatchCalls++;
 if($GLOBALS['wpdb']->mode==='throws')throw new RuntimeException('private content must never appear in log');
 if($GLOBALS['wpdb']->mode==='rejected')return false;
 return true;
}return $value;}
function checkr($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: ".$msg."\\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
foreach(array('unreadable','invalid','throws','rejected','write_fail','healthy') as $mode){
 $GLOBALS['wpdb']=new ODB();$GLOBALS['wpdb']->mode=$mode;LSCH_Events::$audits=array();
 $result=LSCH_Operations::process_outbox();
 checkr($GLOBALS['wpdb']->unlocks===1,'outbox lock must always release');
 if($mode==='unreadable'){checkr($result instanceof WP_Error&&$result->code==='lsch_outbox_item_read_failed','failed read reports 503');checkr(count($GLOBALS['wpdb']->writes)===0,'failed read cannot update');}
 if($mode==='invalid'){checkr($GLOBALS['wpdb']->dispatchCalls===0,'bad payload not dispatched');checkr($GLOBALS['wpdb']->writes[0]['last_error_code']==='invalid_payload','invalid payload recorded');}
 if($mode==='throws'){checkr($GLOBALS['wpdb']->writes[0]['last_error_code']==='consumer_exception','consumer throw becomes retry');checkr(in_array('outbox_consumer_exception',LSCH_Events::$audits,true),'consumer exception audited');}
 if($mode==='rejected'){checkr($GLOBALS['wpdb']->writes[0]['status']==='retry','rejected consumer bounded retry');}
 if($mode==='write_fail'){checkr($result instanceof WP_Error&&$result->code==='lsch_outbox_terminal_state_unverified','failed terminal SQL reports 503');}
 if($mode==='healthy'){checkr($GLOBALS['wpdb']->writes[0]['status']==='processed','processed event acknowledged only after durable SQL');}
}
echo "PASS: Cycle R outbox payload, consumer exception, item read, terminal write, lock release.\\n";
