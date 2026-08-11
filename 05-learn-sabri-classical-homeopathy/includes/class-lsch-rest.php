<?php
/** Versioned REST command/query contract. */
defined( 'ABSPATH' ) || exit;

final class LSCH_REST {
	const NS = 'learn-sabri-classical-homeopathy/v2';

	public function hooks() { add_action( 'rest_api_init', array( $this, 'register' ) ); add_filter( 'rest_post_dispatch', array( $this, 'trace_response' ), 20, 3 ); }

	public function trace_response( $response, $server, $request ) {
		unset( $server );
		if ( ! $request instanceof WP_REST_Request || 0 !== strpos( (string) $request->get_route(), '/' . self::NS . '/' ) ) { return $response; }
		$trace = LSCH_Policy::request_id();
		if ( is_wp_error( $response ) ) { $code = $response->get_error_code(); $data = $response->get_error_data( $code ); $data = is_array( $data ) ? $data : array(); $data['trace_id'] = $trace; $response->add_data( $data, $code ); return $response; }
		$response = rest_ensure_response( $response );
		if ( $response instanceof WP_REST_Response ) {
			$response->header( 'X-Request-ID', $trace );
			if ( $response->get_status() >= 400 ) { $data = $response->get_data(); if ( is_array( $data ) ) { if ( isset( $data['data'] ) && is_array( $data['data'] ) ) { $data['data']['trace_id'] = $trace; } else { $data['trace_id'] = $trace; } $response->set_data( $data ); } }
		}
		return $response;
	}

	public function register() {
		register_rest_route( self::NS, '/catalog', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'catalog' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::NS, '/lesson/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'lesson' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::NS, '/dashboard', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'dashboard' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/course/(?P<id>\d+)/enroll', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'enroll' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/course/(?P<id>\d+)/state', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'enrollment_state' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/course/(?P<id>\d+)/reminder', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'reminder' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/course/(?P<id>\d+)/analytics', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'analytics' ), 'permission_callback' => array( $this, 'teacher' ) ) );
		register_rest_route( self::NS, '/lesson/(?P<id>\d+)/progress', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'progress' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/lesson/(?P<id>\d+)/progress/reset', array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( $this, 'progress_reset' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/object/(?P<type>[a-z_]+)/(?P<id>\d+)/bookmark', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'bookmark' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/lesson/(?P<id>\d+)/note', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'note_get' ), 'permission_callback' => array( $this, 'approved' ) ),
			array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'note_save' ), 'permission_callback' => array( $this, 'approved' ) ),
		) );
		register_rest_route( self::NS, '/lesson/(?P<id>\d+)/consent', array(
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'consent_store' ), 'permission_callback' => array( $this, 'author_or_reviewer' ) ),
			array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( $this, 'consent_withdraw' ), 'permission_callback' => array( $this, 'author_or_reviewer' ) ),
		) );
		register_rest_route( self::NS, '/assessment/(?P<id>\d+)/start', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'assessment_start' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/assessment/(?P<id>\d+)/submit', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'assessment' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/assignment/(?P<id>\d+)/submit', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'assignment' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/submission/(?P<id>\d+)/grade', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'grade' ), 'permission_callback' => array( $this, 'assessor' ) ) );
		register_rest_route( self::NS, '/submission/(?P<id>\d+)/appeal', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'appeal' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( self::NS, '/object/(?P<type>[a-z_]+)/(?P<id>\d+)/related', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'related' ), 'permission_callback' => array( $this, 'manager' ) ) );
		register_rest_route( self::NS, '/staff', array(
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'staff_assign' ), 'permission_callback' => array( $this, 'manager' ) ),
			array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( $this, 'staff_remove' ), 'permission_callback' => array( $this, 'manager' ) ),
		) );
		register_rest_route( self::NS, '/system-check', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'system_check' ), 'permission_callback' => array( $this, 'operator' ) ) );
	}

	public function approved() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions(); }
	public function assessor() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::ASSESS ); }
	public function reviewer() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::REVIEW_LESSONS ); }
	public function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ); }
	public function teacher() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( current_user_can( LSCH_Capabilities::TEACH ) || current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ); }
	public function operator() { return is_user_logged_in() && current_user_can( LSCH_Capabilities::OPERATE ) && ( LSCH_Policy::can_use_protected_reads() || current_user_can( 'manage_options' ) ); }
	public function author_or_reviewer( WP_REST_Request $request ) { $id = absint( $request['id'] ); $user_id = get_current_user_id(); return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( LSCH_Policy::can_manage_object( $id, $user_id ) || ( current_user_can( LSCH_Capabilities::REVIEW_LESSONS ) && LSCH_Services::staff_scope_allows( $user_id, 'lesson', $id, 'reviewer' ) ) ); }

	private function guard_rate( $bucket, $limit = 60, $window = 60 ) {
		$subject = get_current_user_id() ?: ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'guest' );
		return LSCH_Policy::rate_limit( $bucket, $subject, $limit, $window ) ? true : new WP_Error( 'lsch_rate_limited', __( 'Please wait before trying again.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 429, 'trace_id' => LSCH_Policy::request_id() ) );
	}

	public function catalog( WP_REST_Request $request ) {
		$rate = $this->guard_rate( 'catalog', 120, 60 ); if ( is_wp_error( $rate ) ) { return $rate; }
		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		$types = array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON );
		$post_type = in_array( $type, $types, true ) ? $type : $types;
		$page = max( 1, absint( $request->get_param( 'page' ) ) ); $per = min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) );
		$args = array( 'post_type' => $post_type, 'post_status' => 'publish', 'paged' => $page, 'posts_per_page' => $per, 's' => sanitize_text_field( (string) $request->get_param( 'search' ) ), 'orderby' => 'date ID', 'order' => 'DESC' );
		$author = absint( $request->get_param( 'author' ) ); if ( $author ) { $args['author'] = $author; }
		$tax = array( 'relation' => 'AND' );
		foreach ( array( 'topic' => LSCH_Content::TOPIC, 'level' => LSCH_Content::LEVEL, 'competency' => LSCH_Content::COMPETENCY ) as $param => $taxonomy ) { $value = sanitize_title( (string) $request->get_param( $param ) ); if ( $value ) { $tax[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => array( $value ) ); } }
		if ( count( $tax ) > 1 ) { $args['tax_query'] = $tax; }
		$protected_viewer = LSCH_Policy::can_use_protected_reads();
		$meta = array( 'relation' => 'AND' );
		foreach ( array( 'book' => '_lsch_book_id', 'course' => '_lsch_course_id' ) as $param => $key ) { $value = absint( $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value, 'type' => 'NUMERIC' ); } }
		foreach ( array( 'language' => '_lsch_language', 'duration' => '_lsch_duration' ) as $param => $key ) { $value = sanitize_text_field( (string) $request->get_param( $param ) ); if ( $value ) { $meta[] = array( 'key' => $key, 'value' => $value ); } }
		$requested_access = sanitize_key( (string) $request->get_param( 'access' ) );
		if ( $protected_viewer && in_array( $requested_access, array( 'public', 'account', 'restricted' ), true ) ) {
			$meta[] = array( 'key' => '_lsch_access', 'value' => $requested_access );
		} elseif ( ! $protected_viewer ) {
			$meta[] = array( 'relation' => 'OR', array( 'key' => '_lsch_access', 'compare' => 'NOT EXISTS' ), array( 'key' => '_lsch_access', 'value' => 'public' ) );
		}
		if ( count( $meta ) > 1 ) { $args['meta_query'] = $meta; }
		$query = new WP_Query( $args ); $items = array();
		foreach ( $query->posts as $post ) {
			if ( ! LSCH_Policy::can_read_post( $post->ID ) ) { continue; }
			$items[] = array( 'id' => $post->ID, 'type' => $post->post_type, 'title' => get_the_title( $post ), 'summary' => get_the_excerpt( $post ), 'url' => get_permalink( $post ), 'access' => LSCH_Content::access( $post->ID ), 'version' => LSCH_Content::version( $post->ID ), 'duration' => (string) get_post_meta( $post->ID, '_lsch_duration', true ), 'language' => (string) get_post_meta( $post->ID, '_lsch_language', true ), 'levels' => wp_get_object_terms( $post->ID, LSCH_Content::LEVEL, array( 'fields' => 'names' ) ), 'topics' => wp_get_object_terms( $post->ID, LSCH_Content::TOPIC, array( 'fields' => 'names' ) ) );
		}
		$response = rest_ensure_response( array( 'items' => $items, 'page' => $page, 'pages' => (int) $query->max_num_pages, 'total' => count( $items ), 'access_model' => LSCH_Policy::access_model() ) );
		$response->header( 'Cache-Control', $protected_viewer ? 'private, no-store' : 'public, max-age=60, stale-while-revalidate=120' ); return $response;
	}

	public function lesson( WP_REST_Request $request ) {
		$id = absint( $request['id'] );
		if ( LSCH_Content::LESSON !== get_post_type( $id ) || ! LSCH_Policy::can_read_post( $id ) ) { return new WP_Error( 'lsch_not_found', __( 'Lesson not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 404 ) ); }
		$post = get_post( $id );
		$data = array( 'id' => $id, 'title' => get_the_title( $id ), 'summary' => get_the_excerpt( $id ), 'body_html' => apply_filters( 'the_content', $post->post_content ), 'objectives' => (string) get_post_meta( $id, '_lsch_objectives', true ), 'sources' => (string) get_post_meta( $id, '_lsch_sources', true ), 'key_terms' => (string) get_post_meta( $id, '_lsch_key_terms', true ), 'examples' => (string) get_post_meta( $id, '_lsch_examples', true ), 'safety' => (string) get_post_meta( $id, '_lsch_safety', true ), 'accessibility' => (string) get_post_meta( $id, '_lsch_accessibility', true ), 'format' => (string) get_post_meta( $id, '_lsch_format', true ), 'required_components' => json_decode( (string) get_post_meta( $id, '_lsch_required_components', true ), true ) ?: array( 'content' ), 'version' => LSCH_Content::version( $id ), 'access' => LSCH_Content::access( $id ), 'course_id' => absint( get_post_meta( $id, '_lsch_course_id', true ) ), 'book_id' => absint( get_post_meta( $id, '_lsch_book_id', true ) ), 'related' => LSCH_Operations::related_links( LSCH_Content::LESSON, $id ) );
		$response = rest_ensure_response( $data ); $response->header( 'Cache-Control', 'public' === LSCH_Content::access( $id ) ? 'public, max-age=120' : 'private, no-store' ); return $response;
	}

	public function dashboard() { $response = rest_ensure_response( LSCH_Services::dashboard( get_current_user_id() ) ); $response->header( 'Cache-Control', 'private, no-store' ); return $response; }
	public function enroll( WP_REST_Request $r ) { return LSCH_Services::enroll( absint( $r['id'] ), get_current_user_id(), $r->get_header( 'Idempotency-Key' ) ); }
	public function enrollment_state( WP_REST_Request $r ) { return LSCH_Services::change_enrollment_state( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'state' ), absint( $r->get_param( 'version' ) ) ); }
	public function reminder( WP_REST_Request $r ) { return LSCH_Services::set_reminder( absint( $r['id'] ), get_current_user_id(), (bool) $r->get_param( 'enabled' ), $r->get_param( 'cadence' ), (array) $r->get_param( 'quiet_hours' ) ); }
	public function analytics( WP_REST_Request $r ) { return LSCH_Services::course_analytics( absint( $r['id'] ) ); }
	public function progress( WP_REST_Request $r ) { return LSCH_Services::progress( absint( $r['id'] ), get_current_user_id(), (array) $r->get_json_params() ); }
	public function progress_reset( WP_REST_Request $r ) { return LSCH_Services::reset_progress( absint( $r['id'] ), get_current_user_id() ); }
	public function bookmark( WP_REST_Request $r ) { return LSCH_Services::toggle_bookmark( sanitize_key( $r['type'] ), absint( $r['id'] ), get_current_user_id() ); }
	public function note_get( WP_REST_Request $r ) { $response = rest_ensure_response( LSCH_Services::get_note( absint( $r['id'] ), get_current_user_id() ) ); $response->header( 'Cache-Control', 'private, no-store' ); return $response; }
	public function note_save( WP_REST_Request $r ) { return LSCH_Services::save_note( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'note' ), absint( $r->get_param( 'version' ) ) ); }
	public function consent_store( WP_REST_Request $r ) { return LSCH_Services::store_case_consent( absint( $r['id'] ), get_current_user_id(), (array) $r->get_json_params() ); }
	public function consent_withdraw( WP_REST_Request $r ) { return LSCH_Services::withdraw_case_consent( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'reason' ) ); }
	public function assessment_start( WP_REST_Request $r ) { return LSCH_Services::start_assessment( absint( $r['id'] ), get_current_user_id(), $r->get_header( 'Idempotency-Key' ) ); }
	public function assessment( WP_REST_Request $r ) { return LSCH_Services::submit_assessment( absint( $r['id'] ), get_current_user_id(), (array) $r->get_param( 'answers' ), $r->get_header( 'Idempotency-Key' ) ); }
	public function assignment( WP_REST_Request $r ) { return LSCH_Services::submit_assignment( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'body' ), (array) $r->get_param( 'attachments' ) ); }
	public function grade( WP_REST_Request $r ) { return LSCH_Services::grade_submission( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'score' ), $r->get_param( 'feedback' ), absint( $r->get_param( 'version' ) ) ); }
	public function appeal( WP_REST_Request $r ) { return LSCH_Services::appeal_submission( absint( $r['id'] ), get_current_user_id(), $r->get_param( 'reason' ), absint( $r->get_param( 'version' ) ) ); }
	public function related( WP_REST_Request $r ) { return LSCH_Services::upsert_related_link( sanitize_key( $r['type'] ), absint( $r['id'] ), (array) $r->get_json_params() ); }
	public function staff_assign( WP_REST_Request $r ) { return LSCH_Services::assign_staff( absint( $r->get_param( 'user_id' ) ), $r->get_param( 'object_type' ), absint( $r->get_param( 'object_id' ) ), $r->get_param( 'role' ), (array) $r->get_param( 'scope' ), $r->get_param( 'conflict_status' ) ?: 'clear' ); }
	public function staff_remove( WP_REST_Request $r ) { return LSCH_Services::remove_staff( absint( $r->get_param( 'user_id' ) ), $r->get_param( 'object_type' ), absint( $r->get_param( 'object_id' ) ), $r->get_param( 'role' ) ); }
	public function system_check() { return LSCH_Operations::system_check(); }
}
