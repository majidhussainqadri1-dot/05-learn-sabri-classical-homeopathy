<?php
/**
 * Database schema and low-level persistence helpers.
 *
 * @package SabriLearning
 */

defined( 'ABSPATH' ) || exit;

final class SLC_Database {
	const OPTION = 'slc_schema_version';

	/** Install or upgrade schema idempotently. */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_progress (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			lesson_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'started',
			score smallint(5) unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson (user_id,lesson_id),
			KEY lesson_id (lesson_id),
			KEY user_updated (user_id,updated_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_bookmarks (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			lesson_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson (user_id,lesson_id),
			KEY lesson_id (lesson_id),
			KEY user_created (user_id,created_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_audit_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lesson_id bigint(20) unsigned NOT NULL,
			actor_id bigint(20) unsigned NOT NULL,
			action varchar(40) NOT NULL,
			from_state varchar(30) NOT NULL DEFAULT '',
			to_state varchar(30) NOT NULL DEFAULT '',
			note text NOT NULL,
			request_id varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY lesson_created (lesson_id,created_at),
			KEY actor_created (actor_id,created_at),
			KEY action (action)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_consents (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lesson_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			policy_version varchar(32) NOT NULL,
			subject_type varchar(20) NOT NULL,
			consent_source varchar(40) NOT NULL,
			scope text NOT NULL,
			evidence_reference varchar(190) NOT NULL DEFAULT '',
			confirmed_at datetime NOT NULL,
			withdrawn_at datetime NULL,
			withdrawn_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY lesson_policy (lesson_id,policy_version),
			KEY author_id (author_id),
			KEY withdrawn_at (withdrawn_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_metrics (
			lesson_id bigint(20) unsigned NOT NULL,
			view_count bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (lesson_id),
			KEY view_count (view_count)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_rate_limits (
			rate_key varchar(191) NOT NULL,
			window_start datetime NOT NULL,
			hit_count int(10) unsigned NOT NULL DEFAULT 0,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (rate_key),
			KEY expires_at (expires_at)
		) {$charset};" );

		self::assert_tables();
		self::migrate_legacy_data();
		SLC_Permissions::install_caps();
		update_option( self::OPTION, SLC_SCHEMA_VERSION, false );
	}

	/** Run idempotent upgrades. */
	public static function maybe_upgrade() {
		if ( (int) get_option( self::OPTION, 0 ) < SLC_SCHEMA_VERSION ) {
			self::install();
		}
	}

	/** Migrate the immutable v0.1.0 baseline without inventing consent evidence. */
	private static function migrate_legacy_data() {
		global $wpdb;
		$wpdb->query(
			"INSERT INTO {$wpdb->prefix}slc_metrics (lesson_id,view_count,updated_at)
			 SELECT pm.post_id,MAX(CAST(pm.meta_value AS UNSIGNED)),UTC_TIMESTAMP()
			 FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id
			 WHERE pm.meta_key='_slc_views' AND p.post_type='slc_lesson'
			 GROUP BY pm.post_id
			 ON DUPLICATE KEY UPDATE view_count=GREATEST(view_count,VALUES(view_count)),updated_at=UTC_TIMESTAMP()" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$wpdb->query(
			"INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
			 SELECT p.ID,'_slc_workflow_state',CASE p.post_status WHEN 'publish' THEN 'published' WHEN 'pending' THEN 'submitted' WHEN 'private' THEN 'hidden' ELSE 'rejected' END
			 FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_slc_workflow_state'
			 WHERE p.post_type='slc_lesson' AND pm.meta_id IS NULL" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$wpdb->query(
			"INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
			 SELECT p.ID,'_slc_row_version','1' FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_slc_row_version'
			 WHERE p.post_type='slc_lesson' AND pm.meta_id IS NULL" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$wpdb->query(
			"INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
			 SELECT p.ID,'_slc_medical_notice_version','legacy-0.1.0' FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_slc_medical_notice_version'
			 WHERE p.post_type='slc_lesson' AND p.post_status='publish' AND pm.meta_id IS NULL" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	/** @throws RuntimeException When a required table is unavailable. */
	private static function assert_tables() {
		global $wpdb;
		foreach ( array( 'progress', 'bookmarks', 'audit_log', 'consents', 'metrics', 'rate_limits' ) as $suffix ) {
			$table = $wpdb->prefix . 'slc_' . $suffix;
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( $found !== $table ) {
				throw new RuntimeException( sprintf( 'Required File 05 table was not created: %s', $table ) );
			}
		}
	}

	/**
	 * Atomic fixed-window rate limiter.
	 *
	 * @param string $key Rate key.
	 * @param int    $limit Maximum hits.
	 * @param int    $window Window seconds.
	 * @return bool True when allowed.
	 */
	public static function allow( $key, $limit, $window ) {
		global $wpdb;
		$key    = substr( hash( 'sha256', (string) $key ), 0, 64 );
		$now    = time();
		$start  = gmdate( 'Y-m-d H:i:s', $now );
		$expiry = gmdate( 'Y-m-d H:i:s', $now + max( 1, absint( $window ) ) );
		$table  = $wpdb->prefix . 'slc_rate_limits';

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (rate_key,window_start,hit_count,expires_at) VALUES (%s,%s,1,%s)
				 ON DUPLICATE KEY UPDATE
				 hit_count = IF(expires_at <= UTC_TIMESTAMP(), 1, hit_count + 1),
				 window_start = IF(expires_at <= UTC_TIMESTAMP(), VALUES(window_start), window_start),
				 expires_at = IF(expires_at <= UTC_TIMESTAMP(), VALUES(expires_at), expires_at)",
				$key,
				$start,
				$expiry
			)
		);
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT hit_count FROM {$table} WHERE rate_key=%s", $key ) );
		return $count <= max( 1, absint( $limit ) );
	}

	/** Whether a patient-case lesson has a current consent record. */
	public static function valid_consent( $lesson_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}slc_consents WHERE lesson_id=%d AND withdrawn_at IS NULL ORDER BY id DESC LIMIT 1", absint( $lesson_id ) ) );
	}

	/** Whether a lesson may be exposed or acted upon publicly. */
	public static function lesson_publicly_available( $lesson_id ) {
		$lesson_id = absint( $lesson_id );
		if ( SLC_Content::LESSON !== get_post_type( $lesson_id ) || 'publish' !== get_post_status( $lesson_id ) ) {
			return false;
		}
		$topic = SLC_Content::term( $lesson_id, SLC_Content::TOPIC, 'slug' );
		$level = SLC_Content::term( $lesson_id, SLC_Content::LEVEL, 'slug' );
		if ( ! SLC_Content::allowed( $topic, SLC_Content::TOPIC ) || ! SLC_Content::allowed( $level, SLC_Content::LEVEL ) ) {
			return false;
		}
		return 'patient-case-learning' !== $topic || self::valid_consent( $lesson_id );
	}

	/** Atomically increment and return a lesson view count. */
	public static function increment_view( $lesson_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'slc_metrics';
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (lesson_id,view_count,updated_at) VALUES (%d,1,UTC_TIMESTAMP())
				 ON DUPLICATE KEY UPDATE view_count=view_count+1, updated_at=UTC_TIMESTAMP()",
				absint( $lesson_id )
			)
		);
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT view_count FROM {$table} WHERE lesson_id=%d", absint( $lesson_id ) ) );
	}

	/** Return a lesson view count. */
	public static function views( $lesson_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT view_count FROM {$wpdb->prefix}slc_metrics WHERE lesson_id=%d", absint( $lesson_id ) ) );
	}

	/** Delete orphaned per-user and per-lesson records. */
	public static function cleanup_orphans() {
		global $wpdb;
		foreach ( array( 'progress', 'bookmarks' ) as $suffix ) {
			$table = $wpdb->prefix . 'slc_' . $suffix;
			$wpdb->query( "DELETE r FROM {$table} r LEFT JOIN {$wpdb->users} u ON u.ID=r.user_id LEFT JOIN {$wpdb->posts} p ON p.ID=r.lesson_id WHERE u.ID IS NULL OR p.ID IS NULL OR p.post_type<>'slc_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$wpdb->query( "DELETE m FROM {$wpdb->prefix}slc_metrics m LEFT JOIN {$wpdb->posts} p ON p.ID=m.lesson_id WHERE p.ID IS NULL OR p.post_type<>'slc_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->prefix}slc_rate_limits WHERE expires_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
