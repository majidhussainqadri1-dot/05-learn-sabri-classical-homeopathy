<?php
/**
 * Append-only mentorship feedback history (File 05 v12).
 *
 * Migrates the existing immutable feedback_json list on the first authorized
 * write. The original JSON remains as a read-only migration witness; no
 * historical academic feedback is deleted or silently overwritten.
 *
 * All writes are called through File05's versioned REST command and outer
 * LSCH_Idempotency transaction; do not start nested MySQL transactions here.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_Mentor_History {
	const SCHEMA = 1;
	const OPTION = 'lsch_mentor_history_schema';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'lsch_f18_mentor_feedback';
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( self::OPTION, 0 ) >= self::SCHEMA ) { return; }
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t = self::table();
		$c = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			mentorship_id bigint(20) unsigned NOT NULL,
			sequence_no bigint(20) unsigned NOT NULL,
			mentor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			feedback_json longtext NOT NULL,
			origin varchar(24) NOT NULL DEFAULT 'native',
			recorded_at datetime NOT NULL,
			redacted_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY mentorship_sequence (mentorship_id,sequence_no),
			KEY mentor_history (mentor_id,recorded_at),
			KEY mentorship_history (mentorship_id,id)
		) {$c};" );
		$actual = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) );
		if ( $actual !== $t ) {
			throw new RuntimeException( 'File05 mentorship history schema was not verified after migration.' );
		}
		update_option( self::OPTION, self::SCHEMA, false );
	}

	public static function ready() {
		global $wpdb;
		$t = self::table();
		if ( (int) get_option( self::OPTION, 0 ) < self::SCHEMA ) { return false; }
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) );
		return $t === $found;
	}

	/** Only accepted existing canonical JSON-array history; never repair by truncation. */
	public static function legacy_entries( $raw ) {
		if ( ! is_string( $raw ) || '[' !== substr( ltrim( $raw ), 0, 1 ) ) {
			return new WP_Error( 'lsch_mentor_legacy_history_invalid', __( 'Historical mentor feedback must be reconciled before new writing.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$existing = json_decode( $raw, true );
		if ( ! is_array( $existing ) || array_values( $existing ) !== $existing ) {
			return new WP_Error( 'lsch_mentor_legacy_history_invalid', __( 'Historical mentor feedback cannot be decoded safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		foreach ( $existing as $entry ) {
			if ( ! is_array( $entry ) ) {
				return new WP_Error( 'lsch_mentor_legacy_history_invalid', __( 'Historical mentor feedback entry is malformed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
			}
		}
		return $existing;
	}


	/**
	 * Verify the entire legacy prefix against normalized, immutable rows.
	 * A matching COUNT(*) or MAX(sequence_no) alone cannot detect substitutions,
	 * deleted middle entries or reordered historic feedback.
	 */
	private static function verify_legacy_prefix( $mentorship_id, array $legacy ) {
		$expected = count( $legacy );
		if ( 0 === $expected ) { return true; }
		global $wpdb;
		$t = self::table();
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT sequence_no,feedback_json,origin,redacted_at FROM {$t} WHERE mentorship_id=%d AND sequence_no<=%d ORDER BY sequence_no ASC LIMIT %d",
			absint( $mentorship_id ), $expected, $expected
		), ARRAY_A );
		if ( ! is_array( $rows ) || count( $rows ) !== $expected ) {
			return new WP_Error( 'lsch_mentor_history_prefix_unverified', __( 'The entire historic mentoring feedback prefix could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		foreach ( $rows as $index => $row ) {
			$decoded = isset( $row['feedback_json'] ) ? json_decode( (string) $row['feedback_json'], true ) : null;
			if ( ! isset( $row['sequence_no'], $row['origin'] ) || (int) $row['sequence_no'] !== $index + 1
				|| 'legacy' !== $row['origin'] || ! empty( $row['redacted_at'] ) || ! is_array( $decoded ) || $legacy[ $index ] !== $decoded ) {
				return new WP_Error( 'lsch_mentor_history_prefix_mismatch', __( 'Historic mentor feedback differs from its preserved original. Operator reconciliation is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
			}
		}
		return true;
	}

	/**
	 * Before the first write, historical JSON must remain readable to the
	 * authorized participants without a GET request silently changing the DB.
	 */
	private static function legacy_page( $mentorship_id, array $parent, array $legacy, $limit, $before ) {
		$size = count( $legacy );
		$highest = $before ? min( $size, absint( $before ) - 1 ) : $size;
		$items = array();
		for ( $number = $highest; $number >= 1 && count( $items ) < $limit; $number-- ) {
			$entry = $legacy[ $number - 1 ];
			$recorded = isset( $entry['at'] ) && is_scalar( $entry['at'] ) && strtotime( (string) $entry['at'] )
				? gmdate( 'Y-m-d H:i:s', strtotime( (string) $entry['at'] ) )
				: (string) ( $parent['created_at'] ?? '' );
			$items[] = array(
				'mentorship_id' => absint( $mentorship_id ),
				'sequence_no' => $number,
				'mentor_id' => absint( $entry['mentor_id'] ?? ( $parent['mentor_id'] ?? 0 ) ),
				'feedback_json' => wp_json_encode( $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'origin' => 'legacy',
				'recorded_at' => $recorded,
			);
		}
		return $items;
	}

	/**
	 * The parent mentorship row's version is the optimistic concurrency token.
	 * This method must run within the REST command's existing outer transaction.
	 * Return false/error on any partial migration; never lose an old entry.
	 */
	public static function append( array $parent, $mentor_id, array $feedback ) {
		$mentorship_id = absint( $parent['id'] ?? 0 );
		$mentor_id = absint( $mentor_id );
		if ( ! $mentorship_id || ! $mentor_id || ! self::ready() ) {
			return new WP_Error( 'lsch_mentor_history_schema_unverified', __( 'Historical mentoring storage is not verified; no feedback was saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$legacy = self::legacy_entries( (string) ( $parent['feedback_json'] ?? '' ) );
		if ( is_wp_error( $legacy ) ) { return $legacy; }
		global $wpdb;
		$t = self::table();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE mentorship_id=%d", $mentorship_id ) );
		if ( false === $count || null === $count || ! is_numeric( $count ) ) {
			return new WP_Error( 'lsch_mentor_history_count_failed', __( 'Existing mentoring feedback count could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$count = (int) $count;
		// Migration runs once, only from a completely empty normalized history.
		if ( 0 === $count ) {
			foreach ( $legacy as $index => $entry ) {
				$at = isset( $entry['at'] ) && is_scalar( $entry['at'] ) && strtotime( (string) $entry['at'] )
					? gmdate( 'Y-m-d H:i:s', strtotime( (string) $entry['at'] ) )
					: (string) ( $parent['created_at'] ?? current_time( 'mysql', true ) );
				$serialized = wp_json_encode( $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				if ( ! is_string( $serialized ) || strlen( $serialized ) > 30000 ) {
					return new WP_Error( 'lsch_mentor_legacy_history_invalid', __( 'Historical feedback payload exceeded the safe migration limit.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
				}
				$ok = $wpdb->insert( $t, array(
					'mentorship_id' => $mentorship_id, 'sequence_no' => $index + 1,
					'mentor_id' => absint( $entry['mentor_id'] ?? $parent['mentor_id'] ),
					'feedback_json' => $serialized, 'origin' => 'legacy',
					'recorded_at' => $at, 'created_at' => current_time( 'mysql', true ),
				), array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' ) );
				if ( 1 !== $ok ) {
					return new WP_Error( 'lsch_mentor_history_migration_failed', __( 'Legacy mentorship feedback migration must be retried before writing.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
				}
			}
			$count = count( $legacy );
		}
		// A short/partial or corrupted historical import must block all writes.
		if ( $count < count( $legacy ) ) {
			return new WP_Error( 'lsch_mentor_history_migration_partial', __( 'Historical mentorship feedback requires operator reconciliation.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$verified_prefix = self::verify_legacy_prefix( $mentorship_id, $legacy );
		if ( is_wp_error( $verified_prefix ) ) { return $verified_prefix; }
		$next = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sequence_no),0) FROM {$t} WHERE mentorship_id=%d", $mentorship_id ) );
		if ( false === $next || null === $next || ! is_numeric( $next ) || (int) $next !== $count ) {
			return new WP_Error( 'lsch_mentor_history_sequence_unverified', __( 'Mentoring feedback sequence could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$feedback['at'] = gmdate( 'c' );
		$feedback['mentor_id'] = $mentor_id;
		$json = wp_json_encode( $feedback, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) || strlen( $json ) > 30000 || '{}' === $json ) {
			return new WP_Error( 'lsch_mentor_feedback_payload_invalid', __( 'Mentor feedback has an invalid size or shape.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$ok = $wpdb->insert( $t, array(
			'mentorship_id' => $mentorship_id, 'sequence_no' => (int) $next + 1,
			'mentor_id' => $mentor_id, 'feedback_json' => $json, 'origin' => 'native',
			'recorded_at' => current_time( 'mysql', true ), 'created_at' => current_time( 'mysql', true ),
		), array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' ) );
		return 1 === $ok
			? array( 'mentorship_id' => $mentorship_id, 'sequence_no' => (int) $next + 1 )
			: new WP_Error( 'lsch_mentor_feedback_append_failed', __( 'Mentor feedback could not be appended without altering prior entries.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
	}

	/** Only a participant may read their own mentorship feedback; no global listings. */
	public static function records( $mentorship_id, $viewer_id, $limit = 100, $before = 0 ) {
		$mentorship_id = absint( $mentorship_id ); $viewer_id = absint( $viewer_id );
		if ( ! $mentorship_id || ! $viewer_id || ! LSCH_Policy::can_use_learning_actions( $viewer_id ) || ! self::ready() ) {
			return new WP_Error( 'lsch_mentor_history_unavailable', __( 'Mentor feedback history is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb; $t = self::table(); $m = LSCH_Future18::tables()['mentorship'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id,mentor_id,learner_id,status,created_at,feedback_json FROM {$m} WHERE id=%d AND (mentor_id=%d OR learner_id=%d) LIMIT 1", $mentorship_id, $viewer_id, $viewer_id ), ARRAY_A );
		if ( ! $row && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'lsch_mentor_history_authorization_unverified', __( 'Mentorship participants could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		if ( ! $row ) {
			return new WP_Error( 'lsch_mentor_history_forbidden', __( 'This mentoring record is not available to this account.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		// File24's governed erasure ends and de-identifies counterpart mentorships.
		// In that exact state the old JSON is a redacted '{}' tombstone, not a
		// malformed active history to be reconstructed or disclosed.
		$redacted_parent = 'ended' === (string) ( $row['status'] ?? '' )
			&& ( 0 === absint( $row['mentor_id'] ?? 0 ) || 0 === absint( $row['learner_id'] ?? 0 ) )
			&& '{}' === trim( (string) ( $row['feedback_json'] ?? '' ) );
		$legacy = $redacted_parent ? array() : self::legacy_entries( (string) ( $row['feedback_json'] ?? '' ) );
		if ( is_wp_error( $legacy ) ) { return $legacy; }
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE mentorship_id=%d", $mentorship_id ) );
		if ( false === $count || null === $count || ! is_numeric( $count ) ) {
			return new WP_Error( 'lsch_mentor_history_read_failed', __( 'Historic mentor feedback count could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		if ( (int) $count < count( $legacy ) && 0 !== (int) $count ) {
			return new WP_Error( 'lsch_mentor_history_migration_partial', __( 'Private mentoring history is incompletely migrated. Operator reconciliation is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$limit = max( 1, min( 100, absint( $limit ) ) );
		if ( 0 === (int) $count ) {
			return self::legacy_page( $mentorship_id, $row, $legacy, $limit, $before );
		}
		$verified_prefix = self::verify_legacy_prefix( $mentorship_id, $legacy );
		if ( is_wp_error( $verified_prefix ) ) { return $verified_prefix; }
		$where = $before ? $wpdb->prepare( ' AND sequence_no<%d', absint( $before ) ) : '';
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT id,mentorship_id,sequence_no,mentor_id,feedback_json,origin,recorded_at FROM {$t} WHERE mentorship_id=%d{$where} ORDER BY sequence_no DESC LIMIT %d", $mentorship_id, $limit ), ARRAY_A );
		if ( ! is_array( $items ) ) {
			return new WP_Error( 'lsch_mentor_history_read_failed', __( 'Private mentoring history could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		// An inconsistent latest sequence is not silently represented as empty history.
		if ( ! $before && $items && (int) $items[0]['sequence_no'] !== (int) $count ) {
			return new WP_Error( 'lsch_mentor_history_sequence_unverified', __( 'Mentoring feedback sequence requires reconciliation.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		return $items;
	}
}
