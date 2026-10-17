<?php
/** WordPress core privacy export must never reveal other learners' private data to assessors/reviewers. */
define( 'ABSPATH', '/test-wordpress/' );
define( 'ARRAY_A', 'ARRAY_A' );
$GLOBALS['export_sql'] = array();
class LSCH_Database {
	public static function tables() {
		$tables = array( 'enrollments','progress','bookmarks','attempts','submissions','staff','completions','reminders','request_keys','audit','consents','notes' );
		$map = array(); foreach ( $tables as $key ) { $map[$key] = 'wp_lsch_' . $key; }
		return $map;
	}
}
class LSCH_State {
	public static function tables() {
		return array( 'saved_searches'=>'wp_lsch_saved_searches','value_events'=>'wp_lsch_value_events','corrections'=>'wp_lsch_corrections' );
	}
}
class WP_Error { public function __construct( $code, $message = '' ) {} }
class PrivacyProjectionDB {
	public function prepare( $query, ...$args ) { if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; } return vsprintf( $query, $args ); }
	public function get_results( $query, $return = null ) {
		$GLOBALS['export_sql'][] = $query;
		if ( false !== strpos( $query, 'wp_lsch_consents' ) ) {
			if ( false !== strpos( $query, 'WHERE withdrawn_by=25 AND created_by<>25' ) ) {
				return array( array('id'=>401,'policy_version'=>'v1','withdrawn_by'=>25,'withdrawn_at'=>'2026-01-01','version'=>2) );
			}
			if ( false !== strpos( $query, 'WHERE created_by=25' ) ) { return array( array('id'=>400,'created_by'=>25,'evidence_reference'=>'OWN_CONSENT_EVIDENCE') ); }
			return array( array('id'=>401,'created_by'=>33,'withdrawn_by'=>25,'evidence_reference'=>'OTHER_PATIENT_CONSENT_SECRET') );
		}
		if ( false !== strpos( $query, 'wp_lsch_staff' ) ) {
			if ( false !== strpos( $query, 'WHERE assigned_by=25 AND user_id<>25' ) ) {
				return array( array('id'=>501,'object_type'=>'lesson','object_id'=>13,'role'=>'assessor','active'=>1,'assigned_by'=>25,'version'=>1) );
			}
			if ( false !== strpos( $query, 'WHERE user_id=25' ) ) { return array( array('id'=>500,'user_id'=>25,'scope_json'=>'OWN_SCOPE') ); }
			return array( array('id'=>501,'assigned_by'=>25,'user_id'=>34,'scope_json'=>'OTHER_PERSON_SCOPE_SECRET') );
		}
		if ( false !== strpos( $query, 'wp_lsch_submissions' ) ) {
			if ( false !== strpos( $query, 'WHERE assessor_id=25 AND user_id<>25' ) ) {
				return array( array( 'id'=>202, 'status'=>'graded', 'assessor_id'=>25, 'version'=>3, 'updated_at'=>'2026-01-01 00:00:00' ) );
			}
			if ( false !== strpos( $query, 'WHERE user_id=25' ) ) {
				return array( array( 'id'=>201, 'user_id'=>25, 'body'=>'OWN_LEARNER_ANSWER', 'attachments_json'=>'[]' ) );
			}
			return array( array( 'id'=>202, 'user_id'=>32, 'body'=>'OTHER_LEARNER_ANSWER_SECRET', 'appeal_text'=>'PRIVATE_APPEAL_SECRET' ) );
		}
		if ( false !== strpos( $query, 'wp_lsch_corrections' ) ) {
			if ( false !== strpos( $query, 'WHERE reviewer_id=25 AND proposer_id<>25' ) ) {
				return array( array( 'id'=>302, 'status'=>'accepted', 'reviewer_id'=>25, 'version'=>2, 'decided_at'=>'2026-01-01 00:00:00' ) );
			}
			if ( false !== strpos( $query, 'WHERE proposer_id=25' ) ) {
				return array( array( 'id'=>301, 'proposer_id'=>25, 'reason'=>'OWN_CORRECTION_TEXT' ) );
			}
			return array( array( 'id'=>302, 'proposer_id'=>33, 'reason'=>'OTHER_LEARNER_PROPOSAL_SECRET', 'source_reference'=>'PRIVATE_SOURCE' ) );
		}
		return array();
	}
}
$GLOBALS['wpdb'] = new PrivacyProjectionDB();
function get_user_by( $field, $email ) { return 'email' === $field && 'actor@example.test' === $email ? (object)array('ID'=>25) : false; }
function absint( $x ) { return abs( (int)$x ); }
function __( $text, $domain='' ) { return $text; }
function wp_json_encode( $x, $flags=0 ) { return json_encode( $x, $flags ); }
function privacy_check( $ok, $label ) { if(!$ok){fwrite(STDERR,"FAIL: {$label}\n");exit(1);} }
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-privacy.php';
$export = ( new LSCH_Privacy() )->export( 'actor@example.test', 1 );
privacy_check( is_array($export) && true === $export['done'], 'complete paginated export returns valid response' );
$json = json_encode( $export );
privacy_check( false !== strpos($json,'OWN_LEARNER_ANSWER'), 'own learner submission included' );
privacy_check( false !== strpos($json,'OWN_CORRECTION_TEXT'), 'own correction included' );
privacy_check( false === strpos($json,'OTHER_LEARNER_ANSWER_SECRET'), 'no other learner answer leakage' );
privacy_check( false === strpos($json,'PRIVATE_APPEAL_SECRET'), 'no other learner appeal leakage' );
privacy_check( false === strpos($json,'OTHER_LEARNER_PROPOSAL_SECRET'), 'no other user correction content' );
privacy_check( false !== strpos($json,'lsch-assessor-actions'), 'assessor actor-only audit visible' );

privacy_check( false !== strpos($json,'OWN_CONSENT_EVIDENCE'), 'my own consent evidence exported' );
privacy_check( false !== strpos($json,'OWN_SCOPE'), 'my own staff assignment included' );
privacy_check( false === strpos($json,'OTHER_PATIENT_CONSENT_SECRET'), 'no other patient consent evidence leaked to withdrawal actor' );
privacy_check( false === strpos($json,'OTHER_PERSON_SCOPE_SECRET'), 'no other staff member protected scope leaked to assigner' );
privacy_check( false !== strpos($json,'lsch-consent-withdrawal-actions'), 'withdrawal actor-only reference exported' );
privacy_check( false !== strpos($json,'lsch-staff-assignment-actions'), 'staff-assignment actor-only reference exported' );

privacy_check( false !== strpos($json,'lsch-correction-review-actions'), 'reviewer actor-only audit visible' );
foreach($GLOBALS['export_sql'] as $sql){
	if ( false !== strpos($sql,'wp_lsch_submissions') ) {
		privacy_check( false === strpos($sql,'user_id=25 OR assessor_id=25'), 'no full submission SELECT for another learner via OR' );
	}
	if ( false !== strpos($sql,'wp_lsch_corrections') ) {
		privacy_check( false === strpos($sql,'proposer_id=25 OR reviewer_id=25'), 'no full correction SELECT for another proposer via OR' );
	}
}
echo "PASS: File05 core privacy exporter isolates learner-owned data and actor-only academic actions.\n";
