<?php
/** Isolated exact-current File05 -> File26 public document gate; not WordPress staging. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
class WP_Post {
    public $ID=17; public $post_type='lsch_lesson'; public $post_status='publish';
    public $post_excerpt='Public overview';
}
class WP_Error {private $code; public function __construct($code,$message=''){$this->code=$code;} public function get_error_code(){return $this->code;}}
class LSCH_Dependencies {public static function runtime_ready(){return $GLOBALS['ready'];}}
class LSCH_Policy {
    public static function central_policy_ready(){return $GLOBALS['ready'];}
    public static function publicly_visible($id){return $GLOBALS['public'];}
}
class LSCH_Content {public static function version($id){return $GLOBALS['version'];}}
function is_wp_error($v){return $v instanceof WP_Error;}
function get_post($id){return (int)$id===17?$GLOBALS['post']:null;}
function get_post_meta($id,$key,$single=true){return $key==='_lsch_language'?'en-US':'';}
function get_permalink($post){return 'https://example.org/learn/lesson/17';}
function get_the_title($post){return 'Safe reading lesson';}
function wp_get_post_terms($id,$taxonomy,$opts=array()){return $GLOBALS['topics'];}
function wp_http_validate_url($v){return is_string($v)&&0===strpos($v,'https://');}
function sanitize_text_field($v){return trim((string)$v);}
function wp_strip_all_tags($v,$remove_breaks=true){return strip_tags($v);}
function post_type_exists($t){return in_array($t,array('lsch_lesson','lsch_course','lsch_book'),true);}
function get_option($key,$fallback=false){return false;}
class FakeOwnerDB {public $posts='wp_posts';public $last_error='';public function prepare($query,$args){return $query;}public function get_col($query){if(!empty($GLOBALS['db_fails'])){$this->last_error='read_failed';return false;}return array(17);}}
$GLOBALS['wpdb']=new FakeOwnerDB();
function must($value,$label){$GLOBALS['checks']++;if(!$value){fwrite(STDERR,"FAILED: {$label}\n");exit(1);}}
$GLOBALS['checks']=0;$GLOBALS['ready']=true;$GLOBALS['public']=true;
$GLOBALS['topics']=array('foundations');$GLOBALS['version']=8;$GLOBALS['post']=new WP_Post();
require dirname(__DIR__).'/05-learn-sabri-classical-homeopathy/includes/class-lsch-file26-connector.php';
$document=LSCH_File26_Connector::fetch_object('lesson','17');
must(is_array($document)&&$document['object_version']===8,'Current public lesson projected');
must(LSCH_File26_Connector::can_view($document),'Exact-current document permitted');
$stale=$document;$stale['object_version']=7;
must(!LSCH_File26_Connector::can_view($stale),'Stale indexed owner version denied');
$wrong=$document;$wrong['canonical_url']='https://example.org/learn/lesson/elsewhere';
must(!LSCH_File26_Connector::can_view($wrong),'Wrong canonical URL denied');
$wrong=$document;$wrong['state']='withdrawn';
must(!LSCH_File26_Connector::can_view($wrong),'Withdrawn indexed state denied');
$wrong=$document;$wrong['object_id']='18';
must(!LSCH_File26_Connector::can_view($wrong),'Different owner ID denied');
must(LSCH_File26_Connector::fetch_object('lesson','00017')===null,'Leading-zero alternate object ID refused');
must(LSCH_File26_Connector::fetch_object('lesson','9999999999999999999999999')===null,'Out-of-range object ID refused');
must(LSCH_File26_Connector::fetch_object('lesson',true)===null,'Boolean object ID refused');
must(LSCH_File26_Connector::fetch_object(array('lesson'),'17')===null,'Malformed entity type refused');
$malformed=$document;$malformed['canonical_url']=array('injection');
must(!LSCH_File26_Connector::can_view($malformed),'Malformed canonical URL field refused');
$malformed=$document;$malformed['entity_type']=array('lesson');
must(!LSCH_File26_Connector::can_view($malformed),'Malformed entity type field refused');
$malformed=$document;$malformed['object_version']=array(8);
must(!LSCH_File26_Connector::can_view($malformed),'Malformed object version refused');
$GLOBALS['version']=9;
must(!LSCH_File26_Connector::can_view($document),'Owner version change invalidates old result');
$GLOBALS['version']=8;$GLOBALS['ready']=false;
must(LSCH_File26_Connector::fetch_object('lesson','17')===null,'Dependency loss blocks fetch');
must(!LSCH_File26_Connector::can_view($document),'Dependency loss blocks click-time check');
must(LSCH_File26_Connector::health()['state']==='degraded','Missing runtime reports degraded');
$GLOBALS['ready']=true;$GLOBALS['public']=false;
must(!LSCH_File26_Connector::can_view($document),'Revoked public ACL blocks search result');
$GLOBALS['public']=true;$GLOBALS['topics']=array('patient-case-learning');
must(!LSCH_File26_Connector::can_view($document),'Patient-case lesson never indexes');
$GLOBALS['topics']=new WP_Error('taxonomy_unavailable');
must(!LSCH_File26_Connector::can_view($document),'Taxonomy failure blocks indexing');
$failed_snapshot=LSCH_File26_Connector::list_batch();
must($failed_snapshot instanceof WP_Error && $failed_snapshot->get_error_code()==='file05_search_taxonomy_unavailable',
    'Taxonomy provider failure must abort source batch, not skip indexed documents');
$GLOBALS['topics']=array('foundations');
$bad_scope=LSCH_File26_Connector::list_batch('',50,array('locale'=>'ur'));
must($bad_scope instanceof WP_Error && $bad_scope->get_error_code()==='file05_search_partial_scope',
    'Unsupported scope must fail rather than silently return unfiltered public snapshot');
$GLOBALS['db_fails']=true;
$failed_snapshot=LSCH_File26_Connector::list_batch();
must($failed_snapshot instanceof WP_Error && $failed_snapshot->get_error_code()==='file05_search_owner_db_error',
    'Owner database read failure must abort source batch');
$GLOBALS['db_fails']=false;
$source_snapshot=LSCH_File26_Connector::list_batch();
must(is_array($source_snapshot)&&count($source_snapshot['items'])===1&&$source_snapshot['done']===true,
    'Healthy owner source read returns one complete public lesson');
$GLOBALS['topics']=array('foundations');$GLOBALS['post']->post_status='draft';
must(!LSCH_File26_Connector::can_view($document),'Withdrawn publication blocks result');
echo "File05->File26 exact-public-version and fallback: {$GLOBALS['checks']} isolated checks PASS.\n";
