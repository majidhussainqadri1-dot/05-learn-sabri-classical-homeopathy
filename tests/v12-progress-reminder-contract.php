<?php
/** File 05 v12: private, fail-closed revision reads and reminder-state reads. */
define( 'ABSPATH', '/tests/' );
define( 'ARRAY_A', 'ARRAY_A' );

class WP_Error {
	public $code;
	public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; }
}
class WP_REST_Request extends ArrayObject {}
class WP_REST_Response {
	public $data; public $headers = array();
	public function __construct( $data ) { $this->data = $data; }
	public function header( $name, $value ) { $this->headers[ $name ] = $value; }
}
class LSCH_Content { const LESSON = 'lsch_lesson'; const COURSE = 'lsch_course'; }
class LSCH_Policy {
	public static $allowed = true;
	public static function can_use_learning_actions( $id = null ) { return self::$allowed; }
	public static function can_read_post( $id, $user = null ) { return self::$allowed; }
}
class LSCH_Services {
	public static $progress = null;
	public static function progress_row( $lesson_id, $user_id ) {
		if ( 7 !== $lesson_id || 41 !== $user_id ) { throw new RuntimeException( 'Read was not scoped to learner.' ); }
		return self::$progress;
	}
}
class LSCH_Database {
	public static function tables() { return array( 'reminders' => 'wp_lsch_reminders' ); }
}
class V12DB {
	public $last_error = ''; public $reminder = null;
	public function prepare( $sql, ...$args ) { return $sql; }
	public function get_row( $query, $mode = null ) {
		if ( false === strpos( $query, 'wp_lsch_reminders' ) ) { throw new RuntimeException( 'Unexpected query.' ); }
		return $this->reminder;
	}
}
$GLOBALS['wpdb'] = new V12DB();
function __( $msg, $domain = '' ) { return $msg; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', $v ) ); }
function get_current_user_id() { return 41; }
function get_post_type( $id ) { return 7 === $id ? LSCH_Content::LESSON : ( 9 === $id ? LSCH_Content::COURSE : 'post' ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function rest_ensure_response( $value ) { return is_wp_error( $value ) ? $value : new WP_REST_Response( $value ); }
function v12_assert( $okay, $message ) { if ( ! $okay ) { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); } }

require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php';
$rest = new LSCH_REST();
$lesson = new WP_REST_Request( array( 'id' => 7 ) );
$course = new WP_REST_Request( array( 'id' => 9 ) );
LSCH_Services::$progress = array( 'id' => 20, 'state' => 'in_progress', 'percent' => 80, 'version' => 12, 'components_json' => '{"sensitive":true}' );
$r = $rest->progress_get( $lesson );
v12_assert( $r instanceof WP_REST_Response && 12 === $r->data['version'], 'Progress should expose the actual version.' );
v12_assert( ! isset( $r->data['components_json'] ), 'Revision endpoint must not disclose private component payloads.' );
v12_assert( 'private, no-store' === ( $r->headers['Cache-Control'] ?? '' ), 'Progress response must not be publicly cached.' );
LSCH_Services::$progress = null;
$r = $rest->progress_get( $lesson );
v12_assert( $r->data['version'] === 0 && $r->data['state'] === 'not_started', 'Missing progress must have version zero.' );
$GLOBALS['wpdb']->last_error = 'query failed';
$r = $rest->progress_get( $lesson );
v12_assert( $r instanceof WP_Error && 'lsch_progress_read_failed' === $r->code, 'Progress DB failure cannot become an empty record.' );
$GLOBALS['wpdb']->last_error = '';
$GLOBALS['wpdb']->reminder = array( 'enabled' => 1, 'cadence' => 'daily', 'version' => 6 );
$r = $rest->reminder_get( $course );
v12_assert( $r instanceof WP_REST_Response && true === $r->data['enabled'] && 'daily' === $r->data['cadence'], 'Current reminder state must be returned.' );
v12_assert( 'private, no-store' === ( $r->headers['Cache-Control'] ?? '' ), 'Reminder state must not be publicly cached.' );
$GLOBALS['wpdb']->reminder = null;
$r = $rest->reminder_get( $course );
v12_assert( $r->data['enabled'] === false && $r->data['version'] === 0, 'Never assume a reminder is enabled.' );
$GLOBALS['wpdb']->last_error = 'disconnected';
$r = $rest->reminder_get( $course );
v12_assert( $r instanceof WP_Error && 'lsch_reminder_read_failed' === $r->code, 'Reminder DB failure cannot become disabled state.' );
$GLOBALS['wpdb']->last_error = '';
LSCH_Policy::$allowed = false;
v12_assert( $rest->progress_get( $lesson ) instanceof WP_Error, 'Unapproved learner cannot read progress.' );
v12_assert( $rest->reminder_get( $course ) instanceof WP_Error, 'Unapproved learner cannot read reminder.' );
echo "PASS: v12 progress/reminder revision reads, privacy isolation and DB fail-closed handling.\n";
