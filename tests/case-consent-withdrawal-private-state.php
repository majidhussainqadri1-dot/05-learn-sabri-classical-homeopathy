<?php
/** Independent regression: patient-case withdrawal cannot claim success if public WordPress state remains visible. */
define('ABSPATH','/unit-wordpress/'); define('ARRAY_A','ARRAY_A');
class WP_Error {public $code; function __construct($code,$msg='',$d=array()){$this->code=$code;}}
class LSCH_Policy {
    public static function can_use_learning_actions($user){return true;}
    public static function can_manage_object($object,$user){return true;}
}
class LSCH_Database {
    public static function tables(){return array('consents'=>'wp_lsch_consents');}
    public static function now(){return '2026-10-15 17:00:00';}
}
class LSCH_Events {
    public static $events=array(),$audits=array();
    public static function publish($name,$type,$id,$data){self::$events[]=$name;return true;}
    public static function audit($name,$type,$id,$data,$scope){self::$audits[]=$name;}
}
class MockDB {
 public $withdrawals=0;
 public function prepare($query,...$args){return vsprintf($query,$args);}
 public function query($sql){$this->withdrawals++;return 1;}
}
$GLOBALS['wpdb']=new MockDB();
$GLOBALS['status']='publish';
$GLOBALS['mode']='error';
$GLOBALS['reason_mode']='pass';
$GLOBALS['reason_saved']='';
function absint($v){return abs((int)$v);}
function __( $v,$domain=''){return $v;}
function sanitize_textarea_field($v){return trim((string)$v);}
function get_post_status($id){return $GLOBALS['status'];}
function wp_update_post($payload,$error=false){
    if('error'===$GLOBALS['mode'])return new WP_Error('wp_update_failed');
    if('zero'===$GLOBALS['mode'])return 0;
    if('stale'===$GLOBALS['mode'])return (int)$payload['ID'];
    $GLOBALS['status']='private';return (int)$payload['ID'];
}
function update_post_meta($id,$key,$value){
    if('fail'===$GLOBALS['reason_mode'])return false;
    $GLOBALS['reason_saved']=$value;return true;
}
function get_post_meta($id,$key,$single=true){return $GLOBALS['reason_saved'];}
function is_wp_error($x){return $x instanceof WP_Error;}
function test_ok($check,$desc){if(!$check){fwrite(STDERR,"FAIL: ".$desc."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
foreach(array('error','zero','stale') as $mode){
    $GLOBALS['status']='publish';$GLOBALS['mode']=$mode;
    $old_events=count(LSCH_Events::$events);
    $result=LSCH_Services::withdraw_case_consent(71,7,'Patient withdrawal of permission');
    test_ok($result instanceof WP_Error&&$result->code==='lsch_consent_privacy_transition_failed','Public WordPress state failure is not success: '.$mode);
    test_ok(count(LSCH_Events::$events)===$old_events,'Failed public state must not emit successful withdrawal event');
}
$GLOBALS['status']='publish';$GLOBALS['mode']='ok';$GLOBALS['reason_mode']='fail';$GLOBALS['reason_saved']='';
$failure=LSCH_Services::withdraw_case_consent(71,7,'Withdrawal reason must persist');
test_ok($failure instanceof WP_Error&&$failure->code==='lsch_consent_withdrawal_reason_unverified','Failed withdrawal-reason write fails closed');
$GLOBALS['status']='publish';$GLOBALS['reason_mode']='pass';
$valid=LSCH_Services::withdraw_case_consent(71,7,'Valid patient withdrawal');
test_ok(true===$valid && 'private'===$GLOBALS['status'],'Successful privacy transition returns true');
test_ok(in_array('LearningCaseConsentWithdrawn.v1',LSCH_Events::$events,true),'Successful withdrawal emits durable event');
echo "PASS: Consent withdrawal confirms WordPress privacy state and reason evidence.\n";
