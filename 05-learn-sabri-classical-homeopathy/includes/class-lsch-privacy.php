<?php
/** WordPress privacy export/erasure with academic-integrity retention. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Privacy {
	public function hooks() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
	}

	public function exporters( $items ) {
		$items['lsch-learning'] = array(
			'exporter_friendly_name' => __( 'Sabri learning records', 'learn-sabri-classical-homeopathy' ),
			'callback'               => array( $this, 'export' ),
		);
		return $items;
	}

	public function erasers( $items ) {
		$items['lsch-learning'] = array(
			'eraser_friendly_name' => __( 'Sabri learning records', 'learn-sabri-classical-homeopathy' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $items;
	}

	public function export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
		$page = max( 1, absint( $page ) ); $limit = 100; $offset = ( $page - 1 ) * $limit;
		global $wpdb; $t = LSCH_Database::tables(); $state = LSCH_State::tables(); $data = array(); $done = true;
		$uid = absint( $user->ID );
		$specs = array(
			array( $t['enrollments'], 'user_id=%d', array( $uid ), 'lsch-enrollments', __( 'Learning enrollments', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['progress'], 'user_id=%d', array( $uid ), 'lsch-progress', __( 'Learning progress', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['bookmarks'], 'user_id=%d', array( $uid ), 'lsch-bookmarks', __( 'Learning bookmarks', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['attempts'], 'user_id=%d', array( $uid ), 'lsch-attempts', __( 'Assessment attempts', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['submissions'], '(user_id=%d OR assessor_id=%d)', array( $uid, $uid ), 'lsch-submissions', __( 'Assignment submissions and assessment actions', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['staff'], '(user_id=%d OR assigned_by=%d)', array( $uid, $uid ), 'lsch-staff-assignments', __( 'Learning staff assignments', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['completions'], 'user_id=%d', array( $uid ), 'lsch-completions', __( 'Learning completions', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['reminders'], 'user_id=%d', array( $uid ), 'lsch-reminders', __( 'Learning reminders', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['request_keys'], 'user_id=%d', array( $uid ), 'lsch-request-history', __( 'Protected request history', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['audit'], 'actor_id=%d', array( $uid ), 'lsch-audit-history', __( 'Learning audit history', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['consents'], '(created_by=%d OR withdrawn_by=%d)', array( $uid, $uid ), 'lsch-case-consent-actions', __( 'Learning case consent actions', 'learn-sabri-classical-homeopathy' ) ),
			array( $state['saved_searches'], 'user_id=%d', array( $uid ), 'lsch-saved-learning-searches', __( 'Saved learning searches', 'learn-sabri-classical-homeopathy' ) ),
			array( $state['value_events'], 'user_id=%d', array( $uid ), 'lsch-learning-value-events', __( 'Learning value events', 'learn-sabri-classical-homeopathy' ) ),
		);
		foreach ( $specs as $spec ) {
			$args = array_merge( $spec[2], array( $limit, $offset ) );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$spec[0]} WHERE {$spec[1]} ORDER BY id ASC LIMIT %d OFFSET %d", $args ), ARRAY_A );
			if ( ! is_array( $rows ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Learning privacy export could not read all required records safely.', 'learn-sabri-classical-homeopathy' ) ); }
			if ( count( $rows ) === $limit ) { $done = false; }
			foreach ( $rows as $row ) { $item_id = isset( $row['id'] ) ? absint( $row['id'] ) : substr( hash( 'sha256', wp_json_encode( $row ) ), 0, 16 ); $data[] = array( 'group_id' => $spec[3], 'group_label' => $spec[4], 'item_id' => $spec[3] . '-' . $item_id, 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) ); }
		}
		$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['notes']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $uid, $limit, $offset ), ARRAY_A );
		if ( ! is_array( $notes ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Private learning notes could not be read safely for export.', 'learn-sabri-classical-homeopathy' ) ); }
		if ( count( $notes ) === $limit ) { $done = false; }
		foreach ( $notes as $row ) { $plain = LSCH_Policy::decrypt_note_checked( $row, $uid, $row['lesson_id'] ); $data[] = array( 'group_id' => 'lsch-private-notes', 'group_label' => __( 'Private learning notes', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'note-' . $row['id'], 'data' => array( array( 'name' => __( 'Lesson', 'learn-sabri-classical-homeopathy' ), 'value' => get_the_title( $row['lesson_id'] ) ), array( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => is_wp_error( $plain ) ? '[encrypted-note-unavailable:' . sanitize_key( $plain->get_error_code() ) . ']' : $plain ), array( 'name' => __( 'Version', 'learn-sabri-classical-homeopathy' ), 'value' => (string) $row['version'] ), array( 'name' => __( 'Updated', 'learn-sabri-classical-homeopathy' ), 'value' => $row['updated_at'] ) ) ); }
		$corrections = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$state['corrections']} WHERE proposer_id=%d OR reviewer_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $uid, $uid, $limit, $offset ), ARRAY_A );
		if ( ! is_array( $corrections ) ) { return new WP_Error( 'lsch_privacy_export_query_failed', __( 'Learning correction records could not be read safely for export.', 'learn-sabri-classical-homeopathy' ) ); }
		if ( count( $corrections ) === $limit ) { $done = false; }
		foreach ( $corrections as $row ) { $data[] = array( 'group_id' => 'lsch-corrections', 'group_label' => __( 'Learning correction records', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'correction-' . $row['id'], 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) ); }
		return array( 'data' => $data, 'done' => $done );
	}
	public function erase( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user || $page > 1 ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}

		global $wpdb;
		$t = LSCH_Database::tables();
		$state = LSCH_State::tables();
		$hold = (bool) apply_filters( 'lsch_user_legal_hold', false, $user->ID );
		$removed = false;
		$retained = false;
		$messages = array();
		$failures = array();

		foreach ( array( 'progress', 'bookmarks', 'notes', 'reminders', 'request_keys' ) as $key ) {
			$result = $wpdb->delete( $t[ $key ], array( 'user_id' => $user->ID ), array( '%d' ) );
			if ( false === $result ) {
				$failures[] = $key;
			} elseif ( $result > 0 ) {
				$removed = true;
			}
		}
		foreach ( array( 'saved_searches', 'value_events' ) as $key ) {
			$result = $wpdb->delete( $state[ $key ], array( 'user_id' => $user->ID ), array( '%d' ) );
			if ( false === $result ) {
				$failures[] = $key;
			} elseif ( $result > 0 ) {
				$removed = true;
			}
		}

		if ( $hold ) {
			$retained = true;
			$messages[] = __( 'Assessment, submission, completion and correction-governance records were retained under an active legal hold.', 'learn-sabri-classical-homeopathy' );
		} else {
			$queries = array(
				$wpdb->prepare( "UPDATE {$t['attempts']} SET answers_json='{}',result_json='{}',integrity_status='erased_subject',version=version+1 WHERE user_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['submissions']} SET body='[erased]',attachments_json='[]',appeal_text='',version=version+1 WHERE user_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['submissions']} SET assessor_id=0,version=version+1 WHERE assessor_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['audit']} SET actor_id=0 WHERE actor_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['consents']} SET created_by=0,evidence_reference='[erased]' WHERE created_by=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['consents']} SET withdrawn_by=0 WHERE withdrawn_by=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['staff']} SET assigned_by=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE assigned_by=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$state['corrections']} SET proposer_id=0,source_reference='[erased]',version=version+1,updated_at=UTC_TIMESTAMP() WHERE proposer_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$state['corrections']} SET reviewer_id=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE reviewer_id=%d", $user->ID ),
			);
			foreach ( $queries as $index => $sql ) {
				if ( false === $wpdb->query( $sql ) ) {
					$failures[] = 'anonymize-' . $index;
				}
			}
			$retained = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT (SELECT COUNT(*) FROM {$t['enrollments']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['attempts']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['submissions']} WHERE user_id=%d) + (SELECT COUNT(*) FROM {$t['completions']} WHERE user_id=%d)", $user->ID, $user->ID, $user->ID, $user->ID ) );
			$messages[] = __( 'Private activity and notes were removed. Minimum enrollment, assessment, submission and earned-record evidence may remain identifiable only under the documented academic-retention policy; other actor-role identifiers were anonymized.', 'learn-sabri-classical-homeopathy' );
		}

		if ( $failures ) {
			$messages[] = __( 'Some privacy operations could not be verified and require operator retry.', 'learn-sabri-classical-homeopathy' );
			LSCH_Events::audit( 'privacy_erasure_partial_failure', 'user', $user->ID, array( 'failure_count' => count( $failures ) ), 'privacy' );
		}

		LSCH_Events::audit( 'privacy_erasure', 'user', $user->ID, array( 'legal_hold' => $hold, 'failure_count' => count( $failures ) ), 'privacy' );
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $messages,
			'done'           => true,
		);
	}
}
