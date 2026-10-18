<?php
/** Regression: an event ID cannot be reused for a different event name with identical payload. */
define( 'ABSPATH', '/unit-wp/' );
define( 'ARRAY_A', 'ARRAY_A' );
class WP_Error {}
class LSCH_Database {
    public static function tables() { return array( 'inbox' => 'wp_lsch_inbox', 'audit' => 'wp_lsch_audit' ); }
    public static function uuid() { return 'c71349c8-cfb8-4525-9a92-03bdf2976112'; }
    public static function now() { return '2026-10-17 08:30:00'; }
}
class LSCH_Policy { public static function request_id() { return 'inbox-name-test'; } }
class LSCH_Dependencies {
    public static $audits = array();
    public static function audit( $action, $context ) { self::$audits[] = $action; }
}
class InboxNameDB {
    public $stored_name = 'LearningContentPublished.v1';
    public $locks = 0;
    public $unlocks = 0;
    public $writes = 0;
    public function prepare( $sql, ...$args ) { return vsprintf( $sql, $args ); }
    public function get_var( $sql ) {
        if ( strpos( $sql, 'GET_LOCK' ) !== false ) { $this->locks++; return 1; }
        if ( strpos( $sql, 'RELEASE_LOCK' ) !== false ) { $this->unlocks++; return 1; }
        return 0;
    }
    public function get_row( $sql, $style = null ) {
        return array( 'event_name' => $this->stored_name, 'payload_hash' => $GLOBALS['payloadHash'], 'status' => 'processed' );
    }
    public function insert( $table, $values, $formats ) { $this->writes++; return 1; }
    public function query( $sql ) { throw new Exception( 'Replay must not mutate inbox.' ); }
    public function update( $table, $values, $where, $formats, $where_formats ) { throw new Exception( 'Replay must not update inbox.' ); }
}
function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return strtolower( (string) $v ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function get_current_user_id() { return 19; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function do_action( $name, ...$args ) {}
function assert_identity( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
$GLOBALS['wpdb'] = new InboxNameDB();
$payload = array( 'course' => 9 );
$GLOBALS['payloadHash'] = hash( 'sha256', json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-events.php';
$calls = 0;
$handler = function( $data ) use ( &$calls ) { $calls++; return true; };
$ok = LSCH_Events::consume( 'event-1', 'LearningContentPublished.v1', $payload, $handler );
assert_identity( true === $ok && 0 === $calls, 'matching processed replay must be deduplicated' );
$bad = LSCH_Events::consume( 'event-1', 'LearningContentRemoved.v1', $payload, $handler );
assert_identity( false === $bad && 0 === $calls, 'event-name substitution with identical event ID/payload must fail closed' );
assert_identity( in_array( 'inbox_event_name_conflict', LSCH_Dependencies::$audits, true ), 'name conflict must be audited' );
assert_identity( 2 === $GLOBALS['wpdb']->unlocks, 'both paths must release the advisory lock' );
echo "PASS: inbox event ID binds event name and payload identity.\n";
