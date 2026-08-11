<?php
/** Durable, privacy-minimized idempotency gate for File 05 mutating REST requests. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Idempotency {
	private static $active = false;
	private static $key_hash = '';
	private static $request_hash = '';
	private static $lock_name = '';
	private static $route = '';
	private static $transaction_open = false;

	public static function hooks() {
		/* These hooks execute after route permission callbacks, preventing denied callers from filling the replay ledger. */
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'before_callbacks' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'after_callbacks' ), 10, 3 );
		add_action( 'shutdown', array( __CLASS__, 'release' ), 0 );
	}

	private static function is_mutation( WP_REST_Request $request ) {
		$route = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );
		return 0 === strpos( $route, '/learn-sabri-classical-homeopathy/v2/' )
			&& ! in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true );
	}

	private static function normalized( $value ) {
		if ( is_array( $value ) ) {
			if ( $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
				ksort( $value );
			}
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::normalized( $item );
			}
		}
		return $value;
	}

	private static function request_hash( WP_REST_Request $request ) {
		$params = (array) $request->get_params();
		unset( $params['_wpnonce'], $params['_locale'] );
		$payload = array(
			'method' => strtoupper( (string) $request->get_method() ),
			'route'  => (string) $request->get_route(),
			'params' => self::normalized( $params ),
		);
		$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
		if ( false === $json ) {
			return new WP_Error( 'lsch_idempotency_request_encoding', __( 'The protected request could not be normalized safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		return hash( 'sha256', $json );
	}

	private static function response_reference( $response ) {
		if ( is_wp_error( $response ) ) {
			return array( 'error_code' => sanitize_key( (string) $response->get_error_code() ) );
		}
		$response = rest_ensure_response( $response );
		if ( ! $response instanceof WP_REST_Response ) {
			return array();
		}
		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			return array();
		}
		$allowed = array( 'id', 'public_id', 'status', 'version', 'object_version', 'active', 'reset', 'valid', 'state', 'score' );
		$out = array();
		foreach ( $allowed as $key ) {
			if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
				$out[ $key ] = $data[ $key ];
			}
		}
		return $out;
	}

	private static function response_status( $response ) {
		if ( is_wp_error( $response ) ) {
			$data = $response->get_error_data();
			return is_array( $data ) && ! empty( $data['status'] ) ? absint( $data['status'] ) : 500;
		}
		$response = rest_ensure_response( $response );
		return $response instanceof WP_REST_Response ? absint( $response->get_status() ) : 200;
	}

	private static function gc() {
		if ( get_transient( 'lsch_idempotency_gc_v1' ) ) {
			return;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$t['request_keys']} WHERE expires_at < %s LIMIT 500", LSCH_Database::now() ) );
		set_transient( 'lsch_idempotency_gc_v1', 1, HOUR_IN_SECONDS );
	}

	public static function before_callbacks( $result, $handler, $request ) {
		unset( $handler );
		if ( null !== $result || ! $request instanceof WP_REST_Request || ! self::is_mutation( $request ) ) {
			return $result;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'lsch_authentication_required', __( 'Sign in before using this protected learning action.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 401 ) );
		}

		$route = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );
		$rate_bucket = 'rest_mutation_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 24 );
		if ( ! LSCH_Policy::rate_limit( $rate_bucket, (string) $user_id, 60, 60 ) ) {
			return new WP_Error( 'lsch_rate_limited', __( 'Please wait before trying this protected learning action again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 429, 'trace_id' => LSCH_Policy::request_id() ) );
		}
		$action = 'rest_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 32 );
		$key_hash = LSCH_Policy::idempotency_key( $request->get_header( 'Idempotency-Key' ), $user_id, $action );
		if ( is_wp_error( $key_hash ) ) {
			return $key_hash;
		}
		$request_hash = self::request_hash( $request );
		if ( is_wp_error( $request_hash ) ) {
			return $request_hash;
		}
		$lock_name = 'lsch:idem:' . substr( $key_hash, 0, 48 );

		global $wpdb;
		$t = LSCH_Database::tables();
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock_name ) );
		if ( 1 !== $locked ) {
			return new WP_Error( 'lsch_idempotency_busy', __( 'This request is already being processed. Retry with the same idempotency key.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}

		self::$lock_name = $lock_name;
		self::gc();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['request_keys']} WHERE key_hash=%s LIMIT 1", $key_hash ), ARRAY_A );
		if ( $row ) {
			if ( ! hash_equals( (string) $row['request_hash'], $request_hash ) ) {
				self::release();
				return new WP_Error( 'lsch_idempotency_payload_conflict', __( 'This idempotency key was already used for a different request.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
			}
			if ( 'completed' === $row['status'] ) {
				$reference = json_decode( (string) $row['response_ref_json'], true );
				$reference = is_array( $reference ) ? $reference : array();
				$status = absint( $row['response_status'] ) ?: 200;
				self::release();
				if ( ! empty( $reference['error_code'] ) ) {
					return new WP_Error( sanitize_key( $reference['error_code'] ), __( 'The original protected action returned an error; this is its idempotent replay.', 'learn-sabri-classical-homeopathy' ), array( 'status' => $status, 'idempotent_replay' => true ) );
				}
				$reference['idempotent_replay'] = true;
				$response = new WP_REST_Response( $reference, $status );
				$response->header( 'X-Idempotent-Replay', 'true' );
				$response->header( 'Cache-Control', 'private, no-store' );
				return $response;
			}
			self::release();
			return new WP_Error( 'lsch_idempotency_incomplete', __( 'A previous attempt with this key did not reach a recorded completion. Use System Check before retrying with a new key.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}

		$now = LSCH_Database::now();
		$expires = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$inserted = $wpdb->insert(
			$t['request_keys'],
			array(
				'key_hash'          => $key_hash,
				'user_id'           => $user_id,
				'route'             => substr( $route, 0, 190 ),
				'method'            => substr( $method, 0, 10 ),
				'request_hash'      => $request_hash,
				'status'            => 'processing',
				'response_status'   => 0,
				'response_ref_json' => '{}',
				'created_at'        => $now,
				'updated_at'        => $now,
				'expires_at'        => $expires,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( 1 !== $inserted ) {
			self::release();
			return new WP_Error( 'lsch_idempotency_record_failed', __( 'The protected action could not establish its replay guard.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}

		LSCH_Events::reset_request_integrity();
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => 503, 'response_ref_json' => wp_json_encode( array( 'error_code' => 'lsch_transaction_unavailable' ) ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => $key_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s' ) );
			self::release();
			return new WP_Error( 'lsch_transaction_unavailable', __( 'The protected learning transaction could not start safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id() ) );
		}
		self::$transaction_open = true;
		self::$active = true;
		self::$key_hash = $key_hash;
		self::$request_hash = $request_hash;
		self::$route = $route;
		return null;
	}

	public static function after_callbacks( $response, $handler, $request ) {
		unset( $handler );
		if ( ! self::$active || ! $request instanceof WP_REST_Request || self::$route !== (string) $request->get_route() ) { return $response; }
		global $wpdb; $t = LSCH_Database::tables();
		$status = self::response_status( $response );
		$integrity_error = LSCH_Events::request_integrity_error();
		$failed = is_wp_error( $response ) || $status >= 400 || '' !== $integrity_error;
		if ( $failed ) {
			if ( self::$transaction_open ) { $wpdb->query( 'ROLLBACK' ); self::$transaction_open = false; }
			if ( '' !== $integrity_error && $status < 400 ) { $response = new WP_Error( 'lsch_transaction_integrity_failed', __( 'The protected action was rolled back because its required audit/event evidence could not be persisted.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id(), 'reason' => $integrity_error ) ); $status = 503; }
			$reference = self::response_reference( $response );
			$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => $status, 'response_ref_json' => wp_json_encode( $reference ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash, 'request_hash' => self::$request_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s', '%s' ) );
			self::release();
			return $response;
		}
		$reference = self::response_reference( $response );
		$updated = $wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => $status, 'response_ref_json' => wp_json_encode( $reference ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash, 'request_hash' => self::$request_hash, 'status' => 'processing' ), array( '%s', '%d', '%s', '%s' ), array( '%s', '%s', '%s' ) );
		if ( 1 !== $updated || false === $wpdb->query( 'COMMIT' ) ) {
			if ( self::$transaction_open ) { $wpdb->query( 'ROLLBACK' ); }
			self::$transaction_open = false;
			$response = new WP_Error( 'lsch_transaction_commit_failed', __( 'The protected learning action could not be committed safely.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503, 'trace_id' => LSCH_Policy::request_id() ) );
			$wpdb->update( $t['request_keys'], array( 'status' => 'completed', 'response_status' => 503, 'response_ref_json' => wp_json_encode( array( 'error_code' => 'lsch_transaction_commit_failed' ) ), 'updated_at' => LSCH_Database::now() ), array( 'key_hash' => self::$key_hash ), array( '%s', '%d', '%s', '%s' ), array( '%s' ) );
			self::release();
			return $response;
		}
		self::$transaction_open = false;
		self::release();
		return $response;
	}

	public static function release() {
		if ( self::$transaction_open ) { global $wpdb; $wpdb->query( 'ROLLBACK' ); self::$transaction_open = false; }
		if ( self::$lock_name ) {
			global $wpdb;
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::$lock_name ) );
		}
		self::$active = false;
		self::$key_hash = '';
		self::$request_hash = '';
		self::$lock_name = '';
		self::$route = '';
	}
}
