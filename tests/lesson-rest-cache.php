<?php
/** File05 lesson REST Cache-Control must reflect effective ancestor ACL and viewer privacy. */
define('ABSPATH','/wordpress/');
class WP_Error {}
class WP_REST_Request implements ArrayAccess {
	public function offsetExists($key){return $key==='id';}
	public function offsetGet($key){return 45;}
	public function offsetSet($key,$value){}
	public function offsetUnset($key){}
}
class WP_REST_Response {
	public $headers=array();public $data;
	function __construct($x){$this->data=$x;}
	function header($key,$value){$this->headers[$key]=$value;}
}
class LSCH_Content {const LESSON='lsch_lesson';const TOPIC='lsch_topic';
	public static function access($id){return 'public';}
	public static function version($id){return 1;}
}
class LSCH_Policy {
	public static function can_read_post($id){return true;}
	public static function publicly_visible($id){return $GLOBALS['public_eligible'];}
	public static function can_use_protected_reads(){return $GLOBALS['protected'];}
}
class LSCH_Operations {public static function related_links($type,$id){return array();}}
function get_post_type($id){return 'lsch_lesson';}
function get_post($id){return(object)array('post_content'=>'lesson text');}
function absint($x){return abs((int)$x);}
function get_the_title($id){return 'Safe lesson';}
function get_the_excerpt($id){return 'Safe summary';}
function get_post_meta($id,$key,$single=true){return '';}
function apply_filters($name,$text){return $text;}
function rest_ensure_response($data){return new WP_REST_Response($data);}
function check_c($ok,$message){if(!$ok){fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php';
$rest=new LSCH_REST();$request=new WP_REST_Request();
$GLOBALS['protected']=true;$GLOBALS['public_eligible']=false;
$resp=$rest->lesson($request);check_c('private, no-store'===$resp->headers['Cache-Control'],'restricted-ancestor authorized lesson must never be publicly cached');
$GLOBALS['public_eligible']=true;
$resp=$rest->lesson($request);check_c('private, no-store'===$resp->headers['Cache-Control'],'signed-in viewer-sensitive related lesson cannot enter shared cache');
$GLOBALS['protected']=false;
$resp=$rest->lesson($request);check_c('public, max-age=120'===$resp->headers['Cache-Control'],'fully public guest lesson is safely cacheable');
$GLOBALS['public_eligible']=false;
$resp=$rest->lesson($request);check_c('private, no-store'===$resp->headers['Cache-Control'],'a nonpublic lesson can never advertise public caching');
echo "PASS: File05 REST lesson effective visibility and viewer-sensitive cache privacy.\n";
