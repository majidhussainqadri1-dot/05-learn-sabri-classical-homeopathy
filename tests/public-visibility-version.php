<?php
/** File05 public visibility, no-shared-cache and single-save-version regression. */
define( 'ABSPATH', '/unit-wordpress/' );
define( 'ARRAY_A', 'ARRAY_A' );
$fixtures = array(
    1 => array( 'type' => 'lsch_lesson', 'status' => 'draft', 'access' => 'public', 'version' => 1 ),
    2 => array( 'type' => 'lsch_book', 'status' => 'publish', 'access' => 'public', 'version' => 1 ),
    3 => array( 'type' => 'lsch_book', 'status' => 'publish', 'access' => 'account', 'version' => 1 ),
    4 => array( 'type' => 'lsch_course', 'status' => 'publish', 'access' => 'restricted', 'version' => 1 ),
    5 => array( 'type' => 'lsch_lesson', 'status' => 'publish', 'access' => 'public', 'version' => 1 ),
    6 => array( 'type' => 'lsch_course', 'status' => 'publish', 'access' => 'public', 'version' => 1 ),
    7 => array( 'type' => 'lsch_lesson', 'status' => 'publish', 'access' => 'public', 'version' => 1 ),
    8 => array( 'type' => 'lsch_book', 'status' => 'publish', 'access' => 'private-unknown', 'version' => 1 ),
    9 => array( 'type' => 'lsch_book', 'status' => 'publish', 'access' => '', 'version' => 1 ),
);
$GLOBALS['fixtures'] = $fixtures;
$GLOBALS['metadata'] = array();
$GLOBALS['query_log'] = array();
$GLOBALS['actions_log'] = array();
$GLOBALS['logged_in'] = true;
$GLOBALS['queried_post_id'] = 6;
$GLOBALS['singular_type'] = 'lsch_course';
$GLOBALS['page_id'] = 0;
$GLOBALS['consent_ok'] = false;
class WP_Post {
    public $ID; public $post_type; public $post_status; public $post_author;
    public $post_title = ''; public $post_content = ''; public $post_excerpt = '';
    public $post_parent = 0; public $menu_order = 0;
    public function __construct( $id ) {
        $row = $GLOBALS['fixtures'][$id];
        $this->ID = $id; $this->post_type = $row['type'];
        $this->post_status = $row['status']; $this->post_author = 99;
    }
}
class WP_Error {}
class FakeWPDB {
    public function prepare( $sql, ...$arguments ) { return vsprintf( $sql, $arguments ); }
    public function get_row( $sql, $mode = null ) {
        if ( ! $GLOBALS['consent_ok'] ) { return null; }
        return array( 'policy_version'=>'case-consent-v1', 'subject_type'=>'patient',
            'consent_source'=>'patient', 'scope'=>'education', 'evidence_reference'=>'reference',
            'confirmed_at'=>'2026-01-01', 'withdrawn_at'=>null );
    }
}
class LSCH_Database { public static function tables() { return array( 'consents'=>'wp_lsch_consents' ); } }
class LSCH_Capabilities { public static function can_author( $id = 0 ) { return true; } const MANAGE_CURRICULUM = 'manage_lsch_curriculum'; }
$GLOBALS['wpdb'] = new FakeWPDB();
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace('/[^a-z0-9_\-]/', '', (string) $value) ); }
function sanitize_text_field( $value ) { return (string) $value; }
function get_current_user_id() { return 99; }
function current_user_can( $cap ) { return true; }
function wp_is_post_revision( $id ) { return false; }
function wp_is_post_autosave( $id ) { return false; }
function get_post( $id ) { return isset($GLOBALS['fixtures'][$id]) ? new WP_Post($id) : null; }
function get_post_type( $id ) { return isset($GLOBALS['fixtures'][$id]) ? $GLOBALS['fixtures'][$id]['type'] : false; }
function get_post_meta( $id, $key, $single = true ) {
    if (array_key_exists($key,$GLOBALS['metadata'][$id] ?? array())) { return $GLOBALS['metadata'][$id][$key]; }
    return '_lsch_access' === $key ? ($GLOBALS['fixtures'][$id]['access'] ?? '') :
        ('_lsch_version' === $key ? ($GLOBALS['fixtures'][$id]['version'] ?? '') : '');
}
function update_post_meta( $id, $key, $value ) { $GLOBALS['metadata'][$id][$key] = $value; return true; }
function do_action( $name, ...$args ) { $GLOBALS['actions_log'][] = array($name,$args); }
function wp_get_object_terms( $id, $taxonomy, $args ) {
    return 7 === $id ? array('patient-case-learning') : array('foundations');
}
function is_wp_error( $object ) { return $object instanceof WP_Error; }
function apply_filters( $name, $value ) { return $value; }
function get_posts( $query ) {
    $GLOBALS['query_log'][] = $query;
    $items=array();
    foreach ($GLOBALS['fixtures'] as $id=>$data) {
        if ($data['type']===$query['post_type'] && $data['status']==='publish') { $items[]=new WP_Post($id); }
    }
    return $items;
}
function get_option( $name, $default = false ) { return 'lsch_page_map' === $name ? array( 'dashboard'=>50,'mastery'=>51 ) : $default; }
function is_page( $id ) { return (int)$id === (int)$GLOBALS['page_id']; }
function is_singular( $types ) { return in_array($GLOBALS['singular_type'],(array)$types,true); }
function is_user_logged_in() { return (bool)$GLOBALS['logged_in']; }
function get_queried_object_id() { return $GLOBALS['queried_post_id']; }
function __( $message, $domain = '' ) { return $message; }
function assert_file05( $condition, $description ) {
    if (!$condition) { fwrite(STDERR,"FAIL: ".$description."\n"); exit(1); }
}
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-frontend.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php';

assert_file05( LSCH_Policy::publicly_visible(2), 'public approved book visible' );
assert_file05( !LSCH_Policy::publicly_visible(3), 'account-only book excluded despite logged-in editor' );
assert_file05( !LSCH_Policy::publicly_visible(4), 'restricted course excluded despite logged-in editor' );
assert_file05( !LSCH_Policy::publicly_visible(1), 'draft not public' );
assert_file05( !LSCH_Policy::publicly_visible(999), 'nonexistent not public' );
assert_file05( !LSCH_Policy::publicly_visible(7), 'patient-case lesson without consent never public' );
assert_file05( LSCH_Content::access(8)==='restricted', 'malformed explicit access fails closed' );
assert_file05( !LSCH_Policy::publicly_visible(8), 'malformed explicit access not published to public indexes' );
assert_file05( LSCH_Content::access(9)==='public', 'missing legacy access retains approved public default' );
$GLOBALS['consent_ok']=true;
assert_file05( LSCH_Policy::publicly_visible(7), 'verified public case consent accepted' );

$books=LSCH_Frontend::public_catalog_posts(LSCH_Content::BOOK,8);
assert_file05(count($books)===1 && $books[0]->ID===2, 'catalog filters account-only book even if WordPress query is overbroad');
$courses=LSCH_Frontend::public_catalog_posts(LSCH_Content::COURSE,12);
assert_file05(count($courses)===1 && $courses[0]->ID===6, 'catalog excludes restricted course');
foreach($GLOBALS['query_log'] as $query){
    assert_file05(isset($query['meta_query']) && in_array('OR', $query['meta_query'], true), 'query enforces public metadata at database level');
}
$front=new LSCH_Frontend();
$reflection=new ReflectionMethod(LSCH_Frontend::class,'is_private_learning_surface');
$reflection->setAccessible(true);
assert_file05($reflection->invoke($front), 'logged-in public course must be noncacheable');
$GLOBALS['logged_in']=false;
assert_file05(!$reflection->invoke($front), 'guest public course remains cacheable');
$GLOBALS['queried_post_id']=4;
assert_file05($reflection->invoke($front), 'restricted guest course is not cacheable');
$GLOBALS['singular_type']='other'; $GLOBALS['page_id']=51;
assert_file05($reflection->invoke($front), 'mastery personal dashboard always private');

$before=new WP_Post(1); $before->post_content='old';
$after=new WP_Post(1); $after->post_content='changed';
LSCH_Content::bump_version_on_post_update(1,$after,$before);
assert_file05(LSCH_Content::version(1)===2, 'post_updated version bump occurs once');
(new LSCH_Plugin())->lesson_saved(1,$after,true);
assert_file05(LSCH_Content::version(1)===2, 'save_post does not duplicate the same version bump');
assert_file05(count($GLOBALS['actions_log'])===1, 'one learning version event per logical save');
echo "PASS: File05 public catalog, cache privacy and single-save version regression.\n";
