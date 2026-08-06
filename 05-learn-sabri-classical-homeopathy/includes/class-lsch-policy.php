<?php
/** Native authorization, free-tier policy, privacy and integrity helpers. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Policy {
	/** Latest Founder directive: one complete free tier; no paid education gate. */
	public static function access_model() {
		return 'single-free-tier-v2';
	}

	public static function can_read_post( $post_id, $user_id = 0 ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );
		if ( ! $post || ! LSCH_Content::object_type( $post_id ) ) {
			return false;
		}
		if ( 'publish' !== $post->post_status ) {
			return $user_id && user_can( $user_id, 'edit_post', $post_id );
		}
		if ( LSCH_Content::LESSON === $post->post_type ) {
			$topics = wp_get_object_terms( $post_id, LSCH_Content::TOPIC, array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $topics ) && in_array( 'patient-case-learning', (array) $topics, true ) && ! self::valid_case_consent( $post_id ) ) {
				return $user_id && user_can( $user_id, 'edit_post', $post_id );
			}
		}
		$access = LSCH_Content::access( $post_id );
		if ( 'public' === $access ) {
			return true;
		}
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && LSCH_Capabilities::approved_account( $user_id ) && LSCH_Capabilities::guardian_gate_passes( $user_id );
	}


	public static function valid_case_consent( $lesson_id ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['consents']} WHERE lesson_id=%d AND withdrawn_at IS NULL ORDER BY id DESC LIMIT 1", absint( $lesson_id ) ) );
	}

	public static function can_use_learning_actions( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && LSCH_Capabilities::approved_account( $user_id ) && LSCH_Capabilities::guardian_gate_passes( $user_id );
	}

	public static function can_manage_object( $post_id, $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && user_can( $user_id, 'edit_post', absint( $post_id ) ) && ( LSCH_Capabilities::can_author( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );
	}

	public static function validate_prerequisites( $course_id, $user_id ) {
		$raw = (string) get_post_meta( $course_id, '_lsch_prerequisites', true );
		if ( '' === trim( $raw ) ) {
			return true;
		}
		$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $raw ) ) );
		if ( ! $ids ) {
			return true;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		foreach ( $ids as $required_course ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['completions']} WHERE user_id=%d AND course_id=%d AND status='earned' LIMIT 1", $user_id, $required_course ) );
			if ( ! $exists ) {
				return new WP_Error( 'lsch_prerequisite_missing', __( 'A required course has not yet been completed.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
		}
		return true;
	}

	public static function sanitize_json( $value, $max_bytes = 65535 ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( wp_unslash( $value ), true );
		} else {
			$decoded = $value;
		}
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}
		$json = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json || strlen( $json ) > $max_bytes ) {
			return new WP_Error( 'lsch_invalid_payload', __( 'The submitted data is invalid or too large.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		return $json;
	}

	public static function encrypt_note( $plain, $user_id, $lesson_id ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return new WP_Error( 'lsch_crypto_unavailable', __( 'Private notes are unavailable because encryption support is missing.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		$plain = trim( wp_strip_all_tags( (string) $plain ) );
		if ( strlen( $plain ) > 20000 ) {
			return new WP_Error( 'lsch_note_too_large', __( 'The private note is too long.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_SALT . 'lsch-note-v1', true );
		$iv  = random_bytes( 12 );
		$aad = $user_id . ':' . $lesson_id . ':v1';
		$tag = '';
		$ciphertext = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16 );
		if ( false === $ciphertext ) {
			return new WP_Error( 'lsch_note_encrypt_failed', __( 'The private note could not be encrypted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		return array( 'ciphertext' => base64_encode( $ciphertext ), 'iv' => base64_encode( $iv ), 'tag' => base64_encode( $tag ), 'key_version' => 1 );
	}

	public static function decrypt_note( array $row, $user_id, $lesson_id ) {
		if ( ! function_exists( 'openssl_decrypt' ) || empty( $row['ciphertext'] ) || empty( $row['iv'] ) || empty( $row['tag'] ) ) {
			return '';
		}
		$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_SALT . 'lsch-note-v1', true );
		$aad = $user_id . ':' . $lesson_id . ':v1';
		$plain = openssl_decrypt( base64_decode( $row['ciphertext'], true ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode( $row['iv'], true ), base64_decode( $row['tag'], true ), $aad );
		return false === $plain ? '' : $plain;
	}

	public static function request_id() {
		$header = isset( $_SERVER['HTTP_X_REQUEST_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUEST_ID'] ) ) : '';
		return preg_match( '/^[A-Za-z0-9._-]{8,64}$/', $header ) ? $header : LSCH_Database::uuid();
	}

	public static function idempotency_key( $provided, $user_id, $action ) {
		$provided = sanitize_text_field( (string) $provided );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{12,128}$/', $provided ) ) {
			return new WP_Error( 'lsch_idempotency_required', __( 'A valid idempotency key is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		return hash( 'sha256', $user_id . '|' . sanitize_key( $action ) . '|' . $provided );
	}

	public static function rate_limit( $bucket, $subject, $limit, $window ) {
		$key   = 'lsch_rl_' . substr( hash( 'sha256', $bucket . '|' . $subject ), 0, 40 );
		$state = get_transient( $key );
		$state = is_array( $state ) ? $state : array( 'count' => 0, 'start' => time() );
		if ( time() - absint( $state['start'] ) >= $window ) {
			$state = array( 'count' => 0, 'start' => time() );
		}
		$state['count']++;
		set_transient( $key, $state, $window );
		return $state['count'] <= $limit;
	}
}
