<?php
/**
 * File 05 owned auxiliary state: local saved learning searches, correction
 * proposals and privacy-minimized learning-value events.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_State {
	const OPTION = 'lsch_state_schema_version';
	const SCHEMA = 3;

	public static function tables() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lsch_';
		return array(
			'saved_searches' => $prefix . 'saved_searches',
			'corrections'    => $prefix . 'corrections',
			'value_events'   => $prefix . 'value_events',
		);
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( self::OPTION, 0 ) < self::SCHEMA ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t = self::tables();
		$c = $wpdb->get_charset_collate();

		dbDelta( "CREATE TABLE {$t['saved_searches']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			public_id char(36) NOT NULL,
			label varchar(120) NOT NULL,
			query_json text NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY public_id (public_id),
			KEY user_updated (user_id,updated_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['corrections']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			object_type varchar(32) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			proposer_id bigint(20) unsigned NOT NULL,
			proposed_object_version bigint(20) unsigned NOT NULL DEFAULT 1,
			applied_object_version bigint(20) unsigned NOT NULL DEFAULT 0,
			reason text NOT NULL,
			source_reference text NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'submitted',
			reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			decision_reason text NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			decided_at datetime NULL,
			PRIMARY KEY (id),
			UNIQUE KEY public_id (public_id),
			KEY object_status (object_type,object_id,status),
			KEY proposer_status (proposer_id,status),
			KEY reviewer_status (reviewer_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['value_events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_name varchar(64) NOT NULL,
			object_type varchar(32) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			context_json text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY event_id (event_id),
			KEY event_created (event_name,created_at),
			KEY user_created (user_id,created_at)
		) {$c};" );

		foreach ( $t as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				throw new RuntimeException( 'A required File 05 auxiliary state table was not created.' );
			}
		}
		update_option( self::OPTION, self::SCHEMA, false );
	}

	public static function hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			LSCH_REST::NS,
			'/search/saved',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rest_saved_searches' ),
					'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'rest_save_search' ),
					'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
				),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/search/saved/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'rest_delete_saved_search' ),
				'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/corrections',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rest_corrections' ),
					'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'rest_submit_correction' ),
					'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
				),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/corrections/(?P<id>\d+)/decision',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_decide_correction' ),
				'permission_callback' => array( __CLASS__, 'can_review_corrections' ),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/corrections/(?P<id>\d+)/resubmit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_resubmit_correction' ),
				'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/corrections/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'rest_withdraw_correction' ),
				'permission_callback' => array( __CLASS__, 'can_use_private_state' ),
			)
		);
	}

	public static function can_use_private_state() {
		return LSCH_Policy::can_use_learning_actions();
	}

	public static function can_review_corrections() {
		$user_id = get_current_user_id();
		return $user_id && ( LSCH_Capabilities::can_review( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );
	}

	private static function normalize_search_query( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out = array();
		$text = isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '';
		if ( '' !== $text ) {
			$out['search'] = substr( $text, 0, 160 );
		}
		foreach ( array( 'type', 'topic', 'level', 'language' ) as $key ) {
			if ( isset( $input[ $key ] ) && '' !== (string) $input[ $key ] ) {
				$out[ $key ] = sanitize_key( (string) $input[ $key ] );
			}
		}
		$out['scope'] = 'file05-learning-only';
		return $out;
	}

	public static function rest_saved_searches() {
		global $wpdb;
		$t = self::tables();
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id,public_id,label,query_json,version,created_at,updated_at FROM {$t['saved_searches']} WHERE user_id=%d ORDER BY updated_at DESC,id DESC LIMIT 50",
				get_current_user_id()
			),
			ARRAY_A
		);
		foreach ( $rows as &$row ) {
			$row['query'] = json_decode( (string) $row['query_json'], true );
			unset( $row['query_json'] );
		}
		unset( $row );
		return rest_ensure_response( $rows );
	}

	public static function rest_save_search( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$user_id = get_current_user_id();
		$label = sanitize_text_field( (string) $request->get_param( 'label' ) );
		$query = self::normalize_search_query( (array) $request->get_param( 'query' ) );
		if ( '' === $label || ! $query ) {
			return new WP_Error( 'lsch_saved_search_invalid', __( 'A label and valid File 05 learning query are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$label = substr( $label, 0, 120 );
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert(
			$t['saved_searches'],
			array(
				'user_id'    => $user_id,
				'public_id'  => $public_id,
				'label'      => $label,
				'query_json' => wp_json_encode( $query ),
				'version'    => 1,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_saved_search_write_failed', __( 'The learning search could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		self::record_value( 'LearningSearchSaved.v1', 'search', 0, array( 'filter_count' => count( $query ) - 1 ), $user_id );
		return new WP_REST_Response( array( 'id' => (int) $wpdb->insert_id, 'public_id' => $public_id ), 201 );
	}

	public static function rest_delete_saved_search( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$id = absint( $request['id'] );
		$deleted = $wpdb->delete(
			$t['saved_searches'],
			array( 'id' => $id, 'user_id' => get_current_user_id() ),
			array( '%d', '%d' )
		);
		if ( ! $deleted ) {
			return new WP_Error( 'lsch_saved_search_not_found', __( 'Saved learning search not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) );
		}
		return new WP_REST_Response( null, 204 );
	}

	public static function rest_corrections() {
		global $wpdb;
		$t = self::tables();
		$user_id = get_current_user_id();
		$reviewer = self::can_review_corrections();
		if ( $reviewer ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$t['corrections']} WHERE status IN ('submitted','under_review','needs_information','applying','apply_failed') OR proposer_id=%d ORDER BY updated_at DESC,id DESC LIMIT 150",
				$user_id
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$t['corrections']} WHERE proposer_id=%d ORDER BY updated_at DESC,id DESC LIMIT 100",
				$user_id
			);
		}
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( $reviewer && ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			$rows = array_values( array_filter( $rows, static function( $row ) use ( $user_id ) {
				return absint( $row['proposer_id'] ) === $user_id || user_can( $user_id, 'edit_post', absint( $row['object_id'] ) );
			} ) );
		}
		return rest_ensure_response( $rows );
	}

	public static function rest_submit_correction( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$object_id = absint( $request->get_param( 'object_id' ) );
		$object_type = LSCH_Content::object_type( $object_id );
		$reason = sanitize_textarea_field( (string) $request->get_param( 'reason' ) );
		$source = sanitize_textarea_field( (string) $request->get_param( 'source_reference' ) );
		if ( ! $object_id || ! $object_type || ! LSCH_Policy::can_read_post( $object_id ) || strlen( $reason ) < 10 ) {
			return new WP_Error( 'lsch_correction_invalid', __( 'A readable File 05 object and a substantive correction reason are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert(
			$t['corrections'],
			array(
				'public_id'        => $public_id,
				'object_type'      => $object_type,
				'object_id'        => $object_id,
				'proposer_id'      => get_current_user_id(),
				'proposed_object_version' => LSCH_Content::version( $object_id ),
				'applied_object_version'  => 0,
				'reason'           => substr( $reason, 0, 5000 ),
				'source_reference' => substr( $source, 0, 5000 ),
				'status'           => 'submitted',
				'reviewer_id'      => 0,
				'decision_reason'  => '',
				'version'          => 1,
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_correction_write_failed', __( 'The correction proposal could not be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		LSCH_Events::publish( 'LearningCorrectionProposed.v1', $object_type, $object_id, array( 'correction_id' => $public_id ) );
		return new WP_REST_Response( array( 'id' => (int) $wpdb->insert_id, 'public_id' => $public_id, 'status' => 'submitted' ), 201 );
	}

	public static function rest_decide_correction( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$id = absint( $request['id'] );
		$decision = sanitize_key( (string) $request->get_param( 'decision' ) );
		$reason = sanitize_textarea_field( (string) $request->get_param( 'reason' ) );
		if ( ! in_array( $decision, array( 'accepted', 'rejected', 'needs_information' ), true ) || strlen( $reason ) < 5 ) {
			return new WP_Error( 'lsch_correction_decision_invalid', __( 'A valid correction decision and reason are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['corrections']} WHERE id=%d LIMIT 1", $id ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'lsch_correction_not_found', __( 'Correction proposal not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) );
		}
		if ( absint( $row['proposer_id'] ) === get_current_user_id() ) {
			return new WP_Error( 'lsch_correction_conflict', __( 'A proposer cannot independently approve their own correction.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		if ( ! user_can( get_current_user_id(), 'edit_post', absint( $row['object_id'] ) ) && ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return new WP_Error( 'lsch_correction_scope', __( 'You are not assigned to review corrections for this learning object.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		if ( ! in_array( $row['status'], array( 'submitted', 'under_review' ), true ) ) {
			return new WP_Error( 'lsch_correction_state', __( 'This correction is not in a reviewable state.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$current_object_version = LSCH_Content::version( absint( $row['object_id'] ) );
		if ( 'accepted' === $decision && $current_object_version !== absint( $row['proposed_object_version'] ) ) {
			return new WP_Error( 'lsch_correction_stale', __( 'The learning object changed after this correction was proposed. Request updated information or resubmission.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$now = current_time( 'mysql', true );

		if ( 'needs_information' === $decision ) {
			$ok = $wpdb->update(
				$t['corrections'],
				array( 'status' => 'needs_information', 'reviewer_id' => get_current_user_id(), 'decision_reason' => substr( $reason, 0, 5000 ), 'version' => absint( $row['version'] ) + 1, 'updated_at' => $now, 'decided_at' => null ),
				array( 'id' => $id, 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
				array( '%s', '%d', '%s', '%d', '%s', '%s' ),
				array( '%d', '%d', '%s' )
			);
			if ( 1 !== $ok ) {
				return new WP_Error( 'lsch_correction_conflict', __( 'The correction changed while it was being reviewed. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
			LSCH_Events::publish( 'LearningCorrectionNeedsInformation.v1', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'] ) );
			return rest_ensure_response( array( 'id' => $id, 'status' => 'needs_information' ) );
		}

		if ( 'rejected' === $decision ) {
			$ok = $wpdb->update(
				$t['corrections'],
				array( 'status' => 'rejected', 'reviewer_id' => get_current_user_id(), 'decision_reason' => substr( $reason, 0, 5000 ), 'version' => absint( $row['version'] ) + 1, 'updated_at' => $now, 'decided_at' => $now ),
				array( 'id' => $id, 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
				array( '%s', '%d', '%s', '%d', '%s', '%s' ),
				array( '%d', '%d', '%s' )
			);
			if ( 1 !== $ok ) {
				return new WP_Error( 'lsch_correction_conflict', __( 'The correction changed while it was being reviewed. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
			LSCH_Events::publish( 'LearningCorrectionDecided.v1', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'], 'decision' => 'rejected' ) );
			return rest_ensure_response( array( 'id' => $id, 'status' => 'rejected' ) );
		}

		$claimed = $wpdb->update(
			$t['corrections'],
			array( 'status' => 'applying', 'reviewer_id' => get_current_user_id(), 'decision_reason' => substr( $reason, 0, 5000 ), 'version' => absint( $row['version'] ) + 1, 'updated_at' => $now, 'decided_at' => null ),
			array( 'id' => $id, 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
			array( '%s', '%d', '%s', '%d', '%s', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $claimed ) {
			return new WP_Error( 'lsch_correction_conflict', __( 'The correction changed while it was being reviewed. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}

		$applied = self::apply_correction( absint( $row['object_id'] ), $reason, absint( $row['proposed_object_version'] ) );
		if ( is_wp_error( $applied ) ) {
			$wpdb->update(
				$t['corrections'],
				array( 'status' => 'apply_failed', 'version' => absint( $row['version'] ) + 2, 'updated_at' => current_time( 'mysql', true ) ),
				array( 'id' => $id, 'status' => 'applying', 'reviewer_id' => get_current_user_id() ),
				array( '%s', '%d', '%s' ),
				array( '%d', '%s', '%d' )
			);
			LSCH_Events::audit( 'learning_correction_apply_failed', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'], 'error_code' => $applied->get_error_code() ), 'editorial_governance' );
			return $applied;
		}

		$finalized = $wpdb->update(
			$t['corrections'],
			array( 'status' => 'accepted', 'applied_object_version' => absint( $applied ), 'version' => absint( $row['version'] ) + 2, 'updated_at' => current_time( 'mysql', true ), 'decided_at' => current_time( 'mysql', true ) ),
			array( 'id' => $id, 'status' => 'applying', 'reviewer_id' => get_current_user_id() ),
			array( '%s', '%d', '%d', '%s', '%s' ),
			array( '%d', '%s', '%d' )
		);
		if ( 1 !== $finalized ) {
			return new WP_Error( 'lsch_correction_finalize_pending', __( 'The correction was applied, but its governance record still requires reconciliation.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		LSCH_Events::publish( 'LearningCorrectionDecided.v1', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'], 'decision' => 'accepted', 'new_version' => absint( $applied ) ) );
		return rest_ensure_response( array( 'id' => $id, 'status' => 'accepted', 'object_version' => absint( $applied ) ) );
	}

	public static function rest_resubmit_correction( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$id = absint( $request['id'] );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['corrections']} WHERE id=%d AND proposer_id=%d LIMIT 1", $id, get_current_user_id() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'lsch_correction_not_found', __( 'Correction proposal not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) );
		}
		if ( 'needs_information' !== $row['status'] ) {
			return new WP_Error( 'lsch_correction_resubmit_state', __( 'Only a correction awaiting information can be resubmitted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$reason = sanitize_textarea_field( (string) $request->get_param( 'reason' ) );
		$source = sanitize_textarea_field( (string) $request->get_param( 'source_reference' ) );
		if ( strlen( $reason ) < 10 ) {
			return new WP_Error( 'lsch_correction_invalid', __( 'A substantive correction reason is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$current_version = LSCH_Content::version( absint( $row['object_id'] ) );
		if ( ! $current_version ) {
			return new WP_Error( 'lsch_correction_object_gone', __( 'The learning object is no longer available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 410 ) );
		}
		$ok = $wpdb->update(
			$t['corrections'],
			array( 'status' => 'submitted', 'reason' => substr( $reason, 0, 5000 ), 'source_reference' => substr( $source, 0, 5000 ), 'proposed_object_version' => $current_version, 'applied_object_version' => 0, 'reviewer_id' => 0, 'decision_reason' => '', 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ), 'decided_at' => null ),
			array( 'id' => $id, 'proposer_id' => get_current_user_id(), 'version' => absint( $row['version'] ), 'status' => 'needs_information' ),
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%s' ),
			array( '%d', '%d', '%d', '%s' )
		);
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_correction_conflict', __( 'The correction changed while it was being resubmitted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		LSCH_Events::publish( 'LearningCorrectionResubmitted.v1', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'] ) );
		return rest_ensure_response( array( 'id' => $id, 'status' => 'submitted' ) );
	}

	public static function rest_withdraw_correction( WP_REST_Request $request ) {
		global $wpdb;
		$t = self::tables();
		$id = absint( $request['id'] );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['corrections']} WHERE id=%d AND proposer_id=%d LIMIT 1", $id, get_current_user_id() ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'lsch_correction_not_found', __( 'Correction proposal not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) );
		}
		if ( ! in_array( $row['status'], array( 'submitted', 'under_review', 'needs_information' ), true ) ) {
			return new WP_Error( 'lsch_correction_withdraw_state', __( 'This correction can no longer be withdrawn.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$ok = $wpdb->update(
			$t['corrections'],
			array( 'status' => 'withdrawn', 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ), 'decided_at' => current_time( 'mysql', true ) ),
			array( 'id' => $id, 'proposer_id' => get_current_user_id(), 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d', '%d', '%d', '%s' )
		);
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_correction_conflict', __( 'The correction changed while it was being withdrawn.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		LSCH_Events::publish( 'LearningCorrectionWithdrawn.v1', $row['object_type'], absint( $row['object_id'] ), array( 'correction_id' => $row['public_id'] ) );
		return new WP_REST_Response( null, 204 );
	}


	private static function correction_object_lock( $object_id ) {
		global $wpdb;
		$name = 'lsch:corr:' . substr( hash( 'sha256', (string) absint( $object_id ) ), 0, 48 );
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $name ) );
		return 1 === $locked ? $name : new WP_Error( 'lsch_correction_object_busy', __( 'Another correction for this learning object is being applied. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
	}

	private static function correction_object_unlock( $name ) {
		if ( ! $name ) {
			return;
		}
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	}

	private static function apply_correction( $object_id, $reason, $expected_version ) {
		global $wpdb;
		$object_id = absint( $object_id );
		$lock = self::correction_object_lock( $object_id );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		try {
			clean_post_cache( $object_id );
			$type = LSCH_Content::object_type( $object_id );
			if ( ! $type ) {
				return new WP_Error( 'lsch_correction_object_gone', __( 'The learning object is no longer available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 410 ) );
			}
			$current_version = LSCH_Content::version( $object_id );
			if ( $current_version !== absint( $expected_version ) ) {
				return new WP_Error( 'lsch_correction_stale', __( 'The learning object changed before the correction could be applied.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
			$new_version = $current_version + 1;
			$wpdb->query( 'START TRANSACTION' );
			try {
				update_post_meta( $object_id, '_lsch_correction_note', sanitize_textarea_field( $reason ) );
				if ( false === update_post_meta( $object_id, '_lsch_version', $new_version ) ) {
					throw new RuntimeException( 'content_version_write_failed' );
				}
				if ( LSCH_Content::LESSON === $type ) {
					$t = LSCH_Database::tables();
					$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$t['progress']} SET needs_review=1,version=version+1,updated_at=%s WHERE lesson_id=%d AND lesson_version<%d", LSCH_Database::now(), $object_id, $new_version ) );
					if ( false === $updated ) {
						throw new RuntimeException( 'learner_review_mark_failed' );
					}
				}
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					throw new RuntimeException( 'correction_commit_failed' );
				}
			} catch ( Throwable $error ) {
				$wpdb->query( 'ROLLBACK' );
				clean_post_cache( $object_id );
				return new WP_Error( 'lsch_correction_apply_failed', __( 'The correction could not be applied atomically. No partial correction was accepted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500, 'reason_code' => sanitize_key( $error->getMessage() ) ) );
			}
			clean_post_cache( $object_id );
			LSCH_Events::publish( 'LearningContentCorrected.v1', $type, $object_id, array( 'previous_version' => $current_version, 'new_version' => $new_version ) );
			LSCH_Events::audit( 'learning_content_corrected', $type, $object_id, array( 'new_version' => $new_version ), 'editorial_governance' );
			return $new_version;
		} finally {
			self::correction_object_unlock( $lock );
		}
	}

	/**
	 * Store only bounded, privacy-minimized value telemetry. Raw search text,
	 * note content, assessment answers and clinical/patient data are forbidden.
	 */
	public static function record_value( $event_name, $object_type = '', $object_id = 0, array $context = array(), $user_id = 0 ) {
		global $wpdb;
		$allowed = array( 'filter_count', 'source_count', 'format', 'result_count', 'completion_percent', 'correction_status', 'low_bandwidth' );
		$clean = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				$value = $context[ $key ];
				$clean[ $key ] = is_bool( $value ) ? $value : ( is_numeric( $value ) ? (float) $value : sanitize_key( (string) $value ) );
			}
		}
		$t = self::tables();
		return 1 === $wpdb->insert(
			$t['value_events'],
			array(
				'event_id'     => LSCH_Database::uuid(),
				'user_id'      => absint( $user_id ),
				'event_name'   => sanitize_key( str_replace( '.', '_', (string) $event_name ) ),
				'object_type'  => sanitize_key( (string) $object_type ),
				'object_id'    => absint( $object_id ),
				'context_json' => wp_json_encode( $clean ),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}
}
