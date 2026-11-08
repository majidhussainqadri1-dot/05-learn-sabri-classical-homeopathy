<?php
define('ABSPATH','/unit/');define('ARRAY_A','ARRAY_A');define('DAY_IN_SECONDS',86400);
class WP_Error {public $code;function __construct($c,$m='',$d=array()){$this->code=$c;}}
class LSCH_Database {static function tables(){return array('jobs'=>'wp_jobs');}static function now(){return '2026-10-12 18:00:00';}}
class LSCH_Events {static $audits=array();static function audit($name,$type,$id,$data,$purpose){self::$audits[]=$name;}}
class WorkerDB {
 public $mode='healthy',$claims=0,$recoveries=0,$writes=array();
 function prepare($sql,...$args){return vsprintf($sql,$args);}
 function get_results($sql,$f=null){return array(array('id'=>7));}
 function query($sql){if(strpos($sql,"status='running'")!==false&&strpos($sql,"SET status='running'")!==false){$this->claims++;return 1;} $this->recoveries++;return $this->mode==='unrecoverable'?false:1;}
 function get_row($sql,$f=null){if($this->mode==='unreadable'||$this->mode==='unrecoverable')return false;return array('id'=>7,'job_type'=>'test_job','job_key'=>'test-7','payload_json'=>$this->mode==='invalid'?'broken':'{"id":7}','attempts'=>0,'max_attempts'=>3);}
 function update($table,$values,$where,$format,$whereFormat){$this->writes[]=$values;return $this->mode==='write_fail'?false:1;}
}
function get_option($k,$default=false){return $default;}
function absint($n){return abs((int)$n);}
function sanitize_key($v){return strtolower((string)$v);}
function __($v,$d=''){return $v;}
function apply_filters($name,$value,...$args){if($name==='lsch_run_job'){if($GLOBALS['wpdb']->mode==='throws')throw new Exception('worker failure');return true;}return $value;}
function verify($x,$message){if(!$x){fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
foreach(array('unreadable','unrecoverable','invalid','throws','write_fail','healthy') as $mode){
 $GLOBALS['wpdb']=new WorkerDB();$GLOBALS['wpdb']->mode=$mode;LSCH_Events::$audits=array();
 $response=LSCH_Operations::process_jobs();
 if($mode==='unreadable'){verify($GLOBALS['wpdb']->recoveries===1,'claimed unreadable job requeued');}
 if($mode==='unrecoverable'){verify($response instanceof WP_Error&&$response->code==='lsch_job_recovery_unverified','recovery failure is observable');}
 if($mode==='invalid'){verify($GLOBALS['wpdb']->writes[0]['last_error_code']==='invalid_payload','bad JSON not dispatched as valid');}
 if($mode==='throws'){verify($GLOBALS['wpdb']->writes[0]['last_error_code']==='handler_exception','consumer exception safely retried');}
 if($mode==='write_fail'){verify($response instanceof WP_Error&&$response->code==='lsch_job_terminal_state_unverified','terminal SQL failure is observable');}
 if($mode==='healthy'){verify($GLOBALS['wpdb']->writes[0]['status']==='completed','healthy job completes');}
}
echo "PASS: File05 job worker recovery, malformed payload, exceptions, and terminal DB failure.\n";
