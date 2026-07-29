<?php
/** Plugin runtime wiring. */

defined( 'ABSPATH' ) || exit;

final class SLC_Plugin {
	public function run() {
		add_action( 'init', array( 'SLC_Content', 'register' ) );
		( new SLC_Publishing() )->hooks();
		( new SLC_Catalog() )->hooks();
		( new SLC_Learning() )->hooks();
		( new SLC_Comments() )->hooks();
		( new SLC_Admin() )->hooks();
		( new SLC_Privacy() )->hooks();
		( new SLC_SEO() )->hooks();
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'delete_post', array( $this, 'cleanup_lesson' ) );
		add_action( 'slc_daily_maintenance', array( 'SLC_Database', 'cleanup_orphans' ) );
		if ( ! wp_next_scheduled( 'slc_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'slc_daily_maintenance' );
		}
	}

	public function assets() {
		global $post;
		$foundation = (array) get_option( 'spf_page_map', array() );
		$managed    = (array) get_option( 'slc_page_map', array() );
		$ids        = array_merge( array_filter( array( isset( $foundation['learn'] ) ? $foundation['learn'] : 0 ) ), $managed );
		$needed     = is_singular( array( SLC_Content::BOOK, SLC_Content::LESSON ) ) || is_post_type_archive( array( SLC_Content::BOOK, SLC_Content::LESSON ) ) || ( $post instanceof WP_Post && ( in_array( $post->ID, array_map( 'absint', $ids ), true ) || has_shortcode( $post->post_content, 'slc_learning_home' ) || has_shortcode( $post->post_content, 'sabri_learning' ) || has_shortcode( $post->post_content, 'slc_my_learning' ) || has_shortcode( $post->post_content, 'slc_submit_lesson' ) ) );
		if ( ! $needed ) {
			return;
		}
		wp_enqueue_style( 'slc-learning', SLC_URL . 'assets/css/learning.css', array(), SLC_VERSION );
		wp_enqueue_style( 'slc-learning-extras', SLC_URL . 'assets/css/lesson-extras.css', array( 'slc-learning' ), SLC_VERSION );
		wp_enqueue_style( 'slc-learning-progress', SLC_URL . 'assets/css/progress.css', array( 'slc-learning' ), SLC_VERSION );
		wp_enqueue_script( 'slc-learning', SLC_URL . 'assets/js/learning.js', array(), SLC_VERSION, true );
		wp_localize_script( 'slc-learning', 'slcLearning', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'slc_learning' ), 'loginUrl' => wp_login_url( home_url( '/' ) ), 'strings' => array( 'failed' => __( 'The learning action failed.', 'sabri-learning' ), 'score' => __( 'Score', 'sabri-learning' ), 'correct' => __( 'Correct', 'sabri-learning' ), 'review' => __( 'Review', 'sabri-learning' ) ) ) );
	}

	public function admin_assets( $hook ) {
		if ( false !== strpos( $hook, 'sabri-learning' ) ) {
			wp_enqueue_style( 'slc-learning-admin', SLC_URL . 'assets/css/admin.css', array(), SLC_VERSION );
		}
	}

	public function cleanup_lesson( $post_id ) {
		if ( SLC_Content::LESSON !== get_post_type( $post_id ) ) {
			return;
		}
		global $wpdb;
		foreach ( array( 'progress', 'bookmarks', 'audit_log', 'consents', 'metrics' ) as $suffix ) {
			$wpdb->delete( $wpdb->prefix . 'slc_' . $suffix, array( 'lesson_id' => absint( $post_id ) ), array( '%d' ) );
		}
	}
}
