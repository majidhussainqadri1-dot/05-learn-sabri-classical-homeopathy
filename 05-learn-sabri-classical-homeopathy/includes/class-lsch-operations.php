<?php
/** Operations, jobs, reconciliation, safe mode and health. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Operations {
	public function hooks() {
		add_action( 'lsch_process_outbox', array( __CLASS__, 'process_outbox' ) );
		add_action( 'lsch_process_jobs', array( __CLASS__, 'process_jobs' ) );
		add_action( 'lsch_daily_reconcile', array( __CLASS__, 'daily_reconcile' ) );
		add_filter( 'site_status_tests', array( $this, 'site_health_tests' ) );
	}

	public static function safe_mode() { return (bool) get_option( 'lsch_safe_mode', false ) || (bool) apply_filters( 'lsch_safe_mode', false ); }
	public static function set_safe_mode( $enabled, $reason = '' ) { update_option( 'lsch_safe_mode', (bool) $enabled, false ); update_option( 'lsch_safe_mode_reason', sanitize_text_field( $reason ), false ); LSCH_Events::audit( 'safe_mode_changed', 'system', 'file05', array( 'enabled' => (bool) $enabled, 'reason' => $reason ), 'operations' ); }

	public static function system_check() {
		global $wpdb; $checks = array(); $missing = LSCH_Dependencies::missing();
		$checks['Dependencies'] = array( 'status' => $missing ? 'fail' : 'pass', 'detail' => $missing ? implode( ', ', $missing ) : 'Required File 00, File 01 and File 20 contracts are available.' );
		foreach ( LSCH_Database::tables() as $name => $table ) { $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table; $checks[ 'Table: ' . $name ] = array( 'status' => $exists ? 'pass' : 'fail', 'detail' => $exists ? $table : 'Missing owner table.' ); }
		$pages = (array) get_option( 'lsch_page_map', array() ); $checks['Managed pages'] = array( 'status' => ! empty( $pages['home'] ) && ! empty( $pages['dashboard'] ) ? 'pass' : 'warn', 'detail' => wp_json_encode( $pages ) );
		$checks['Cron outbox'] = array( 'status' => wp_next_scheduled( 'lsch_process_outbox' ) ? 'pass' : 'warn', 'detail' => wp_next_scheduled( 'lsch_process_outbox' ) ? 'Scheduled' : 'Not scheduled' );
		$checks['Safe mode'] = array( 'status' => self::safe_mode() ? 'warn' : 'pass', 'detail' => self::safe_mode() ? (string) get_option( 'lsch_safe_mode_reason', 'Enabled' ) : 'Disabled' );
		$checks['Encryption'] = array( 'status' => function_exists( 'openssl_encrypt' ) ? 'pass' : 'fail', 'detail' => function_exists( 'openssl_encrypt' ) ? 'AES-256-GCM private notes available.' : 'OpenSSL unavailable; private notes fail closed.' );
		$overall = 'pass'; foreach ( $checks as $check ) { if ( 'fail' === $check['status'] ) { $overall = 'fail'; break; } if ( 'warn' === $check['status'] ) { $overall = 'warn'; } }
		return array( 'status' => $overall, 'version' => LSCH_VERSION, 'schema' => LSCH_SCHEMA_VERSION, 'safe_mode' => self::safe_mode(), 'checks' => $checks, 'generated_at' => gmdate( 'c' ) );
	}

	public static function repair( $dry_run = true ) {
		$plan = array( 'seed_vocabularies', 'recreate_missing_pages', 'reschedule_cron', 'reconcile_orphans', 'requeue_stale_jobs' );
		if ( $dry_run ) { return array( 'dry_run' => true, 'plan' => $plan ); }
		LSCH_Content::seed_vocabularies(); LSCH_Activator::ensure_pages(); self::schedule(); self::daily_reconcile(); return array( 'dry_run' => false, 'completed' => $plan );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'lsch_process_outbox' ) ) { wp_schedule_event( time() + 60, 'hourly', 'lsch_process_outbox' ); }
		if ( ! wp_next_scheduled( 'lsch_process_jobs' ) ) { wp_schedule_event( time() + 120, 'hourly', 'lsch_process_jobs' ); }
		if ( ! wp_next_scheduled( 'lsch_daily_reconcile' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lsch_daily_reconcile' ); }
	}
	public static function unschedule() { foreach ( array( 'lsch_process_outbox', 'lsch_process_jobs', 'lsch_daily_reconcile' ) as $hook ) { $timestamp = wp_next_scheduled( $hook ); while ( $timestamp ) { wp_unschedule_event( $timestamp, $hook ); $timestamp = wp_next_scheduled( $hook ); } } }

	public static function process_outbox() {
		if ( self::safe_mode() ) { return; }
		global $wpdb; $t = LSCH_Database::tables(); $rows = $wpdb->get_results( "SELECT * FROM {$t['outbox']} WHERE status IN ('pending','retry') AND next_attempt_at<=UTC_TIMESTAMP() ORDER BY id ASC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $row ) {
			$payload = json_decode( $row['payload_json'], true ); $delivered = apply_filters( 'lsch_dispatch_event', null, $row['event_name'], $payload, $row['event_id'] );
			if ( true === $delivered || null === $delivered ) { $wpdb->update( $t['outbox'], array( 'status' => 'processed', 'processed_at' => LSCH_Database::now(), 'attempts' => absint( $row['attempts'] ) + 1 ), array( 'id' => absint( $row['id'] ) ), array( '%s', '%s', '%d' ), array( '%d' ) ); }
			else { $attempts = absint( $row['attempts'] ) + 1; $status = $attempts >= 5 ? 'dead' : 'retry'; $next = gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, 60 * ( 2 ** $attempts ) ) ); $wpdb->update( $t['outbox'], array( 'status' => $status, 'attempts' => $attempts, 'next_attempt_at' => $next, 'last_error_code' => 'consumer_rejected' ), array( 'id' => absint( $row['id'] ) ), array( '%s', '%d', '%s', '%s' ), array( '%d' ) ); }
		}
	}

	public static function process_jobs() {
		if ( self::safe_mode() ) { return; }
		global $wpdb; $t = LSCH_Database::tables(); $jobs = $wpdb->get_results( "SELECT * FROM {$t['jobs']} WHERE status IN ('pending','retry') AND run_after<=UTC_TIMESTAMP() ORDER BY id ASC LIMIT 25", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $jobs as $job ) { $payload = json_decode( $job['payload_json'], true ); $result = apply_filters( 'lsch_run_job', null, $job['job_type'], $payload, $job['job_key'] ); if ( true === $result || null === $result ) { $wpdb->update( $t['jobs'], array( 'status' => 'completed', 'updated_at' => LSCH_Database::now() ), array( 'id' => absint( $job['id'] ) ), array( '%s', '%s' ), array( '%d' ) ); } else { $attempts = absint( $job['attempts'] ) + 1; $dead = $attempts >= absint( $job['max_attempts'] ); $wpdb->update( $t['jobs'], array( 'status' => $dead ? 'dead' : 'retry', 'attempts' => $attempts, 'run_after' => gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, 60 * ( 2 ** $attempts ) ) ), 'last_error_code' => 'handler_rejected', 'updated_at' => LSCH_Database::now() ), array( 'id' => absint( $job['id'] ) ), array( '%s', '%d', '%s', '%s', '%s' ), array( '%d' ) ); } }
	}

	public static function daily_reconcile() {
		global $wpdb; $t = LSCH_Database::tables();
		$wpdb->query( "DELETE b FROM {$t['bookmarks']} b LEFT JOIN {$wpdb->posts} p ON p.ID=b.object_id WHERE p.ID IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE p FROM {$t['progress']} p LEFT JOIN {$wpdb->posts} l ON l.ID=p.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE n FROM {$t['notes']} n LEFT JOIN {$wpdb->posts} l ON l.ID=n.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE c FROM {$t['consents']} c LEFT JOIN {$wpdb->posts} l ON l.ID=c.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE r FROM {$t['reminders']} r LEFT JOIN {$wpdb->posts} c ON c.ID=r.course_id WHERE c.ID IS NULL OR c.post_type<>'lsch_course'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE s FROM {$t['staff']} s LEFT JOIN {$wpdb->users} u ON u.ID=s.user_id WHERE u.ID IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "UPDATE {$t['jobs']} SET status='retry',locked_at=NULL,run_after=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE status='running' AND locked_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		update_option( 'lsch_last_reconcile', gmdate( 'c' ), false );
	}

	public static function related_links( $source_type, $source_id ) { global $wpdb; $t = LSCH_Database::tables(); return $wpdb->get_results( $wpdb->prepare( "SELECT target_file,target_type,target_id,target_url,relation,version FROM {$t['related']} WHERE source_type=%s AND source_id=%d AND status='active' ORDER BY id ASC LIMIT 100", sanitize_key( $source_type ), absint( $source_id ) ), ARRAY_A ); }

	public function site_health_tests( $tests ) { $tests['direct']['lsch_learning_health'] = array( 'label' => __( 'File 05 learning health', 'learn-sabri-classical-homeopathy' ), 'test' => array( $this, 'site_health' ) ); return $tests; }
	public function site_health() { $report = self::system_check(); return array( 'label' => __( 'File 05 learning system health', 'learn-sabri-classical-homeopathy' ), 'status' => 'pass' === $report['status'] ? 'good' : ( 'warn' === $report['status'] ? 'recommended' : 'critical' ), 'badge' => array( 'label' => 'File 05', 'color' => 'blue' ), 'description' => '<p>' . esc_html( sprintf( __( 'Learning health status: %s.', 'learn-sabri-classical-homeopathy' ), $report['status'] ) ) . '</p>', 'actions' => '<p><a href="' . esc_url( admin_url( 'admin.php?page=lsch-system-check' ) ) . '">' . esc_html__( 'Open System Check', 'learn-sabri-classical-homeopathy' ) . '</a></p>', 'test' => 'lsch_learning_health' ); }
}
