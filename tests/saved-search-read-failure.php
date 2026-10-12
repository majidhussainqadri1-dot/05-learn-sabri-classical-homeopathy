<?php
/** Seventh audit: saved learning searches must not treat a failed DB query as a completed export. */
define('ABSPATH','/test-wp/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$msg='',$details=array()){$this->code=$code;}}
class WP_REST_Response {public $data;function __construct($payload){$this->data=$payload;}}
class LSCH_Database {public static function uuid(){return '00000000-0000-4000-8000-000000000001';}}
class TestSearchDB {
	public $prefix='wp_';public $failed=true,$deleteResult=false;
	public function prepare($sql,...$args){return vsprintf($sql,$args);}
	public function delete($table,$where,$formats){return $this->deleteResult;}
	public function get_results($sql,$format=null){
		return $this->failed?false:array(array('id'=>1,'public_id'=>'one','label'=>'Remedies','query_json'=>'{"type":"lsch_course"}','version'=>1));
	}
}
$GLOBALS['wpdb']=new TestSearchDB();
function get_current_user_id(){return 12;}
function __($s,$domain=''){return $s;}
function check_search($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
function rest_ensure_response($d){return new WP_REST_Response($d);}
class WP_REST_Request implements ArrayAccess {public function offsetExists($k):bool{return true;}public function offsetGet($k){return 13;}public function offsetSet($k,$v):void{}public function offsetUnset($k):void{}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-state.php';
$bad=LSCH_State::rest_saved_searches();
check_search($bad instanceof WP_Error && $bad->code==='lsch_saved_search_read_failed','failed database read returns explicit WP_Error');
$GLOBALS['wpdb']->failed=false;
$good=LSCH_State::rest_saved_searches();
check_search($good instanceof WP_REST_Response && is_array($good->data),'healthy search results remain valid');
check_search($good->data[0]['query']['type']==='lsch_course','query parsed for owner');
check_search(!isset($good->data[0]['query_json']),'raw JSON not redundantly exposed');
$failed=LSCH_State::rest_delete_saved_search(new WP_REST_Request());
check_search($failed instanceof WP_Error && $failed->code==='lsch_saved_search_delete_failed','SQL DELETE outage is not a misleading 404');
$GLOBALS['wpdb']->deleteResult=0;
$missing=LSCH_State::rest_delete_saved_search(new WP_REST_Request());
check_search($missing instanceof WP_Error && $missing->code==='lsch_saved_search_not_found','genuinely absent owner search returns 404');
$GLOBALS['wpdb']->deleteResult=1;
$deleted=LSCH_State::rest_delete_saved_search(new WP_REST_Request());
check_search($deleted instanceof WP_REST_Response,'healthy owned search deletion returns response');
echo "PASS: File05 private saved searches DB read/delete failures and healthy responses.\n";
