<?php
defined( 'ABSPATH' ) || exit;

final class SLC_Activator {
	public static function activate() {
		SLC_Content::register(); SLC_Content::seed_terms(); self::role(); self::tables(); self::pages(); self::books(); self::starter_lesson(); update_option( 'slc_version', SLC_VERSION, false ); set_transient( 'slc_activation_notice', '1', 120 ); flush_rewrite_rules();
	}
	public static function deactivate() { flush_rewrite_rules(); }
	private static function role() { $admin = get_role( 'administrator' ); if ( $admin ) { $admin->add_cap( 'manage_sabri_learning' ); } }
	private static function tables() {
		global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_progress (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,user_id bigint(20) unsigned NOT NULL,lesson_id bigint(20) unsigned NOT NULL,status varchar(20) NOT NULL DEFAULT 'started',score smallint(5) unsigned NOT NULL DEFAULT 0,updated_at datetime NOT NULL,PRIMARY KEY  (id),UNIQUE KEY user_lesson (user_id,lesson_id),KEY user_id (user_id)) {$charset};" );
		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_bookmarks (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,user_id bigint(20) unsigned NOT NULL,lesson_id bigint(20) unsigned NOT NULL,created_at datetime NOT NULL,PRIMARY KEY  (id),UNIQUE KEY user_lesson (user_id,lesson_id),KEY user_id (user_id)) {$charset};" );
		dbDelta( "CREATE TABLE {$wpdb->prefix}slc_audit_log (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,lesson_id bigint(20) unsigned NOT NULL,actor_id bigint(20) unsigned NOT NULL,action varchar(30) NOT NULL,note text NOT NULL,created_at datetime NOT NULL,PRIMARY KEY  (id),KEY lesson_id (lesson_id)) {$charset};" );
	}
	private static function pages() { $map = (array) get_option( 'slc_page_map', array() ); $map['progress'] = self::page( 'My Learning', 'my-learning', '[slc_my_learning]' ); $map['submit'] = self::page( 'Submit Learning Lesson', 'submit-learning-lesson', '[slc_submit_lesson]' ); update_option( 'slc_page_map', $map, false ); }
	private static function page( $title, $slug, $content ) { $page = get_page_by_path( $slug ); if ( $page instanceof WP_Post ) { return $page->ID; } $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content ), true ); if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_slc_managed_page', '1' ); return $id; } return 0; }
	private static function books() {
		$books = array(
			'Sabri Materia Medica — Third Expanded Edition' => 'An organized reference foundation for the study of homeopathic medicines and their characteristic symptom pictures.',
			'Sabri Nutrition Science' => 'A structured learning foundation for nutrition, dietary observation and responsible health education.',
			'Qawaneen-e-Sabri for Hygiene and Dietary Reform' => 'Principles of hygiene, preventive habits and responsible dietary reform.',
			'Sabri Anwar-e-Shifa: Islamic and Spiritual Healing' => 'Educational study of Islamic spiritual wellbeing, ethics and supportive practices without replacing medical care.',
			'Philosophy of Sabri Homeopathy' => 'A structured introduction to the philosophical framework and study principles of Sabri Homeopathy.',
			'Comprehensive Sabri Clinical Pathology and Experienced Homeopathic Medicines' => 'A clinical education framework connecting pathology study with cautious homeopathic learning.',
			'Manhaj-e-Sabri Homeopathic Repertory and Key to Use' => 'A learning guide for symptom hierarchy, repertory study and responsible case analysis.',
			'Dastoor-e-Daulat-o-Kamyabi' => 'A Founder publication catalog entry for ethical, intellectual and personal development study.',
		);
		foreach ( $books as $title => $desc ) { $existing = get_page_by_title( $title, OBJECT, SLC_Content::BOOK ); if ( ! $existing ) { $id = wp_insert_post( array( 'post_type' => SLC_Content::BOOK, 'post_status' => 'publish', 'post_title' => $title, 'post_excerpt' => $desc, 'post_content' => $desc . "\n\nThe chapter catalog and authorized reading material will be expanded in later approved files.", 'post_author' => SLC_Permissions::founder_id() ), true ); if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_slc_catalog_seed', '1' ); } } }
	}
	private static function starter_lesson() {
		$title = 'Samuel Hahnemann and the Foundation of Classical Homeopathy'; if ( get_page_by_title( $title, OBJECT, SLC_Content::LESSON ) ) { return; }
		$content = "Christian Friedrich Samuel Hahnemann (1755–1843) was a German physician and the founder of homeopathy. His work developed a systematic approach centered on careful observation, individualized symptom study and the principle commonly summarized as ‘like cures like.’\n\nThis introductory lesson provides historical orientation. It does not establish clinical effectiveness for any condition and should not be used as a personal treatment guide.";
		$id = wp_insert_post( array( 'post_type' => SLC_Content::LESSON, 'post_status' => 'publish', 'post_title' => $title, 'post_excerpt' => 'A responsible historical introduction to Samuel Hahnemann and the origins of classical homeopathy.', 'post_content' => $content, 'post_author' => SLC_Permissions::founder_id(), 'comment_status' => 'open' ), true );
		if ( ! is_wp_error( $id ) ) { SLC_Content::assign( $id, 'foundations', SLC_Content::TOPIC ); SLC_Content::assign( $id, 'beginner', SLC_Content::LEVEL ); update_post_meta( $id, '_slc_objectives', "Identify Samuel Hahnemann\nDescribe the historical setting of early homeopathy\nDistinguish historical study from personal medical advice" ); update_post_meta( $id, '_slc_terms', 'Samuel Hahnemann, classical homeopathy, historical foundations' ); update_post_meta( $id, '_slc_references', "Encyclopaedia Britannica: Samuel Hahnemann\nHahnemann, Organon of Medicine — historical source" ); update_post_meta( $id, '_slc_quiz', array( array( 'q' => 'Samuel Hahnemann is historically associated with founding which system?', 'o' => array( 'Homeopathy', 'Radiology', 'Dentistry' ), 'a' => 0, 'e' => 'Hahnemann is recognized as the founder of homeopathy.' ) ) ); }
	}
}

