<?php
/** Cross-file source ownership and projection visibility contract regression. */
define( 'ABSPATH', '/test-wordpress/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'LSCH_VERSION', '4.0.0' );
$GLOBALS['lsch_target_allowed'] = false;
$GLOBALS['lsch_rows_fail'] = false;
$GLOBALS['lsch_source_access'] = true;
class LSCH_Content {
	const PROGRAM = 'lsch_program';
	const COURSE = 'lsch_course';
	const BOOK = 'lsch_book';
	const LESSON = 'lsch_lesson';
	public static function object_type( $id ) { return in_array( (int)$id, array( 10, 11 ), true ) ? self::LESSON : ''; }
	public static function version( $id ) { return 1; }
}
class LSCH_Policy {
	public static function can_read_post( $id, $viewer = 0 ) { return $GLOBALS['lsch_source_access'] && (int)$viewer > 0; }
	public static function publicly_visible( $id ) { return 10 === (int)$id; }
	public static function access_model() { return 'single-free-tier-v2'; }
}
class LSCH_Database { public static function tables() { return array( 'related' => 'wp_lsch_related_links' ); } }
class LSCH_REST { const NS = 'learn-sabri-classical-homeopathy/v2'; }
class CrossFileTestDB {
	public $queries = array();
	public function prepare( $query, ...$args ) { $this->queries[] = $query; return vsprintf( $query, $args ); }
	public function get_results( $query, $type ) {
		if ( $GLOBALS['lsch_rows_fail'] ) { return null; }
		return array(
			array( 'target_file'=>'06', 'target_type'=>'remedy', 'target_id'=>'good', 'target_url'=>'https://test.test/remedy/good', 'relation'=>'reading', 'version'=>1 ),
			array( 'target_file'=>'12', 'target_type'=>'pdf', 'target_id'=>'revoked', 'target_url'=>'https://test.test/pdf/revoked', 'relation'=>'reading', 'version'=>1 ),
		);
	}
}
$GLOBALS['wpdb'] = new CrossFileTestDB();
function absint( $x ) { return abs( (int)$x ); }
function sanitize_key( $x ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string)$x ) ); }
function get_current_user_id() { return 20; }
function rest_url( $url ) { return 'https://test.test/wp-json/' . $url; }
function get_the_title( $id ) { return 'Lesson ' . (int)$id; }
function get_permalink( $id ) { return 'https://test.test/lesson/' . (int)$id; }
function get_post_meta( $id, $name, $single = true ) { return ''; }
function apply_filters( $name, $default, ...$args ) {
	if ( 'lsch_related_learning_reference_visible' === $name ) {
		return $GLOBALS['lsch_target_allowed'] && 'good' === $args[0]['target_id'];
	}
	return $default;
}
function cross_assert( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-value.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-plugin.php';

$none = LSCH_Operations::related_links( 'lsch_lesson', 10 );
cross_assert( array() === $none, 'missing owner visibility provider fails closed' );
$GLOBALS['lsch_target_allowed'] = true;
$allowed = LSCH_Operations::related_links( 'lsch_lesson', 10 );
cross_assert( count( $allowed ) === 1 && 'good' === $allowed[0]['target_id'], 'current owner permits only unrevoked reference' );
$GLOBALS['lsch_source_access'] = false;
cross_assert( array() === LSCH_Operations::related_links( 'lsch_lesson', 10 ), 'source access denied before returning foreign targets' );
$GLOBALS['lsch_source_access'] = true;
$GLOBALS['lsch_rows_fail'] = true;
cross_assert( array() === LSCH_Operations::related_links( 'lsch_lesson', 10 ), 'database failure fails closed' );
$GLOBALS['lsch_rows_fail'] = false;

$search = ( new LSCH_Plugin() )->search_connector( array() );
cross_assert( $search['file05-learning.v4']['visibility_recheck'] === array( 'LSCH_Policy', 'publicly_visible' ), 'File26 public search contract uses guest-safe visibility' );
$providers = LSCH_Value::file26_provider( array() );
cross_assert( $providers['file05-learning.v3']['visibility_recheck'] === array( 'LSCH_Policy', 'publicly_visible' ), 'File26 supplemental public provider uses public visibility' );
$plugin = new LSCH_Plugin();
cross_assert( null === $plugin->community_context_card( 'lsch_lesson', 11 ), 'guest community resolver must not inherit logged-in staff access' );
cross_assert( is_array( $plugin->community_context_card( 'lsch_lesson', 11, 20 ) ), 'explicit approved viewer retains scoped context' );
cross_assert( null === LSCH_Value::resolve_ai_context( 'lsch_lesson', 11 ), 'guest AI resolver cannot use ambient editor identity' );
cross_assert( is_array( LSCH_Value::resolve_ai_context( 'lsch_lesson', 11, 20 ) ), 'explicit viewer may obtain permitted educational AI context' );
echo "PASS: File05 canonical link rechecks, File26 public-only search, guest AI/community projections.\n";
