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
		$page = max( 1, absint( $page ) );
		$limit = 100;
		$offset = ( $page - 1 ) * $limit;
		global $wpdb;
		$t = LSCH_Database::tables();
		$state = LSCH_State::tables();
		$data = array();
		$done = true;

		$specs = array(
			array( $t['enrollments'], 'user_id', 'lsch-enrollments', __( 'Learning enrollments', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['progress'], 'user_id', 'lsch-progress', __( 'Learning progress', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['bookmarks'], 'user_id', 'lsch-bookmarks', __( 'Learning bookmarks', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['attempts'], 'user_id', 'lsch-attempts', __( 'Assessment attempts', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['submissions'], 'user_id', 'lsch-submissions', __( 'Assignment submissions', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['staff'], 'user_id', 'lsch-staff-assignments', __( 'Learning staff assignments', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['completions'], 'user_id', 'lsch-completions', __( 'Learning completions', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['reminders'], 'user_id', 'lsch-reminders', __( 'Learning reminders', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['request_keys'], 'user_id', 'lsch-request-history', __( 'Protected request history', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['audit'], 'actor_id', 'lsch-audit-history', __( 'Learning audit history', 'learn-sabri-classical-homeopathy' ) ),
			array( $t['consents'], 'created_by', 'lsch-case-consent-actions', __( 'Learning case consent actions', 'learn-sabri-classical-homeopathy' ) ),
			array( $state['saved_searches'], 'user_id', 'lsch-saved-learning-searches', __( 'Saved learning searches', 'learn-sabri-classical-homeopathy' ) ),
			array( $state['value_events'], 'user_id', 'lsch-learning-value-events', __( 'Learning value events', 'learn-sabri-classical-homeopathy' ) ),
		);
		foreach ( $specs as $spec ) {
			$table = $spec[0]; $column = $spec[1];
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$column}=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $limit, $offset ), ARRAY_A );
			if ( count( $rows ) === $limit ) { $done = false; }
			foreach ( $rows as $row ) {
				$item_id = isset( $row['id'] ) ? absint( $row['id'] ) : substr( hash( 'sha256', wp_json_encode( $row ) ), 0, 16 );
				$data[] = array(
					'group_id' => $spec[2], 'group_label' => $spec[3], 'item_id' => $spec[2] . '-' . $item_id,
					'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ),
				);
			}
		}

		$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['notes']} WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $limit, $offset ), ARRAY_A );
		if ( count( $notes ) === $limit ) { $done = false; }
		foreach ( $notes as $row ) {
			$plain = LSCH_Policy::decrypt_note_checked( $row, $user->ID, $row['lesson_id'] );
			$data[] = array(
				'group_id' => 'lsch-private-notes', 'group_label' => __( 'Private learning notes', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'note-' . $row['id'],
				'data' => array(
					array( 'name' => __( 'Lesson', 'learn-sabri-classical-homeopathy' ), 'value' => get_the_title( $row['lesson_id'] ) ),
					array( 'name' => __( 'Note', 'learn-sabri-classical-homeopathy' ), 'value' => is_wp_error( $plain ) ? '[encrypted-note-unavailable:' . sanitize_key( $plain->get_error_code() ) . ']' : $plain ),
					array( 'name' => __( 'Version', 'learn-sabri-classical-homeopathy' ), 'value' => (string) $row['version'] ),
					array( 'name' => __( 'Updated', 'learn-sabri-classical-homeopathy' ), 'value' => $row['updated_at'] ),
				),
			);
		}

		$corrections = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$state['corrections']} WHERE proposer_id=%d OR reviewer_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $user->ID, $limit, $offset ), ARRAY_A );
		if ( count( $corrections ) === $limit ) { $done = false; }
		foreach ( $corrections as $row ) {
			$data[] = array( 'group_id' => 'lsch-corrections', 'group_label' => __( 'Learning correction records', 'learn-sabri-classical-homeopathy' ), 'item_id' => 'correction-' . $row['id'], 'data' => array_map( static function( $key, $value ) { return array( 'name' => (string) $key, 'value' => is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value ) ); }, array_keys( $row ), array_values( $row ) ) );
		}

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
				$wpdb->prepare( "UPDATE {$t['audit']} SET actor_id=0 WHERE actor_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['consents']} SET created_by=0,evidence_reference='[erased]' WHERE created_by=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=%d", $user->ID ),
				$wpdb->prepare( "UPDATE {$state['corrections']} SET proposer_id=0,source_reference='[erased]',version=version+1,updated_at=UTC_TIMESTAMP() WHERE proposer_id=%d", $user->ID ),
			);
			foreach ( $queries as $index => $sql ) {
				if ( false === $wpdb->query( $sql ) ) {
					$failures[] = 'anonymize-' . $index;
				}
			}
			$retained = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['completions']} WHERE user_id=%d", $user->ID ) );
			$messages[] = __( 'Private activity and notes were removed. Minimum assessment, correction and earned-record evidence was anonymized or retained for academic integrity.', 'learn-sabri-classical-homeopathy' );
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
