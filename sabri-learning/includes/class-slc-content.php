<?php
defined( 'ABSPATH' ) || exit;

final class SLC_Content {
	const BOOK = 'slc_book';
	const LESSON = 'slc_lesson';
	const TOPIC = 'slc_topic';
	const LEVEL = 'slc_level';

	public static function topics() {
		return array(
			'foundations' => 'Foundations of Classical Homeopathy', 'organon-principles' => 'Organon and Principles', 'materia-medica' => 'Materia Medica', 'repertory' => 'Repertory', 'case-taking' => 'Case Taking', 'symptom-analysis' => 'Symptom Analysis', 'homeopathy-philosophy' => 'Homeopathy Philosophy', 'miasms-chronic-diseases' => 'Miasms and Chronic Diseases', 'clinical-education' => 'Clinical Education', 'pathology' => 'Pathology', 'anatomy' => 'Anatomy', 'nutrition' => 'Nutrition', 'principles-hygiene' => 'Principles of Hygiene', 'islamic-spiritual-healing' => 'Islamic Spiritual Healing', 'research-methodology' => 'Research Methodology', 'patient-case-learning' => 'Patient Case Learning',
		);
	}

	public static function levels() { return array( 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'professional-reference' => 'Professional Reference' ); }

	public static function register() {
		register_post_type( self::BOOK, array( 'labels' => array( 'name' => 'Learning Books', 'singular_name' => 'Learning Book' ), 'public' => true, 'show_ui' => true, 'show_in_menu' => false, 'show_in_rest' => false, 'has_archive' => 'learning-books', 'rewrite' => array( 'slug' => 'learning-book', 'with_front' => false ), 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ), 'capability_type' => 'post', 'capabilities' => array( 'create_posts' => 'do_not_allow' ), 'map_meta_cap' => true ) );
		register_post_type( self::LESSON, array( 'labels' => array( 'name' => 'Learning Lessons', 'singular_name' => 'Learning Lesson' ), 'public' => true, 'show_ui' => true, 'show_in_menu' => false, 'show_in_rest' => false, 'has_archive' => 'classical-homeopathy-lessons', 'rewrite' => array( 'slug' => 'classical-homeopathy-lesson', 'with_front' => false ), 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions' ), 'taxonomies' => array( self::TOPIC, self::LEVEL ), 'capability_type' => 'post', 'capabilities' => array( 'create_posts' => 'do_not_allow' ), 'map_meta_cap' => true, 'delete_with_user' => false ) );
		register_taxonomy( self::TOPIC, array( self::LESSON ), array( 'labels' => array( 'name' => 'Learning Topics' ), 'public' => true, 'show_ui' => false, 'show_in_rest' => false, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'learning-topic' ) ) );
		register_taxonomy( self::LEVEL, array( self::LESSON ), array( 'labels' => array( 'name' => 'Learning Levels' ), 'public' => true, 'show_ui' => false, 'show_in_rest' => false, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'learning-level' ) ) );
	}

	public static function seed_terms() { foreach ( array( self::TOPIC => self::topics(), self::LEVEL => self::levels() ) as $tax => $items ) { foreach ( $items as $slug => $name ) { if ( ! get_term_by( 'slug', $slug, $tax ) ) { wp_insert_term( $name, $tax, array( 'slug' => $slug ) ); } } } }
	public static function allowed( $slug, $taxonomy ) { $items = self::TOPIC === $taxonomy ? self::topics() : self::levels(); return isset( $items[ sanitize_title( $slug ) ] ); }
	public static function term( $post_id, $taxonomy, $field = 'name' ) { $terms = get_the_terms( absint( $post_id ), $taxonomy ); return $terms && ! is_wp_error( $terms ) ? $terms[0]->{$field} : ''; }
	public static function assign( $post_id, $slug, $taxonomy ) { self::seed_terms(); $term = get_term_by( 'slug', $slug, $taxonomy ); return $term && ! is_wp_error( wp_set_object_terms( $post_id, array( (int) $term->term_id ), $taxonomy, false ) ); }
}

