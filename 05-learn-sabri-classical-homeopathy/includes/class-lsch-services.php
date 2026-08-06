<?php
/** Canonical command/query services. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Services {
	public static function enroll( $course_id, $user_id, $idempotency ) {
		$course_id = absint( $course_id );
		$user_id   = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) || ! LSCH_Policy::can_read_post( $course_id, $user_id ) ) {
			return new WP_Error( 'lsch_forbidden', __( 'Enrollment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$prerequisites = LSCH_Policy::validate_prerequisites( $course_id, $user_id );
		if ( is_wp_error( $prerequisites ) ) {
			return $prerequisites;
		}
		$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'enroll' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$now = LSCH_Database::now();
		$public_id = LSCH_Database::uuid();
		$sql = $wpdb->prepare(
			"INSERT INTO {$t['enrollments']} (public_id,user_id,course_id,status,terms_version,course_version,version,started_at,created_at,updated_at) VALUES (%s,%d,%d,'active',%s,%d,1,%s,%s,%s)
			ON DUPLICATE KEY UPDATE status=IF(status IN ('withdrawn','paused'),'active',status),version=version+1,updated_at=VALUES(updated_at)",
			$public_id,
			$user_id,
			$course_id,
			LSCH_Policy::access_model(),
			LSCH_Content::version( $course_id ),
			$now,
			$now,
			$now
		);
		if ( false === $wpdb->query( $sql ) ) {
			return new WP_Error( 'lsch_enrollment_failed', __( 'Enrollment could not be saved.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d AND course_id=%d", $user_id, $course_id ), ARRAY_A );
		LSCH_Events::publish( 'LearningEnrollmentCreated.v1', 'course', $course_id, array( 'user_id' => $user_id, 'course_id' => $course_id, 'status' => $row['status'], 'terms_version' => $row['terms_version'] ) );
		LSCH_Events::audit( 'enroll_course', 'course', $course_id, array( 'user_id' => $user_id, 'idempotency_hash' => $key ), 'learning' );
		return $row;
	}

	public static function change_enrollment_state( $course_id, $user_id, $state, $expected_version ) {
		$allowed = array( 'active', 'paused', 'withdrawn' );
		$state   = sanitize_key( $state );
		if ( ! in_array( $state, $allowed, true ) || ! LSCH_Policy::can_use_learning_actions( $user_id ) ) {
			return new WP_Error( 'lsch_invalid_transition', __( 'The enrollment transition is not allowed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$column = 'paused' === $state ? 'paused_at' : ( 'withdrawn' === $state ? 'withdrawn_at' : '' );
		$set = "status=%s,version=version+1,updated_at=%s" . ( $column ? ",{$column}=%s" : '' );
		$args = array( $state, LSCH_Database::now() );
		if ( $column ) { $args[] = LSCH_Database::now(); }
		$args[] = absint( $user_id );
		$args[] = absint( $course_id );
		$args[] = absint( $expected_version );
		$sql = $wpdb->prepare( "UPDATE {$t['enrollments']} SET {$set} WHERE user_id=%d AND course_id=%d AND version=%d", $args );
		if ( 1 !== $wpdb->query( $sql ) ) {
			return new WP_Error( 'lsch_stale_enrollment', __( 'Enrollment changed. Reload and try again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		LSCH_Events::audit( 'enrollment_' . $state, 'course', $course_id, array( 'user_id' => $user_id ), 'learning' );
		return true;
	}

	public static function progress( $lesson_id, $user_id, array $input ) {
		$lesson_id = absint( $lesson_id );
		$user_id   = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! LSCH_Policy::can_read_post( $lesson_id, $user_id ) ) {
			return new WP_Error( 'lsch_progress_forbidden', __( 'Progress cannot be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t          = LSCH_Database::tables();
		$course_id  = absint( get_post_meta( $lesson_id, '_lsch_course_id', true ) );
		$current    = self::progress_row( $lesson_id, $user_id );
		$expected   = isset( $input['version'] ) ? absint( $input['version'] ) : 0;
		if ( $current && $expected && absint( $current['version'] ) !== $expected ) {
			return new WP_Error( 'lsch_progress_conflict', __( 'Progress changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$components = $current && ! empty( $current['components_json'] ) ? json_decode( $current['components_json'], true ) : array();
		$components = is_array( $components ) ? $components : array();
		$components['content'] = ! empty( $input['complete'] ) || ! empty( $components['content'] );

		$assessment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSESSMENT, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );
		if ( $assessment_ids ) {
			$components['assessment'] = true;
			foreach ( $assessment_ids as $assessment_id ) { $pass = max( 0, min( 100, absint( get_post_meta( $assessment_id, '_lsch_pass_mark', true ) ?: 50 ) ) ); $best = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(score),-1) FROM {$t['attempts']} WHERE user_id=%d AND assessment_id=%d AND status='graded' AND integrity_status='clear'", $user_id, absint( $assessment_id ) ) ); if ( $best < $pass ) { $components['assessment'] = false; break; } }
		}
		$assignment_ids = get_posts( array( 'post_type' => LSCH_Content::ASSIGNMENT, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_lesson_id', 'meta_value' => $lesson_id, 'no_found_rows' => true ) );
		if ( $assignment_ids ) {
			$components['assignment'] = true;
			foreach ( $assignment_ids as $assignment_id ) { $pass = max( 0, min( 100, absint( get_post_meta( $assignment_id, '_lsch_pass_mark', true ) ?: 50 ) ) ); $best = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(score),-1) FROM {$t['submissions']} WHERE user_id=%d AND assignment_id=%d AND status='graded'", $user_id, absint( $assignment_id ) ) ); if ( $best < $pass ) { $components['assignment'] = false; break; } }
		}

		$required = json_decode( (string) get_post_meta( $lesson_id, '_lsch_required_components', true ), true );
		$required = is_array( $required ) && $required ? array_values( array_intersect( array_map( 'sanitize_key', $required ), array( 'content', 'assessment', 'assignment' ) ) ) : array( 'content' );
		$done = 0;
		foreach ( $required as $component ) { if ( ! empty( $components[ $component ] ) ) { $done++; } }
		$percent = (int) floor( 100 * $done / max( 1, count( $required ) ) );
		$resume  = min( 86400 * 100, absint( isset( $input['resume_point'] ) ? $input['resume_point'] : ( $current ? $current['resume_point'] : 0 ) ) );
		$state   = 100 === $percent ? 'completed' : 'in_progress';
		$now     = LSCH_Database::now();
		$json    = wp_json_encode( $components );
		if ( $current ) {
			$updated = $wpdb->update(
				$t['progress'],
				array( 'course_id' => $course_id, 'state' => $state, 'percent' => $percent, 'resume_point' => $resume, 'components_json' => $json, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'needs_review' => 0, 'version' => absint( $current['version'] ) + 1, 'completed_at' => 'completed' === $state ? ( $current['completed_at'] ?: $now ) : null, 'updated_at' => $now ),
				array( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ) ),
				array( '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s' ),
				array( '%d', '%d' )
			);
			if ( 1 !== $updated ) { return new WP_Error( 'lsch_progress_conflict', __( 'Progress changed while saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
		} else {
			$wpdb->insert( $t['progress'], array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_id' => $lesson_id, 'state' => $state, 'percent' => $percent, 'resume_point' => $resume, 'components_json' => $json, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'needs_review' => 0, 'version' => 1, 'started_at' => $now, 'completed_at' => 'completed' === $state ? $now : null, 'updated_at' => $now ), array( '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ) );
		}
		if ( 'completed' === $state && ( ! $current || 'completed' !== $current['state'] ) ) {
			LSCH_Events::publish( 'LessonCompleted.v1', 'lesson', $lesson_id, array( 'user_id' => $user_id, 'course_id' => $course_id, 'lesson_version' => LSCH_Content::version( $lesson_id ), 'components' => $required ) );
			self::recalculate_completion( $course_id, $user_id );
		}
		LSCH_Events::audit( 'record_progress', 'lesson', $lesson_id, array( 'user_id' => $user_id, 'state' => $state, 'percent' => $percent, 'components' => $components ), 'learning' );
		return self::progress_row( $lesson_id, $user_id );
	}

	public static function progress_row( $lesson_id, $user_id ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['progress']} WHERE user_id=%d AND lesson_id=%d", absint( $user_id ), absint( $lesson_id ) ), ARRAY_A );
	}

	public static function toggle_bookmark( $object_type, $object_id, $user_id ) {
		$object_type = sanitize_key( $object_type );
		$object_id   = absint( $object_id );
		$user_id     = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::object_type( $object_id ) !== $object_type || ! LSCH_Policy::can_read_post( $object_id, $user_id ) ) {
			return new WP_Error( 'lsch_bookmark_forbidden', __( 'Bookmark action is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['bookmarks']} WHERE user_id=%d AND object_type=%s AND object_id=%d", $user_id, $object_type, $object_id ) );
		if ( $id ) {
			$wpdb->delete( $t['bookmarks'], array( 'id' => absint( $id ) ), array( '%d' ) );
			$active = false;
		} else {
			$active = false !== $wpdb->insert( $t['bookmarks'], array( 'user_id' => $user_id, 'object_type' => $object_type, 'object_id' => $object_id, 'created_at' => LSCH_Database::now() ), array( '%d', '%s', '%d', '%s' ) );
		}
		LSCH_Events::audit( $active ? 'bookmark_added' : 'bookmark_removed', $object_type, $object_id, array( 'user_id' => $user_id ), 'learning' );
		return array( 'active' => $active );
	}

	public static function save_note( $lesson_id, $user_id, $note, $expected_version = 0 ) {
		$lesson_id = absint( $lesson_id );
		$user_id   = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ! LSCH_Policy::can_read_post( $lesson_id, $user_id ) ) {
			return new WP_Error( 'lsch_note_forbidden', __( 'Private notes are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$encrypted = LSCH_Policy::encrypt_note( $note, $user_id, $lesson_id );
		if ( is_wp_error( $encrypted ) ) { return $encrypted; }
		global $wpdb;
		$t = LSCH_Database::tables();
		$now = LSCH_Database::now();
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT id,version FROM {$t['notes']} WHERE user_id=%d AND lesson_id=%d", $user_id, $lesson_id ), ARRAY_A );
		if ( $current ) {
			if ( $expected_version && absint( $current['version'] ) !== absint( $expected_version ) ) {
				return new WP_Error( 'lsch_note_conflict', __( 'This note changed on another device. Reload before saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
			$wpdb->update( $t['notes'], array( 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => 1, 'version' => absint( $current['version'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $current['id'] ) ), array( '%s', '%s', '%s', '%d', '%d', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( $t['notes'], array( 'user_id' => $user_id, 'lesson_id' => $lesson_id, 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag'], 'key_version' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );
		}
		LSCH_Events::audit( 'private_note_saved', 'lesson', $lesson_id, array( 'user_id' => $user_id ), 'private_learning' );
		return self::get_note( $lesson_id, $user_id );
	}

	public static function get_note( $lesson_id, $user_id ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['notes']} WHERE user_id=%d AND lesson_id=%d", absint( $user_id ), absint( $lesson_id ) ), ARRAY_A );
		return $row ? array( 'lesson_id' => absint( $lesson_id ), 'note' => LSCH_Policy::decrypt_note( $row, absint( $user_id ), absint( $lesson_id ) ), 'version' => absint( $row['version'] ), 'updated_at' => $row['updated_at'] ) : array( 'lesson_id' => absint( $lesson_id ), 'note' => '', 'version' => 0 );
	}

	public static function start_assessment( $assessment_id, $user_id, $idempotency ) {
		$assessment_id = absint( $assessment_id ); $user_id = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::ASSESSMENT !== get_post_type( $assessment_id ) || ! LSCH_Policy::can_read_post( $assessment_id, $user_id ) ) {
			return new WP_Error( 'lsch_assessment_forbidden', __( 'Assessment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$key = LSCH_Policy::idempotency_key( $idempotency, $user_id, 'assessment-attempt' ); if ( is_wp_error( $key ) ) { return $key; }
		global $wpdb; $t = LSCH_Database::tables();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['attempts']} WHERE idempotency_key=%s", $key ), ARRAY_A ); if ( $existing ) { return $existing; }
		$attempt_number = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(attempt_number),0) FROM {$t['attempts']} WHERE assessment_id=%d AND user_id=%d", $assessment_id, $user_id ) );
		$max = max( 1, absint( get_post_meta( $assessment_id, '_lsch_max_attempts', true ) ) ); if ( $attempt_number > $max ) { return new WP_Error( 'lsch_attempt_limit', __( 'No further attempts are available.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
		$now     = LSCH_Database::now();
		$limit   = absint( get_post_meta( $assessment_id, '_lsch_time_limit', true ) );
		$expires = $limit ? gmdate( 'Y-m-d H:i:s', time() + min( DAY_IN_SECONDS, $limit * MINUTE_IN_SECONDS ) ) : '';
		$data    = array(
			'public_id'       => LSCH_Database::uuid(),
			'assessment_id'   => $assessment_id,
			'user_id'         => $user_id,
			'attempt_number'  => $attempt_number,
			'item_version'    => LSCH_Content::version( $assessment_id ),
			'answers_json'    => '{}',
			'result_json'     => '{}',
			'score'           => 0,
			'status'          => 'started',
			'integrity_status'=> 'clear',
			'idempotency_key' => $key,
			'version'         => 1,
			'started_at'      => $now,
		);
		$formats = array( '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s' );
		if ( $expires ) {
			$data['expires_at'] = $expires;
			$formats[]          = '%s';
		}
		if ( false === $wpdb->insert( $t['attempts'], $data, $formats ) ) {
			return new WP_Error( 'lsch_assessment_start_failed', __( 'The assessment attempt could not be started.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['attempts']} WHERE idempotency_key=%s", $key ), ARRAY_A );
		LSCH_Events::audit( 'assessment_started', 'assessment', $assessment_id, array( 'user_id' => $user_id, 'attempt' => $attempt_number ), 'assessment' ); return $row;
	}

	public static function submit_assessment( $assessment_id, $user_id, array $answers, $idempotency ) {
		$attempt = self::start_assessment( $assessment_id, $user_id, $idempotency ); if ( is_wp_error( $attempt ) ) { return $attempt; }
		if ( 'graded' === $attempt['status'] ) { return $attempt; }
		if ( ! empty( $attempt['expires_at'] ) && strtotime( $attempt['expires_at'] . ' UTC' ) < time() ) {
			global $wpdb; $t = LSCH_Database::tables(); $wpdb->update( $t['attempts'], array( 'status' => 'expired', 'integrity_status' => 'time_expired', 'submitted_at' => LSCH_Database::now(), 'version' => absint( $attempt['version'] ) + 1 ), array( 'id' => absint( $attempt['id'] ), 'version' => absint( $attempt['version'] ) ), array( '%s', '%s', '%s', '%d' ), array( '%d', '%d' ) );
			return new WP_Error( 'lsch_assessment_expired', __( 'The assessment time limit has expired.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$questions = json_decode( (string) get_post_meta( $assessment_id, '_lsch_questions', true ), true ); $questions = is_array( $questions ) ? $questions : array();
		$correct = 0; $total = 0; $review = array();
		foreach ( $questions as $index => $question ) { if ( ! isset( $question['correct'] ) ) { continue; } $given = isset( $answers[ $index ] ) ? (string) $answers[ $index ] : ''; $ok = hash_equals( (string) $question['correct'], $given ); $total++; if ( $ok ) { $correct++; } $review[] = array( 'index' => $index, 'correct' => $ok, 'explanation' => isset( $question['explanation'] ) ? sanitize_text_field( $question['explanation'] ) : '' ); }
		if ( ! $total ) { return new WP_Error( 'lsch_assessment_invalid', __( 'Assessment blueprint has no valid items.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) ); }
		$score = round( 100 * $correct / $total, 2 ); $now = LSCH_Database::now(); global $wpdb; $t = LSCH_Database::tables();
		$updated = $wpdb->update( $t['attempts'], array( 'answers_json' => wp_json_encode( $answers ), 'result_json' => wp_json_encode( $review ), 'score' => $score, 'status' => 'graded', 'version' => absint( $attempt['version'] ) + 1, 'submitted_at' => $now, 'graded_at' => $now ), array( 'id' => absint( $attempt['id'] ), 'version' => absint( $attempt['version'] ), 'status' => 'started' ), array( '%s', '%s', '%f', '%s', '%d', '%s', '%s' ), array( '%d', '%d', '%s' ) );
		if ( 1 !== $updated ) { return new WP_Error( 'lsch_assessment_conflict', __( 'The assessment changed while being submitted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['attempts']} WHERE id=%d", absint( $attempt['id'] ) ), ARRAY_A );
		LSCH_Events::publish( 'AssessmentSubmitted.v1', 'assessment', $assessment_id, array( 'user_id' => $user_id, 'attempt_id' => $row['public_id'], 'score' => $score, 'item_version' => LSCH_Content::version( $assessment_id ) ) );
		LSCH_Events::audit( 'submit_assessment', 'assessment', $assessment_id, array( 'user_id' => $user_id, 'attempt' => $row['attempt_number'], 'score' => $score ), 'assessment' );
		$lesson_id = absint( get_post_meta( $assessment_id, '_lsch_lesson_id', true ) ); if ( $lesson_id ) { self::progress( $lesson_id, $user_id, array() ); }
		return $row;
	}

	public static function submit_assignment( $assignment_id, $user_id, $body, array $attachments = array() ) {
		$assignment_id = absint( $assignment_id ); $user_id = absint( $user_id );
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::ASSIGNMENT !== get_post_type( $assignment_id ) || ! LSCH_Policy::can_read_post( $assignment_id, $user_id ) ) {
			return new WP_Error( 'lsch_assignment_forbidden', __( 'Assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$body = wp_kses_post( $body );
		if ( '' === trim( wp_strip_all_tags( $body ) ) || strlen( $body ) > 100000 ) {
			return new WP_Error( 'lsch_assignment_invalid', __( 'Assignment response is required and must remain within the size limit.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$validated_attachments = array();
		if ( count( $attachments ) > 10 ) { return new WP_Error( 'lsch_assignment_attachments', __( 'Too many assignment attachments were supplied.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		foreach ( $attachments as $attachment ) {
			if ( ! is_array( $attachment ) || '12' !== (string) ( isset( $attachment['owner_file'] ) ? $attachment['owner_file'] : '' ) || empty( $attachment['object_id'] ) ) { return new WP_Error( 'lsch_assignment_attachment_invalid', __( 'Assignment attachments must be validated File 12 references.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
			$approved = apply_filters( 'lsch_validate_assignment_attachment', false, $attachment, $assignment_id, $user_id );
			if ( true !== $approved ) { return new WP_Error( 'lsch_assignment_attachment_unverified', __( 'An assignment attachment could not be verified.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
			$validated_attachments[] = array( 'owner_file' => '12', 'object_id' => sanitize_text_field( $attachment['object_id'] ), 'version' => absint( isset( $attachment['version'] ) ? $attachment['version'] : 1 ) );
		}
		$files = LSCH_Policy::sanitize_json( $validated_attachments, 20000 ); if ( is_wp_error( $files ) ) { return $files; }
		global $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
		$wpdb->insert( $t['submissions'], array( 'public_id' => LSCH_Database::uuid(), 'assignment_id' => $assignment_id, 'user_id' => $user_id, 'body' => $body, 'attachments_json' => $files, 'status' => 'submitted', 'rubric_version' => LSCH_Content::version( $assignment_id ), 'assessor_id' => 0, 'feedback' => '', 'score' => 0, 'appeal_text' => '', 'appeal_status' => '', 'version' => 1, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%s' ) );
		$id = $wpdb->insert_id;
		LSCH_Events::audit( 'assignment_submitted', 'assignment', $assignment_id, array( 'user_id' => $user_id, 'submission_id' => $id ), 'assessment' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['submissions']} WHERE id=%d", $id ), ARRAY_A );
	}

	public static function grade_submission( $submission_id, $assessor_id, $score, $feedback, $expected_version ) {
		$submission_id = absint( $submission_id ); $assessor_id = absint( $assessor_id );
		if ( ! user_can( $assessor_id, LSCH_Capabilities::ASSESS ) ) {
			return new WP_Error( 'lsch_grade_forbidden', __( 'You are not assigned to assess this work.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb; $t = LSCH_Database::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['submissions']} WHERE id=%d", $submission_id ), ARRAY_A );
		if ( $row && ! user_can( $assessor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) && ! self::staff_scope_allows( $assessor_id, 'assignment', absint( $row['assignment_id'] ), 'assessor' ) ) {
			return new WP_Error( 'lsch_grade_scope', __( 'An active, conflict-cleared assessor assignment is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		if ( ! $row || absint( $row['user_id'] ) === $assessor_id || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_grade_conflict', __( 'Submission is unavailable, conflicted, or cannot be self-assessed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$updated = $wpdb->update( $t['submissions'], array( 'status' => 'graded', 'assessor_id' => $assessor_id, 'score' => min( 100, max( 0, (float) $score ) ), 'feedback' => wp_kses_post( $feedback ), 'version' => absint( $row['version'] ) + 1, 'updated_at' => LSCH_Database::now() ), array( 'id' => $submission_id, 'version' => absint( $expected_version ) ), array( '%s', '%d', '%f', '%s', '%d', '%s' ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) { return new WP_Error( 'lsch_grade_conflict', __( 'Submission changed while grading.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
		LSCH_Events::audit( 'submission_graded', 'submission', $submission_id, array( 'assessor_id' => $assessor_id, 'score' => $score ), 'assessment' );
		$lesson_id = absint( get_post_meta( absint( $row['assignment_id'] ), '_lsch_lesson_id', true ) ); if ( $lesson_id ) { self::progress( $lesson_id, absint( $row['user_id'] ), array() ); }
		return true;
	}

	public static function appeal_submission( $submission_id, $user_id, $text, $expected_version ) {
		global $wpdb; $t = LSCH_Database::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['submissions']} WHERE id=%d AND user_id=%d", absint( $submission_id ), absint( $user_id ) ), ARRAY_A );
		if ( ! $row || 'graded' !== $row['status'] || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_appeal_invalid', __( 'This submission cannot be appealed in its current state.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$text = sanitize_textarea_field( $text );
		if ( strlen( $text ) < 20 ) { return new WP_Error( 'lsch_appeal_reason_required', __( 'Provide a clear appeal reason.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		$wpdb->update( $t['submissions'], array( 'status' => 'appealed', 'appeal_text' => $text, 'appeal_status' => 'submitted', 'version' => absint( $row['version'] ) + 1, 'updated_at' => LSCH_Database::now() ), array( 'id' => absint( $submission_id ), 'version' => absint( $expected_version ) ), array( '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%d' ) );
		LSCH_Events::publish( 'LearningAssessmentAppealed.v1', 'submission', $submission_id, array( 'user_id' => $user_id ) );
		LSCH_Events::audit( 'submission_appealed', 'submission', $submission_id, array( 'user_id' => $user_id ), 'appeal' );
		return true;
	}

	public static function mark_content_corrected( $lesson_id, $reason ) {
		$lesson_id = absint( $lesson_id );
		if ( ! current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) || LSCH_Content::LESSON !== get_post_type( $lesson_id ) ) {
			return new WP_Error( 'lsch_correction_forbidden', __( 'Correction action is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === $reason ) { return new WP_Error( 'lsch_correction_reason', __( 'A correction reason is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		$version = LSCH_Content::version( $lesson_id ) + 1;
		update_post_meta( $lesson_id, '_lsch_version', $version );
		update_post_meta( $lesson_id, '_lsch_correction_note', $reason );
		global $wpdb; $t = LSCH_Database::tables();
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['progress']} SET needs_review=1,state=IF(state='completed','needs_review',state),version=version+1,updated_at=%s WHERE lesson_id=%d AND lesson_version<%d", LSCH_Database::now(), $lesson_id, $version ) );
		LSCH_Events::publish( 'LearningContentCorrected.v1', 'lesson', $lesson_id, array( 'lesson_version' => $version, 'reason' => $reason ) );
		LSCH_Events::audit( 'learning_content_corrected', 'lesson', $lesson_id, array( 'version' => $version ), 'editorial_integrity' );
		return $version;
	}

	public static function assign_staff( $user_id, $object_type, $object_id, $role, array $scope, $conflict = 'clear' ) {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$role = sanitize_key( $role );
		if ( ! in_array( $role, array( 'teacher', 'assessor', 'reviewer', 'curriculum_lead' ), true ) || ! get_userdata( absint( $user_id ) ) ) {
			return new WP_Error( 'lsch_staff_invalid', __( 'Staff assignment is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$scope_json = LSCH_Policy::sanitize_json( $scope, 10000 ); if ( is_wp_error( $scope_json ) ) { return $scope_json; }
		global $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
		$sql = $wpdb->prepare( "INSERT INTO {$t['staff']} (user_id,object_type,object_id,role,scope_json,conflict_status,active,version,assigned_by,created_at,updated_at) VALUES (%d,%s,%d,%s,%s,%s,%d,1,%d,%s,%s) ON DUPLICATE KEY UPDATE scope_json=VALUES(scope_json),conflict_status=VALUES(conflict_status),active=VALUES(active),version=version+1,assigned_by=VALUES(assigned_by),updated_at=VALUES(updated_at)", absint( $user_id ), sanitize_key( $object_type ), absint( $object_id ), $role, $scope_json, sanitize_key( $conflict ), 'clear' === sanitize_key( $conflict ) ? 1 : 0, get_current_user_id(), $now, $now );
		$wpdb->query( $sql );
		LSCH_Events::audit( 'staff_assigned', $object_type, $object_id, array( 'user_id' => absint( $user_id ), 'role' => $role, 'conflict' => $conflict ), 'governance' );
		return true;
	}

	public static function reset_progress( $lesson_id, $user_id ) {
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) ) { return new WP_Error( 'lsch_reset_forbidden', __( 'Progress reset is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		global $wpdb; $t = LSCH_Database::tables();
		$deleted = $wpdb->delete( $t['progress'], array( 'user_id' => absint( $user_id ), 'lesson_id' => absint( $lesson_id ) ), array( '%d', '%d' ) );
		LSCH_Events::audit( 'progress_reset', 'lesson', $lesson_id, array( 'user_id' => absint( $user_id ), 'deleted' => (bool) $deleted ), 'learning' );
		return array( 'reset' => (bool) $deleted );
	}

	public static function store_case_consent( $lesson_id, $actor_id, array $data ) {
		$lesson_id = absint( $lesson_id ); $actor_id = absint( $actor_id );
		if ( LSCH_Content::LESSON !== get_post_type( $lesson_id ) || ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) ) ) { return new WP_Error( 'lsch_consent_forbidden', __( 'Case consent cannot be recorded.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		$source = sanitize_key( isset( $data['consent_source'] ) ? $data['consent_source'] : '' );
		$scope = sanitize_textarea_field( isset( $data['scope'] ) ? $data['scope'] : '' );
		$policy = sanitize_text_field( isset( $data['policy_version'] ) ? $data['policy_version'] : 'case-consent-v1' );
		if ( ! in_array( $source, array( 'patient', 'guardian', 'institution' ), true ) || '' === $scope ) { return new WP_Error( 'lsch_consent_invalid', __( 'A valid consent source and scope are required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		global $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
		$sql = $wpdb->prepare( "INSERT INTO {$t['consents']} (lesson_id,created_by,policy_version,subject_type,consent_source,scope,evidence_reference,confirmed_at,withdrawn_at,withdrawn_by,version) VALUES (%d,%d,%s,%s,%s,%s,%s,%s,NULL,0,1) ON DUPLICATE KEY UPDATE created_by=VALUES(created_by),subject_type=VALUES(subject_type),consent_source=VALUES(consent_source),scope=VALUES(scope),evidence_reference=VALUES(evidence_reference),confirmed_at=VALUES(confirmed_at),withdrawn_at=NULL,withdrawn_by=0,version=version+1", $lesson_id, $actor_id, $policy, sanitize_key( isset( $data['subject_type'] ) ? $data['subject_type'] : 'patient' ), $source, $scope, sanitize_text_field( isset( $data['evidence_reference'] ) ? $data['evidence_reference'] : '' ), $now );
		$wpdb->query( $sql );
		LSCH_Events::audit( 'case_consent_recorded', 'lesson', $lesson_id, array( 'actor_id' => $actor_id, 'policy_version' => $policy ), 'patient_privacy' );
		return array( 'valid' => LSCH_Policy::valid_case_consent( $lesson_id ) );
	}

	public static function withdraw_case_consent( $lesson_id, $actor_id, $reason ) {
		if ( ! LSCH_Policy::can_manage_object( $lesson_id, $actor_id ) && ! user_can( $actor_id, LSCH_Capabilities::REVIEW_LESSONS ) ) { return new WP_Error( 'lsch_consent_forbidden', __( 'Case consent cannot be withdrawn.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		$reason = sanitize_textarea_field( $reason ); if ( '' === $reason ) { return new WP_Error( 'lsch_consent_reason', __( 'A withdrawal reason is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		global $wpdb; $t = LSCH_Database::tables();
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['consents']} SET withdrawn_at=COALESCE(withdrawn_at,%s),withdrawn_by=%d,version=version+1 WHERE lesson_id=%d AND withdrawn_at IS NULL", LSCH_Database::now(), absint( $actor_id ), absint( $lesson_id ) ) );
		if ( 'publish' === get_post_status( $lesson_id ) ) { wp_update_post( array( 'ID' => absint( $lesson_id ), 'post_status' => 'private' ) ); }
		update_post_meta( $lesson_id, '_lsch_consent_withdrawal_reason', $reason );
		LSCH_Events::publish( 'LearningCaseConsentWithdrawn.v1', 'lesson', $lesson_id, array( 'actor_id' => absint( $actor_id ) ) );
		LSCH_Events::audit( 'case_consent_withdrawn', 'lesson', $lesson_id, array( 'actor_id' => absint( $actor_id ), 'reason' => $reason ), 'patient_privacy' );
		return true;
	}

	public static function set_reminder( $course_id, $user_id, $enabled, $cadence, array $quiet_hours = array() ) {
		if ( ! LSCH_Policy::can_use_learning_actions( $user_id ) || LSCH_Content::COURSE !== get_post_type( $course_id ) ) { return new WP_Error( 'lsch_reminder_forbidden', __( 'Course reminder is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		$cadence = sanitize_key( $cadence ); if ( ! in_array( $cadence, array( 'daily', 'weekly', 'monthly' ), true ) ) { $cadence = 'weekly'; }
		$quiet = LSCH_Policy::sanitize_json( $quiet_hours, 2000 ); if ( is_wp_error( $quiet ) ) { return $quiet; }
		global $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
		$sql = $wpdb->prepare( "INSERT INTO {$t['reminders']} (user_id,course_id,enabled,cadence,quiet_hours_json,version,created_at,updated_at) VALUES (%d,%d,%d,%s,%s,1,%s,%s) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),cadence=VALUES(cadence),quiet_hours_json=VALUES(quiet_hours_json),version=version+1,updated_at=VALUES(updated_at)", absint( $user_id ), absint( $course_id ), $enabled ? 1 : 0, $cadence, $quiet, $now, $now );
		$wpdb->query( $sql );
		LSCH_Events::publish( 'LearningReminderPreferenceChanged.v1', 'course', $course_id, array( 'user_id' => absint( $user_id ), 'enabled' => (bool) $enabled, 'cadence' => $cadence ) );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['reminders']} WHERE user_id=%d AND course_id=%d", absint( $user_id ), absint( $course_id ) ), ARRAY_A );
	}

	public static function upsert_related_link( $source_type, $source_id, array $data ) {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_related_forbidden', __( 'Related knowledge management is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		$source_type = sanitize_key( $source_type ); $source_id = absint( $source_id ); $target_file = strtoupper( sanitize_text_field( isset( $data['target_file'] ) ? $data['target_file'] : '' ) );
		if ( ! in_array( $target_file, array( '06', '10', '12', '15' ), true ) || LSCH_Content::object_type( $source_id ) !== $source_type ) { return new WP_Error( 'lsch_related_invalid', __( 'Related knowledge reference is invalid.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		$url = esc_url_raw( isset( $data['target_url'] ) ? $data['target_url'] : '' );
		if ( $url && wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) { return new WP_Error( 'lsch_related_host', __( 'Related links must use the approved platform host.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		global $wpdb; $t = LSCH_Database::tables(); $now = LSCH_Database::now();
		$sql = $wpdb->prepare( "INSERT INTO {$t['related']} (source_type,source_id,target_file,target_type,target_id,target_url,relation,status,version,created_at,updated_at) VALUES (%s,%d,%s,%s,%s,%s,%s,'active',1,%s,%s) ON DUPLICATE KEY UPDATE target_url=VALUES(target_url),status='active',version=version+1,updated_at=VALUES(updated_at)", $source_type, $source_id, $target_file, sanitize_key( isset( $data['target_type'] ) ? $data['target_type'] : '' ), sanitize_text_field( isset( $data['target_id'] ) ? $data['target_id'] : '' ), $url, sanitize_key( isset( $data['relation'] ) ? $data['relation'] : 'related' ), $now, $now );
		$wpdb->query( $sql );
		LSCH_Events::audit( 'related_knowledge_updated', $source_type, $source_id, array( 'target_file' => $target_file ), 'knowledge_integration' );
		return LSCH_Operations::related_links( $source_type, $source_id );
	}

	public static function course_analytics( $course_id ) {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) && ! self::staff_scope_allows( get_current_user_id(), 'course', $course_id, 'teacher' ) ) { return new WP_Error( 'lsch_analytics_forbidden', __( 'Course analytics are unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		global $wpdb; $t = LSCH_Database::tables();
		$learners = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['enrollments']} WHERE course_id=%d", absint( $course_id ) ) );
		if ( $learners < 5 ) { return array( 'suppressed' => true, 'threshold' => 5 ); }
		return array( 'suppressed' => false, 'enrollments' => $learners, 'completed' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['enrollments']} WHERE course_id=%d AND status='completed'", absint( $course_id ) ) ), 'active' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['enrollments']} WHERE course_id=%d AND status='active'", absint( $course_id ) ) ), 'average_progress' => round( (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(AVG(percent),0) FROM {$t['progress']} WHERE course_id=%d", absint( $course_id ) ) ), 2 ) );
	}

	private static function staff_scope_allows( $user_id, $object_type, $object_id, $role ) {
		global $wpdb; $t = LSCH_Database::tables();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['staff']} WHERE user_id=%d AND object_type=%s AND object_id=%d AND role=%s AND active=1 AND conflict_status='clear' LIMIT 1", absint( $user_id ), sanitize_key( $object_type ), absint( $object_id ), sanitize_key( $role ) ) );
	}

	public static function remove_staff( $user_id, $object_type, $object_id, $role ) {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { return new WP_Error( 'lsch_staff_forbidden', __( 'Staff assignment is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) ); }
		global $wpdb; $t = LSCH_Database::tables();
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['staff']} SET active=0,version=version+1,updated_at=%s WHERE user_id=%d AND object_type=%s AND object_id=%d AND role=%s", LSCH_Database::now(), absint( $user_id ), sanitize_key( $object_type ), absint( $object_id ), sanitize_key( $role ) ) );
		LSCH_Events::audit( 'staff_removed', $object_type, $object_id, array( 'user_id' => absint( $user_id ), 'role' => sanitize_key( $role ) ), 'governance' );
		return true;
	}

	private static function recalculate_completion( $course_id, $user_id ) {
		$course_id = absint( $course_id );
		if ( ! $course_id || LSCH_Content::COURSE !== get_post_type( $course_id ) ) { return false; }
		$lessons = get_posts( array( 'post_type' => LSCH_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_lsch_course_id', 'meta_value' => $course_id, 'no_found_rows' => true ) );
		$required = array_filter( $lessons, static function( $id ) { return 1 === absint( get_post_meta( $id, '_lsch_required', true ) ?: 1 ); } );
		if ( ! $required ) { return false; }
		global $wpdb; $t = LSCH_Database::tables();
		$completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['progress']} WHERE user_id=%d AND lesson_id IN (" . implode( ',', array_map( 'absint', $required ) ) . ") AND state='completed'", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $completed !== count( $required ) ) { return false; }
		$claims = LSCH_Dependencies::claims( $user_id );
		if ( empty( $claims['identity_verified'] ) ) { return false; }
		$competencies = wp_get_object_terms( $course_id, LSCH_Content::COMPETENCY, array( 'fields' => 'slugs' ) );
		$snapshot = wp_json_encode( array( 'competencies' => is_wp_error( $competencies ) ? array() : $competencies, 'course_version' => LSCH_Content::version( $course_id ), 'required_lessons' => array_map( 'absint', $required ), 'completed_at' => gmdate( 'c' ) ) );
		$now = LSCH_Database::now(); $public_id = LSCH_Database::uuid();
		$sql = $wpdb->prepare( "INSERT INTO {$t['completions']} (public_id,user_id,course_id,course_version,competency_snapshot_json,status,identity_assurance,integrity_status,version,earned_at,revoked_reason) VALUES (%s,%d,%d,%d,%s,'earned','verified','clear',1,%s,'') ON DUPLICATE KEY UPDATE competency_snapshot_json=VALUES(competency_snapshot_json),status='earned',version=version+1,revoked_at=NULL,revoked_reason='',earned_at=VALUES(earned_at)", $public_id, $user_id, $course_id, LSCH_Content::version( $course_id ), $snapshot, $now );
		$wpdb->query( $sql );
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['enrollments']} SET status='completed',completed_at=%s,version=version+1,updated_at=%s WHERE user_id=%d AND course_id=%d", $now, $now, $user_id, $course_id ) );
		LSCH_Events::publish( 'CourseCompleted.v1', 'course', $course_id, array( 'user_id' => $user_id, 'course_version' => LSCH_Content::version( $course_id ), 'certificate_status' => 'eligible' ) );
		return true;
	}

	public static function dashboard( $user_id ) {
		global $wpdb; $t = LSCH_Database::tables();
		$enrollments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['enrollments']} WHERE user_id=%d ORDER BY updated_at DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		$progress = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['progress']} WHERE user_id=%d ORDER BY updated_at DESC LIMIT 250", absint( $user_id ) ), ARRAY_A );
		$bookmarks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['bookmarks']} WHERE user_id=%d ORDER BY created_at DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		$attempts = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,assessment_id,attempt_number,score,status,integrity_status,submitted_at FROM {$t['attempts']} WHERE user_id=%d ORDER BY id DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		$submissions = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,assignment_id,status,score,appeal_status,version,updated_at FROM {$t['submissions']} WHERE user_id=%d ORDER BY updated_at DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		$reminders = $wpdb->get_results( $wpdb->prepare( "SELECT course_id,enabled,cadence,quiet_hours_json,version,updated_at FROM {$t['reminders']} WHERE user_id=%d ORDER BY updated_at DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		$completions = $wpdb->get_results( $wpdb->prepare( "SELECT public_id,course_id,course_version,status,identity_assurance,integrity_status,earned_at,revoked_at FROM {$t['completions']} WHERE user_id=%d ORDER BY earned_at DESC LIMIT 100", absint( $user_id ) ), ARRAY_A );
		return array( 'access_model' => LSCH_Policy::access_model(), 'enrollments' => $enrollments, 'progress' => $progress, 'bookmarks' => $bookmarks, 'attempts' => $attempts, 'submissions' => $submissions, 'reminders' => $reminders, 'completions' => $completions );
	}
}
