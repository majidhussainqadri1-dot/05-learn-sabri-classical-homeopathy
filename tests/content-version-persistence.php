<?php
/** File05 FR04/FR18: reject a non-persisted content version without false events. */
define( 'ABSPATH', '/unit-wordpress/' );
class LSCH_Events {
    public static $audits=array();
    public static function audit( $action, $type, $id, $context, $purpose ) { self::$audits[]=$action; }
}
$GLOBALS['meta_version']=array(7=>2,8=>2);
$GLOBALS['version_fail_post']=7;
$GLOBALS['version_events']=array();
function absint($v){return abs((int)$v);}
function sanitize_key($v){return strtolower((string)$v);}
function get_post_type($id){return 'lsch_lesson';}
function get_post_meta($post,$name,$single=true){return $name==='_lsch_version'?($GLOBALS['meta_version'][$post]??''):'';}
function update_post_meta($post,$name,$value){
    if($name==='_lsch_version'&&$post===$GLOBALS['version_fail_post'])return false;
    if($name==='_lsch_version')$GLOBALS['meta_version'][$post]=$value;
    return true;
}
function do_action($name,...$args){$GLOBALS['version_events'][]=array($name,$args);}
function must($flag,$msg){if(!$flag){fwrite(STDERR,"FAIL: ".$msg."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php';
$bad=LSCH_Content::bump_version(7,'academic_edit');
must(0===$bad,'Unpersisted version update is failure, not successful 3');
must(2===$GLOBALS['meta_version'][7],'Version write failure leaves persisted value unchanged');
must(count($GLOBALS['version_events'])===0,'No version-bumped event on failure');
must(in_array('learning_content_version_write_failed',LSCH_Events::$audits,true),'Failed version gets an audit');
$good=LSCH_Content::bump_version(8,'academic_edit');
must(3===$good&&3===$GLOBALS['meta_version'][8],'Persisted version increments exactly once');
must(count($GLOBALS['version_events'])===1&&'lsch_content_version_bumped'===$GLOBALS['version_events'][0][0],'Version event only after persisted value');
$again=LSCH_Content::bump_version(8,'repeat_same_request');
must(3===$again&&count($GLOBALS['version_events'])===1,'Same-request version guard does not double-announce');
echo "PASS: Content revision cannot claim a failed metadata write.\n";
