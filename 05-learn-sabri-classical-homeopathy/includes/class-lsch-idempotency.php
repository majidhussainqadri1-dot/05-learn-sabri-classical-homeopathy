<?php
/** Durable, privacy-minimized idempotency gate for File 05 mutating REST requests. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Idempotency {
	private static $active = false;
	private static $key_hash = '';
	private static $request_hash = '';
	private static $lock_name = '';
	private static $route = '';

	public static function hooks() {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'pre_dispatch' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'post_dispatch' ), 10, 3 );
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
			if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
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
		return hash( 'sha256', wp_json_encode( $payload ) );
	}

	private static function response_reference( WP_REST_Response $response ) {
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

	private static function gc() {
		if ( get_transient( 'lsch_idempotency_gc_v1' ) ) {
			return;
		}
		global $wpdb;
		$t = LSCH_Database::tables();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$t['request_keys']} WHERE expires_at < %s LIMIT 500", LSCH_Database::now() ) );
		set_transient( 'lsch_idempotency_gc_v1', 1, HOUR_IN_SECONDS );
	}

	public static function pre_dispatch( $result, $server, $request ) {
		unset( $server );
		if ( null !== $result || ! $request instanceof WP_REST_Request || ! self::is_mutation( $request ) ) {
			return $result;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'lsch_authentication_required', __( 'Sign in before using this protected learning action.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 401 ) );
		}

		$route = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );
		$action = 'rest_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 32 );
		$key_hash = LSCH_Policy::idempotency_key( $request->get_header( 'Idempotency-Key' ), $user_id, $action );
		if ( is_wp_error( $key_hash ) ) {
			return $key_hash;
		}
		$request_hash = self::request_hash( $request );
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
				$reference['idempotent_replay'] = true;
				$response = new WP_REST_Response( $reference, absint( $row['response_status'] ) ?: 200 );
				$response->header( 'X-Idempotent-Replay', 'true' );
				$response->header( 'Cache-Control', 'private, no-store' );
				self::release();
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

		self::$active = true;
		self::$key_hash = $key_hash;
		self::$request_hash = $request_hash;
		self::$route = $route;
		return null;
	}

	public static function post_dispatch( $response, $server, $request ) {
		unset( $server );
		if ( ! self::$active || ! $request instanceof WP_REST_Request || self::$route !== (string) $request->get_route() ) {
			return $response;
		}
		$response = rest_ensure_response( $response );
		global $wpdb;
		$t = LSCH_Database::tables();
		$reference = self::response_reference( $response );
		$updated = $wpdb->update(
			$t['request_keys'],
			array(
				'status'            => 'completed',
				'response_status'   => $response->get_status(),
				'response_ref_json' => wp_json_encode( $reference ),
				'updated_at'        => LSCH_Database::now(),
			),
			array( 'key_hash' => self::$key_hash, 'request_hash' => self::$request_hash, 'status' => 'processing' ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%s', '%s', '%s' )
		);
		if ( 1 !== $updated ) {
			LSCH_Events::audit( 'rest_idempotency_finalize_failed', 'rest_request', 0, array( 'route_hash' => substr( hash( 'sha256', self::$route ), 0, 16 ) ), 'reliability' );
		}
		self::release();
		return $response;
	}

	public static function release() {
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
