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
	public $prefix = 'wp_', $last_error = '', $insert_id = 0, $history = array(), $legacy, $mentorship_version = 1, $portfolio_writes = 0, $fail_insert = false, $fail_prefix_read = false, $deny_reader = false;
	public function __construct() {
		$this->legacy = '[]';
	}
	public function prepare( $sql, ...$args ) { return vsprintf( $sql, $args ); }
	public function get_row( $sql, $mode = null ) {
		if ( $this->deny_reader && false !== strpos( $sql, 'OR learner_id=' ) ) { return null; }
		return array( 'id' => 15, 'mentor_id' => 25, 'learner_id' => 26, 'version' => $this->mentorship_version,
			'status' => 'active', 'course_id' => 4, 'feedback_json' => $this->legacy, 'created_at' => '2026-01-01 00:00:00' );
	}
	public function get_var( $sql ) {
		if ( false !== strpos( $sql, 'SHOW TABLES LIKE' ) ) { return 'wp_lsch_f18_mentor_feedback'; }
		if ( false !== strpos( $sql, 'COUNT(*)' ) ) { return count( $this->history ); }
		if ( false !== strpos( $sql, 'MAX(sequence_no)' ) ) { return count( $this->history ); }
		throw new RuntimeException( 'Unexpected DB scalar: ' . $sql );
	}

	public function get_results( $sql, $format = null ) {
		if ( $this->fail_prefix_read && false !== strpos( $sql, 'sequence_no<=' ) ) { $this->last_error = 'DB unavailable'; return null; }
		$this->last_error = '';
		$items = $this->history;
		if ( preg_match( '/sequence_no<=(\d+)/', $sql, $matches ) ) {
			$items = array_values( array_filter( $items, function( $item ) use ( $matches ) { return $item['sequence_no'] <= (int) $matches[1]; } ) );
		}
		if ( preg_match( '/sequence_no<(\d+)/', $sql, $matches ) ) {
			$items = array_values( array_filter( $items, function( $item ) use ( $matches ) { return $item['sequence_no'] < (int) $matches[1]; } ) );
		}
		usort( $items, function( $a, $b ) use ( $sql ) {
			return false !== strpos( $sql, 'DESC' ) ? $b['sequence_no'] <=> $a['sequence_no'] : $a['sequence_no'] <=> $b['sequence_no'];
		} );
		if ( preg_match( '/LIMIT (\d+)/', $sql, $matches ) ) { $items = array_slice( $items, 0, (int) $matches[1] ); }
		return array_map( function( $entry ) { return $entry + array( 'redacted_at' => null ); }, $items );
	}
	public function insert( $table, $row, $formats = array() ) {
		if ( $this->fail_insert ) { return false; }
		if ( false !== strpos( $table, 'mentor_feedback' ) ) {
			foreach ( $this->history as $item ) {
				if ( (int) $item['sequence_no'] === (int) $row['sequence_no'] ) { return false; }
			}
			$this->insert_id++;
			$row['id'] = $this->insert_id;
			$this->history[] = $row;
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

$legacy_before_write = LSCH_Mentor_History::records( 15, 26, 20 );
check_r( is_array( $legacy_before_write ) && 20 === count( $legacy_before_write ) && 100 === $legacy_before_write[0]['sequence_no'], 'Old mentor feedback is readable before first normalized write.' );
$older = LSCH_Mentor_History::records( 15, 26, 20, 81 );
check_r( 20 === count( $older ) && 80 === $older[0]['sequence_no'] && 61 === $older[19]['sequence_no'], 'Read-only legacy cursor preserves exact descending chronology.' );
check_r( 0 === count( $GLOBALS['wpdb']->history ), 'Read-only old feedback viewing must not run a migration write.' );
$GLOBALS['wpdb']->deny_reader = true;
$forbidden = LSCH_Mentor_History::records( 15, 999, 10 );
check_r( $forbidden instanceof WP_Error && 'lsch_mentor_history_forbidden' === $forbidden->code, 'Cross-learner mentor history is object-scoped.' );
$GLOBALS['wpdb']->deny_reader = false;
$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 101' ), 1 );
check_r( is_array( $result ) && 101 === $result['sequence_no'], 'The 101st feedback must append.' );
check_r( 101 === count( $GLOBALS['wpdb']->history ), 'The original 100 entries must remain and one new entry be appended.' );
check_r( 'legacy' === $GLOBALS['wpdb']->history[0]['origin'] && 'native' === $GLOBALS['wpdb']->history[100]['origin'], 'Historical and native origins preserved.' );
check_r( 1 === $GLOBALS['wpdb']->portfolio_writes && 2 === $GLOBALS['wpdb']->mentorship_version, 'History and portfolio evidence accompany revision increment.' );

$records = LSCH_Mentor_History::records( 15, 26, 50 );
check_r( 50 === count( $records ) && 101 === $records[0]['sequence_no'] && 52 === $records[49]['sequence_no'], 'Migrated history returns bounded recent page including new entry.' );
$older = LSCH_Mentor_History::records( 15, 26, 50, 52 );
check_r( 50 === count( $older ) && 51 === $older[0]['sequence_no'] && 2 === $older[49]['sequence_no'], 'Normalized cursor can navigate beyond first 100 entries.' );
$original = $GLOBALS['wpdb']->history[0]['feedback_json'];
$GLOBALS['wpdb']->history[0]['feedback_json'] = '{"feedback":"tampered"}';
$corrupted = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'malicious append' ), 2 );
check_r( $corrupted instanceof WP_Error && 'lsch_mentor_history_prefix_mismatch' === $corrupted->code, 'Matching row counts must never hide a substituted historic record.' );
check_r( 101 === count( $GLOBALS['wpdb']->history ), 'No append is allowed after historic checksum mismatch.' );
$GLOBALS['wpdb']->history[0]['feedback_json'] = $original;
$GLOBALS['wpdb']->fail_prefix_read = true;
$unreadable = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'blocked on DB fault' ), 2 );
check_r( $unreadable instanceof WP_Error && 'lsch_mentor_history_prefix_unverified' === $unreadable->code, 'Failed historical row read blocks an append.' );
$GLOBALS['wpdb']->fail_prefix_read = false;


$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 102' ), 1 );
check_r( $result instanceof WP_Error && 'lsch_future18_mentorship_conflict' === $result->code, 'Stale parent version rejected.' );
check_r( 101 === count( $GLOBALS['wpdb']->history ), 'Version conflict cannot add a record.' );

$result = LSCH_Future18::mentor_feedback( 25, 15, array( 'feedback' => 'entry 102' ), 2 );
check_r( is_array( $result ) && 102 === $result['sequence_no'] && 102 === count( $GLOBALS['wpdb']->history ), 'More than 100 records supported without deleting or duplicating.' );
check_r( false === strpos( $GLOBALS['wpdb']->legacy, 'entry 101' ), 'Original legacy JSON remains a read-only migration witness.' );

echo "PASS: 100+ append-only mentor records, preserved historical evidence, failed corrupt/duplicate writes and version guard.\n";
