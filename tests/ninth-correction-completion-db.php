<?php
/** Cycle I: correction inbox and course completion cannot silently treat failed DB reads as empty. */
define('ABSPATH','/unit-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$msg='',$ctx=array()){$this->code=$code;}}
class LSCH_Content {const COURSE='lsch_course';const LESSON='lsch_lesson';
	public static function version($id){return 3;}
}
class LSCH_Database {
	public static function tables(){return array('progress'=>'wp_lsch_progress');}
}
class LSCH_Capabilities {const MANAGE_CURRICULUM='manage_curriculum';
	public static function can_review($id){return true;}
}
class LSCH_Events {public static $audits=array();public static function audit($name,$object,$id,$data,$purpose){self::$audits[]=$name;}}
class BrokenReadDB {
	public $prefix='wp_';
	public function prepare($query,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return vsprintf($query,$args);}
	public function get_results($sql,$format=null){return false;}
}
$GLOBALS['wpdb']=new BrokenReadDB();
function get_post_type($id){return (int)$id===9?'lsch_course':'lsch_lesson';}
function get_post_meta($id,$key,$single=true){return '';}
function get_posts($query){return array(7);}
function get_current_user_id(){return 4;}
function current_user_can($c){return false;}
function user_can($id,$cap,$object=null){return false;}
function absint($v){return abs((int)$v);}
function __( $s,$d=''){return $s;}
function is_wp_error($x){return $x instanceof WP_Error;}
function check_i($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php';
$method=new ReflectionMethod('LSCH_Services','recalculate_completion');$method->setAccessible(true);
$res=$method->invoke(null,9,4);
check_i($res instanceof WP_Error&&$res->code==='lsch_completion_evidence_unverified','failed evidence DB read returns retryable error');
check_i(in_array('completion_evidence_read_failed',LSCH_Events::$audits,true),'failed completion evidence audited');
$rows=LSCH_State::rest_corrections();
check_i($rows instanceof WP_Error&&$rows->code==='lsch_corrections_read_failed','failed correction queue read returns error before filtering');
echo "PASS: Cycle I completion evidence read and reviewer correction queue read failures.\n";
