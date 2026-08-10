<?php
/** Versioned REST surface for the File 05 Future-18 learning layer. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Future18_REST {
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	public function register() {
		$ns = LSCH_REST::NS;
		register_rest_route( $ns, '/future18/center', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'center' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/mastery', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'mastery' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/mastery/evidence', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'mastery_evidence' ), 'permission_callback' => array( $this, 'assessor_or_teacher' ) ) );
		register_rest_route( $ns, '/future18/review-queue', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'review_queue' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/review/(?P<id>\d+)/result', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'review_result' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/flashcards', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'flashcard' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/mistakes', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'mistakes' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/blueprint/(?P<lesson_id>\d+)', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'blueprint' ), 'permission_callback' => array( $this, 'manager' ) ) );

		$labs = array(
			'case-simulation' => LSCH_Future18::FEATURE_CASE_SIMULATION,
			'remedy-differentiation' => LSCH_Future18::FEATURE_REMEDY_DIFFERENTIATION,
			'case-taking' => LSCH_Future18::FEATURE_CASE_TAKING,
			'repertory-reasoning' => LSCH_Future18::FEATURE_REPERTORY_REASONING,
			'clinical-reasoning-map' => LSCH_Future18::FEATURE_REASONING_MAP,
			'viva' => LSCH_Future18::FEATURE_VIVA,
			'osce' => LSCH_Future18::FEATURE_OSCE,
			'evidence-appraisal' => LSCH_Future18::FEATURE_EVIDENCE_APPRAISAL,
		);
		foreach ( $labs as $slug => $mode ) {
			register_rest_route( $ns, '/future18/lab/' . $slug, array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => function( WP_REST_Request $request ) use ( $mode ) { return $this->practice( $request, $mode ); },
				'permission_callback' => array( $this, 'approved' ),
			) );
		}
		register_rest_route( $ns, '/future18/practice/(?P<id>\d+)/grade', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'practice_grade' ), 'permission_callback' => array( $this, 'assessor' ) ) );
		register_rest_route( $ns, '/future18/learning-path', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'learning_path' ), 'permission_callback' => array( $this, 'approved' ) ),
			array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'learning_path' ), 'permission_callback' => array( $this, 'approved' ) ),
		) );
		register_rest_route( $ns, '/future18/portfolio', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'portfolio' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/portfolio/(?P<id>\d+)/visibility', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'portfolio_visibility' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/mentorship', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'mentorships' ), 'permission_callback' => array( $this, 'approved_or_teacher' ) ),
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'mentorship_assign' ), 'permission_callback' => array( $this, 'manager' ) ),
		) );
		register_rest_route( $ns, '/future18/mentorship/(?P<id>\d+)/feedback', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'mentorship_feedback' ), 'permission_callback' => array( $this, 'teacher' ) ) );
		register_rest_route( $ns, '/future18/cpd', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'cpd_get' ), 'permission_callback' => array( $this, 'approved' ) ),
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'cpd_record' ), 'permission_callback' => array( $this, 'approved' ) ),
		) );
		register_rest_route( $ns, '/future18/cpd/(?P<id>\d+)/verify', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'cpd_verify' ), 'permission_callback' => array( $this, 'assessor_or_teacher' ) ) );
		register_rest_route( $ns, '/future18/tutor', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'tutor' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/change-impact', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'change_impacts' ), 'permission_callback' => array( $this, 'approved' ) ) );
		register_rest_route( $ns, '/future18/change-impact/(?P<id>\d+)/resolve', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'change_impact_resolve' ), 'permission_callback' => array( $this, 'approved' ) ) );
	}

	public function approved() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions(); }
	public function manager() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ); }
	public function teacher() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( current_user_can( LSCH_Capabilities::TEACH ) || current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ); }
	public function assessor() { return is_user_logged_in() && LSCH_Policy::can_use_learning_actions() && ( current_user_can( LSCH_Capabilities::ASSESS ) || current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ); }
	public function approved_or_teacher() { return $this->approved() || $this->teacher(); }
	public function assessor_or_teacher() { return $this->assessor() || $this->teacher(); }

	public function center() {
		$response = rest_ensure_response( LSCH_Future18::center_snapshot( get_current_user_id() ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function mastery() {
		$response = rest_ensure_response( LSCH_Future18::mastery_snapshot( get_current_user_id() ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function mastery_evidence( WP_REST_Request $request ) {
		$user_id = absint( $request->get_param( 'user_id' ) ?: get_current_user_id() );
		return LSCH_Future18::record_mastery_evidence_as_actor( get_current_user_id(), $user_id, $request->get_param( 'competency_key' ), $request->get_param( 'score' ), $request->get_param( 'weight' ) ?: 1, $request->get_param( 'source_type' ), $request->get_param( 'source_id' ) );
	}

	public function review_queue( WP_REST_Request $request ) {
		$response = rest_ensure_response( LSCH_Future18::review_queue( get_current_user_id(), $request->get_param( 'type' ), $request->get_param( 'limit' ) ?: 100 ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function review_result( WP_REST_Request $request ) {
		return LSCH_Future18::record_review_result( get_current_user_id(), absint( $request['id'] ), absint( $request->get_param( 'quality' ) ), absint( $request->get_param( 'version' ) ) );
	}

	public function flashcard( WP_REST_Request $request ) {
		return LSCH_Future18::create_flashcard( get_current_user_id(), (array) $request->get_json_params() );
	}

	public function mistakes( WP_REST_Request $request ) {
		$response = rest_ensure_response( LSCH_Future18::mistakes( get_current_user_id(), $request->get_param( 'limit' ) ?: 100 ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function blueprint( WP_REST_Request $request ) {
		return LSCH_Future18::set_blueprint( absint( $request['lesson_id'] ), $request->get_param( 'mode' ), (array) $request->get_param( 'blueprint' ) );
	}

	public function practice( WP_REST_Request $request, $mode ) {
		$params = (array) $request->get_json_params();
		return LSCH_Future18::submit_practice( get_current_user_id(), $mode, isset( $params['source_type'] ) ? $params['source_type'] : 'lesson', isset( $params['source_id'] ) ? $params['source_id'] : 0, isset( $params['response'] ) && is_array( $params['response'] ) ? $params['response'] : array() );
	}

	public function practice_grade( WP_REST_Request $request ) {
		return LSCH_Future18::grade_practice( absint( $request['id'] ), get_current_user_id(), $request->get_param( 'score' ), (array) $request->get_param( 'feedback' ), absint( $request->get_param( 'version' ) ) );
	}

	public function learning_path( WP_REST_Request $request ) {
		$goal = sanitize_key( (string) $request->get_param( 'goal' ) );
		$persist = ! in_array( strtoupper( (string) $request->get_method() ), array( 'GET', 'HEAD' ), true );
		$response = rest_ensure_response( LSCH_Future18::build_learning_path( get_current_user_id(), $goal ?: 'balanced_mastery', $persist ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function portfolio( WP_REST_Request $request ) {
		$response = rest_ensure_response( LSCH_Future18::portfolio( get_current_user_id(), $request->get_param( 'limit' ) ?: 200 ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function portfolio_visibility( WP_REST_Request $request ) {
		return LSCH_Future18::set_portfolio_visibility( get_current_user_id(), absint( $request['id'] ), $request->get_param( 'visibility' ), absint( $request->get_param( 'version' ) ) );
	}

	public function mentorship_assign( WP_REST_Request $request ) {
		return LSCH_Future18::assign_mentor( $request->get_param( 'mentor_id' ), $request->get_param( 'learner_id' ), $request->get_param( 'course_id' ), (array) $request->get_param( 'goals' ) );
	}

	public function mentorship_feedback( WP_REST_Request $request ) {
		return LSCH_Future18::mentor_feedback( get_current_user_id(), absint( $request['id'] ), (array) $request->get_param( 'feedback' ), absint( $request->get_param( 'version' ) ) );
	}

	public function mentorships() {
		$response = rest_ensure_response( LSCH_Future18::mentorships( get_current_user_id() ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function cpd_record( WP_REST_Request $request ) {
		return LSCH_Future18::record_cpd( get_current_user_id(), (array) $request->get_json_params() );
	}

	public function cpd_get() {
		$response = rest_ensure_response( LSCH_Future18::cpd_records( get_current_user_id() ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}


	public function cpd_verify( WP_REST_Request $request ) {
		return LSCH_Future18::verify_cpd( absint( $request['id'] ), get_current_user_id(), absint( $request->get_param( 'version' ) ) );
	}

	public function tutor( WP_REST_Request $request ) {
		return LSCH_Future18::socratic_tutor( get_current_user_id(), absint( $request->get_param( 'lesson_id' ) ), $request->get_param( 'question' ), $request->get_param( 'stage' ) );
	}

	public function change_impacts( WP_REST_Request $request ) {
		$response = rest_ensure_response( LSCH_Future18::impacts( get_current_user_id(), $request->get_param( 'status' ) ?: 'pending' ) );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public function change_impact_resolve( WP_REST_Request $request ) {
		return LSCH_Future18::resolve_impact( get_current_user_id(), absint( $request['id'] ) );
	}
}
