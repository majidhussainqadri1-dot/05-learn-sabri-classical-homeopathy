<?php
/** Activation, migration, ownership, and rollback controls. */

defined( 'ABSPATH' ) || exit;

final class SLC_Activator {
	const STATE_OPTION = 'slc_activation_state';

	public static function activate() {
		$preflight = SLC_Dependencies::activation_preflight();
		if ( is_wp_error( $preflight ) ) {
			deactivate_plugins( plugin_basename( SLC_FILE ) );
			wp_die( esc_html( $preflight->get_error_message() ), esc_html__( 'File 05 activation stopped', 'sabri-learning' ), array( 'back_link' => true ) );
		}

		$created = array( 'pages' => array(), 'posts' => array() );
		update_option( self::STATE_OPTION, array( 'status' => 'running', 'started_at' => gmdate( 'c' ), 'version' => SLC_VERSION ), false );

		try {
			SLC_Content::register();
			SLC_Permissions::install_caps();
			SLC_Database::install();
			SLC_Content::seed_terms();
			self::pages( $created );
			self::books( $created );
			self::starter_lesson( $created );
			update_option( 'slc_version', SLC_VERSION, false );
			update_option( self::STATE_OPTION, array( 'status' => 'complete', 'completed_at' => gmdate( 'c' ), 'version' => SLC_VERSION ), false );
			set_transient( 'slc_activation_notice', '1', 120 );
			flush_rewrite_rules();
		} catch ( Throwable $error ) {
			self::rollback_created_content( $created );
			update_option(
				self::STATE_OPTION,
				array(
					'status'    => 'failed',
					'failed_at' => gmdate( 'c' ),
					'version'   => SLC_VERSION,
					'error'     => sanitize_text_field( $error->getMessage() ),
				),
				false
			);
			SLC_Dependencies::audit( 'activation_failed', array( 'error' => $error->getMessage() ) );
			deactivate_plugins( plugin_basename( SLC_FILE ) );
			wp_die( esc_html( $error->getMessage() ), esc_html__( 'File 05 activation rolled back', 'sabri-learning' ), array( 'back_link' => true ) );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'slc_daily_maintenance' );
		flush_rewrite_rules();
	}

	private static function pages( array &$created ) {
		$map = (array) get_option( 'slc_page_map', array() );
		$map['progress'] = self::managed_page( 'progress', __( 'My Learning', 'sabri-learning' ), 'my-learning', '[slc_my_learning]', $created );
		$map['submit']   = self::managed_page( 'submit', __( 'Submit Learning Lesson', 'sabri-learning' ), 'submit-learning-lesson', '[slc_submit_lesson]', $created );
		update_option( 'slc_page_map', $map, false );
	}

	private static function managed_page( $key, $title, $slug, $shortcode, array &$created ) {
		$map      = (array) get_option( 'slc_page_map', array() );
		$stored   = isset( $map[ $key ] ) ? absint( $map[ $key ] ) : 0;
		$existing = $stored ? get_post( $stored ) : null;

		if ( $existing instanceof WP_Post && 'page' === $existing->post_type && '1' === get_post_meta( $stored, '_slc_managed_page', true ) && $shortcode === trim( $existing->post_content ) ) {
			return $stored;
		}

		$conflict = get_page_by_path( $slug );
		if ( $conflict instanceof WP_Post && '1' !== get_post_meta( $conflict->ID, '_slc_managed_page', true ) ) {
			$slug .= '-file-05';
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $shortcode,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		update_post_meta( $id, '_slc_managed_page', '1' );
		update_post_meta( $id, '_slc_managed_page_key', sanitize_key( $key ) );
		$created['pages'][] = $id;
		return $id;
	}

	private static function books( array &$created ) {
		$founder = SLC_Permissions::founder_id();
		if ( ! $founder ) {
			throw new RuntimeException( __( 'The File 00 official Founder account is not available.', 'sabri-learning' ) );
		}

		$books = array(
			'Sabri Materia Medica — Third Expanded Edition' => 'An organized reference foundation for the study of homeopathic medicines and their characteristic symptom pictures.',
			'Sabri Nutrition Science' => 'A structured learning foundation for nutrition, dietary observation and responsible health education.',
			'Qawaneen-e-Sabri for Hygiene and Dietary Reform' => 'Principles of hygiene, preventive habits and responsible dietary reform.',
			'Sabri Anwar-e-Shifa: Islamic and Spiritual Healing' => 'Educational study of Islamic spiritual wellbeing, ethics and supportive practices.',
			'Philosophy of Sabri Homeopathy' => 'A structured introduction to the philosophical framework and study principles of Sabri Homeopathy.',
			'Comprehensive Sabri Clinical Pathology and Experienced Homeopathic Medicines' => 'A clinical education framework connecting pathology study with careful homeopathic learning.',
			'Manhaj-e-Sabri Homeopathic Repertory and Key to Use' => 'A learning guide for symptom hierarchy, repertory study and responsible case analysis.',
			'Dastoor-e-Daulat-o-Kamyabi' => 'A Founder publication catalog entry for ethical, intellectual and personal development study.',
		);

		foreach ( $books as $title => $description ) {
			$existing = get_page_by_title( $title, OBJECT, SLC_Content::BOOK );
			if ( $existing instanceof WP_Post ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => SLC_Content::BOOK,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => $description,
					'post_content' => $description . "\n\n" . __( 'The chapter catalog and authorized reading material will be expanded in later approved files.', 'sabri-learning' ),
					'post_author'  => $founder,
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				throw new RuntimeException( $id->get_error_message() );
			}
			update_post_meta( $id, '_slc_catalog_seed', '1' );
			$created['posts'][] = $id;
		}
	}

	private static function starter_lesson( array &$created ) {
		$title = 'Samuel Hahnemann and the Foundation of Classical Homeopathy';
		if ( get_page_by_title( $title, OBJECT, SLC_Content::LESSON ) ) {
			return;
		}
		$founder = SLC_Permissions::founder_id();
		$content = "Christian Friedrich Samuel Hahnemann (1755–1843) was a German physician and the founder of homeopathy. His work developed a systematic approach centered on careful observation, individualized symptom study, and the principle commonly summarized as ‘like cures like.’\n\nThis introductory lesson provides historical orientation and is not a personal treatment guide.";
		$id      = wp_insert_post(
			array(
				'post_type'      => SLC_Content::LESSON,
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_excerpt'   => 'A historical introduction to Samuel Hahnemann and the origins of classical homeopathy.',
				'post_content'   => $content,
				'post_author'    => $founder,
				'comment_status' => 'open',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		$created['posts'][] = $id;
		if ( ! SLC_Content::assign( $id, 'foundations', SLC_Content::TOPIC ) || ! SLC_Content::assign( $id, 'beginner', SLC_Content::LEVEL ) ) {
			throw new RuntimeException( __( 'The starter lesson classifications could not be assigned.', 'sabri-learning' ) );
		}
		update_post_meta( $id, '_slc_objectives', "Identify Samuel Hahnemann\nDescribe the historical setting of early homeopathy\nDistinguish historical study from personal medical advice" );
		update_post_meta( $id, '_slc_terms', 'Samuel Hahnemann, classical homeopathy, historical foundations' );
		update_post_meta( $id, '_slc_references', "Hahnemann, Organon of Medicine — historical source\nApproved institutional bibliography pending" );
		update_post_meta( $id, '_slc_quiz', array( array( 'q' => 'Samuel Hahnemann is historically associated with founding which system?', 'o' => array( 'Homeopathy', 'Radiology', 'Dentistry' ), 'a' => 0, 'e' => 'Hahnemann is historically recognized as the founder of homeopathy.' ) ) );
		update_post_meta( $id, '_slc_workflow_state', 'published' );
		update_post_meta( $id, '_slc_row_version', 1 );
	}

	private static function rollback_created_content( array $created ) {
		foreach ( array_merge( $created['posts'], $created['pages'] ) as $post_id ) {
			wp_delete_post( absint( $post_id ), true );
		}
	}
}
