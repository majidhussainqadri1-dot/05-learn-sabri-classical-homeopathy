<?php
/**
 * File 05 Future-18 privacy regression against a controlled WordPress DB stub.
 * This is a unit/contract fixture, NOT a WordPress staging or migration test.
 */
define( 'ABSPATH', '/unit-wordpress/' );
$GLOBALS['file05_hold'] = false;
function absint( $v ) { return abs( (int) $v ); }
function __( $v, $domain = '' ) { return $v; }
function get_user_by( $field, $value ) {
    return 'email' === $field && 'learner@example.test' === $value ? (object) array( 'ID' => 25 ) : false;
}
function apply_filters( $name, $default, $user_id = null ) {
    return 'lsch_user_legal_hold' === $name ? $GLOBALS['file05_hold'] : $default;
}
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
final class LSCH_Events {
    public static function audit( $action, $type, $id, $context, $purpose ) {}
}
final class File05TestDB {
    public $prefix = 'wp_';
    public $queries = array();
    public $prepared = array();
    public function prepare( $sql, ...$params ) {
        $this->prepared[] = $sql;
        return vsprintf( $sql, $params );
    }
    public function get_results( $sql, $mode = null ) {
        if ( false !== strpos( $sql, 'lsch_f18_pathways' ) ) {
            return array( array( 'goal' => 'balanced_mastery', 'plan_json' => '{"recommendations":[]}', 'version' => 1 ) );
        }
        if ( false !== strpos( $sql, 'lsch_f18_practice' ) && false !== strpos( $sql, 'WHERE assessor_id=25' ) ) {
            return array( array( 'public_id' => 'test-practice', 'status' => 'graded', 'assessor_id' => 25 ) );
        }
        if ( false !== strpos( $sql, 'lsch_f18_cpd' ) && false !== strpos( $sql, 'WHERE verified_by=25' ) ) {
            return array( array( 'public_id' => 'test-cpd', 'status' => 'verified', 'verified_by' => 25 ) );
        }
        return array();
    }
    public function delete( $table, $where, $formats = array() ) { return 0; }
    public function query( $sql ) {
        $this->queries[] = $sql;
        return ( false !== strpos( $sql, 'SET assessor_id=0' ) || false !== strpos( $sql, 'SET verified_by=0' ) ) ? 1 : 0;
    }
}
$wpdb = new File05TestDB();
define( 'ARRAY_A', 'ARRAY_A' );
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
function check_file05( $truth, $label ) {
    if ( ! $truth ) { fwrite( STDERR, "FAIL: {$label}\n" ); exit( 1 ); }
}
$export = LSCH_Future18::privacy_export( 'learner@example.test', 1 );
check_file05( isset( $export['done'] ) && true === $export['done'], 'all export projections are paginated' );
check_file05( count( $export['data'] ) === 1, 'actor-only or pathway data triggers nonempty export' );
$entries = $export['data'][0]['data'];
$by_name = array();
foreach ( $entries as $entry ) { $by_name[ $entry['name'] ] = $entry['value']; }
foreach ( array(
    'Personal learning pathways' => 'balanced_mastery',
    'Assessor actions (no learner responses)' => 'test-practice',
    'CPD verification actions (no learner evidence)' => 'test-cpd',
) as $name => $marker ) {
    check_file05( isset( $by_name[ $name ] ) && false !== strpos( $by_name[ $name ], $marker ), $name . ' exported' );
}
check_file05( false === strpos( $by_name['Assessor actions (no learner responses)'], 'response_json' ), 'assessor export omits learner response' );
check_file05( false === strpos( $by_name['CPD verification actions (no learner evidence)'], 'evidence_json' ), 'verifier export omits learner evidence' );

$result = LSCH_Future18::privacy_erase( 'learner@example.test', 1 );
check_file05( $result['items_removed'] && $result['items_retained'], 'actor identifiers removed with academic rows retained' );
check_file05( count( $wpdb->queries ) > 0, 'SQL redaction executed' );
$sql = implode( "\n", $wpdb->queries );
check_file05( false !== strpos( $sql, 'UPDATE wp_lsch_f18_practice SET assessor_id=0' ), 'other learner practice assessor deidentified' );
check_file05( false !== strpos( $sql, 'UPDATE wp_lsch_f18_cpd SET verified_by=0' ), 'other learner CPD verifier deidentified' );

$GLOBALS['file05_hold'] = true;
$wpdb->queries = array();
$hold_result = LSCH_Future18::privacy_erase( 'learner@example.test', 1 );
check_file05( $hold_result['items_retained'], 'legal hold reported' );
$sql = implode( "\n", $wpdb->queries );
check_file05( false === strpos( $sql, 'SET assessor_id=0' ) && false === strpos( $sql, 'SET verified_by=0' ), 'legal hold preserves audit identities' );
echo "PASS: File05 Future18 privacy export, actor redaction and legal-hold contract tests.\n";
