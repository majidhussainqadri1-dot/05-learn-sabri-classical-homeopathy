<?php
/**
 * Continuous-value, File 26 discovery, citations, download and AI-context
 * contracts for File 05. No foreign domain truth is copied.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_Value {
	public static function hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'sabri_file26_domain_providers', array( __CLASS__, 'file26_provider' ) );
		add_filter( 'sabri_ai_context_providers', array( __CLASS__, 'ai_context_provider' ) );
		add_filter( 'sabri_download_providers', array( __CLASS__, 'download_provider' ) );
		add_filter( 'lsch_learning_capabilities', array( __CLASS__, 'capabilities' ) );
	}

	public static function routes() {
		register_rest_route(
			LSCH_REST::NS,
			'/citation/(?P<id>\d+)/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_citation_export' ),
				'permission_callback' => array( __CLASS__, 'can_read_route_object' ),
				'args'                => array(
					'format' => array(
						'default'           => 'text',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/learning-record/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_learning_record_export' ),
				'permission_callback' => static function() {
					return LSCH_Policy::can_use_learning_actions();
				},
			)
		);
		register_rest_route(
			LSCH_REST::NS,
			'/value/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_value_summary' ),
				'permission_callback' => static function() {
					$user_id = get_current_user_id();
					return $user_id && user_can( $user_id, LSCH_Capabilities::VIEW_ANALYTICS );
				},
			)
		);
	}

	public static function capabilities( $caps ) {
		$caps = (array) $caps;
		$caps['one_roof_student_journey'] = true;
		$caps['advanced_learning_discovery'] = true;
		$caps['citation_exports'] = array( 'text', 'apa', 'vancouver', 'bibtex', 'ris' );
		$caps['correction_trail'] = true;
		$caps['healthy_use'] = array( 'reminders' => 'opt-in', 'shame_or_streak_pressure' => false );
		$caps['low_bandwidth'] = array( 'text_first' => true, 'conditional_assets' => true );
		$caps['accessibility'] = array( 'rtl' => true, 'keyboard' => true, 'reduced_motion' => true, 'zoom' => true );
		$caps['ai_governance'] = array( 'source_context' => true, 'diagnosis_authority' => false, 'prescription_authority' => false );
		$caps['access_model'] = LSCH_Policy::access_model();
		return $caps;
	}

	public static function file26_provider( $providers ) {
		$providers = (array) $providers;
		$providers['file05-learning.v3'] = array(
			'owner'             => 'file05',
			'provider_version'  => LSCH_VERSION,
			'scope'             => 'learning-catalog-projection',
			'global_rank_owner' => 'file26',
			'query_endpoint'    => rest_url( LSCH_REST::NS . '/catalog' ),
			'public_types'      => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ),
			'visibility_recheck'=> array( 'LSCH_Policy', 'can_read_post' ),
			'freshness'         => 'owner-version',
			'why_metadata'      => true,
			'no_copied_truth'   => true,
		);
		return $providers;
	}

	public static function ai_context_provider( $providers ) {
		$providers = (array) $providers;
		$providers['file05-learning.v2'] = array(
			'owner'                  => 'file05',
			'resolver'               => array( __CLASS__, 'resolve_ai_context' ),
			'allowed_types'          => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ),
			'current_acl_required'   => true,
			'source_grounded'        => true,
			'diagnosis_authority'    => false,
			'prescription_authority' => false,
			'emergency_replacement'  => false,
		);
		return $providers;
	}

	public static function download_provider( $providers ) {
		$providers = (array) $providers;
		$providers['file05-learning-record.v2'] = array(
			'owner'      => 'file05',
			'label'      => __( 'Learning record export', 'learn-sabri-classical-homeopathy' ),
			'resolver'   => array( __CLASS__, 'learning_record_export' ),
			'permission' => array( 'LSCH_Policy', 'can_use_learning_actions' ),
			'privacy'    => 'account-owned',
			'format'     => 'json',
		);
		return $providers;
	}

	public static function can_read_route_object( WP_REST_Request $request ) {
		$id = absint( $request['id'] );
		return $id && LSCH_Policy::can_read_post( $id );
	}

	public static function resolve_ai_context( $object_type, $object_id, $viewer_id = 0 ) {
		$object_id = absint( $object_id );
		$viewer_id = absint( $viewer_id );
		if ( LSCH_Content::object_type( $object_id ) !== $object_type || ! LSCH_Policy::can_read_post( $object_id, $viewer_id ) ) {
			return null;
		}
		return array(
			'owner'       => 'file05',
			'type'        => $object_type,
			'id'          => $object_id,
			'title'       => get_the_title( $object_id ),
			'url'         => get_permalink( $object_id ),
			'version'     => LSCH_Content::version( $object_id ),
			'objectives'  => (string) get_post_meta( $object_id, '_lsch_objectives', true ),
			'sources'     => self::sources( $object_id ),
			'safety'      => (string) get_post_meta( $object_id, '_lsch_safety', true ),
			'constraints' => array(
				'education_only'       => true,
				'no_diagnosis'         => true,
				'no_prescription'      => true,
				'no_emergency_replace' => true,
			),
		);
	}

	public static function sources( $object_id ) {
		$raw = trim( (string) get_post_meta( absint( $object_id ), '_lsch_sources', true ) );
		if ( '' === $raw ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$out = array();
			foreach ( array_slice( $decoded, 0, 100 ) as $source ) {
				if ( is_array( $source ) ) {
					$out[] = self::normalize_source( $source );
				} elseif ( is_scalar( $source ) ) {
					$out[] = self::normalize_source( array( 'title' => (string) $source ) );
				}
			}
			return array_values( array_filter( $out ) );
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$out = array();
		foreach ( array_slice( (array) $lines, 0, 100 ) as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' !== $line ) {
				$out[] = self::normalize_source( array( 'title' => $line ) );
			}
		}
		return array_values( array_filter( $out ) );
	}

	private static function normalize_source( array $source ) {
		$out = array(
			'author' => sanitize_text_field( (string) ( $source['author'] ?? '' ) ),
			'title'  => sanitize_text_field( (string) ( $source['title'] ?? '' ) ),
			'year'   => preg_replace( '/[^0-9]/', '', (string) ( $source['year'] ?? '' ) ),
			'edition'=> sanitize_text_field( (string) ( $source['edition'] ?? '' ) ),
			'pages'  => sanitize_text_field( (string) ( $source['pages'] ?? '' ) ),
			'doi'    => sanitize_text_field( (string) ( $source['doi'] ?? '' ) ),
			'url'    => isset( $source['url'] ) ? esc_url_raw( (string) $source['url'] ) : '',
		);
		return '' === $out['title'] && '' === $out['author'] ? array() : $out;
	}

	public static function rest_citation_export( WP_REST_Request $request ) {
		$id = absint( $request['id'] );
		$format = sanitize_key( (string) $request->get_param( 'format' ) );
		$result = self::citation_export( $id, $format );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		LSCH_State::record_value( 'LearningCitationExported.v1', LSCH_Content::object_type( $id ), $id, array( 'format' => $format, 'source_count' => count( self::sources( $id ) ) ), get_current_user_id() );
		return rest_ensure_response( $result );
	}

	public static function citation_export( $object_id, $format = 'text' ) {
		$object_id = absint( $object_id );
		$format = sanitize_key( $format );
		if ( ! in_array( $format, array( 'text', 'apa', 'vancouver', 'bibtex', 'ris' ), true ) ) {
			return new WP_Error( 'lsch_citation_format', __( 'Unsupported citation format.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
		}
		$sources = self::sources( $object_id );
		$lines = array();
		foreach ( $sources as $index => $source ) {
			$lines[] = self::format_source( $source, $format, $index + 1 );
		}
		return array(
			'object_id'    => $object_id,
			'object_title' => get_the_title( $object_id ),
			'format'       => $format,
			'count'        => count( $lines ),
			'content'      => implode( "\n", $lines ),
		);
	}

	private static function format_source( array $s, $format, $index ) {
		$author = $s['author'] ?: 'Unknown author';
		$title = $s['title'] ?: 'Untitled source';
		$year = $s['year'] ?: 'n.d.';
		$extra = trim( implode( ', ', array_filter( array( $s['edition'], $s['pages'], $s['doi'], $s['url'] ) ) ) );

		if ( 'apa' === $format ) {
			return trim( $author . ' (' . $year . '). ' . $title . ( $extra ? '. ' . $extra : '' ) . '.' );
		}
		if ( 'vancouver' === $format ) {
			return $index . '. ' . trim( $author . '. ' . $title . '. ' . $year . ( $extra ? '; ' . $extra : '' ) . '.' );
		}
		if ( 'bibtex' === $format ) {
			$key = 'file05_' . $index . '_' . preg_replace( '/[^A-Za-z0-9]+/', '', substr( $author, 0, 24 ) );
			return "@misc{{$key},\n  author = {" . $author . "},\n  title = {" . $title . "},\n  year = {" . $year . "}\n}";
		}
		if ( 'ris' === $format ) {
			return "TY  - GEN\nAU  - {$author}\nTI  - {$title}\nPY  - {$year}\nER  -";
		}
		return trim( $author . ' — ' . $title . ' — ' . $year . ( $extra ? ' — ' . $extra : '' ) );
	}

	public static function rest_learning_record_export() {
		return rest_ensure_response( self::learning_record_export( get_current_user_id() ) );
	}

	public static function learning_record_export( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id || $user_id !== get_current_user_id() || ! LSCH_Policy::can_use_learning_actions( $user_id ) ) {
			return new WP_Error( 'lsch_export_forbidden', __( 'Learning record export is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		return array(
			'schema'       => 'file05-learning-record.v2',
			'generated_at' => gmdate( 'c' ),
			'user_id'      => $user_id,
			'access_model' => LSCH_Policy::access_model(),
			'records'      => LSCH_Services::dashboard( $user_id ),
		);
	}

	public static function rest_value_summary() {
		global $wpdb;
		$t = LSCH_State::tables();
		$rows = $wpdb->get_results(
			"SELECT event_name,COUNT(*) AS event_count,MAX(created_at) AS latest_at FROM {$t['value_events']} GROUP BY event_name ORDER BY event_count DESC LIMIT 100",
			ARRAY_A
		);
		return rest_ensure_response( array( 'privacy' => 'aggregate-only', 'events' => $rows ) );
	}
}
