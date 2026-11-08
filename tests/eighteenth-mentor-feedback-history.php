<?php
/** File05 FUT-15 v12 regression: corrupt legacy feedback remains blocked; 100+ items append without truncation. */
define( 'ABSPATH', '/unit-wp/' );
define( 'ARRAY_A', 'ARRAY_A' );
class WP_Error {
	public $code;
	public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; }
}
class LSCH_Capabilities { const TEACH = 'teach'; }
class LSCH_Policy {
	public static function can_use_learning_actions( $id = 0 ) { return true; }
	public static function sanitize_json( $data, $max = 65535 ) {
		$json = json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return strlen( $json ) <= $max ? $json : new WP_Error( 'too_large' );
	}
}
class LSCH_Database { public static function uuid() { return 'test-guid'; } }
class LSCH_Events {
	public static $audits = array();
	public static function audit( $a, $t, $id, $data, $purpose ) { self::$audits[] = $a; }
}
class MentorDB {
	public $prefix = 'wp_', $last_error = '', $insert_id = 0, $history = array(), $legacy, $mentorship_version = 1, $portfolio_writes = 0, $fail_insert = false;
	public function __construct() {
		$this->legacy = '[]';
	}
	public function prepare( $sql, ...$args ) { return vsprintf( $sql, $args ); }
	public function get_row( $sql, $mode = null ) {
		return array( 'id' => 15, 'mentor_id' => 25, 'learner_id' => 26, 'version' => $this->mentorship_version,
			'status' => 'active', 'course_id' => 4, 'feedback_json' => $this->legacy, 'created_at' => '2026-01-01 00:00:00' );
	}
	public function get_var( $sql ) {
		if ( false !== strpos( $sql, 'SHOW TABLES LIKE' ) ) { return 'wp_lsch_f18_mentor_feedback'; }
		if ( false !== strpos( $sql, 'COUNT(*)' ) ) { return count( $this->history ); }
		if ( false !== strpos( $sql, 'MAX(sequence_no)' ) ) { return count( $this->history ); }
		throw new RuntimeException( 'Unexpected DB scalar: ' . $sql );
	}
	public function insert( $table, $row, $formats = array() ) {
		if ( $this->fail_insert ) { return false; }
		if ( false !== strpos( $table, 'mentor_feedback' ) ) {
			foreach ( $this->history as $item ) {
				if ( (int) $item['sequence_no'] === (int) $row['sequence_no'] ) { return false; }
			}
			$this->history[] = $row;
			$this->insert_id++;
			return 1;
		}
		if ( false !== strpos( $table, 'portfolio' ) ) { $this->portfolio_writes++; $this->insert_id++; return 1; }
		throw new RuntimeException( 'Unexpected table insertion: ' . $table );
	}
	public function update( $table, $row, $where, $formats, $where_formats ) {
		if ( false === strpos( $table, 'mentorship' ) ) { throw new RuntimeException( 'Unexpected update.' ); }
		if ( $this->mentorship_version !== (int) $where['version'] ) { return 0; }
		$this->mentorship_version++;
		return 1;
	}
}
$GLOBALS['wpdb'] = new MentorDB();
function absint( $v ) { return abs( (int) $v ); }
function __( $v, $domain = '' ) { return $v; }
function user_can( $id, $cap ) { return true; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function sanitize_key( $v ) { return strtolower( (string) $v ); }
function sanitize_text_field( $v ) { return strip_tags( (string) $v ); }
function current_time( $key, $gmt = true ) { return '2026-11-08 00:00:00'; }
function get_option( $key, $default = false ) { return 1; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function check_r( $truth, $why ) { if ( ! $truth ) { fwrite( STDERR, 'FAIL: ' . $why . PHP_EOL ); exit( 1 ); } }
require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-mentor-history.php';
require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';

foreach ( array( 'corrupt', '{"unexpected":"object"}', '{"0":{"feedback":"historic"}}', '{"0":{},"1":{}}', 'null', '[1,' ) as $corrupt ) {
	$GLOBALS['wpdb']->legacy = $corrupt;
	$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'quality review' ), 1 );
	check_r( $result instanceof WP_Error && 'lsch_mentor_legacy_history_invalid' === $result->code, 'Malformed legacy array blocked: ' . $corrupt );
	check_r( 0 === count( $GLOBALS['wpdb']->history ) && 0 === $GLOBALS['wpdb']->portfolio_writes, 'No mutation on corrupt old mentoring history.' );
}
$GLOBALS['wpdb']->legacy = json_encode( array_fill( 0, 100, array( 'feedback' => 'historical' ) ) );
$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 101' ), 1 );
check_r( is_array( $result ) && 101 === $result['sequence_no'], 'The 101st feedback must append.' );
check_r( 101 === count( $GLOBALS['wpdb']->history ), 'The original 100 entries must remain and one new entry be appended.' );
check_r( 'legacy' === $GLOBALS['wpdb']->history[0]['origin'] && 'native' === $GLOBALS['wpdb']->history[100]['origin'], 'Historical and native origins preserved.' );
check_r( 1 === $GLOBALS['wpdb']->portfolio_writes && 2 === $GLOBALS['wpdb']->mentorship_version, 'History and portfolio evidence accompany revision increment.' );

$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 102' ), 1 );
check_r( $result instanceof WP_Error && 'lsch_future18_mentorship_conflict' === $result->code, 'Stale parent version rejected.' );
check_r( 101 === count( $GLOBALS['wpdb']->history ), 'Version conflict cannot add a record.' );

$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 102' ), 2 );
check_r( is_array( $result ) && 102 === $result['sequence_no'] && 102 === count( $GLOBALS['wpdb']->history ), 'More than 100 records supported without deleting or duplicating.' );
check_r( false === strpos( $GLOBALS['wpdb']->legacy, 'entry 101' ), 'Original legacy JSON remains a read-only migration witness.' );

echo "PASS: 100+ append-only mentor records, preserved historical evidence, failed corrupt/duplicate writes and version guard.\n";
