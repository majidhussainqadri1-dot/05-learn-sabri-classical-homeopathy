<?php
/** Seventh audit: saved learning searches must not treat a failed DB query as a completed export. */
define('ABSPATH','/test-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$msg='',$details=array()){$this->code=$code;}}
class WP_REST_Response {public $data;function __construct($payload){$this->data=$payload;}}
class LSCH_Database {public static function uuid(){return '00000000-0000-4000-8000-000000000001';}}
class TestSearchDB {
	public $prefix='wp_';public $failed=true;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function get_results($sql,$format=null){
		return $this->failed?false:array(array('id'=>1,'public_id'=>'one','label'=>'Remedies','query_json'=>'{"type":"lsch_course"}','version'=>1));
	}
}
$GLOBALS['wpdb']=new TestSearchDB();
function get_current_user_id(){return 12;}
function __($s,$domain=''){return $s;}
function check_search($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
function rest_ensure_response($d){return new WP_REST_Response($d);}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php';
$bad=LSCH_State::rest_saved_searches();
check_search($bad instanceof WP_Error && $bad->code==='lsch_saved_search_read_failed','failed database read returns explicit WP_Error');
$GLOBALS['wpdb']->failed=false;
$good=LSCH_State::rest_saved_searches();
check_search($good instanceof WP_REST_Response && is_array($good->data),'healthy search results remain valid');
check_search($good->data[0]['query']['type']==='lsch_course','query parsed for owner');
check_search(!isset($good->data[0]['query_json']),'raw JSON not redundantly exposed');
echo "PASS: File05 private saved searches DB read failure and healthy response.\n";
