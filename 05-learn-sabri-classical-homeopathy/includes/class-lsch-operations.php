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

	public static function safe_mode() {
		return (bool) get_option( 'lsch_safe_mode', false ) || (bool) apply_filters( 'lsch_safe_mode', false );
	}

	public static function set_safe_mode( $enabled, $reason = '' ) {
		update_option( 'lsch_safe_mode', (bool) $enabled, false );
		update_option( 'lsch_safe_mode_reason', sanitize_text_field( $reason ), false );
		LSCH_Events::audit( 'safe_mode_changed', 'system', 'file05', array( 'enabled' => (bool) $enabled, 'reason' => $reason ), 'operations' );
	}

	public static function system_check() {
		global $wpdb;
		$t = LSCH_Database::tables();
		$checks = array();
		$missing = LSCH_Dependencies::missing();
		$checks['Dependencies'] = array(
			'status' => $missing ? 'fail' : 'pass',
			'detail' => $missing ? implode( ', ', $missing ) : 'Required File 00 public assertions/policy, File 01 and File 20 contracts are available.',
		);
		$checks['Central policy'] = array(
			'status' => LSCH_Policy::central_policy_ready() ? 'pass' : 'fail',
			'detail' => LSCH_Policy::central_policy_ready() ? 'Single free tier, no donor advantage, Sabri Green and File 26 ownership verified.' : 'Current central business/design/search policy is not verified.',
		);
		foreach ( $t as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			$checks[ 'Table: ' . $name ] = array( 'status' => $exists ? 'pass' : 'fail', 'detail' => $exists ? $table : 'Missing owner table.' );
		}
		foreach ( LSCH_State::tables() as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			$checks[ 'Value table: ' . $name ] = array( 'status' => $exists ? 'pass' : 'fail', 'detail' => $exists ? $table : 'Missing File 05 auxiliary state table.' );
		}
		foreach ( LSCH_Future18::tables() as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			$checks[ 'Future18 table: ' . $name ] = array( 'status' => $exists ? 'pass' : 'fail', 'detail' => $exists ? $table : 'Missing File 05 Future-18 learning table.' );
		}
		$pages = (array) get_option( 'lsch_page_map', array() );
		$core_schema=(int)get_option(LSCH_Database::OPTION,0); $state_schema=(int)get_option(LSCH_State::OPTION,0); $future_schema=(int)get_option(LSCH_Future18::OPTION,0);
		$checks['Core schema version']=array('status'=>LSCH_SCHEMA_VERSION===$core_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$core_schema,LSCH_SCHEMA_VERSION));
		$checks['Aux state schema version']=array('status'=>LSCH_State::SCHEMA===$state_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$state_schema,LSCH_State::SCHEMA));
		$checks['Future18 schema version']=array('status'=>LSCH_Future18::SCHEMA===$future_schema?'pass':'fail','detail'=>sprintf('installed=%d expected=%d',$future_schema,LSCH_Future18::SCHEMA));
		$checks['Managed pages'] = array(
			'status' => ! empty( $pages['home'] ) && ! empty( $pages['dashboard'] ) && ! empty( $pages['mastery'] ) ? 'pass' : 'warn',
			'detail' => wp_json_encode( $pages ),
		);
		$dead_outbox=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['outbox']} WHERE status='dead'"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$dead_jobs=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['jobs']} WHERE status='dead'"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$checks['Dead outbox']=array('status'=>$dead_outbox?'warn':'pass','detail'=>sprintf('%d exhausted outbox event(s).',$dead_outbox));
		$checks['Dead jobs']=array('status'=>$dead_jobs?'warn':'pass','detail'=>sprintf('%d exhausted background job(s).',$dead_jobs));
		$checks['Cron outbox'] = array(
			'status' => wp_next_scheduled( 'lsch_process_outbox' ) ? 'pass' : 'warn',
			'detail' => wp_next_scheduled( 'lsch_process_outbox' ) ? 'Scheduled' : 'Not scheduled',
		);
		$checks['Safe mode'] = array(
			'status' => self::safe_mode() ? 'warn' : 'pass',
			'detail' => self::safe_mode() ? (string) get_option( 'lsch_safe_mode_reason', 'Enabled' ) : 'Disabled',
		);
		$checks['Private note key'] = array(
			'status' => LSCH_Policy::note_encryption_ready() ? 'pass' : 'fail',
			'detail' => LSCH_Policy::note_encryption_ready() ? 'Independent AES-256-GCM File 05 note key is configured.' : 'Private notes fail closed until LSCH_NOTE_MASTER_KEY / approved keyring is configured.',
		);
		$write_key_version = LSCH_Policy::note_write_key_version();
		$stale_note_keys = 0;
		if ( $write_key_version >= LSCH_Policy::NOTE_KEY_VERSION ) {
			$t = LSCH_Database::tables();
			$stale_note_keys = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$t['notes']} WHERE key_version<>%d", $write_key_version )
			);
		}
		$checks['Private note key rotation'] = array(
			'status' => $stale_note_keys ? 'warn' : ( $write_key_version ? 'pass' : 'fail' ),
			'detail' => $write_key_version ? sprintf( 'Write key generation %d; %d note(s) require bounded re-encryption.', $write_key_version, $stale_note_keys ) : 'No current note write-key generation is available.',
		);

		$overall = 'pass';
		foreach ( $checks as $check ) {
			if ( 'fail' === $check['status'] ) {
				$overall = 'fail';
				break;
			}
			if ( 'warn' === $check['status'] ) {
				$overall = 'warn';
			}
		}
		return array(
			'status'       => $overall,
			'version'      => LSCH_VERSION,
			'schema'       => LSCH_SCHEMA_VERSION,
			'state_schema' => LSCH_State::SCHEMA,
			'future18_schema' => LSCH_Future18::SCHEMA,
			'safe_mode'    => self::safe_mode(),
			'checks'       => $checks,
			'generated_at' => gmdate( 'c' ),
		);
	}

	public static function repair( $dry_run = true ) {
		$plan = array( 'ensure_state_schema', 'ensure_future18_schema', 'seed_vocabularies', 'recreate_missing_pages', 'reschedule_cron', 'reconcile_orphans', 'requeue_stale_jobs', 'rotate_private_note_keys_bounded' );
		if ( $dry_run ) {
			return array( 'dry_run' => true, 'plan' => $plan );
		}
		LSCH_State::install();
		LSCH_Future18::install();
		LSCH_Content::seed_vocabularies();
		LSCH_Activator::ensure_pages();
		self::schedule();
		self::daily_reconcile();
		$rotation = self::rotate_note_keys( 100 );
		return array( 'dry_run' => false, 'completed' => $plan, 'note_key_rotation' => $rotation );
	}

	/**
	 * Re-encrypt at most $limit account-owned notes to the current independent
	 * key generation. Rows that cannot be decrypted are retained unchanged and
	 * surfaced as failures; no destructive fallback is allowed.
	 */
	public static function rotate_note_keys( $limit = 100 ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		$limit = max( 1, min( 500, absint( $limit ) ) );
		$write_version = LSCH_Policy::note_write_key_version();
		if ( $write_version < LSCH_Policy::NOTE_KEY_VERSION || ! LSCH_Policy::note_encryption_ready() ) {
			return array( 'status' => 'blocked', 'rotated' => 0, 'failed' => 0, 'remaining' => null );
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$t['notes']} WHERE key_version<>%d ORDER BY id ASC LIMIT %d", $write_version, $limit ),
			ARRAY_A
		);
		$rotated = 0;
		$failed = 0;
		foreach ( $rows as $row ) {
			$plain = LSCH_Policy::decrypt_note_checked( $row, absint( $row['user_id'] ), absint( $row['lesson_id'] ) );
			if ( is_wp_error( $plain ) ) {
				$failed++;
				continue;
			}
			$encrypted = LSCH_Policy::encrypt_note( $plain, absint( $row['user_id'] ), absint( $row['lesson_id'] ) );
			if ( is_wp_error( $encrypted ) ) {
				$failed++;
				continue;
			}
			$updated = $wpdb->update(
				$t['notes'],
				array(
					'ciphertext'  => $encrypted['ciphertext'],
					'iv'          => $encrypted['iv'],
					'tag'         => $encrypted['tag'],
					'key_version' => absint( $encrypted['key_version'] ),
					'version'     => absint( $row['version'] ) + 1,
					'updated_at'  => LSCH_Database::now(),
				),
				array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ), 'key_version' => absint( $row['key_version'] ) ),
				array( '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d', '%d', '%d' )
			);
			if ( 1 === $updated ) { $rotated++; } else { $failed++; }
		}
		$remaining = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['notes']} WHERE key_version<>%d", $write_version ) );
		LSCH_Events::audit( 'private_note_key_rotation', 'system', 'file05', array( 'write_key_version' => $write_version, 'rotated' => $rotated, 'failed' => $failed, 'remaining' => $remaining ), 'privacy' );
		return array( 'status' => $failed ? 'partial' : 'ok', 'rotated' => $rotated, 'failed' => $failed, 'remaining' => $remaining );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'lsch_process_outbox' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'lsch_process_outbox' );
		}
		if ( ! wp_next_scheduled( 'lsch_process_jobs' ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'lsch_process_jobs' );
		}
		if ( ! wp_next_scheduled( 'lsch_daily_reconcile' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lsch_daily_reconcile' );
		}
	}

	public static function unschedule() {
		foreach ( array( 'lsch_process_outbox', 'lsch_process_jobs', 'lsch_daily_reconcile' ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			while ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
				$timestamp = wp_next_scheduled( $hook );
			}
		}
	}

	private static function mysql_lock( $name ) {
		global $wpdb;
		$name = 'lsch_' . substr( hash( 'sha256', (string) $name ), 0, 48 );
		return 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $name ) ) ? $name : false;
	}

	private static function mysql_unlock( $name ) {
		global $wpdb;
		if ( $name ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
		}
	}

	public static function process_outbox() {
		if ( self::safe_mode() ) {
			return;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$rows = $wpdb->get_results( "SELECT id FROM {$t['outbox']} WHERE status IN ('pending','retry') AND next_attempt_at<=UTC_TIMESTAMP() ORDER BY id ASC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $candidate ) {
			$id = absint( $candidate['id'] );
			$lock = self::mysql_lock( 'outbox_' . $id );
			if ( ! $lock ) {
				continue;
			}
			try {
				$row = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$t['outbox']} WHERE id=%d AND status IN ('pending','retry') AND next_attempt_at<=UTC_TIMESTAMP() LIMIT 1", $id ),
					ARRAY_A
				);
				if ( ! $row ) {
					continue;
				}
				$payload = json_decode( $row['payload_json'], true );
				$delivered = apply_filters( 'lsch_dispatch_event', null, $row['event_name'], $payload, $row['event_id'] );
				if ( true === $delivered ) {
					$wpdb->update(
						$t['outbox'],
						array( 'status' => 'processed', 'processed_at' => LSCH_Database::now(), 'attempts' => absint( $row['attempts'] ) + 1 ),
						array( 'id' => $id, 'status' => $row['status'] ),
						array( '%s', '%s', '%d' ),
						array( '%d', '%s' )
					);
				} else {
					$attempts = absint( $row['attempts'] ) + 1;
					$status = $attempts >= 5 ? 'dead' : 'retry';
					$next = gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, 60 * ( 2 ** min( 10, $attempts ) ) ) );
					$wpdb->update(
						$t['outbox'],
						array( 'status' => $status, 'attempts' => $attempts, 'next_attempt_at' => $next, 'last_error_code' => null === $delivered ? 'consumer_unavailable' : 'consumer_rejected' ),
						array( 'id' => $id, 'status' => $row['status'] ),
						array( '%s', '%d', '%s', '%s' ),
						array( '%d', '%s' )
					);
				}
			} finally {
				self::mysql_unlock( $lock );
			}
		}
	}

	public static function process_jobs() {
		if ( self::safe_mode() ) {
			return;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$jobs = $wpdb->get_results( "SELECT id FROM {$t['jobs']} WHERE status IN ('pending','retry') AND run_after<=UTC_TIMESTAMP() ORDER BY id ASC LIMIT 25", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $jobs as $candidate ) {
			$id = absint( $candidate['id'] );
			$claimed = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$t['jobs']} SET status='running',locked_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=%d AND status IN ('pending','retry') AND run_after<=UTC_TIMESTAMP()",
					$id
				)
			);
			if ( 1 !== $claimed ) {
				continue;
			}
			$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['jobs']} WHERE id=%d AND status='running' LIMIT 1", $id ), ARRAY_A );
			if ( ! $job ) {
				continue;
			}
			$payload = json_decode( $job['payload_json'], true );
			$result = apply_filters( 'lsch_run_job', null, $job['job_type'], $payload, $job['job_key'] );
			if ( true === $result ) {
				$wpdb->update(
					$t['jobs'],
					array( 'status' => 'completed', 'locked_at' => null, 'updated_at' => LSCH_Database::now() ),
					array( 'id' => $id, 'status' => 'running' ),
					array( '%s', '%s', '%s' ),
					array( '%d', '%s' )
				);
			} else {
				$attempts = absint( $job['attempts'] ) + 1;
				$dead = $attempts >= absint( $job['max_attempts'] );
				$wpdb->update(
					$t['jobs'],
					array(
						'status'          => $dead ? 'dead' : 'retry',
						'attempts'        => $attempts,
						'run_after'       => gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, 60 * ( 2 ** min( 10, $attempts ) ) ) ),
						'locked_at'       => null,
						'last_error_code' => null === $result ? 'handler_unavailable' : 'handler_rejected',
						'updated_at'      => LSCH_Database::now(),
					),
					array( 'id' => $id, 'status' => 'running' ),
					array( '%s', '%d', '%s', '%s', '%s', '%s' ),
					array( '%d', '%s' )
				);
			}
		}
	}

	public static function daily_reconcile() {
		global $wpdb;
		$t = LSCH_Database::tables();
		$state = LSCH_State::tables();

		$wpdb->query( "DELETE b FROM {$t['bookmarks']} b LEFT JOIN {$wpdb->posts} p ON p.ID=b.object_id WHERE p.ID IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE p FROM {$t['progress']} p LEFT JOIN {$wpdb->posts} l ON l.ID=p.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE n FROM {$t['notes']} n LEFT JOIN {$wpdb->posts} l ON l.ID=n.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE c FROM {$t['consents']} c LEFT JOIN {$wpdb->posts} l ON l.ID=c.lesson_id WHERE l.ID IS NULL OR l.post_type<>'lsch_lesson'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE r FROM {$t['reminders']} r LEFT JOIN {$wpdb->posts} c ON c.ID=r.course_id WHERE c.ID IS NULL OR c.post_type<>'lsch_course'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE s FROM {$t['staff']} s LEFT JOIN {$wpdb->users} u ON u.ID=s.user_id WHERE u.ID IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "UPDATE {$t['jobs']} SET status='retry',locked_at=NULL,run_after=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE status='running' AND locked_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$wpdb->query( "UPDATE {$state['corrections']} c LEFT JOIN {$wpdb->posts} p ON p.ID=c.object_id SET c.status='object_gone',c.version=c.version+1,c.updated_at=UTC_TIMESTAMP() WHERE p.ID IS NULL AND c.status IN ('submitted','under_review','needs_information','applying','apply_failed')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::reconcile_corrections();
		$wpdb->query( "DELETE FROM {$state['value_events']} WHERE created_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 395 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		update_option( 'lsch_last_reconcile', gmdate( 'c' ), false );
	}

	private static function reconcile_corrections() {
		global $wpdb;
		$state = LSCH_State::tables();
		$rows = $wpdb->get_results(
			"SELECT * FROM {$state['corrections']} WHERE status IN ('applying','apply_failed') ORDER BY updated_at ASC,id ASC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		foreach ( $rows as $row ) {
			$current = LSCH_Content::version( absint( $row['object_id'] ) );
			$proposed = absint( $row['proposed_object_version'] );
			if ( $current === $proposed + 1 && 'applying' === $row['status'] ) {
				$wpdb->update(
					$state['corrections'],
					array( 'status' => 'accepted', 'applied_object_version' => $current, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ), 'decided_at' => current_time( 'mysql', true ) ),
					array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ), 'status' => 'applying' ),
					array( '%s', '%d', '%d', '%s', '%s' ),
					array( '%d', '%d', '%s' )
				);
				continue;
			}
			if ( $current === $proposed ) {
				$wpdb->update(
					$state['corrections'],
					array( 'status' => 'submitted', 'reviewer_id' => 0, 'applied_object_version' => 0, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ), 'decided_at' => null ),
					array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
					array( '%s', '%d', '%d', '%d', '%s', '%s' ),
					array( '%d', '%d', '%s' )
				);
				continue;
			}
			$wpdb->update(
				$state['corrections'],
				array( 'status' => 'stale_conflict', 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ),
				array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ), 'status' => $row['status'] ),
				array( '%s', '%d', '%s' ),
				array( '%d', '%d', '%s' )
			);
		}
	}

	public static function related_links( $source_type, $source_id ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT target_file,target_type,target_id,target_url,relation,version FROM {$t['related']} WHERE source_type=%s AND source_id=%d AND status='active' ORDER BY id ASC LIMIT 100",
				sanitize_key( $source_type ),
				absint( $source_id )
			),
			ARRAY_A
		);
	}

	public function site_health_tests( $tests ) {
		$tests['direct']['lsch_learning_health'] = array(
			'label' => __( 'File 05 learning health', 'learn-sabri-classical-homeopathy' ),
			'test'  => array( $this, 'site_health' ),
		);
		return $tests;
	}

	public function site_health() {
		$report = self::system_check();
		return array(
			'label'       => __( 'File 05 learning system health', 'learn-sabri-classical-homeopathy' ),
			'status'      => 'pass' === $report['status'] ? 'good' : ( 'warn' === $report['status'] ? 'recommended' : 'critical' ),
			'badge'       => array( 'label' => 'File 05', 'color' => 'blue' ),
			'description' => '<p>' . esc_html( sprintf( __( 'Learning health status: %s.', 'learn-sabri-classical-homeopathy' ), $report['status'] ) ) . '</p>',
			'actions'     => '<p><a href="' . esc_url( admin_url( 'admin.php?page=lsch-system-check' ) ) . '">' . esc_html__( 'Open System Check', 'learn-sabri-classical-homeopathy' ) . '</a></p>',
			'test'        => 'lsch_learning_health',
		);
	}
}
