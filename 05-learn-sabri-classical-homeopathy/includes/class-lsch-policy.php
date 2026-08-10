<?php
/** Native authorization, free-tier policy, privacy and integrity helpers. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Policy {
	const NOTE_KEY_VERSION = 2;

	/** Latest Founder directive: one complete free tier; no paid education gate. */
	public static function access_model() {
		return 'single-free-tier-v2';
	}

	public static function central_policy_ready() {
		return LSCH_Dependencies::governing_policy_ready();
	}

	public static function can_read_post( $post_id, $user_id = 0 ) {
		$post_id = absint( $post_id );
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
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
		return $user_id && self::central_policy_ready() && LSCH_Capabilities::approved_account( $user_id ) && LSCH_Capabilities::guardian_gate_passes( $user_id );
	}

	public static function valid_case_consent( $lesson_id ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['consents']} WHERE lesson_id=%d AND withdrawn_at IS NULL ORDER BY id DESC LIMIT 1", absint( $lesson_id ) ) );
	}

	public static function can_use_learning_actions( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return
			$user_id &&
			! LSCH_Operations::safe_mode() &&
			self::central_policy_ready() &&
			LSCH_Capabilities::approved_account( $user_id ) &&
			LSCH_Capabilities::guardian_gate_passes( $user_id );
	}

	public static function can_manage_object( $post_id, $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return
			$user_id &&
			self::central_policy_ready() &&
			user_can( $user_id, 'edit_post', absint( $post_id ) ) &&
			( LSCH_Capabilities::can_author( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );
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

	/** Decode deployment key material without accepting short/passphrase secrets. */
	private static function decode_note_material( $value ) {
		$value = trim( (string) $value );
		if ( 0 === strpos( $value, 'base64:' ) ) {
			$decoded = base64_decode( substr( $value, 7 ), true );
			return is_string( $decoded ) && 32 === strlen( $decoded ) ? $decoded : false;
		}
		if ( 0 === strpos( $value, 'hex:' ) && preg_match( '/^[a-f0-9]{64}$/i', substr( $value, 4 ) ) ) {
			$decoded = hex2bin( substr( $value, 4 ) );
			return is_string( $decoded ) && 32 === strlen( $decoded ) ? $decoded : false;
		}
		return false;
	}

	/**
	 * Keyring for private learning notes.
	 *
	 * New notes never derive keys from WordPress authentication salts. Key
	 * material must be supplied by deployment configuration or an approved
	 * File 24/key-management adapter through `lsch_note_keyring`.
	 */
	public static function note_keyring() {
		$raw = array();
		if ( defined( 'LSCH_NOTE_MASTER_KEY' ) ) {
			$raw[ self::NOTE_KEY_VERSION ] = LSCH_NOTE_MASTER_KEY;
		}
		$raw = (array) apply_filters( 'lsch_note_keyring', $raw );
		$keys = array();
		foreach ( $raw as $version => $material ) {
			$version = absint( $version );
			$decoded = self::decode_note_material( $material );
			if ( $version >= self::NOTE_KEY_VERSION && false !== $decoded ) {
				$keys[ $version ] = $decoded;
			}
		}
		ksort( $keys, SORT_NUMERIC );
		return $keys;
	}

	public static function note_write_key_version() {
		$keys = self::note_keyring();
		return $keys ? max( array_map( 'absint', array_keys( $keys ) ) ) : 0;
	}

	public static function note_encryption_ready() {
		$keys = self::note_keyring();
		$version = self::note_write_key_version();
		return
			function_exists( 'openssl_encrypt' ) &&
			function_exists( 'openssl_decrypt' ) &&
			$version >= self::NOTE_KEY_VERSION &&
			isset( $keys[ $version ] ) &&
			32 === strlen( $keys[ $version ] );
	}

	private static function note_aad( $user_id, $lesson_id, $key_version ) {
		return 'file05-note|' . absint( $user_id ) . '|' . absint( $lesson_id ) . '|v' . absint( $key_version );
	}

	public static function encrypt_note( $plain, $user_id, $lesson_id ) {
		if ( ! self::note_encryption_ready() ) {
			return new WP_Error(
				'lsch_note_key_unavailable',
				__( 'Private notes are safely paused until an independent File 05 note-encryption key is configured.', 'learn-sabri-classical-homeopathy' ),
				array( 'status' => 503 )
			);
		}
		$plain = trim( wp_strip_all_tags( (string) $plain ) );
		if ( strlen( $plain ) > 20000 ) {
			return new WP_Error( 'lsch_note_too_large', __( 'The private note is too long.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$keys = self::note_keyring();
		$key_version = self::note_write_key_version();
		$key = $keys[ $key_version ];
		$iv = random_bytes( 12 );
		$aad = self::note_aad( $user_id, $lesson_id, $key_version );
		$tag = '';
		$ciphertext = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16 );
		if ( false === $ciphertext ) {
			return new WP_Error( 'lsch_note_encrypt_failed', __( 'The private note could not be encrypted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 500 ) );
		}
		return array(
			'ciphertext' => base64_encode( $ciphertext ),
			'iv'         => base64_encode( $iv ),
			'tag'        => base64_encode( $tag ),
			'key_version'=> $key_version,
		);
	}

	public static function decrypt_note_checked( array $row, $user_id, $lesson_id ) {
		if ( ! function_exists( 'openssl_decrypt' ) || empty( $row['ciphertext'] ) || empty( $row['iv'] ) || empty( $row['tag'] ) ) {
			return new WP_Error( 'lsch_note_cipher_missing', __( 'The encrypted private-note record is incomplete.', 'learn-sabri-classical-homeopathy' ) );
		}
		$key_version = max( 1, absint( $row['key_version'] ?? 1 ) );
		$key = false;
		$aad = '';

		if ( 1 === $key_version ) {
			/* Read-only compatibility for schema-7 notes; never used for new writes. */
			if ( defined( 'AUTH_KEY' ) && defined( 'SECURE_AUTH_SALT' ) ) {
				$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_SALT . 'lsch-note-v1', true );
				$aad = absint( $user_id ) . ':' . absint( $lesson_id ) . ':v1';
			}
		} else {
			$keys = self::note_keyring();
			if ( isset( $keys[ $key_version ] ) ) {
				$key = $keys[ $key_version ];
				$aad = self::note_aad( $user_id, $lesson_id, $key_version );
			}
		}

		if ( false === $key ) {
			return new WP_Error( 'lsch_note_key_generation_unavailable', __( 'The key generation required for this private note is unavailable.', 'learn-sabri-classical-homeopathy' ) );
		}

		$cipher = base64_decode( (string) $row['ciphertext'], true );
		$iv = base64_decode( (string) $row['iv'], true );
		$tag = base64_decode( (string) $row['tag'], true );
		if ( false === $cipher || false === $iv || false === $tag || 12 !== strlen( $iv ) || 16 !== strlen( $tag ) ) {
			return new WP_Error( 'lsch_note_cipher_invalid', __( 'The encrypted private-note record failed structural validation.', 'learn-sabri-classical-homeopathy' ) );
		}
		$plain = openssl_decrypt( $cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad );
		if ( false === $plain ) {
			return new WP_Error( 'lsch_note_authentication_failed', __( 'The private note could not be authenticated with its recorded key generation.', 'learn-sabri-classical-homeopathy' ) );
		}
		return $plain;
	}

	public static function decrypt_note( array $row, $user_id, $lesson_id ) {
		$result = self::decrypt_note_checked( $row, $user_id, $lesson_id );
		return is_wp_error( $result ) ? '' : $result;
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
		return hash( 'sha256', absint( $user_id ) . '|' . sanitize_key( $action ) . '|' . $provided );
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
