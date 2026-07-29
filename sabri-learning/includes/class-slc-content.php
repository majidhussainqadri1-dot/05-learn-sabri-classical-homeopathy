<?php
/** Content types and controlled classification vocabulary. */

defined( 'ABSPATH' ) || exit;

final class SLC_Content {
	const BOOK   = 'slc_book';
	const LESSON = 'slc_lesson';
	const TOPIC  = 'slc_topic';
	const LEVEL  = 'slc_level';

	public static function topics() {
		return array(
			'foundations'               => __( 'Foundations of Classical Homeopathy', 'sabri-learning' ),
			'organon-principles'        => __( 'Organon and Principles', 'sabri-learning' ),
			'materia-medica'            => __( 'Materia Medica', 'sabri-learning' ),
			'repertory'                 => __( 'Repertory', 'sabri-learning' ),
			'case-taking'               => __( 'Case Taking', 'sabri-learning' ),
			'symptom-analysis'          => __( 'Symptom Analysis', 'sabri-learning' ),
			'homeopathy-philosophy'     => __( 'Homeopathy Philosophy', 'sabri-learning' ),
			'miasms-chronic-diseases'   => __( 'Miasms and Chronic Diseases', 'sabri-learning' ),
			'clinical-education'        => __( 'Clinical Education', 'sabri-learning' ),
			'pathology'                 => __( 'Pathology', 'sabri-learning' ),
			'anatomy'                   => __( 'Anatomy', 'sabri-learning' ),
			'nutrition'                 => __( 'Nutrition', 'sabri-learning' ),
			'principles-hygiene'        => __( 'Principles of Hygiene', 'sabri-learning' ),
			'islamic-spiritual-healing' => __( 'Islamic Spiritual Healing', 'sabri-learning' ),
			'research-methodology'      => __( 'Research Methodology', 'sabri-learning' ),
			'patient-case-learning'     => __( 'Patient Case Learning', 'sabri-learning' ),
		);
	}

	public static function levels() {
		return array(
			'beginner'              => __( 'Beginner', 'sabri-learning' ),
			'intermediate'          => __( 'Intermediate', 'sabri-learning' ),
			'advanced'              => __( 'Advanced', 'sabri-learning' ),
			'professional-reference' => __( 'Professional Reference', 'sabri-learning' ),
		);
	}

	public static function register() {
		register_post_type(
			self::BOOK,
			array(
				'labels'          => array( 'name' => __( 'Learning Books', 'sabri-learning' ), 'singular_name' => __( 'Learning Book', 'sabri-learning' ) ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'has_archive'     => 'learning-books',
				'rewrite'         => array( 'slug' => 'learning-book', 'with_front' => false ),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'page-attributes' ),
				'capability_type' => array( 'slc_book', 'slc_books' ),
				'capabilities'    => SLC_Permissions::post_type_caps( 'slc_book', 'slc_books' ),
				'map_meta_cap'    => true,
				'delete_with_user' => false,
			)
		);

		register_post_type(
			self::LESSON,
			array(
				'labels'          => array( 'name' => __( 'Learning Lessons', 'sabri-learning' ), 'singular_name' => __( 'Learning Lesson', 'sabri-learning' ) ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'has_archive'     => 'classical-homeopathy-lessons',
				'rewrite'         => array( 'slug' => 'classical-homeopathy-lesson', 'with_front' => false ),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions' ),
				'taxonomies'      => array( self::TOPIC, self::LEVEL ),
				'capability_type' => array( 'slc_lesson', 'slc_lessons' ),
				'capabilities'    => SLC_Permissions::post_type_caps( 'slc_lesson', 'slc_lessons' ),
				'map_meta_cap'    => true,
				'delete_with_user' => false,
			)
		);

		register_taxonomy( self::TOPIC, array( self::LESSON ), array( 'labels' => array( 'name' => __( 'Learning Topics', 'sabri-learning' ) ), 'public' => true, 'show_ui' => false, 'show_in_rest' => false, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'learning-topic' ) ) );
		register_taxonomy( self::LEVEL, array( self::LESSON ), array( 'labels' => array( 'name' => __( 'Learning Levels', 'sabri-learning' ) ), 'public' => true, 'show_ui' => false, 'show_in_rest' => false, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'learning-level' ) ) );
	}

	public static function seed_terms() {
		foreach ( array( self::TOPIC => self::topics(), self::LEVEL => self::levels() ) as $taxonomy => $items ) {
			foreach ( $items as $slug => $name ) {
				if ( ! get_term_by( 'slug', $slug, $taxonomy ) ) {
					$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
					if ( is_wp_error( $result ) ) {
						throw new RuntimeException( $result->get_error_message() );
					}
				}
			}
		}
	}

	public static function allowed( $slug, $taxonomy ) {
		$items = self::TOPIC === $taxonomy ? self::topics() : self::levels();
		return isset( $items[ sanitize_title( $slug ) ] );
	}

	public static function term( $post_id, $taxonomy, $field = 'name' ) {
		$terms = get_the_terms( absint( $post_id ), $taxonomy );
		return $terms && ! is_wp_error( $terms ) && isset( $terms[0]->{$field} ) ? $terms[0]->{$field} : '';
	}

	public static function assign( $post_id, $slug, $taxonomy ) {
		if ( ! self::allowed( $slug, $taxonomy ) ) {
			return false;
		}
		self::seed_terms();
		$term = get_term_by( 'slug', $slug, $taxonomy );
		return $term && ! is_wp_error( wp_set_object_terms( $post_id, array( (int) $term->term_id ), $taxonomy, false ) );
	}
}
