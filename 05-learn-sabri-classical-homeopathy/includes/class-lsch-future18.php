<?php
/**
 * File 05 Future-18 learning intelligence and clinical-education layer.
 *
 * Canonical boundary: File 05 owns learning/mastery state only. External
 * knowledge, PDF, repertory, messaging, notification, video and AI truth stay
 * with their native platform owners and are consumed through versioned hooks.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_Future18 {
	const SCHEMA = 1;
	const OPTION = 'lsch_future18_schema';
	const ROUTE_VERSION = '1.0';

	const FEATURE_ADAPTIVE_MASTERY       = 'adaptive_mastery';
	const FEATURE_SPACED_REPETITION      = 'spaced_repetition';
	const FEATURE_FLASHCARDS             = 'smart_flashcards';
	const FEATURE_CASE_SIMULATION        = 'clinical_case_simulation';
	const FEATURE_REMEDY_DIFFERENTIATION = 'remedy_differentiation';
	const FEATURE_CASE_TAKING            = 'case_taking_simulator';
	const FEATURE_REPERTORY_REASONING    = 'repertory_reasoning';
	const FEATURE_REASONING_MAP          = 'clinical_reasoning_map';
	const FEATURE_VIVA                   = 'oral_viva';
	const FEATURE_OSCE                   = 'osce_stations';
	const FEATURE_LEARNING_PRESCRIPTION  = 'personal_learning_prescription';
	const FEATURE_MISTAKE_BOOK           = 'mistake_book';
	const FEATURE_EVIDENCE_APPRAISAL     = 'evidence_appraisal';
	const FEATURE_PORTFOLIO              = 'competency_portfolio';
	const FEATURE_MENTORSHIP             = 'mentorship_supervision';
	const FEATURE_CPD                    = 'continuing_professional_development';
	const FEATURE_SOCRATIC_AI            = 'source_grounded_socratic_ai';
	const FEATURE_CHANGE_IMPACT          = 'knowledge_change_impact';

	public static function feature_ids() {
		return array(
			'F05-FUT-01' => self::FEATURE_ADAPTIVE_MASTERY,
			'F05-FUT-02' => self::FEATURE_SPACED_REPETITION,
			'F05-FUT-03' => self::FEATURE_FLASHCARDS,
			'F05-FUT-04' => self::FEATURE_CASE_SIMULATION,
			'F05-FUT-05' => self::FEATURE_REMEDY_DIFFERENTIATION,
			'F05-FUT-06' => self::FEATURE_CASE_TAKING,
			'F05-FUT-07' => self::FEATURE_REPERTORY_REASONING,
			'F05-FUT-08' => self::FEATURE_REASONING_MAP,
			'F05-FUT-09' => self::FEATURE_VIVA,
			'F05-FUT-10' => self::FEATURE_OSCE,
			'F05-FUT-11' => self::FEATURE_LEARNING_PRESCRIPTION,
			'F05-FUT-12' => self::FEATURE_MISTAKE_BOOK,
			'F05-FUT-13' => self::FEATURE_EVIDENCE_APPRAISAL,
			'F05-FUT-14' => self::FEATURE_PORTFOLIO,
			'F05-FUT-15' => self::FEATURE_MENTORSHIP,
			'F05-FUT-16' => self::FEATURE_CPD,
			'F05-FUT-17' => self::FEATURE_SOCRATIC_AI,
			'F05-FUT-18' => self::FEATURE_CHANGE_IMPACT,
		);
	}

	public static function practice_modes() {
		return array(
			self::FEATURE_CASE_SIMULATION,
			self::FEATURE_REMEDY_DIFFERENTIATION,
			self::FEATURE_CASE_TAKING,
			self::FEATURE_REPERTORY_REASONING,
			self::FEATURE_REASONING_MAP,
			self::FEATURE_VIVA,
			self::FEATURE_OSCE,
			self::FEATURE_EVIDENCE_APPRAISAL,
		);
	}

	public static function tables() {
		global $wpdb;
		$p = $wpdb->prefix . 'lsch_f18_';
		return array(
			'mastery'    => $p . 'mastery',
			'review'     => $p . 'review_queue',
			'practice'   => $p . 'practice',
			'pathways'   => $p . 'pathways',
			'portfolio'  => $p . 'portfolio',
			'mentorship' => $p . 'mentorship',
			'cpd'        => $p . 'cpd',
			'impacts'    => $p . 'change_impacts',
		);
	}

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 3 );
		add_action( 'lsch_event_published', array( __CLASS__, 'event_published' ), 10, 5 );
		add_shortcode( 'lsch_mastery_center', array( __CLASS__, 'shortcode' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'privacy_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'privacy_erasers' ) );
		add_filter( 'sabri_learning_feature_contracts', array( __CLASS__, 'feature_contracts' ) );
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

		dbDelta( "CREATE TABLE {$t['mastery']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			competency_key varchar(96) NOT NULL,
			mastery_score decimal(5,2) NOT NULL DEFAULT 0,
			confidence decimal(5,2) NOT NULL DEFAULT 0,
			evidence_count int(10) unsigned NOT NULL DEFAULT 0,
			last_source_type varchar(40) NOT NULL DEFAULT '',
			last_source_id varchar(64) NOT NULL DEFAULT '',
			last_evidence_at datetime NULL,
			next_review_at datetime NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_competency (user_id,competency_key), KEY due_review (user_id,next_review_at), KEY mastery_score (mastery_score)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['review']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			item_type varchar(32) NOT NULL,
			source_type varchar(32) NOT NULL DEFAULT '',
			source_id varchar(64) NOT NULL DEFAULT '',
			competency_key varchar(96) NOT NULL DEFAULT '',
			prompt text NOT NULL,
			answer text NOT NULL,
			metadata_json longtext NOT NULL,
			interval_days smallint(5) unsigned NOT NULL DEFAULT 0,
			ease decimal(4,2) NOT NULL DEFAULT 2.50,
			due_at datetime NOT NULL,
			last_result tinyint(3) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY user_due (user_id,due_at), KEY user_type (user_id,item_type), KEY source_ref (source_type,source_id)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['practice']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			mode varchar(64) NOT NULL,
			source_type varchar(32) NOT NULL,
			source_id bigint(20) unsigned NOT NULL,
			blueprint_version bigint(20) unsigned NOT NULL DEFAULT 1,
			response_json longtext NOT NULL,
			feedback_json longtext NOT NULL,
			score decimal(5,2) NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'submitted',
			assessor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY user_mode (user_id,mode), KEY source_ref (source_type,source_id), KEY assessor_status (assessor_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['pathways']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			goal varchar(64) NOT NULL,
			plan_json longtext NOT NULL,
			model_version varchar(32) NOT NULL DEFAULT 'deterministic-v1',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY user_goal (user_id,goal), KEY user_updated (user_id,updated_at)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['portfolio']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			item_type varchar(40) NOT NULL,
			object_type varchar(32) NOT NULL DEFAULT '',
			object_id varchar(64) NOT NULL DEFAULT '',
			competency_key varchar(96) NOT NULL DEFAULT '',
			data_json longtext NOT NULL,
			visibility varchar(24) NOT NULL DEFAULT 'private',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY user_type (user_id,item_type), KEY object_ref (object_type,object_id), KEY competency (user_id,competency_key)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['mentorship']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			mentor_id bigint(20) unsigned NOT NULL,
			learner_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'active',
			goals_json longtext NOT NULL,
			feedback_json longtext NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY mentor_learner_course (mentor_id,learner_id,course_id), KEY learner_status (learner_id,status), KEY mentor_status (mentor_id,status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['cpd']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			activity_type varchar(40) NOT NULL,
			object_type varchar(32) NOT NULL DEFAULT '',
			object_id varchar(64) NOT NULL DEFAULT '',
			competency_key varchar(96) NOT NULL DEFAULT '',
			minutes int(10) unsigned NOT NULL DEFAULT 0,
			evidence_json longtext NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'self_recorded',
			verified_by bigint(20) unsigned NOT NULL DEFAULT 0,
			completed_at datetime NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY user_completed (user_id,completed_at), KEY status (status)
		) {$c};" );

		dbDelta( "CREATE TABLE {$t['impacts']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id char(36) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			object_type varchar(32) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			previous_version bigint(20) unsigned NOT NULL DEFAULT 0,
			new_version bigint(20) unsigned NOT NULL DEFAULT 0,
			competency_key varchar(96) NOT NULL DEFAULT '',
			change_summary text NOT NULL,
			required_action varchar(40) NOT NULL DEFAULT 'targeted_review',
			status varchar(24) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			resolved_at datetime NULL,
			PRIMARY KEY (id), UNIQUE KEY event_user_competency (event_id,user_id,competency_key), KEY user_status (user_id,status), KEY object_ref (object_type,object_id)
		) {$c};" );

		update_option( self::OPTION, self::SCHEMA, false );
	}

	public static function feature_contracts( $contracts ) {
		$contracts = (array) $contracts;
		$contracts['file05-future18.v1'] = array(
			'owner' => 'file05-learning',
			'version' => self::ROUTE_VERSION,
			'features' => self::feature_ids(),
			'boundaries' => array(
				'encyclopedia_truth' => 'file06',
				'pdf_truth' => 'file12',
				'repertory_truth' => 'file15',
				'ai_answer_truth' => 'file16',
				'messaging_transport' => 'file17',
				'notification_delivery' => 'file19',
				'global_search_ranking' => 'file26',
			),
		);
		return $contracts;
	}

	private static function approved_user( $user_id ) {
		return $user_id && LSCH_Policy::can_use_learning_actions( $user_id );
	}

	/** Manager, active mentor, or conflict-cleared assigned teacher/assessor may supervise bounded learning state. */
	private static function can_supervise_user( $actor_id, $learner_id, $source_type = '', $source_id = 0 ) {
		$actor_id = absint( $actor_id );
		$learner_id = absint( $learner_id );
		$source_type = sanitize_key( $source_type );
		$source_id = absint( $source_id );
		if ( ! $actor_id || ! $learner_id || $actor_id === $learner_id || ! self::approved_user( $actor_id ) || ! self::approved_user( $learner_id ) ) {
			return false;
		}
		if ( user_can( $actor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return true;
		}
		global $wpdb;
		$t = self::tables();
		if ( user_can( $actor_id, LSCH_Capabilities::TEACH ) && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['mentorship']} WHERE mentor_id=%d AND learner_id=%d AND status='active' LIMIT 1", $actor_id, $learner_id ) ) ) {
			return true;
		}
		if ( ! $source_id || ! $source_type ) {
			return false;
		}
		$core = LSCH_Database::tables();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$core['staff']} WHERE user_id=%d AND object_type=%s AND object_id=%d AND role IN ('teacher','assessor') AND conflict_status='clear' AND active=1 LIMIT 1", $actor_id, $source_type, $source_id ) );
	}

	/** A non-manager assessor must have an active, conflict-cleared assignment to the source object. */
	private static function assessor_scope_allows( $assessor_id, $source_type, $source_id ) {
		$assessor_id = absint( $assessor_id );
		$source_id = absint( $source_id );
		$source_type = sanitize_key( $source_type );
		if ( self::approved_user( $assessor_id ) && user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return true;
		}
		if ( ! $assessor_id || ! $source_id || ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) ) {
			return false;
		}
		global $wpdb;
		$core = LSCH_Database::tables();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$core['staff']} WHERE user_id=%d AND object_type=%s AND object_id=%d AND role='assessor' AND conflict_status='clear' AND active=1 LIMIT 1", $assessor_id, $source_type, $source_id ) );
	}

	private static function competency_key( $value ) {
		$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9._:-]+/', '-', $value );
		return substr( trim( (string) $value, '-' ), 0, 96 );
	}

	private static function encode_json( $value, $max = 65535 ) {
		$json = LSCH_Policy::sanitize_json( $value, $max );
		return $json;
	}

	private static function reject_sensitive_practice_payload( array $payload ) {
		$forbidden = array( 'patient_name', 'full_name', 'email', 'phone', 'mobile', 'address', 'national_id', 'passport', 'cnic', 'identity_document', 'date_of_birth' );
		$stack = array( $payload );
		while ( $stack ) {
			$current = array_pop( $stack );
			foreach ( $current as $key => $value ) {
				$key = strtolower( sanitize_key( (string) $key ) );
				if ( in_array( $key, $forbidden, true ) ) {
					return new WP_Error( 'lsch_future18_sensitive_case_data', __( 'Practice laboratories accept simulated/de-identified educational data only.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
				}
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
		}
		return true;
	}

	private static function review_schedule( $quality, $interval, $ease ) {
		$quality = max( 0, min( 5, (int) $quality ) );
		$interval = max( 0, (int) $interval );
		$ease = max( 1.30, min( 3.00, (float) $ease ) );
		if ( $quality < 3 ) {
			$interval = 1;
		} elseif ( 0 === $interval ) {
			$interval = 1;
		} elseif ( 1 === $interval ) {
			$interval = 3;
		} elseif ( $interval <= 3 ) {
			$interval = 7;
		} else {
			$interval = (int) max( 1, round( $interval * $ease ) );
		}
		$ease = $ease + ( 0.1 - ( 5 - $quality ) * ( 0.08 + ( 5 - $quality ) * 0.02 ) );
		$ease = max( 1.30, min( 3.00, $ease ) );
		return array( $interval, $ease );
	}

	public static function record_mastery_evidence_as_actor( $actor_id, $user_id, $competency, $score, $weight = 1.0, $source_type = '', $source_id = '' ) {
		$actor_id = absint( $actor_id );
		$user_id = absint( $user_id );
		if ( ! self::can_supervise_user( $actor_id, $user_id, $source_type, $source_id ) ) {
			return new WP_Error( 'lsch_future18_mastery_supervision_forbidden', __( 'Manual mastery evidence requires an assigned mentor or curriculum manager.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		return self::record_mastery_evidence( $user_id, $competency, $score, $weight, $source_type, $source_id );
	}

	public static function record_mastery_evidence( $user_id, $competency, $score, $weight = 1.0, $source_type = '', $source_id = '', $schedule_review = true ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_mastery_forbidden', __( 'Mastery evidence is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$competency = self::competency_key( $competency );
		if ( '' === $competency ) {
			return new WP_Error( 'lsch_future18_competency_required', __( 'A competency key is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$score = max( 0, min( 100, (float) $score ) );
		$weight = max( 0.10, min( 5.00, (float) $weight ) );
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['mastery']} WHERE user_id=%d AND competency_key=%s LIMIT 1", $user_id, $competency ), ARRAY_A );
		$old_score = $row ? (float) $row['mastery_score'] : 0.0;
		$old_confidence = $row ? (float) $row['confidence'] : 0.0;
		$alpha = min( 0.65, 0.20 + ( $weight * 0.10 ) );
		$new_score = $row ? round( ( $old_score * ( 1 - $alpha ) ) + ( $score * $alpha ), 2 ) : round( $score, 2 );
		$new_confidence = min( 100, round( $old_confidence + ( 8 * $weight ), 2 ) );
		$review_days = $new_score >= 90 ? 60 : ( $new_score >= 80 ? 30 : ( $new_score >= 65 ? 14 : ( $new_score >= 50 ? 7 : 2 ) ) );
		$now = current_time( 'mysql', true );
		$next = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * $review_days );
		if ( $schedule_review ) {
			$scheduled_due = $wpdb->get_var( $wpdb->prepare( "SELECT due_at FROM {$t['review']} WHERE user_id=%d AND item_type='spaced' AND competency_key=%s LIMIT 1", $user_id, $competency ) );
			if ( is_string( $scheduled_due ) && '' !== $scheduled_due ) {
				$next = $scheduled_due;
			}
		}
		if ( $row ) {
			$ok = $wpdb->update(
				$t['mastery'],
				array( 'mastery_score' => $new_score, 'confidence' => $new_confidence, 'evidence_count' => absint( $row['evidence_count'] ) + 1, 'last_source_type' => sanitize_key( $source_type ), 'last_source_id' => sanitize_text_field( (string) $source_id ), 'last_evidence_at' => $now, 'next_review_at' => $next, 'version' => absint( $row['version'] ) + 1, 'updated_at' => $now ),
				array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ) ),
				array( '%f', '%f', '%d', '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%d' )
			);
			if ( 1 !== $ok ) {
				return new WP_Error( 'lsch_future18_mastery_conflict', __( 'Mastery state changed while saving. Reload and retry.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
		} else {
			$ok = $wpdb->insert(
				$t['mastery'],
				array( 'user_id' => $user_id, 'competency_key' => $competency, 'mastery_score' => $new_score, 'confidence' => $new_confidence, 'evidence_count' => 1, 'last_source_type' => sanitize_key( $source_type ), 'last_source_id' => sanitize_text_field( (string) $source_id ), 'last_evidence_at' => $now, 'next_review_at' => $next, 'version' => 1, 'updated_at' => $now ),
				array( '%d', '%s', '%f', '%f', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
			);
			if ( 1 !== $ok ) {
				return new WP_Error( 'lsch_future18_mastery_write_failed', __( 'Mastery evidence could not be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
			}
		}
		if ( $schedule_review ) { self::ensure_competency_review_item( $user_id, $competency, $next ); }
		if ( $score < 60 ) {
			self::add_mistake( $user_id, $competency, $source_type, $source_id, __( 'Review the reasoning behind this weak result.', 'learn-sabri-classical-homeopathy' ) );
		}
		LSCH_Events::publish( 'LearningMasteryUpdated.v1', 'competency', $competency, array( 'user_id' => $user_id, 'mastery_score' => $new_score, 'confidence' => $new_confidence ) );
		return self::mastery_snapshot( $user_id );
	}

	private static function ensure_competency_review_item( $user_id, $competency, $due_at ) {
		global $wpdb;
		$t = self::tables();
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['review']} WHERE user_id=%d AND item_type='spaced' AND competency_key=%s LIMIT 1", $user_id, $competency ) );
		$now = current_time( 'mysql', true );
		if ( $id ) {
			/* Once a spaced-review item exists, record_review_result owns its due schedule. */
			return;
		}
		$wpdb->insert(
			$t['review'],
			array( 'public_id' => LSCH_Database::uuid(), 'user_id' => $user_id, 'item_type' => 'spaced', 'source_type' => 'competency', 'source_id' => $competency, 'competency_key' => $competency, 'prompt' => sprintf( __( 'Recall and explain competency: %s', 'learn-sabri-classical-homeopathy' ), $competency ), 'answer' => '', 'metadata_json' => '{}', 'interval_days' => 0, 'ease' => 2.50, 'due_at' => $due_at, 'last_result' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	public static function mastery_snapshot( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_mastery_forbidden', __( 'Mastery snapshot is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT competency_key,mastery_score,confidence,evidence_count,last_source_type,last_source_id,last_evidence_at,next_review_at,version FROM {$t['mastery']} WHERE user_id=%d ORDER BY mastery_score ASC,competency_key ASC LIMIT 500", $user_id ), ARRAY_A );
		$summary = array( 'count' => count( $rows ), 'average' => 0, 'weak' => 0, 'strong' => 0 );
		if ( $rows ) {
			$total = 0.0;
			foreach ( $rows as $row ) {
				$total += (float) $row['mastery_score'];
				if ( (float) $row['mastery_score'] < 60 ) { $summary['weak']++; }
				if ( (float) $row['mastery_score'] >= 80 ) { $summary['strong']++; }
			}
			$summary['average'] = round( $total / count( $rows ), 2 );
		}
		return array( 'summary' => $summary, 'competencies' => $rows );
	}

	public static function create_flashcard( $user_id, array $data ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_flashcard_forbidden', __( 'Flashcards are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$prompt = sanitize_textarea_field( (string) ( $data['prompt'] ?? '' ) );
		$answer = sanitize_textarea_field( (string) ( $data['answer'] ?? '' ) );
		if ( strlen( $prompt ) < 3 || strlen( $prompt ) > 4000 || strlen( $answer ) > 8000 ) {
			return new WP_Error( 'lsch_future18_flashcard_invalid', __( 'Flashcard prompt/answer is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$meta = self::encode_json( (array) ( $data['metadata'] ?? array() ), 10000 );
		if ( is_wp_error( $meta ) ) { return $meta; }
		global $wpdb;
		$t = self::tables();
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert( $t['review'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'item_type' => 'flashcard', 'source_type' => sanitize_key( (string) ( $data['source_type'] ?? 'lesson' ) ), 'source_id' => sanitize_text_field( (string) ( $data['source_id'] ?? '' ) ), 'competency_key' => self::competency_key( $data['competency_key'] ?? '' ), 'prompt' => $prompt, 'answer' => $answer, 'metadata_json' => $meta, 'interval_days' => 0, 'ease' => 2.50, 'due_at' => $now, 'last_result' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%d', '%d', '%s', '%s' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_future18_flashcard_write_failed', __( 'Flashcard could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		LSCH_Events::audit( 'future18_flashcard_created', 'review_item', $public_id, array( 'user_id' => $user_id ), 'learning' );
		return array( 'id' => (int) $wpdb->insert_id, 'public_id' => $public_id, 'due_at' => $now );
	}

	public static function review_queue( $user_id, $type = '', $limit = 100 ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_review_forbidden', __( 'Review queue is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$limit = max( 1, min( 200, absint( $limit ) ) );
		$type = sanitize_key( $type );
		if ( $type ) {
			$sql = $wpdb->prepare( "SELECT id,public_id,item_type,source_type,source_id,competency_key,prompt,answer,metadata_json,interval_days,ease,due_at,last_result,version FROM {$t['review']} WHERE user_id=%d AND item_type=%s AND due_at<=%s ORDER BY due_at ASC,id ASC LIMIT %d", $user_id, $type, current_time( 'mysql', true ), $limit );
		} else {
			$sql = $wpdb->prepare( "SELECT id,public_id,item_type,source_type,source_id,competency_key,prompt,answer,metadata_json,interval_days,ease,due_at,last_result,version FROM {$t['review']} WHERE user_id=%d AND due_at<=%s ORDER BY due_at ASC,id ASC LIMIT %d", $user_id, current_time( 'mysql', true ), $limit );
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function record_review_result( $user_id, $id, $quality, $expected_version = 0 ) {
		$user_id = absint( $user_id );
		$id = absint( $id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_review_forbidden', __( 'Review action is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['review']} WHERE id=%d AND user_id=%d LIMIT 1", $id, $user_id ), ARRAY_A );
		if ( ! $row || ( $expected_version && absint( $row['version'] ) !== absint( $expected_version ) ) ) {
			return new WP_Error( 'lsch_future18_review_conflict', __( 'Review item changed or was not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		list( $interval, $ease ) = self::review_schedule( $quality, absint( $row['interval_days'] ), (float) $row['ease'] );
		$due = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * $interval );
		$updated = $wpdb->update( $t['review'], array( 'interval_days' => $interval, 'ease' => $ease, 'due_at' => $due, 'last_result' => max( 0, min( 5, absint( $quality ) ) ), 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'user_id' => $user_id, 'version' => absint( $row['version'] ) ), array( '%d', '%f', '%s', '%d', '%d', '%s' ), array( '%d', '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_review_conflict', __( 'Review item changed while saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		if ( ! empty( $row['competency_key'] ) ) {
			self::record_mastery_evidence( $user_id, $row['competency_key'], ( max( 0, min( 5, absint( $quality ) ) ) / 5 ) * 100, 0.35, 'review_item', $id, false );
		}
		return array( 'id' => $id, 'interval_days' => $interval, 'ease' => $ease, 'due_at' => $due );
	}

	public static function add_mistake( $user_id, $competency, $source_type, $source_id, $reflection ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) { return false; }
		global $wpdb;
		$t = self::tables();
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert( $t['review'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'item_type' => 'mistake', 'source_type' => sanitize_key( $source_type ), 'source_id' => sanitize_text_field( (string) $source_id ), 'competency_key' => self::competency_key( $competency ), 'prompt' => sanitize_textarea_field( $reflection ), 'answer' => '', 'metadata_json' => '{}', 'interval_days' => 1, 'ease' => 2.30, 'due_at' => $now, 'last_result' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%d', '%d', '%s', '%s' ) );
		return 1 === $ok ? $public_id : false;
	}

	public static function mistakes( $user_id, $limit = 100 ) {
		return self::review_queue( $user_id, 'mistake', $limit );
	}

	public static function set_blueprint( $lesson_id, $mode, array $blueprint ) {
		$lesson_id = absint( $lesson_id );
		$mode = sanitize_key( $mode );
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! in_array( $mode, self::practice_modes(), true ) ) {
			return new WP_Error( 'lsch_future18_blueprint_forbidden', __( 'Practice blueprint management is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$json = self::encode_json( $blueprint, 50000 );
		if ( is_wp_error( $json ) ) { return $json; }
		$all = json_decode( (string) get_post_meta( $lesson_id, '_lsch_future18_blueprints', true ), true );
		$all = is_array( $all ) ? $all : array();
		$all[ $mode ] = json_decode( $json, true );
		update_post_meta( $lesson_id, '_lsch_future18_blueprints', wp_json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $lesson_id, '_lsch_future18_blueprint_version', absint( get_post_meta( $lesson_id, '_lsch_future18_blueprint_version', true ) ) + 1 );
		LSCH_Events::audit( 'future18_blueprint_updated', 'lesson', $lesson_id, array( 'mode' => $mode ), 'curriculum' );
		return true;
	}

	private static function blueprint( $source_type, $source_id, $mode, $user_id ) {
		$source_type = sanitize_key( $source_type );
		$source_id = absint( $source_id );
		$mode = sanitize_key( $mode );
		$external = apply_filters( 'lsch_future18_blueprint', null, $mode, $source_type, $source_id, $user_id );
		if ( is_array( $external ) ) {
			return $external;
		}
		if ( 'lesson' !== $source_type || LSCH_Content::LESSON !== get_post_type( $source_id ) || ! LSCH_Policy::can_read_post( $source_id, $user_id ) ) {
			return new WP_Error( 'lsch_future18_blueprint_unavailable', __( 'This governed practice blueprint is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) );
		}
		$all = json_decode( (string) get_post_meta( $source_id, '_lsch_future18_blueprints', true ), true );
		if ( ! is_array( $all ) || empty( $all[ $mode ] ) || ! is_array( $all[ $mode ] ) ) {
			return new WP_Error( 'lsch_future18_blueprint_unavailable', __( 'This practice mode has not yet been configured by the curriculum owner.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$blueprint = $all[ $mode ];
		$blueprint['version'] = max( 1, absint( get_post_meta( $source_id, '_lsch_future18_blueprint_version', true ) ) );
		return $blueprint;
	}

	private static function auto_score( array $blueprint, array $response ) {
		$criteria = isset( $blueprint['criteria'] ) && is_array( $blueprint['criteria'] ) ? $blueprint['criteria'] : array();
		if ( ! $criteria ) {
			return array( 'manual' => true, 'score' => 0, 'feedback' => array() );
		}
		$earned = 0.0;
		$total = 0.0;
		$feedback = array();
		foreach ( $criteria as $criterion ) {
			if ( ! is_array( $criterion ) || empty( $criterion['key'] ) ) { continue; }
			$key = sanitize_key( $criterion['key'] );
			$weight = max( 0.1, min( 100, (float) ( $criterion['weight'] ?? 1 ) ) );
			$total += $weight;
			$given = isset( $response[ $key ] ) ? $response[ $key ] : '';
			$expected = isset( $criterion['expected'] ) ? $criterion['expected'] : null;
			$matched = false;
			if ( is_array( $expected ) ) {
				$normalized = array_map( static function( $v ) { return strtolower( trim( (string) $v ) ); }, $expected );
				$matched = in_array( strtolower( trim( (string) $given ) ), $normalized, true );
			} elseif ( null !== $expected ) {
				$matched = hash_equals( strtolower( trim( (string) $expected ) ), strtolower( trim( (string) $given ) ) );
			}
			if ( $matched ) { $earned += $weight; }
			$feedback[] = array( 'key' => $key, 'matched' => $matched, 'feedback' => sanitize_text_field( (string) ( $criterion['feedback'] ?? '' ) ) );
		}
		return array( 'manual' => false, 'score' => $total > 0 ? round( 100 * $earned / $total, 2 ) : 0, 'feedback' => $feedback );
	}

	public static function submit_practice( $user_id, $mode, $source_type, $source_id, array $response ) {
		$user_id = absint( $user_id );
		$mode = sanitize_key( $mode );
		if ( ! self::approved_user( $user_id ) || ! in_array( $mode, self::practice_modes(), true ) ) {
			return new WP_Error( 'lsch_future18_practice_forbidden', __( 'This learning laboratory is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$sensitive = self::reject_sensitive_practice_payload( $response );
		if ( is_wp_error( $sensitive ) ) { return $sensitive; }
		$blueprint = self::blueprint( $source_type, $source_id, $mode, $user_id );
		if ( is_wp_error( $blueprint ) ) { return $blueprint; }
		$response_json = self::encode_json( $response, 50000 );
		if ( is_wp_error( $response_json ) ) { return $response_json; }
		$scored = self::auto_score( $blueprint, $response );
		$feedback_json = self::encode_json( $scored['feedback'], 30000 );
		if ( is_wp_error( $feedback_json ) ) { return $feedback_json; }
		$status = $scored['manual'] ? 'awaiting_assessor' : 'graded';
		$now = current_time( 'mysql', true );
		global $wpdb;
		$t = self::tables();
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert( $t['practice'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'mode' => $mode, 'source_type' => sanitize_key( $source_type ), 'source_id' => absint( $source_id ), 'blueprint_version' => max( 1, absint( $blueprint['version'] ?? 1 ) ), 'response_json' => $response_json, 'feedback_json' => $feedback_json, 'score' => (float) $scored['score'], 'status' => $status, 'assessor_id' => 0, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%s' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_future18_practice_write_failed', __( 'Practice submission could not be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		$competency = self::competency_key( $blueprint['competency_key'] ?? $mode );
		if ( 'graded' === $status ) {
			self::record_mastery_evidence( $user_id, $competency, $scored['score'], 1.0, 'future18_practice', $public_id );
			self::add_portfolio_item( $user_id, 'practice', 'future18_practice', $public_id, $competency, array( 'mode' => $mode, 'score' => $scored['score'] ) );
		}
		if ( 'graded' === $status && (float) $scored['score'] < 60 ) {
			self::add_mistake( $user_id, $competency, 'future18_practice', $public_id, sprintf( __( 'Revisit the %s exercise and explain the decisive reasoning.', 'learn-sabri-classical-homeopathy' ), $mode ) );
		}
		LSCH_Events::publish( 'LearningPracticeSubmitted.v1', 'future18_practice', $public_id, array( 'user_id' => $user_id, 'mode' => $mode, 'status' => $status, 'score' => $scored['score'] ) );
		return array( 'id' => (int) $wpdb->insert_id, 'public_id' => $public_id, 'mode' => $mode, 'status' => $status, 'score' => (float) $scored['score'], 'feedback' => $scored['feedback'] );
	}

	public static function grade_practice( $practice_id, $assessor_id, $score, array $feedback, $expected_version ) {
		$practice_id = absint( $practice_id );
		$assessor_id = absint( $assessor_id );
		if ( ! $assessor_id || ( ! self::approved_user( $assessor_id ) && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) || ( ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {
			return new WP_Error( 'lsch_future18_assessor_forbidden', __( 'Practice assessment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['practice']} WHERE id=%d LIMIT 1", $practice_id ), ARRAY_A );
		if ( ! $row || absint( $row['user_id'] ) === $assessor_id || 'awaiting_assessor' !== $row['status'] || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_future18_practice_grade_conflict', __( 'Practice record is unavailable, stale, or cannot be self-assessed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		if ( ! self::assessor_scope_allows( $assessor_id, $row['source_type'], absint( $row['source_id'] ) ) ) {
			return new WP_Error( 'lsch_future18_assessor_scope_forbidden', __( 'An active, conflict-cleared assessor assignment to this learning object is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$feedback_json = self::encode_json( $feedback, 30000 );
		if ( is_wp_error( $feedback_json ) ) { return $feedback_json; }
		$score = max( 0, min( 100, (float) $score ) );
		$updated = $wpdb->update( $t['practice'], array( 'feedback_json' => $feedback_json, 'score' => $score, 'status' => 'graded', 'assessor_id' => $assessor_id, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $practice_id, 'version' => absint( $row['version'] ), 'status' => 'awaiting_assessor' ), array( '%s', '%f', '%s', '%d', '%d', '%s' ), array( '%d', '%d', '%s' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_practice_grade_conflict', __( 'Practice record changed while grading.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$blueprint = self::blueprint( $row['source_type'], absint( $row['source_id'] ), $row['mode'], absint( $row['user_id'] ) );
		$competency = is_wp_error( $blueprint ) ? self::competency_key( $row['mode'] ) : self::competency_key( $blueprint['competency_key'] ?? $row['mode'] );
		self::record_mastery_evidence( absint( $row['user_id'] ), $competency, $score, 1.25, 'future18_practice', $row['public_id'] );
		self::add_portfolio_item( absint( $row['user_id'] ), 'assessed_practice', 'future18_practice', $row['public_id'], $competency, array( 'mode' => $row['mode'], 'score' => $score, 'assessor_id' => $assessor_id ) );
		if ( $score < 60 ) {
			self::add_mistake( absint( $row['user_id'] ), $competency, 'future18_practice', $row['public_id'], __( 'Use assessor feedback to correct this clinical-learning reasoning gap.', 'learn-sabri-classical-homeopathy' ) );
		}
		LSCH_Events::publish( 'LearningPracticeGraded.v1', 'future18_practice', $row['public_id'], array( 'user_id' => absint( $row['user_id'] ), 'mode' => $row['mode'], 'score' => $score, 'assessor_id' => $assessor_id ) );
		return true;
	}

	public static function build_learning_path( $user_id, $goal = 'balanced_mastery', $persist = true ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_path_forbidden', __( 'Personal learning prescription is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$goal = sanitize_key( $goal );
		$allowed_goals = array( 'beginner', 'materia_medica', 'case_taking', 'clinical_mastery', 'teacher_training', 'research', 'balanced_mastery' );
		if ( ! in_array( $goal, $allowed_goals, true ) ) { $goal = 'balanced_mastery'; }
		$snapshot = self::mastery_snapshot( $user_id );
		if ( is_wp_error( $snapshot ) ) { return $snapshot; }
		$due = self::review_queue( $user_id, '', 25 );
		if ( is_wp_error( $due ) ) { $due = array(); }
		$recommendations = array();
		foreach ( (array) $snapshot['competencies'] as $row ) {
			if ( count( $recommendations ) >= 10 ) { break; }
			if ( (float) $row['mastery_score'] < 80 ) {
				$recommendations[] = array( 'type' => 'competency_review', 'competency_key' => $row['competency_key'], 'mastery_score' => (float) $row['mastery_score'], 'why' => 'mastery_below_80' );
			}
		}
		if ( ! $recommendations ) {
			$recommendations[] = array( 'type' => 'next_course', 'why' => 'no_weak_competency', 'resolver' => 'file05_catalog' );
		}
		$plan = array( 'goal' => $goal, 'generated_at' => gmdate( 'c' ), 'mastery_average' => (float) $snapshot['summary']['average'], 'due_review_count' => count( $due ), 'recommendations' => $recommendations, 'explainable' => true, 'ranking_owner' => 'file26-for-global-discovery-only' );
		$json = self::encode_json( $plan, 30000 );
		if ( is_wp_error( $json ) ) { return $json; }
		if ( $persist ) {
			global $wpdb;
			$t = self::tables();
			$now = current_time( 'mysql', true );
			$sql = $wpdb->prepare( "INSERT INTO {$t['pathways']} (user_id,goal,plan_json,model_version,version,created_at,updated_at) VALUES (%d,%s,%s,'deterministic-v1',1,%s,%s) ON DUPLICATE KEY UPDATE plan_json=VALUES(plan_json),model_version='deterministic-v1',version=version+1,updated_at=VALUES(updated_at)", $user_id, $goal, $json, $now, $now );
			if ( false === $wpdb->query( $sql ) ) {
				return new WP_Error( 'lsch_future18_path_write_failed', __( 'Personal learning prescription could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
			}
		}
		return $plan;
	}

	public static function add_portfolio_item( $user_id, $item_type, $object_type, $object_id, $competency, array $data, $visibility = 'private' ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) { return false; }
		$visibility = in_array( $visibility, array( 'private', 'shareable_by_consent' ), true ) ? $visibility : 'private';
		$json = self::encode_json( $data, 30000 );
		if ( is_wp_error( $json ) ) { return $json; }
		global $wpdb;
		$t = self::tables();
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$ok = $wpdb->insert( $t['portfolio'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'item_type' => sanitize_key( $item_type ), 'object_type' => sanitize_key( $object_type ), 'object_id' => sanitize_text_field( (string) $object_id ), 'competency_key' => self::competency_key( $competency ), 'data_json' => $json, 'visibility' => $visibility, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );
		return 1 === $ok ? array( 'public_id' => $public_id, 'id' => (int) $wpdb->insert_id ) : false;
	}

	public static function portfolio( $user_id, $limit = 200 ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_portfolio_forbidden', __( 'Learning portfolio is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$limit = max( 1, min( 500, absint( $limit ) ) );
		return $wpdb->get_results( $wpdb->prepare( "SELECT id,public_id,item_type,object_type,object_id,competency_key,data_json,visibility,version,created_at,updated_at FROM {$t['portfolio']} WHERE user_id=%d ORDER BY created_at DESC,id DESC LIMIT %d", $user_id, $limit ), ARRAY_A );
	}

	public static function assign_mentor( $mentor_id, $learner_id, $course_id, array $goals ) {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return new WP_Error( 'lsch_future18_mentorship_forbidden', __( 'Mentorship assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$mentor_id = absint( $mentor_id );
		$learner_id = absint( $learner_id );
		$course_id = absint( $course_id );
		if ( ! $mentor_id || ! $learner_id || $mentor_id === $learner_id || ! user_can( $mentor_id, LSCH_Capabilities::TEACH ) || ! self::approved_user( $mentor_id ) || ! self::approved_user( $learner_id ) ) {
			return new WP_Error( 'lsch_future18_mentorship_invalid', __( 'Mentorship assignment is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$json = self::encode_json( $goals, 20000 );
		if ( is_wp_error( $json ) ) { return $json; }
		global $wpdb;
		$t = self::tables();
		$now = current_time( 'mysql', true );
		$sql = $wpdb->prepare( "INSERT INTO {$t['mentorship']} (mentor_id,learner_id,course_id,status,goals_json,feedback_json,version,created_at,updated_at) VALUES (%d,%d,%d,'active',%s,'[]',1,%s,%s) ON DUPLICATE KEY UPDATE status='active',goals_json=VALUES(goals_json),version=version+1,updated_at=VALUES(updated_at)", $mentor_id, $learner_id, $course_id, $json, $now, $now );
		if ( false === $wpdb->query( $sql ) ) {
			return new WP_Error( 'lsch_future18_mentorship_write_failed', __( 'Mentorship assignment could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		LSCH_Events::publish( 'LearningMentorshipAssigned.v1', 'mentorship', $learner_id . ':' . $mentor_id, array( 'mentor_id' => $mentor_id, 'learner_id' => $learner_id, 'course_id' => $course_id ) );
		return true;
	}

	public static function mentor_feedback( $mentor_id, $mentorship_id, array $feedback, $expected_version ) {
		$mentor_id = absint( $mentor_id );
		$mentorship_id = absint( $mentorship_id );
		if ( ! $mentor_id || ! user_can( $mentor_id, LSCH_Capabilities::TEACH ) || ! self::approved_user( $mentor_id ) ) {
			return new WP_Error( 'lsch_future18_mentor_forbidden', __( 'Mentor feedback is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['mentorship']} WHERE id=%d AND mentor_id=%d AND status='active' LIMIT 1", $mentorship_id, $mentor_id ), ARRAY_A );
		if ( ! $row || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_future18_mentorship_conflict', __( 'Mentorship record changed or was not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$existing = json_decode( (string) $row['feedback_json'], true );
		$existing = is_array( $existing ) ? $existing : array();
		$feedback['at'] = gmdate( 'c' );
		$feedback['mentor_id'] = $mentor_id;
		$existing[] = $feedback;
		if ( count( $existing ) > 100 ) { $existing = array_slice( $existing, -100 ); }
		$json = self::encode_json( $existing, 50000 );
		if ( is_wp_error( $json ) ) { return $json; }
		$updated = $wpdb->update( $t['mentorship'], array( 'feedback_json' => $json, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $mentorship_id, 'mentor_id' => $mentor_id, 'version' => absint( $row['version'] ) ), array( '%s', '%d', '%s' ), array( '%d', '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_mentorship_conflict', __( 'Mentorship record changed while saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		self::add_portfolio_item( absint( $row['learner_id'] ), 'mentor_feedback', 'mentorship', $mentorship_id, '', array( 'mentor_id' => $mentor_id, 'course_id' => absint( $row['course_id'] ) ) );
		return true;
	}

	public static function mentorships( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) && ! user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return new WP_Error( 'lsch_future18_mentorship_forbidden', __( 'Mentorship records are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['mentorship']} WHERE learner_id=%d OR mentor_id=%d ORDER BY updated_at DESC LIMIT 200", $user_id, $user_id ), ARRAY_A );
	}

	public static function record_cpd( $user_id, array $data ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) || ( ! LSCH_Capabilities::verified_doctor( $user_id ) && ! LSCH_Capabilities::is_founder( $user_id ) ) ) {
			return new WP_Error( 'lsch_future18_cpd_forbidden', __( 'Continuing professional development is reserved for verified doctors and the Founder.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$minutes = max( 1, min( 24 * 60, absint( $data['minutes'] ?? 0 ) ) );
		$evidence = self::encode_json( (array) ( $data['evidence'] ?? array() ), 20000 );
		if ( is_wp_error( $evidence ) ) { return $evidence; }
		global $wpdb;
		$t = self::tables();
		$now = current_time( 'mysql', true );
		$public_id = LSCH_Database::uuid();
		$competency = self::competency_key( $data['competency_key'] ?? '' );
		$ok = $wpdb->insert( $t['cpd'], array( 'public_id' => $public_id, 'user_id' => $user_id, 'activity_type' => sanitize_key( (string) ( $data['activity_type'] ?? 'learning' ) ), 'object_type' => sanitize_key( (string) ( $data['object_type'] ?? '' ) ), 'object_id' => sanitize_text_field( (string) ( $data['object_id'] ?? '' ) ), 'competency_key' => $competency, 'minutes' => $minutes, 'evidence_json' => $evidence, 'status' => 'self_recorded', 'verified_by' => 0, 'completed_at' => $now, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'lsch_future18_cpd_write_failed', __( 'CPD activity could not be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		self::add_portfolio_item( $user_id, 'cpd', 'cpd', $public_id, $competency, array( 'minutes' => $minutes, 'activity_type' => sanitize_key( (string) ( $data['activity_type'] ?? 'learning' ) ) ) );
		LSCH_Events::publish( 'LearningCPDRecorded.v1', 'cpd', $public_id, array( 'user_id' => $user_id, 'minutes' => $minutes, 'status' => 'self_recorded' ) );
		return array( 'id' => (int) $wpdb->insert_id, 'public_id' => $public_id, 'status' => 'self_recorded' );
	}


	public static function verify_cpd( $id, $verifier_id, $expected_version ) {
		$id = absint( $id );
		$verifier_id = absint( $verifier_id );
		if ( ! $verifier_id || ( ! self::approved_user( $verifier_id ) && ! user_can( $verifier_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) || ( ! user_can( $verifier_id, LSCH_Capabilities::TEACH ) && ! user_can( $verifier_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) ) {
			return new WP_Error( 'lsch_future18_cpd_verify_forbidden', __( 'CPD verification is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['cpd']} WHERE id=%d LIMIT 1", $id ), ARRAY_A );
		if ( ! $row || absint( $row['user_id'] ) === $verifier_id || absint( $row['version'] ) !== absint( $expected_version ) || ! in_array( $row['status'], array( 'self_recorded', 'needs_evidence' ), true ) ) {
			return new WP_Error( 'lsch_future18_cpd_verify_conflict', __( 'CPD record is unavailable, stale, or cannot be self-verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		if ( ! self::can_supervise_user( $verifier_id, absint( $row['user_id'] ) ) ) {
			return new WP_Error( 'lsch_future18_cpd_verify_scope', __( "CPD verification requires the learner's active mentor or a curriculum manager.", 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$updated = $wpdb->update( $t['cpd'], array( 'status' => 'verified', 'verified_by' => $verifier_id, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'version' => absint( $row['version'] ), 'status' => $row['status'] ), array( '%s', '%d', '%d', '%s' ), array( '%d', '%d', '%s' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_cpd_verify_conflict', __( 'CPD record changed while verifying.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		self::add_portfolio_item( absint( $row['user_id'] ), 'verified_cpd', 'cpd', $row['public_id'], $row['competency_key'], array( 'minutes' => absint( $row['minutes'] ), 'verified_by' => $verifier_id ) );
		if ( ! empty( $row['competency_key'] ) ) { self::record_mastery_evidence( absint( $row['user_id'] ), $row['competency_key'], 75, min( 0.5, absint( $row['minutes'] ) / 240 ), 'verified_cpd', $row['public_id'] ); }
		LSCH_Events::publish( 'LearningCPDVerified.v1', 'cpd', $row['public_id'], array( 'user_id' => absint( $row['user_id'] ), 'verified_by' => $verifier_id ) );
		return true;
	}

	public static function cpd_records( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_cpd_forbidden', __( 'CPD records are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		return $wpdb->get_results( $wpdb->prepare( "SELECT id,public_id,activity_type,object_type,object_id,competency_key,minutes,status,verified_by,completed_at,version FROM {$t['cpd']} WHERE user_id=%d ORDER BY completed_at DESC LIMIT 500", $user_id ), ARRAY_A );
	}

	public static function socratic_tutor( $user_id, $lesson_id, $question, $stage = 'question' ) {
		$user_id = absint( $user_id );
		$lesson_id = absint( $lesson_id );
		if ( ! self::approved_user( $user_id ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! LSCH_Policy::can_read_post( $lesson_id, $user_id ) ) {
			return new WP_Error( 'lsch_future18_tutor_forbidden', __( 'Socratic learning assistance is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$question = trim( sanitize_textarea_field( (string) $question ) );
		if ( strlen( $question ) < 3 || strlen( $question ) > 4000 ) {
			return new WP_Error( 'lsch_future18_tutor_question_invalid', __( 'A bounded learning question is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$stage = in_array( $stage, array( 'question', 'hint', 'explain', 'challenge', 'recap' ), true ) ? $stage : 'question';
		$context = array(
			'contract' => 'File16.SocraticTutor.v1',
			'owner' => 'file16-ai-answer-truth',
			'learning_owner' => 'file05',
			'user_id' => $user_id,
			'lesson_id' => $lesson_id,
			'lesson_version' => LSCH_Content::version( $lesson_id ),
			'objectives' => (string) get_post_meta( $lesson_id, '_lsch_objectives', true ),
			'sources' => LSCH_Value::sources( $lesson_id ),
			'stage' => $stage,
			'question' => $question,
			'safety' => array( 'educational_only' => true, 'diagnosis_authority' => false, 'prescription_authority' => false, 'emergency_authority' => false, 'source_grounded_required' => true ),
		);
		$result = apply_filters( 'lsch_file16_socratic_tutor', null, $context );
		if ( null === $result || false === $result ) {
			return new WP_Error( 'lsch_future18_tutor_provider_unavailable', __( 'The File 16 source-grounded Socratic tutor provider is not currently available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		if ( is_wp_error( $result ) ) { return $result; }
		if ( ! is_array( $result ) || empty( $result['text'] ) ) {
			return new WP_Error( 'lsch_future18_tutor_provider_invalid', __( 'The File 16 tutor provider returned an invalid learning response.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 502 ) );
		}
		$citations = array();
		foreach ( (array) ( $result['citations'] ?? array() ) as $citation ) {
			if ( ! is_array( $citation ) ) { continue; }
			$item = array(
				'owner_file' => sanitize_text_field( (string) ( $citation['owner_file'] ?? '' ) ),
				'object_id'  => sanitize_text_field( (string) ( $citation['object_id'] ?? '' ) ),
				'title'      => sanitize_text_field( (string) ( $citation['title'] ?? '' ) ),
				'url'        => esc_url_raw( (string) ( $citation['url'] ?? '' ) ),
			);
			if ( $item['object_id'] || $item['url'] ) { $citations[] = $item; }
		}
		if ( ! $citations ) {
			return new WP_Error( 'lsch_future18_tutor_source_required', __( 'The File 16 Socratic tutor must return at least one approved source citation.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 502 ) );
		}
		$out = array( 'text' => wp_kses_post( (string) $result['text'] ), 'citations' => $citations, 'stage' => $stage, 'provider_owner' => 'file16', 'learning_context_owner' => 'file05' );
		LSCH_Events::audit( 'future18_socratic_tutor_used', 'lesson', $lesson_id, array( 'stage' => $stage, 'question_hash' => hash( 'sha256', $question ), 'citation_count' => count( $out['citations'] ) ), 'learning' );
		return $out;
	}

	public static function event_published( $event_id, $name, $aggregate_type, $aggregate_id, $payload = array() ) {
		$payload = is_array( $payload ) ? $payload : array();
		if ( 'AssessmentSubmitted.v1' === $name && ! empty( $payload['user_id'] ) ) {
			$user_id = absint( $payload['user_id'] );
			$score = isset( $payload['score'] ) ? (float) $payload['score'] : 0;
			$object_id = absint( $aggregate_id );
			$competencies = wp_get_object_terms( $object_id, LSCH_Content::COMPETENCY, array( 'fields' => 'slugs' ) );
			if ( is_wp_error( $competencies ) || ! $competencies ) {
				$lesson_id = absint( get_post_meta( $object_id, '_lsch_lesson_id', true ) );
				$competencies = $lesson_id ? wp_get_object_terms( $lesson_id, LSCH_Content::COMPETENCY, array( 'fields' => 'slugs' ) ) : array();
			}
			if ( is_wp_error( $competencies ) || ! $competencies ) { $competencies = array( 'assessment-general' ); }
			foreach ( array_slice( array_values( array_unique( (array) $competencies ) ), 0, 20 ) as $competency ) {
				self::record_mastery_evidence( $user_id, $competency, $score, 1.0, 'assessment', $object_id );
			}
			return;
		}
		if ( 'LessonCompleted.v1' === $name && ! empty( $payload['user_id'] ) ) {
			self::add_portfolio_item( absint( $payload['user_id'] ), 'lesson_completion', sanitize_key( $aggregate_type ), sanitize_text_field( (string) $aggregate_id ), '', array( 'course_id' => absint( $payload['course_id'] ?? 0 ), 'lesson_version' => absint( $payload['lesson_version'] ?? 1 ) ) );
			return;
		}
		if ( 'CourseCompleted.v1' === $name && ! empty( $payload['user_id'] ) ) {
			self::add_portfolio_item( absint( $payload['user_id'] ), 'course_completion', sanitize_key( $aggregate_type ), sanitize_text_field( (string) $aggregate_id ), '', array( 'course_version' => absint( $payload['course_version'] ?? 1 ) ) );
			return;
		}
		if ( 'LearningContentCorrected.v1' !== $name ) {
			return;
		}
		$object_id = absint( $aggregate_id );
		if ( ! $object_id ) { return; }
		global $wpdb;
		$core = LSCH_Database::tables();
		$t = self::tables();
		$previous = isset( $payload['previous_version'] ) ? absint( $payload['previous_version'] ) : max( 0, LSCH_Content::version( $object_id ) - 1 );
		$new = isset( $payload['new_version'] ) ? absint( $payload['new_version'] ) : LSCH_Content::version( $object_id );
		$summary = sanitize_textarea_field( (string) get_post_meta( $object_id, '_lsch_correction_note', true ) );
		$summary = $summary ? substr( $summary, 0, 2000 ) : __( 'This learning object changed after prior study. Review the corrected version.', 'learn-sabri-classical-homeopathy' );
		$competencies = wp_get_object_terms( $object_id, LSCH_Content::COMPETENCY, array( 'fields' => 'slugs' ) );
		$competencies = is_wp_error( $competencies ) || ! $competencies ? array( '' ) : array_slice( array_values( array_unique( array_map( static function( $value ) { return self::competency_key( $value ); }, $competencies ) ) ), 0, 20 );
		$users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$core['progress']} WHERE lesson_id=%d ORDER BY user_id ASC LIMIT 2000", $object_id ) );
		$now = current_time( 'mysql', true );
		foreach ( $users as $user_id ) {
			foreach ( $competencies as $competency ) {
				$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$t['impacts']} (event_id,user_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at) VALUES (%s,%d,%s,%d,%d,%d,%s,%s,'targeted_review','pending',%s)", sanitize_text_field( $event_id ), absint( $user_id ), sanitize_key( $aggregate_type ), $object_id, $previous, $new, $competency, $summary, $now ) );
			}
		}
		LSCH_Events::publish( 'LearningRestudyRequired.v1', sanitize_key( $aggregate_type ), $object_id, array( 'affected_user_count' => count( $users ), 'previous_version' => $previous, 'new_version' => $new ) );
	}

	public static function impacts( $user_id, $status = 'pending' ) {
		$user_id = absint( $user_id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_impact_forbidden', __( 'Knowledge-change impact records are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$status = sanitize_key( $status );
		global $wpdb;
		$t = self::tables();
		if ( $status ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['impacts']} WHERE user_id=%d AND status=%s ORDER BY created_at DESC LIMIT 500", $user_id, $status ), ARRAY_A );
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['impacts']} WHERE user_id=%d ORDER BY created_at DESC LIMIT 500", $user_id ), ARRAY_A );
	}

	public static function resolve_impact( $user_id, $id ) {
		$user_id = absint( $user_id );
		$id = absint( $id );
		if ( ! self::approved_user( $user_id ) ) {
			return new WP_Error( 'lsch_future18_impact_forbidden', __( 'Knowledge-change impact action is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$updated = $wpdb->update( $t['impacts'], array( 'status' => 'reviewed', 'resolved_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'user_id' => $user_id, 'status' => 'pending' ), array( '%s', '%s' ), array( '%d', '%d', '%s' ) );
		return 1 === $updated ? true : new WP_Error( 'lsch_future18_impact_conflict', __( 'Impact item was not pending or was already changed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
	}

	public static function center_snapshot( $user_id ) {
		$user_id = absint( $user_id );
		$mastery = self::mastery_snapshot( $user_id );
		if ( is_wp_error( $mastery ) ) { return $mastery; }
		$path = self::build_learning_path( $user_id, 'balanced_mastery', false );
		return array(
			'mastery' => $mastery,
			'due_reviews' => self::review_queue( $user_id, '', 20 ),
			'mistakes' => self::mistakes( $user_id, 20 ),
			'learning_path' => is_wp_error( $path ) ? array() : $path,
			'portfolio' => self::portfolio( $user_id, 20 ),
			'mentorships' => self::mentorships( $user_id ),
			'cpd' => self::cpd_records( $user_id ),
			'change_impacts' => self::impacts( $user_id, 'pending' ),
			'features' => self::feature_ids(),
		);
	}

	public static function shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="lsch-notice">' . esc_html__( 'Sign in to open your Mastery & Clinical Education Center.', 'learn-sabri-classical-homeopathy' ) . '</div>';
		}
		$data = self::center_snapshot( get_current_user_id() );
		if ( is_wp_error( $data ) ) {
			return '<div class="lsch-notice">' . esc_html( $data->get_error_message() ) . '</div>';
		}
		$summary = $data['mastery']['summary'];
		$out = '<section class="lsch-future18" aria-labelledby="lsch-f18-title"><h2 id="lsch-f18-title">' . esc_html__( 'Mastery & Clinical Education Center', 'learn-sabri-classical-homeopathy' ) . '</h2>';
		$out .= '<p>' . esc_html( sprintf( __( 'Mastery average: %1$s%% · Weak competencies: %2$d · Due reviews: %3$d', 'learn-sabri-classical-homeopathy' ), $summary['average'], $summary['weak'], count( (array) $data['due_reviews'] ) ) ) . '</p>';
		$out .= '<div class="lsch-f18-grid">';
		$cards = array(
			__( 'Adaptive Mastery', 'learn-sabri-classical-homeopathy' ) => $summary['count'],
			__( 'Spaced Reviews', 'learn-sabri-classical-homeopathy' ) => count( (array) $data['due_reviews'] ),
			__( 'Mistake Book', 'learn-sabri-classical-homeopathy' ) => count( (array) $data['mistakes'] ),
			__( 'Portfolio', 'learn-sabri-classical-homeopathy' ) => count( (array) $data['portfolio'] ),
			__( 'Mentorship', 'learn-sabri-classical-homeopathy' ) => count( (array) $data['mentorships'] ),
			__( 'Change Re-study', 'learn-sabri-classical-homeopathy' ) => count( (array) $data['change_impacts'] ),
		);
		foreach ( $cards as $label => $value ) {
			$out .= '<article class="lsch-f18-card"><h3>' . esc_html( $label ) . '</h3><strong>' . esc_html( (string) $value ) . '</strong></article>';
		}
		$out .= '</div><p class="lsch-f18-boundary">' . esc_html__( 'Educational use only. Real patient diagnosis/prescription is not delegated to simulations or AI. External knowledge, PDF, repertory, messages, notifications and AI answers remain with their canonical platform owners.', 'learn-sabri-classical-homeopathy' ) . '</p></section>';
		return $out;
	}

	public static function privacy_exporters( $exporters ) {
		$exporters['lsch-future18'] = array( 'exporter_friendly_name' => __( 'File 05 Future Learning Data', 'learn-sabri-classical-homeopathy' ), 'callback' => array( __CLASS__, 'privacy_export' ) );
		return $exporters;
	}

	public static function privacy_erasers( $erasers ) {
		$erasers['lsch-future18'] = array( 'eraser_friendly_name' => __( 'File 05 Future Learning Data', 'learn-sabri-classical-homeopathy' ), 'callback' => array( __CLASS__, 'privacy_erase' ) );
		return $erasers;
	}

	public static function privacy_export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user || 1 !== absint( $page ) ) { return array( 'data' => array(), 'done' => true ); }
		$user_id = absint( $user->ID );
		global $wpdb;
		$t = self::tables();
		$mastery = $wpdb->get_results( $wpdb->prepare( "SELECT competency_key,mastery_score,confidence,evidence_count,last_source_type,last_source_id,last_evidence_at,next_review_at,version FROM {$t['mastery']} WHERE user_id=%d ORDER BY competency_key ASC LIMIT 1000", $user_id ), ARRAY_A );
		$review = $wpdb->get_results( $wpdb->prepare( "SELECT item_type,source_type,source_id,competency_key,prompt,answer,metadata_json,interval_days,ease,due_at,last_result,version FROM {$t['review']} WHERE user_id=%d ORDER BY id ASC LIMIT 2000", $user_id ), ARRAY_A );
		$practice = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,mode,source_type,source_id,blueprint_version,response_json,feedback_json,score,status,assessor_id,version,created_at,updated_at FROM {$t['practice']} WHERE user_id=%d ORDER BY id ASC LIMIT 2000", $user_id ), ARRAY_A );
		$portfolio = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,item_type,object_type,object_id,competency_key,data_json,visibility,version,created_at,updated_at FROM {$t['portfolio']} WHERE user_id=%d ORDER BY id ASC LIMIT 2000", $user_id ), ARRAY_A );
		$cpd = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,activity_type,object_type,object_id,competency_key,minutes,evidence_json,status,verified_by,completed_at,version FROM {$t['cpd']} WHERE user_id=%d ORDER BY id ASC LIMIT 1000", $user_id ), ARRAY_A );
		$impacts = $wpdb->get_results( $wpdb->prepare( "SELECT event_id,object_type,object_id,previous_version,new_version,competency_key,change_summary,required_action,status,created_at,resolved_at FROM {$t['impacts']} WHERE user_id=%d ORDER BY id ASC LIMIT 2000", $user_id ), ARRAY_A );
		$mentorship = $wpdb->get_results( $wpdb->prepare( "SELECT id,mentor_id,learner_id,course_id,status,goals_json,feedback_json,version,created_at,updated_at FROM {$t['mentorship']} WHERE learner_id=%d OR mentor_id=%d ORDER BY id ASC LIMIT 1000", $user_id, $user_id ), ARRAY_A );
		$data = array(
			array(
				'group_id' => 'lsch-future18',
				'group_label' => __( 'Learning Mastery and Clinical Education', 'learn-sabri-classical-homeopathy' ),
				'item_id' => 'future18-' . $user_id,
				'data' => array(
					array( 'name' => __( 'Mastery', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $mastery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Review queue and flashcards', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Practice laboratories', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $practice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Portfolio', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $portfolio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Mentorship', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $mentorship, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Continuing professional development', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $cpd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Knowledge-change re-study', 'learn-sabri-classical-homeopathy' ), 'value' => wp_json_encode( $impacts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
				),
			),
		);
		return array( 'data' => $data, 'done' => true );
	}

	public static function privacy_erase( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user || 1 !== absint( $page ) ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ); }
		$user_id = absint( $user->ID );
		global $wpdb;
		$t = self::tables();
		$removed = false;
		foreach ( array( 'mastery', 'review', 'practice', 'pathways', 'portfolio', 'cpd', 'impacts' ) as $key ) {
			$deleted = $wpdb->delete( $t[ $key ], array( 'user_id' => $user_id ), array( '%d' ) ); if ( false !== $deleted && $deleted > 0 ) { $removed = true; }
		}
		$mentorship_deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$t['mentorship']} WHERE learner_id=%d OR mentor_id=%d", $user_id, $user_id ) );
		if ( false !== $mentorship_deleted && $mentorship_deleted > 0 ) { $removed = true; }
		return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}
}
