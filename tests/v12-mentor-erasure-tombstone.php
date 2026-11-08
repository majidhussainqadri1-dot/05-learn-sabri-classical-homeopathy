<?php
/** File05 v12: File24 erasure is readable only as redacted counterpart evidence. */
define( 'ABSPATH', '/unit/' );
define( 'ARRAY_A', 'ARRAY_A' );
class WP_Error {
	public $code; public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; }
}
class LSCH_Policy {
	public static function can_use_learning_actions( $id = 0 ) { return $id === 26; }
}
class LSCH_Future18 {
	public static function tables() { return array( 'mentorship' => 'wp_lsch_f18_mentorship' ); }
}
class TombDB {
	public $prefix = 'wp_', $last_error = '', $status = 'ended', $legacy = '{}', $permit = true;
	public function prepare( $sql, ...$args ) { return vsprintf( $sql, $args ); }
	public function get_var( $sql ) {
		if ( false !== strpos( $sql, 'SHOW TABLES LIKE' ) ) { return 'wp_lsch_f18_mentor_feedback'; }
		if ( false !== strpos( $sql, 'COUNT(*)' ) ) { return 1; }
		return null;
	}
	public function get_row( $sql, $type = null ) {
		if ( ! $this->permit ) { return null; }
		return array( 'id' => 15, 'mentor_id' => 0, 'learner_id' => 26, 'status' => $this->status,
			'feedback_json' => $this->legacy, 'created_at' => '2026-01-01 00:00:00' );
	}
	public function get_results( $sql, $format = null ) {
		return array( array( 'id' => 1, 'mentorship_id' => 15, 'sequence_no' => 1, 'mentor_id' => 0,
			'feedback_json' => '{}', 'origin' => 'legacy', 'recorded_at' => '2026-01-01 00:00:00', 'redacted_at' => '2026-11-08 00:00:00' ) );
	}
}
$GLOBALS['wpdb'] = new TombDB();
function absint( $v ) { return abs( (int) $v ); }
function __( $v, $domain = '' ) { return $v; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_option( $key, $fallback = 0 ) { return 1; }
function assert_t( $condition, $reason ) { if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $reason . PHP_EOL ); exit( 1 ); } }
require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-mentor-history.php';

$records = LSCH_Mentor_History::records( 15, 26, 50 );
assert_t( is_array( $records ) && 1 === count( $records ) && '{}' === $records[0]['feedback_json'], 'Surviving participant sees only a redacted tombstone.' );
$GLOBALS['wpdb']->status = 'active';
$result = LSCH_Mentor_History::records( 15, 26, 50 );
assert_t( $result instanceof WP_Error && 'lsch_mentor_legacy_history_invalid' === $result->code, 'Malformed unredacted active history must fail closed.' );
$GLOBALS['wpdb']->status = 'ended'; $GLOBALS['wpdb']->permit = false;
$result = LSCH_Mentor_History::records( 15, 26, 50 );
assert_t( $result instanceof WP_Error && 'lsch_mentor_history_forbidden' === $result->code, 'No cross-mentorship read after access revocation.' );
$result = LSCH_Mentor_History::records( 15, 99, 50 );
assert_t( $result instanceof WP_Error && 'lsch_mentor_history_unavailable' === $result->code, 'Unapproved viewer is denied before database reads.' );
echo "PASS: redacted post-erasure mentoring history, active-data fail closed and revoked access.\n";
