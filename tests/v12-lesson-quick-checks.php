<?php
/** v12 Five formative questions: strict editor bound, no pre-submit answer leak. */
define( 'ABSPATH', '/unit/' );
class WP_Error { public $code; public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; } }
function __( $text, $domain = '' ) { return $text; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
$GLOBALS['checks'] = '';
function get_post_meta( $id, $key, $single = true ) {
	if ( 22 !== $id || '_lsch_quick_checks' !== $key ) { throw new RuntimeException( 'Unexpected private metadata query.' ); }
	return $GLOBALS['checks'];
}
function assert_v12( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); } }
require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-quick-checks.php';
$valid = array(
	array( 'question' => 'Which response is safer?', 'options' => array( 'A' => 'De-identified case', 'B' => 'Full patient address' ), 'correct' => 'A', 'explanation' => 'Only simulated or de-identified learning data.' ),
	array( 'question' => 'How many checks at most?', 'options' => array( 'A' => 'Five', 'B' => 'Two hundred' ), 'correct' => 'A', 'explanation' => 'The formative quick-check limit is five.' ),
);
$GLOBALS['checks'] = json_encode( $valid );
$normalized = LSCH_Quick_Checks::normalize( $GLOBALS['checks'] );
assert_v12( is_array( $normalized ) && 2 === count( $normalized ), 'Two approved checks should normalize.' );
$view = LSCH_Quick_Checks::questions( 22 );
assert_v12( is_array( $view ) && 2 === count( $view ), 'Read view must contain the intended checks.' );
assert_v12( false === strpos( json_encode( $view ), 'correct' ) && false === strpos( json_encode( $view ), 'De-identified case.' ), 'Correct-answer flags and explanations must never appear before submission.' );
$grade = LSCH_Quick_Checks::grade( 22, array( '0' => 'A', '1' => 'B' ) );
assert_v12( is_array( $grade ) && 1 === $grade['correct'] && 2 === $grade['total'] && false === $grade['credential_credit'], 'Formative scoring must not award credential evidence.' );
assert_v12( false === $grade['review'][1]['correct'] && 2 === count( $grade['review'] ), 'Detailed feedback must follow submission.' );
$GLOBALS['checks'] = json_encode( array_fill( 0, 6, $valid[0] ) );
assert_v12( is_wp_error( LSCH_Quick_Checks::normalize( $GLOBALS['checks'] ) ), 'Six questions must be rejected, not truncated.' );
$GLOBALS['checks'] = json_encode( array( array_merge( $valid[0], array( 'correct' => 'C' ) ) ) );
assert_v12( is_wp_error( LSCH_Quick_Checks::normalize( $GLOBALS['checks'] ) ), 'Invalid answer key must block publication.' );
$GLOBALS['checks'] = '{bad-json';
assert_v12( is_wp_error( LSCH_Quick_Checks::questions( 22 ) ), 'Corrupt blueprint must fail closed.' );
$GLOBALS['checks'] = json_encode( $valid );
assert_v12( is_wp_error( LSCH_Quick_Checks::grade( 22, array( array(), 'A' ) ) ), 'Structured injected answers must be rejected.' );
echo "PASS: v12 bounded quick checks, private answer keys, safe post-submit feedback.\n";
