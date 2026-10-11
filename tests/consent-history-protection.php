<?php
/** Verify patient-case consent cannot be silently rewritten/revived after withdrawal. */
define('ABSPATH','/wordpress/');
define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($c,$message='',$data=array()){$this->code=$c;}}
class LSCH_Content {const LESSON='lsch_lesson';}
class LSCH_Policy {
	public static function can_use_learning_actions($uid){return true;}
	public static function can_manage_object($id,$uid){return true;}
	public static function valid_case_consent($id){return true;}
}
class LSCH_Database {public static function tables(){return array('consents'=>'wp_lsch_consents');}
	public static function now(){return '2026-10-11 08:00:00';}
}
class LSCH_Events {public static function audit($name,$type,$id,$ctx,$purpose){}}
class ConsentDB {
	public $existing=false;public $queries=array();
	public function prepare($sql,...$params){return vsprintf($sql,$params);}
	public function get_row($sql,$format=null){return $this->existing?array('id'=>27,'withdrawn_at'=>'2026-09-01 00:00:00'):null;}
	public function query($sql){$this->queries[]=$sql;return 1;}
}
$GLOBALS['wpdb']=new ConsentDB();
function get_post_type($id){return 'lsch_lesson';}
function absint($v){return abs((int)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function sanitize_textarea_field($v){return trim((string)$v);}
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function apply_filters($name,$value,...$args){return $value;}
function __($msg,$domain=''){return $msg;}
function is_wp_error($x){return $x instanceof WP_Error;}
function check_consent($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$data=array('consent_source'=>'patient','subject_type'=>'patient','scope'=>'education','evidence_reference'=>'patient-approval-evidence','policy_version'=>'case-consent-v1');
$GLOBALS['wpdb']->existing=true;
$res=LSCH_Services::store_case_consent(99,77,$data);
check_consent($res instanceof WP_Error&&$res->code==='lsch_consent_record_exists','previously withdrawn consent must reject same-policy reactivation');
check_consent(empty($GLOBALS['wpdb']->queries),'historical consent evidence must not be modified');
$GLOBALS['wpdb']->existing=false;
$res=LSCH_Services::store_case_consent(99,77,$data);
check_consent(is_array($res)&&!empty($res['valid']),'fresh supported consent works');
check_consent(count($GLOBALS['wpdb']->queries)===1,'exactly one canonical INSERT for fresh consent');
$sql=$GLOBALS['wpdb']->queries[0];
check_consent(false===strpos($sql,'ON DUPLICATE KEY UPDATE'),'consent history cannot be silently overwritten');
echo "PASS: File05 consent creation and withdrawn-history nonreactivation.\n";
