<?php
/** File05 FR04/NFR03: publishing must fail closed on corrupt/unavailable evidence. */
define( 'ABSPATH', '/unit-wordpress/' );
class WP_Error { public $code; public function __construct($code,$message='',$data=array()){$this->code=$code;} }
class LSCH_Content {
    const LESSON='lsch_lesson'; const TOPIC='lsch_topic';
    public static $version_ok=true;
    public static function bump_version($post_id,$reason=''){return self::$version_ok ? 3 : 0;}
}
class LSCH_Capabilities {
    const MANAGE_CURRICULUM='lsch_manage_curriculum';
    public static function can_author($id){return true;}
    public static function is_founder($id){return false;}
    public static function can_review($id){return true;}
}
class LSCH_Policy {
    public static $consent=true;
    public static function central_policy_ready(){return true;}
    public static function valid_case_consent($id){return self::$consent;}
}
class LSCH_Value {public static function sources($id){return array(array('source'=>'verified'));}}
class LSCH_Events {
    public static $blocked=array(),$failed=array();
    public static function audit($action,$kind,$id,$context,$purpose){
        if('lesson_publication_blocked'===$action)self::$blocked[]=$context['reason'];
        if('lesson_publication_block_persist_failed'===$action)self::$failed[]=$context['reason'];
    }
}
$GLOBALS['terms']=array('foundations');
$GLOBALS['meta']=array('_lsch_reviewer_id'=>'23','_lsch_reviewer'=>'',
    '_lsch_objectives'=>'Objectives', '_lsch_sources'=>'Verified refs',
    '_lsch_accessibility'=>'Transcript', '_lsch_safety'=>'Limits');
$GLOBALS['reviewer_write_fail']=false;
$GLOBALS['tag_write_fail']=false;
$GLOBALS['tag_present']=false;
$GLOBALS['post_status']='publish';
$GLOBALS['wp_update_fail']=false;
function wp_is_post_revision($id){return false;}
function wp_is_post_autosave($id){return false;}
function current_user_can($cap){return false;}
function absint($v){return abs((int)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$key]??'';}
function update_post_meta($id,$key,$v){
    if('_lsch_reviewer'===$key&&$GLOBALS['reviewer_write_fail'])return false;
    $GLOBALS['meta'][$key]=$v;return true;
}
function wp_get_object_terms($id,$taxonomy,$args){return $GLOBALS['terms'];}
function is_wp_error($x){return $x instanceof WP_Error;}
function wp_set_post_tags($id,$tags,$append){
    if($GLOBALS['tag_write_fail'])return new WP_Error('tag_write_failed');
    $GLOBALS['tag_present']=true;return array(1);
}
function has_term($tag,$taxonomy,$post_id){return $GLOBALS['tag_present'];}
function remove_action(...$args){}
function add_action(...$args){}
function wp_update_post($row,$wp_error=false){if($GLOBALS['wp_update_fail'])return new WP_Error('post_update_db_error');$GLOBALS['post_status']=$row['post_status'];return (int)$row['ID'];}
function get_post_status($id){return $GLOBALS['post_status'];}
function __( $msg,$domain=''){return $msg;}
function require_true($ok,$label){if(!$ok){fwrite(STDERR,"FAIL: ".$label."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php';
$plugin=new LSCH_Plugin();
$post=(object)array('ID'=>71,'post_author'=>9,'post_status'=>'publish');
function reset_pub(){
    $GLOBALS['post_status']='publish';
    $GLOBALS['tag_present']=false;
    LSCH_Events::$blocked=array();
    LSCH_Events::$failed=array();
    $GLOBALS['wp_update_fail']=false;
    LSCH_Content::$version_ok=true;
    LSCH_Policy::$consent=true;
    $GLOBALS['reviewer_write_fail']=false;
    $GLOBALS['tag_write_fail']=false;
    $GLOBALS['terms']=array('foundations');
    $GLOBALS['meta']['_lsch_reviewer']='';
}
function must_block($reason){
    require_true($GLOBALS['post_status']==='pending','Unverified publication made pending: '.$reason);
    require_true(in_array($reason,LSCH_Events::$blocked,true),'Audited rejection reason: '.$reason);
}
reset_pub();LSCH_Content::$version_ok=false;
$plugin->lesson_saved(71,$post,true);
must_block('content_version_unverified');

reset_pub();$GLOBALS['reviewer_write_fail']=true;
$plugin->lesson_saved(71,$post,false);
must_block('reviewer_evidence_unverified');

reset_pub();$GLOBALS['terms']=new WP_Error('taxonomy_db_failure');
$plugin->lesson_saved(71,$post,false);
must_block('patient_case_taxonomy_unavailable');

reset_pub();$GLOBALS['terms']=null;
$plugin->lesson_saved(71,$post,false);
must_block('patient_case_taxonomy_unavailable');

reset_pub();$GLOBALS['terms']=array('patient-case-learning');LSCH_Policy::$consent=false;
$plugin->lesson_saved(71,$post,false);
must_block('patient_case_consent_required');

reset_pub();$GLOBALS['terms']=array('patient-case-learning');$GLOBALS['tag_write_fail']=true;
$plugin->lesson_saved(71,$post,false);
must_block('patient_case_tag_unverified');

reset_pub();$GLOBALS['terms']=array('patient-case-learning');
$plugin->lesson_saved(71,$post,false);
require_true($GLOBALS['post_status']==='publish'&&$GLOBALS['tag_present'],'Valid consent + tag remain publishable');
require_true(!LSCH_Events::$blocked,'Healthy case does not trigger false rejection');
reset_pub();$GLOBALS['wp_update_fail']=true;LSCH_Content::$version_ok=false;
$plugin->lesson_saved(71,$post,true);
require_true($GLOBALS['post_status']==='publish','WordPress write failure remains visibly uncontained and needs operator action');
require_true(in_array('content_version_unverified',LSCH_Events::$failed,true),'Uncontained publishing failure is separately audited');
require_true(!LSCH_Events::$blocked,'Uncontained failure must not be falsely logged as successfully blocked');
echo "PASS: File05 publishing validates evidence and separately detects failed WordPress containment.\n";
