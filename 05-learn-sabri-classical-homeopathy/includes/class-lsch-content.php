<?php
/** Canonical curriculum content and vocabularies. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Content {
	const PROGRAM    = 'lsch_program';
	const COURSE     = 'lsch_course';
	const BOOK       = 'lsch_book';
	const LESSON     = 'lsch_lesson';
	const ASSESSMENT = 'lsch_assessment';
	const ASSIGNMENT = 'lsch_assignment';
	const COHORT     = 'lsch_cohort';
	const TOPIC      = 'lsch_topic';
	const LEVEL      = 'lsch_level';
	const COMPETENCY = 'lsch_competency';
	private static $version_bumped = array();

	public static function levels() {
		return array(
			'foundation'                => __( 'Foundation', 'learn-sabri-classical-homeopathy' ),
			'intermediate'              => __( 'Intermediate', 'learn-sabri-classical-homeopathy' ),
			'advanced'                  => __( 'Advanced', 'learn-sabri-classical-homeopathy' ),
			'research-clinical-mastery' => __( 'Research / Clinical Mastery', 'learn-sabri-classical-homeopathy' ),
		);
	}

	public static function topics() {
		return array(
			'foundations'               => __( 'Foundations of Classical Homeopathy', 'learn-sabri-classical-homeopathy' ),
			'organon-principles'        => __( 'Organon and Principles', 'learn-sabri-classical-homeopathy' ),
			'materia-medica'            => __( 'Materia Medica', 'learn-sabri-classical-homeopathy' ),
			'repertory'                 => __( 'Repertory', 'learn-sabri-classical-homeopathy' ),
			'case-taking'               => __( 'Case Taking', 'learn-sabri-classical-homeopathy' ),
			'symptom-analysis'          => __( 'Symptom Analysis', 'learn-sabri-classical-homeopathy' ),
			'homeopathy-philosophy'     => __( 'Homeopathy Philosophy', 'learn-sabri-classical-homeopathy' ),
			'miasms-chronic-diseases'   => __( 'Miasms and Chronic Diseases', 'learn-sabri-classical-homeopathy' ),
			'clinical-education'        => __( 'Clinical Education', 'learn-sabri-classical-homeopathy' ),
			'pathology'                 => __( 'Pathology', 'learn-sabri-classical-homeopathy' ),
			'anatomy'                   => __( 'Anatomy', 'learn-sabri-classical-homeopathy' ),
			'nutrition'                 => __( 'Nutrition', 'learn-sabri-classical-homeopathy' ),
			'principles-hygiene'        => __( 'Principles of Hygiene', 'learn-sabri-classical-homeopathy' ),
			'islamic-spiritual-healing' => __( 'Islamic Spiritual Healing', 'learn-sabri-classical-homeopathy' ),
			'research-methodology'      => __( 'Research Methodology', 'learn-sabri-classical-homeopathy' ),
			'patient-case-learning'     => __( 'Patient Case Learning', 'learn-sabri-classical-homeopathy' ),
		);
	}

	public static function register() {
		self::register_post_type( self::PROGRAM, __( 'Learning Programs', 'learn-sabri-classical-homeopathy' ), __( 'Learning Program', 'learn-sabri-classical-homeopathy' ), 'learn/program', array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ) );
		self::register_post_type( self::COURSE, __( 'Learning Courses', 'learn-sabri-classical-homeopathy' ), __( 'Learning Course', 'learn-sabri-classical-homeopathy' ), 'learn/course', array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'page-attributes' ) );
		self::register_post_type( self::BOOK, __( 'Founder Books', 'learn-sabri-classical-homeopathy' ), __( 'Founder Book', 'learn-sabri-classical-homeopathy' ), 'learn/book', array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'page-attributes' ) );
		self::register_post_type( self::LESSON, __( 'Learning Lessons', 'learn-sabri-classical-homeopathy' ), __( 'Learning Lesson', 'learn-sabri-classical-homeopathy' ), 'learn/lesson', array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions', 'page-attributes' ) );
		self::register_post_type( self::ASSESSMENT, __( 'Assessments', 'learn-sabri-classical-homeopathy' ), __( 'Assessment', 'learn-sabri-classical-homeopathy' ), 'learn/assessment', array( 'title', 'editor', 'author', 'revisions' ), false );
		self::register_post_type( self::ASSIGNMENT, __( 'Assignments', 'learn-sabri-classical-homeopathy' ), __( 'Assignment', 'learn-sabri-classical-homeopathy' ), 'learn/assignment', array( 'title', 'editor', 'author', 'revisions' ), false );
		self::register_post_type( self::COHORT, __( 'Learning Cohorts', 'learn-sabri-classical-homeopathy' ), __( 'Learning Cohort', 'learn-sabri-classical-homeopathy' ), 'learn/cohort', array( 'title', 'editor', 'author', 'revisions' ), false );

		register_taxonomy( self::TOPIC, array( self::PROGRAM, self::COURSE, self::BOOK, self::LESSON ), self::taxonomy_args( __( 'Learning Topics', 'learn-sabri-classical-homeopathy' ), 'learn/topic' ) );
		register_taxonomy( self::LEVEL, array( self::PROGRAM, self::COURSE, self::LESSON ), self::taxonomy_args( __( 'Learning Levels', 'learn-sabri-classical-homeopathy' ), 'learn/level' ) );
		register_taxonomy( self::COMPETENCY, array( self::PROGRAM, self::COURSE, self::LESSON, self::ASSESSMENT ), self::taxonomy_args( __( 'Competencies', 'learn-sabri-classical-homeopathy' ), 'learn/competency' ) );
		register_taxonomy_for_object_type( 'post_tag', self::LESSON );

		self::register_meta();
		add_action( 'post_updated', array( __CLASS__, 'bump_version_on_post_update' ), 20, 3 );
	}

	private static function register_post_type( $type, $plural, $singular, $slug, array $supports, $public = true ) {
		register_post_type(
			$type,
			array(
				'labels'              => array( 'name' => $plural, 'singular_name' => $singular ),
				'public'              => $public,
				'publicly_queryable'  => $public,
				'exclude_from_search' => ! $public,
				'show_ui'             => true,
				'show_in_menu'        => 'lsch-learning',
				'show_in_rest'        => false,
				'has_archive'         => $public,
				'rewrite'             => array( 'slug' => $slug, 'with_front' => false ),
				'supports'            => $supports,
				'map_meta_cap'        => true,
				'capability_type'     => array( $type, $type . 's' ),
				'capabilities'        => LSCH_Capabilities::post_type_caps( $type, $type . 's' ),
				'delete_with_user'    => false,
			)
		);
	}

	private static function taxonomy_args( $name, $slug ) {
		return array(
			'labels'       => array( 'name' => $name ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => false,
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => $slug, 'with_front' => false ),
		);
	}

	private static function register_meta() {
		$text = array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => array( __CLASS__, 'can_edit_meta' ) );
		$int  = array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => array( __CLASS__, 'can_edit_meta' ) );
		foreach (
			array(
				'_lsch_language', '_lsch_access', '_lsch_duration', '_lsch_status', '_lsch_version',
				'_lsch_reviewer', '_lsch_copyright', '_lsch_sources', '_lsch_objectives', '_lsch_key_terms',
				'_lsch_examples', '_lsch_safety', '_lsch_prerequisites', '_lsch_equivalence', '_lsch_rubric',
				'_lsch_blueprint', '_lsch_questions', '_lsch_correction_note', '_lsch_required_components',
				'_lsch_accessibility', '_lsch_format', '_lsch_certificate_wording_gate', '_lsch_randomize',
				'_lsch_consent_withdrawal_reason', '_lsch_chapter_map', '_lsch_source_edition',
				'_lsch_citation_style', '_lsch_low_bandwidth', '_lsch_lifelong_learning', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording',
			) as $key
		) {
			register_meta( 'post', $key, $text );
		}
		foreach ( array( '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_teacher_id', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_lesson_id', '_lsch_reviewer_id', '_lsch_cohort_id', '_lsch_certificate_wording_approved' ) as $key ) {
			register_meta( 'post', $key, $int );
		}
	}

	public static function can_edit_meta( $allowed, $meta_key, $post_id, $user_id ) {
		unset( $allowed, $meta_key );
		return LSCH_Policy::can_use_learning_actions( $user_id ) && user_can( $user_id, 'edit_post', $post_id ) && ( LSCH_Capabilities::can_author( $user_id ) || user_can( $user_id, LSCH_Capabilities::MANAGE_CURRICULUM ) );
	}

	public static function seed_vocabularies() {
		foreach ( array( self::LEVEL => self::levels(), self::TOPIC => self::topics() ) as $taxonomy => $values ) {
			foreach ( $values as $slug => $label ) {
				if ( ! get_term_by( 'slug', $slug, $taxonomy ) ) {
					$result = wp_insert_term( $label, $taxonomy, array( 'slug' => $slug ) );
					if ( is_wp_error( $result ) ) {
						throw new RuntimeException( $result->get_error_message() );
					}
				}
			}
		}
	}

	/**
	 * Eight non-public Founder-book slots avoid inventing titles while
	 * completing the governed catalog structure.
	 */
	public static function seed_founder_book_slots() {
		$existing = get_posts(
			array(
				'post_type'      => self::BOOK,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_lsch_founder_seed_slot',
				'no_found_rows'  => true,
			)
		);
		$used = array();
		foreach ( $existing as $id ) {
			$used[] = absint( get_post_meta( $id, '_lsch_founder_seed_slot', true ) );
		}
		$founder = self::founder_id();
		if ( ! $founder ) { return; }
		for ( $slot = 1; $slot <= 8; $slot++ ) {
			if ( in_array( $slot, $used, true ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => self::BOOK,
					'post_status'  => 'draft',
					'post_author'  => $founder,
					'post_title'   => sprintf( __( 'Founder Book Slot %02d — title pending approval', 'learn-sabri-classical-homeopathy' ), $slot ),
					'post_excerpt' => __( 'Governed catalog placeholder. It is never public until the Founder supplies and approves the actual bibliographic record.', 'learn-sabri-classical-homeopathy' ),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				throw new RuntimeException( $id->get_error_message() );
			}
			update_post_meta( $id, '_lsch_founder_seed_slot', $slot );
			update_post_meta( $id, '_lsch_status', 'draft' );
			update_post_meta( $id, '_lsch_version', '1' );
			update_post_meta( $id, '_lsch_access', 'restricted' );
		}
	}

	/**
	 * Founder identity is resolved only through a public provider hook and then
	 * validated by File 00. File 05 never queries File 00 private storage.
	 */
	private static function founder_id() {
		$user_id = absint( apply_filters( 'lsch_founder_user_id', 0 ) );
		if ( $user_id && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id ) ) {
			return $user_id;
		}
		return 0;
	}

	public static function bump_version( $post_id, $reason = 'content_update' ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! self::object_type( $post_id ) ) { return 0; }
		if ( isset( self::$version_bumped[ $post_id ] ) ) { return self::$version_bumped[ $post_id ]; }
		$raw = get_post_meta( $post_id, '_lsch_version', true );
		$next = '' === (string) $raw ? 1 : max( 1, absint( $raw ) ) + 1;
		update_post_meta( $post_id, '_lsch_version', $next );
		self::$version_bumped[ $post_id ] = $next;
		do_action( 'lsch_content_version_bumped', $post_id, $next, sanitize_key( $reason ) );
		return $next;
	}

	public static function bump_version_on_post_update( $post_id, $post_after, $post_before ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::object_type( $post_id ) ) { return; }
		foreach ( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_parent', 'menu_order' ) as $field ) {
			if ( (string) $post_after->$field !== (string) $post_before->$field ) { self::bump_version( $post_id, 'post_update' ); break; }
		}
	}

	public static function object_type( $post_id ) {
		$type = get_post_type( $post_id );
		return in_array( $type, array( self::PROGRAM, self::COURSE, self::BOOK, self::LESSON, self::ASSESSMENT, self::ASSIGNMENT, self::COHORT ), true ) ? $type : '';
	}

	public static function version( $post_id ) {
		return max( 1, absint( get_post_meta( $post_id, '_lsch_version', true ) ) );
	}

	public static function access( $post_id ) {
		$access = sanitize_key( (string) get_post_meta( $post_id, '_lsch_access', true ) );
		return in_array( $access, array( 'public', 'account', 'restricted' ), true ) ? $access : 'public';
	}
}
