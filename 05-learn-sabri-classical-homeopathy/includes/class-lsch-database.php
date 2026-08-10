<?php
/** Normalized learning persistence and legacy reconciliation. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Database {
	const OPTION = 'lsch_schema_version';

	public static function tables() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lsch_';
		return array(
			'enrollments' => $prefix . 'enrollments',
			'progress'    => $prefix . 'progress',
			'bookmarks'   => $prefix . 'bookmarks',
			'notes'       => $prefix . 'notes',
			'attempts'    => $prefix . 'attempts',
			'submissions' => $prefix . 'submissions',
			'staff'       => $prefix . 'staff_assignments',
			'completions' => $prefix . 'completions',
			'related'     => $prefix . 'related_links',
			'consents'    => $prefix . 'case_consents',
			'reminders'   => $prefix . 'reminders',
			'outbox'      => $prefix . 'outbox',
			'inbox'       => $prefix . 'inbox',
			'jobs'        => $prefix . 'jobs',
			'audit'        => $prefix . 'audit_log',
			'request_keys' => $prefix . 'request_keys',
		);
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t = self::tables();
		$c = $wpdb->get_charset_collate();

		dbDelta( "CREATE TABLE {$t['enrollments']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'active',
			terms_version varchar(32) NOT NULL DEFAULT 'free-v1',
			course_version bigint(20) unsigned NOT NULL DEFAULT 1,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			started_at datetime NOT NULL,
			paused_at datetime NULL,
			withdrawn_at datetime NULL,
			completed_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY user_course (user_id,course_id), KEY course_status (course_id,status), KEY user_updated (user_id,updated_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['progress']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			lesson_id bigint(20) unsigned NOT NULL,
			state varchar(24) NOT NULL DEFAULT 'in_progress',
			percent tinyint(3) unsigned NOT NULL DEFAULT 0,
			resume_point int(10) unsigned NOT NULL DEFAULT 0,
			components_json longtext NOT NULL,
			lesson_version bigint(20) unsigned NOT NULL DEFAULT 1,
			needs_review tinyint(1) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			started_at datetime NOT NULL,
			completed_at datetime NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_lesson (user_id,lesson_id), KEY course_state (course_id,state), KEY user_updated (user_id,updated_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['bookmarks']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			object_type varchar(32) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_object (user_id,object_type,object_id), KEY object_ref (object_type,object_id)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['notes']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			lesson_id bigint(20) unsigned NOT NULL,
			ciphertext longtext NOT NULL,
			iv varchar(64) NOT NULL,
			tag varchar(64) NOT NULL,
			key_version smallint(5) unsigned NOT NULL DEFAULT 1,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_lesson (user_id,lesson_id), KEY lesson_id (lesson_id)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['attempts']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			assessment_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			attempt_number smallint(5) unsigned NOT NULL DEFAULT 1,
			item_version bigint(20) unsigned NOT NULL DEFAULT 1,
			answers_json longtext NOT NULL,
			result_json longtext NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'started',
			integrity_status varchar(24) NOT NULL DEFAULT 'clear',
			idempotency_key varchar(64) NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			started_at datetime NOT NULL,
			expires_at datetime NULL,
			submitted_at datetime NULL,
			graded_at datetime NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY idempotency_key (idempotency_key), UNIQUE KEY assessment_user_attempt (assessment_id,user_id,attempt_number), KEY user_status (user_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['submissions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			assignment_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			body longtext NOT NULL,
			attachments_json longtext NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'submitted',
			rubric_version bigint(20) unsigned NOT NULL DEFAULT 1,
			assessor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			feedback longtext NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0,
			appeal_text longtext NOT NULL,
			appeal_status varchar(24) NOT NULL DEFAULT '',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY assignment_user (assignment_id,user_id), KEY assessor_status (assessor_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['staff']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			object_type varchar(24) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			role varchar(24) NOT NULL,
			scope_json longtext NOT NULL,
			conflict_status varchar(24) NOT NULL DEFAULT 'clear',
			active tinyint(1) unsigned NOT NULL DEFAULT 1,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			assigned_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY assignment (user_id,object_type,object_id,role), KEY object_role (object_type,object_id,role,active)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['completions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			course_version bigint(20) unsigned NOT NULL,
			competency_snapshot_json longtext NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'earned',
			identity_assurance varchar(24) NOT NULL DEFAULT 'verified',
			integrity_status varchar(24) NOT NULL DEFAULT 'clear',
			share_token_hash char(64) NOT NULL DEFAULT '',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			earned_at datetime NOT NULL,
			revoked_at datetime NULL,
			revoked_reason text NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY user_course_version (user_id,course_id,course_version), KEY course_status (course_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['related']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_type varchar(24) NOT NULL,
			source_id bigint(20) unsigned NOT NULL,
			target_file varchar(16) NOT NULL,
			target_type varchar(32) NOT NULL,
			target_id varchar(64) NOT NULL,
			target_url text NOT NULL,
			relation varchar(32) NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'active',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY relation_key (source_type,source_id,target_file,target_type,target_id,relation), KEY source_ref (source_type,source_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['consents']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lesson_id bigint(20) unsigned NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			policy_version varchar(32) NOT NULL,
			subject_type varchar(24) NOT NULL,
			consent_source varchar(40) NOT NULL,
			scope text NOT NULL,
			evidence_reference varchar(190) NOT NULL DEFAULT '',
			confirmed_at datetime NOT NULL,
			withdrawn_at datetime NULL,
			withdrawn_by bigint(20) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY (id), UNIQUE KEY lesson_policy (lesson_id,policy_version), KEY active_lesson (lesson_id,withdrawn_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['reminders']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			enabled tinyint(1) unsigned NOT NULL DEFAULT 0,
			cadence varchar(24) NOT NULL DEFAULT 'weekly',
			quiet_hours_json text NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_course (user_id,course_id), KEY enabled_course (enabled,course_id)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['outbox']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id char(36) NOT NULL,
			event_name varchar(96) NOT NULL,
			aggregate_type varchar(32) NOT NULL,
			aggregate_id varchar(64) NOT NULL,
			payload_json longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			next_attempt_at datetime NOT NULL,
			last_error_code varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			processed_at datetime NULL,
			PRIMARY KEY (id), UNIQUE KEY event_id (event_id), KEY dispatch (status,next_attempt_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['inbox']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id char(36) NOT NULL,
			event_name varchar(96) NOT NULL,
			payload_hash char(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'processed',
			received_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY event_id (event_id)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['jobs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_key varchar(64) NOT NULL,
			job_type varchar(64) NOT NULL,
			payload_json longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			max_attempts smallint(5) unsigned NOT NULL DEFAULT 5,
			run_after datetime NOT NULL,
			locked_at datetime NULL,
			last_error_code varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY job_key (job_key), KEY queue (status,run_after)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['audit']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			trace_id char(36) NOT NULL,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			action varchar(64) NOT NULL,
			object_type varchar(32) NOT NULL,
			object_id varchar(64) NOT NULL,
			purpose varchar(64) NOT NULL DEFAULT '',
			context_json longtext NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY trace_id (trace_id), KEY object_ref (object_type,object_id), KEY actor_created (actor_id,created_at)
		) {$c};" );


		dbDelta( "CREATE TABLE {$t['request_keys']} (
			key_hash char(64) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			route varchar(190) NOT NULL,
			method varchar(10) NOT NULL,
			request_hash char(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'processing',
			response_status smallint(5) unsigned NOT NULL DEFAULT 0,
			response_ref_json text NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY (key_hash), KEY user_created (user_id,created_at), KEY expiry (expires_at), KEY state_updated (status,updated_at)
		) {$c};" );

		self::assert_tables();
		self::migrate_legacy();
		update_option( self::OPTION, LSCH_SCHEMA_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( self::OPTION, 0 ) < LSCH_SCHEMA_VERSION ) {
			self::install();
		}
	}

	private static function assert_tables() {
		global $wpdb;
		foreach ( self::tables() as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				throw new RuntimeException( 'Required learning table was not created.' );
			}
		}
	}

	/** Additive migration from the immutable SLC 1.0.0 foundation. */
	private static function migrate_legacy() {
		global $wpdb;
		$t = self::tables();

		$legacy_posts = $wpdb->get_results( "SELECT ID,post_type FROM {$wpdb->posts} WHERE post_type IN ('slc_book','slc_lesson') ORDER BY ID ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$post_map = array();
		foreach ( $legacy_posts as $legacy_post ) {
			$new_type = 'slc_book' === $legacy_post['post_type'] ? LSCH_Content::BOOK : LSCH_Content::LESSON;
			$wpdb->update( $wpdb->posts, array( 'post_type' => $new_type ), array( 'ID' => absint( $legacy_post['ID'] ), 'post_type' => $legacy_post['post_type'] ), array( '%s' ), array( '%d', '%s' ) );
			$legacy_terms = $wpdb->get_results( $wpdb->prepare( "SELECT tt.taxonomy,t.name,t.slug FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id INNER JOIN {$wpdb->terms} t ON t.term_id=tt.term_id WHERE tr.object_id=%d AND tt.taxonomy IN ('slc_topic','slc_level')", absint( $legacy_post['ID'] ) ), ARRAY_A );
			foreach ( $legacy_terms as $legacy_term ) {
				$target_taxonomy = 'slc_topic' === $legacy_term['taxonomy'] ? LSCH_Content::TOPIC : LSCH_Content::LEVEL;
				$target = get_term_by( 'slug', $legacy_term['slug'], $target_taxonomy );
				if ( ! $target ) { $created = wp_insert_term( $legacy_term['name'], $target_taxonomy, array( 'slug' => $legacy_term['slug'] ) ); $target = ! is_wp_error( $created ) ? get_term( $created['term_id'], $target_taxonomy ) : null; }
				if ( $target && ! is_wp_error( $target ) ) { wp_set_object_terms( absint( $legacy_post['ID'] ), array( absint( $target->term_id ) ), $target_taxonomy, true ); }
			}
			$post_map[ absint( $legacy_post['ID'] ) ] = array( 'from' => $legacy_post['post_type'], 'to' => $new_type );
			$meta_map = array( '_slc_objectives' => '_lsch_objectives', '_slc_references' => '_lsch_sources', '_slc_terms' => '_lsch_key_terms', '_slc_study_time' => '_lsch_duration', '_slc_book_id' => '_lsch_book_id', '_slc_language' => '_lsch_language', '_slc_row_version' => '_lsch_version' );
			foreach ( $meta_map as $old_key => $new_key ) {
				$value = get_post_meta( absint( $legacy_post['ID'] ), $old_key, true );
				if ( '' !== (string) $value && '' === (string) get_post_meta( absint( $legacy_post['ID'] ), $new_key, true ) ) {
					update_post_meta( absint( $legacy_post['ID'] ), $new_key, $value );
				}
			}
			if ( LSCH_Content::LESSON === $new_type && '' === (string) get_post_meta( absint( $legacy_post['ID'] ), '_lsch_access', true ) ) {
				update_post_meta( absint( $legacy_post['ID'] ), '_lsch_access', 'public' );
			}
		}
		if ( $post_map ) {
			update_option( 'lsch_legacy_post_type_map', $post_map, false );
		}

		$legacy_progress = $wpdb->prefix . 'slc_progress';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_progress ) ) === $legacy_progress ) {
			$wpdb->query( "INSERT INTO {$t['progress']} (user_id,course_id,lesson_id,state,percent,resume_point,components_json,lesson_version,needs_review,version,started_at,completed_at,updated_at)
				SELECT user_id,0,lesson_id,CASE status WHEN 'completed' THEN 'completed' ELSE 'in_progress' END,CASE status WHEN 'completed' THEN 100 ELSE 10 END,0,'{\"content\":true}',1,0,1,updated_at,CASE status WHEN 'completed' THEN updated_at ELSE NULL END,updated_at FROM {$legacy_progress}
				ON DUPLICATE KEY UPDATE state=VALUES(state),percent=GREATEST(percent,VALUES(percent)),updated_at=GREATEST(updated_at,VALUES(updated_at))" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$legacy_bookmarks = $wpdb->prefix . 'slc_bookmarks';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_bookmarks ) ) === $legacy_bookmarks ) {
			$wpdb->query( "INSERT IGNORE INTO {$t['bookmarks']} (user_id,object_type,object_id,created_at) SELECT user_id,'lsch_lesson',lesson_id,created_at FROM {$legacy_bookmarks}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$legacy_consents = $wpdb->prefix . 'slc_consents';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_consents ) ) === $legacy_consents ) {
			$wpdb->query( "INSERT IGNORE INTO {$t['consents']} (lesson_id,created_by,policy_version,subject_type,consent_source,scope,evidence_reference,confirmed_at,withdrawn_at,withdrawn_by,version) SELECT lesson_id,author_id,policy_version,subject_type,consent_source,scope,evidence_reference,confirmed_at,withdrawn_at,withdrawn_by,1 FROM {$legacy_consents}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		update_option( 'lsch_legacy_migration', array( 'completed_at' => gmdate( 'c' ), 'source' => 'slc-1.0.0', 'mode' => 'additive', 'post_count' => count( $post_map ) ), false );
	}

	public static function uuid() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}
		return sprintf( '%08x-%04x-%04x-%04x-%012x', mt_rand(), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand() );
	}

	public static function now() {
		return current_time( 'mysql', true );
	}
}
