<?php
/** File05 fail-closed prerequisites; controlled DB stub, not staging. */
define('ABSPATH','/wp-stub/');
class WP_Error {public $code;function __construct($c,$m='',$data=array()){$this->code=$c;}}
class LSCH_Content { const COURSE='lsch_course'; public static function version($id){return 3;} }
class LSCH_Database { public static function tables(){return array('completions'=>'wp_lsch_completions');} }
class FakeDB {public $reads=0;function prepare($sql,...$a){return vsprintf($sql,$a);}function get_var($sql){$this->reads++;return $GLOBALS['has_completion']?9:0;}}
$GLOBALS['wpdb']=new FakeDB();
$GLOBALS['has_completion']=false;
$GLOBALS['prerequisites']='';
function absint($v){return abs((int)$v);}
function get_post_type($id){return (int)$id>=2 ?'lsch_course':false;}
function get_post_meta($id,$key,$single=true){return '_lsch_prerequisites'===$key ? $GLOBALS['prerequisites']:'';}
function apply_filters($name,$value,...$more){return $value;}
function __($s,$domain=''){return $s;}
function err($value){return $value instanceof WP_Error;}
function check($condition,$message){if(!$condition){fwrite(STDERR,'FAIL: '.$message."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php';
check(true===LSCH_Policy::validate_prerequisites(2,77),'truly absent prerequisites allowed');
foreach(array('invalid','abc,4','4,abc','0','4,-5','4,2.5','04','4,,5','4,0') as $invalid){
    $GLOBALS['prerequisites']=$invalid;
    $res=LSCH_Policy::validate_prerequisites(2,77);
    check(err($res)&&'lsch_prerequisite_configuration_invalid'===$res->code,'malformed prerequisite fails: '.$invalid);
}
$GLOBALS['prerequisites']='4,5';
check(err(LSCH_Policy::validate_prerequisites(2,77)),'incomplete prerequisite blocked');
$GLOBALS['has_completion']=true;
check(true===LSCH_Policy::validate_prerequisites(2,77),'valid completed prerequisites allowed');
$GLOBALS['prerequisites']='2';
check(err(LSCH_Policy::validate_prerequisites(2,77)),'self-referential prerequisite rejected');
echo "PASS: File05 strict course prerequisite validation.\n";
