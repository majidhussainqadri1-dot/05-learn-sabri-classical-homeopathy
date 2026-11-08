<?php
/** File05 v12: pending viva/OSCE queue must restrict learner submissions by canonical staff scope. */
define( 'ABSPATH', '/unit/' ); define( 'ARRAY_A', 'ARRAY_A' );
class WP_Error {
	public $code; public function __construct( $code, $message = '', $meta = array() ) { $this->code = $code; }
}
class LSCH_Policy { public static $approved = true; public static function can_use_learning_actions( $actor = null ) { return self::$approved; } }
class LSCH_Capabilities { const ASSESS = 'assess'; const MANAGE_CURRICULUM = 'manage'; }
class LSCH_Database { public static function tables() { return array( 'staff' => 'wp_lsch_staff' ); } }
class AssessorDB {
	public $prefix = 'wp_', $query = '', $fail = false, $reads = 0;
	public function prepare( $sql, ...$arguments ) { return vsprintf( $sql, $arguments ); }
	public function get_results( $sql, $mode = null ) {
		$this->query = $sql; $this->reads++;
		if ( $this->fail ) { return null; }
		return array( array( 'id' => 41, 'user_id' => 9, 'mode' => 'osce', 'version' => 1,
			'response_json' => '{"simulated":"learning"}', 'status' => 'awaiting_assessor' ) );
	}
}
$GLOBALS['wpdb'] = new AssessorDB();
$GLOBALS['assessor'] = true; $GLOBALS['manager'] = false;
function __( $msg, $domain = '' ) { return $msg; }
function absint( $v ) { return abs( (int) $v ); }
function user_can( $actor, $cap ) { return LSCH_Capabilities::MANAGE_CURRICULUM === $cap ? $GLOBALS['manager'] : $GLOBALS['assessor']; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function check_assessor( $true, $message ) { if ( ! $true ) { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); } }
require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';

$queue = LSCH_Future18::pending_assessor_practices( 22, 10000 );
check_assessor( is_array( $queue ) && 1 === count( $queue ), 'Authorized pending assessor queue should return matching records.' );
$sql = $GLOBALS['wpdb']->query;
check_assessor( false !== strpos( $sql, 'INNER JOIN wp_lsch_staff s' ), 'Non-manager assessor requires a DB-enforced staff join.' );
check_assessor( false !== strpos( $sql, "s.role='assessor'" ) && false !== strpos( $sql, "s.conflict_status='clear'" )
	&& false !== strpos( $sql, 's.active=1' ) && false !== strpos( $sql, 's.user_id=22' ),
	'Only active, conflict-cleared assignments for this assessor can expose learning evidence.' );
check_assessor( false !== strpos( $sql, 'p.user_id<>22' ), 'Self-assessment forbidden in queue.' );
check_assessor( false !== strpos( $sql, 'LIMIT 50' ), 'The queue must be bounded at 50 records.' );

$GLOBALS['assessor'] = false;
$result = LSCH_Future18::pending_assessor_practices( 22 );
check_assessor( $result instanceof WP_Error && 'lsch_future18_assessor_forbidden' === $result->code, 'A generic learner/teacher cannot view protected grading evidence.' );
$GLOBALS['assessor'] = true; $GLOBALS['manager'] = true;
$result = LSCH_Future18::pending_assessor_practices( 22, 15 );
check_assessor( is_array( $result ) && false === strpos( $GLOBALS['wpdb']->query, 'INNER JOIN' ) && false !== strpos( $GLOBALS['wpdb']->query, 'p.user_id<>22' ), 'Manager may inspect the bounded queue but never self-assess.' );
$GLOBALS['manager'] = false; $GLOBALS['wpdb']->fail = true;
$result = LSCH_Future18::pending_assessor_practices( 22 );
check_assessor( $result instanceof WP_Error && 'lsch_future18_assessor_queue_read_failed' === $result->code, 'DB outage must not fabricate an empty pending queue.' );
$reads = $GLOBALS['wpdb']->reads; LSCH_Policy::$approved = false;
$result = LSCH_Future18::pending_assessor_practices( 22 );
check_assessor( $result instanceof WP_Error && $reads === $GLOBALS['wpdb']->reads, 'Suspended principal denied before DB disclosure.' );
echo "PASS: assessor queue SQL role/assignment/learner boundaries, bounded size and fail-closed reads.\n";
