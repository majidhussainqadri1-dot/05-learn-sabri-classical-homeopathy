<?php
/** Seventh audit: public REST catalog never serializes WP_Error taxonomy data. */
define('ABSPATH','/test-wp/');
class WP_Error { public $code;function __construct($c,$m='',$details=array()){$this->code=$c;} }
class WP_REST_Request {public function get_param($name){return null;}}
class WP_Query {public $posts,$max_num_pages=1;function __construct($args){$this->posts=array((object)array('ID'=>22,'post_type'=>'lsch_lesson'));}}
class WP_REST_Response {public $headers=array();public $data;function __construct($d){$this->data=$d;}function header($name,$v){$this->headers[$name]=$v;}}
class LSCH_Content {
	const PROGRAM='lsch_program';const COURSE='lsch_course';const BOOK='lsch_book';const LESSON='lsch_lesson';
	const TOPIC='lsch_topic';const LEVEL='lsch_level';const COMPETENCY='lsch_competency';
	public static function access($id){return 'public';}
	public static function version($id){return 1;}
}
class LSCH_Policy {
	public static function rate_limit($a,$b,$c,$d){return true;}
	public static function can_use_protected_reads(){return false;}
	public static function can_read_post($id){return true;}
	public static function access_model(){return 'single-free-tier-v2';}
}
$GLOBALS['termsFail']=true;
function get_current_user_id(){return 0;}function absint($i){return abs((int)$i);}
function sanitize_key($s){return strtolower((string)$s);}function sanitize_text_field($s){return trim((string)$s);}
function sanitize_title($s){return trim((string)$s);}
function get_the_title($p){return 'Safe lesson';}function get_the_excerpt($p){return 'Short summary';}
function get_permalink($p){return 'https://test.invalid/lesson/22';}
function get_post_meta($i,$k,$single=true){return '';}
function wp_get_object_terms($id,$tax,$args){return $GLOBALS['termsFail']&&$tax===LSCH_Content::TOPIC ? new WP_Error('db_terms_error') : array('foundation');}
function is_wp_error($x){return $x instanceof WP_Error;}
function rest_ensure_response($value){return new WP_REST_Response($value);}
function __($x,$d=''){return $x;}
function check_cat($condition,$msg){if(!$condition){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php';
$rest=new LSCH_REST();
$bad=$rest->catalog(new WP_REST_Request());
check_cat($bad instanceof WP_Error && $bad->code==='lsch_catalog_taxonomy_failed','taxonomy DB error aborts catalog without leaking WP_Error object');
$GLOBALS['termsFail']=false;
$good=$rest->catalog(new WP_REST_Request());
check_cat($good instanceof WP_REST_Response && count($good->data['items'])===1,'healthy catalog returns one item');
check_cat($good->data['items'][0]['topics']===array('foundation'),'healthy terms are plain strings');
echo "PASS: File05 public learning REST catalog taxonomy failures fail closed.\n";
