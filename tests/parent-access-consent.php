<?php
/** File05 parent-object inheritance and taxonomy-error fail-closed behavior. */
define('ABSPATH','/stub-wp/');
define('ARRAY_A','ARRAY_A');
class WP_Post { public $ID; public $post_type; public $post_status;
	public function __construct($id,$type,$status='publish'){$this->ID=$id;$this->post_type=$type;$this->post_status=$status;}
}
class WP_Error {}
class LSCH_Content {
	const PROGRAM='lsch_program'; const COURSE='lsch_course'; const BOOK='lsch_book';
	const LESSON='lsch_lesson'; const ASSESSMENT='lsch_assessment'; const ASSIGNMENT='lsch_assignment';
	const TOPIC='lsch_topic';
	public static function access($id){return $GLOBALS['access'][$id]??'public';}
	public static function object_type($id){return get_post_type($id)?:'';}
}
class LSCH_Dependencies { public static function governing_policy_ready(){return true;} }
class LSCH_Capabilities {
	const MANAGE_CURRICULUM='manage_lsch_curriculum';
	public static function approved_account($id){return 77===(int)$id;}
	public static function guardian_gate_passes($id){return 77===(int)$id;}
}
class LSCH_Database { public static function tables(){return array('consents'=>'wp_lsch_consents');} }
class StubPolicyDB{
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_row($q,$type){return null;}
}
$GLOBALS['wpdb']=new StubPolicyDB();
$GLOBALS['posts']=array(
	10=>new WP_Post(10,LSCH_Content::LESSON),
	20=>new WP_Post(20,LSCH_Content::COURSE),
	30=>new WP_Post(30,LSCH_Content::PROGRAM),
	40=>new WP_Post(40,LSCH_Content::BOOK),
	50=>new WP_Post(50,LSCH_Content::LESSON),
	60=>new WP_Post(60,LSCH_Content::LESSON),
);
$GLOBALS['access']=array(10=>'public',20=>'restricted',30=>'public',40=>'public',50=>'public',60=>'public');
$GLOBALS['meta']=array(10=>array('_lsch_course_id'=>20),20=>array('_lsch_program_id'=>30),60=>array('_lsch_book_id'=>40));
$GLOBALS['topic_failure']=false;
function absint($x){return abs((int)$x);}
function get_post($id){return $GLOBALS['posts'][$id]??null;}
function get_post_type($id){$p=get_post($id);return $p?$p->post_type:false;}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function get_current_user_id(){return 0;}
function user_can($uid,$cap,$post=0){return (int)$uid===77 && in_array($cap,array('manage_lsch_curriculum','edit_post'),true);}
function wp_get_object_terms($id,$tax,$args){if(array_key_exists('malformed_topic_response',$GLOBALS)&&50===(int)$id)return $GLOBALS['malformed_topic_response'];return $GLOBALS['topic_failure'] && 50===(int)$id?new WP_Error():array('foundation');}
function is_wp_error($x){return $x instanceof WP_Error;}
function apply_filters($name,$default,...$args){return $default;}
function __( $msg,$d=''){return $msg;}
function fail_if($cond,$why){if(!$cond){fwrite(STDERR,"FAIL: ".$why."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php';
fail_if(!LSCH_Policy::publicly_visible(10),'published public lesson in restricted course cannot appear on public listings');
fail_if(!LSCH_Policy::can_read_post(10,0),'published public lesson in restricted course cannot be read as guest');
fail_if(LSCH_Policy::can_read_post(10,77),'authorized curriculum manager may read lesson with governed restricted course');
$GLOBALS['access'][20]='public';
fail_if(LSCH_Policy::publicly_visible(10),'lesson becomes public only when its course is also public');
$GLOBALS['access'][30]='restricted';
fail_if(!LSCH_Policy::publicly_visible(10),'restricted program ancestor hides public course and lesson');
$GLOBALS['access'][30]='public';
$GLOBALS['posts'][20]->post_status='draft';
fail_if(!LSCH_Policy::publicly_visible(10),'unpublished parent course blocks public lesson');
$GLOBALS['posts'][20]->post_status='publish';
$GLOBALS['posts'][40]->post_status='draft';
fail_if(!LSCH_Policy::publicly_visible(60),'unpublished book parent blocks a public lesson');
$GLOBALS['posts'][40]->post_status='publish';
$GLOBALS['topic_failure']=true;
fail_if(!LSCH_Policy::publicly_visible(50),'topic lookup WP_Error fails closed on public listing');
fail_if(!LSCH_Policy::can_read_post(50,0),'topic lookup WP_Error fails closed for guest content read');
fail_if(LSCH_Policy::can_read_post(50,77),'editor retains legitimate draft/case review access');
$GLOBALS['topic_failure']=false;
fail_if(LSCH_Policy::publicly_visible(50),'healthy standalone public lesson still readable');
foreach(array(null,false,'not-an-array',array((object)array('slug'=>'patient-case-learning')),array('')) as $malformed) {
    $GLOBALS['malformed_topic_response']=$malformed;
    fail_if(!LSCH_Policy::publicly_visible(50),'Malformed lesson topics cannot become guest-public');
    fail_if(!LSCH_Policy::can_read_post(50,0),'Malformed lesson topics cannot authorize guest read');
    fail_if(LSCH_Policy::can_read_post(50,77),'Authorized editor retains governed repair access');
}
unset($GLOBALS['malformed_topic_response']);
fail_if(LSCH_Policy::publicly_visible(50),'Valid topics restore public read');
echo "PASS: File05 governed parent access inheritance, malformed taxonomy reads and editorial repair access.\n";
