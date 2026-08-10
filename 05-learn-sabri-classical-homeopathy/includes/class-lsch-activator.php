<?php
/** Safe activation, migration checkpoints, managed pages and rollback metadata. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Activator {
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_site();
				restore_current_blog();
			}
			return;
		}
		self::activate_site();
	}

	private static function activate_site() {
		$preflight = LSCH_Dependencies::activation_preflight();
		if ( is_wp_error( $preflight ) ) {
			deactivate_plugins( LSCH_BASENAME );
			wp_die( esc_html( $preflight->get_error_message() ), esc_html__( 'File 05 activation blocked', 'learn-sabri-classical-homeopathy' ), array( 'back_link' => true ) );
		}

		$checkpoint = array(
			'started_at'              => gmdate( 'c' ),
			'previous_schema'         => (int) get_option( LSCH_Database::OPTION, 0 ),
			'previous_state_schema'   => (int) get_option( LSCH_State::OPTION, 0 ),
			'previous_future18_schema'=> (int) get_option( LSCH_Future18::OPTION, 0 ),
			'previous_version'        => (string) get_option( 'lsch_version', '' ),
			'created_pages'           => array(),
			'status'                  => 'started',
		);
		update_option( 'lsch_activation_checkpoint', $checkpoint, false );

		try {
			LSCH_Capabilities::remove_legacy_caps();
			LSCH_Capabilities::install();
			LSCH_Content::register();
			LSCH_Database::install();
			LSCH_State::install();
			LSCH_Future18::install();
			LSCH_Content::seed_vocabularies();
			LSCH_Content::seed_founder_book_slots();
			self::ensure_pages();
			LSCH_Operations::schedule();
			flush_rewrite_rules( false );

			update_option( 'lsch_version', LSCH_VERSION, false );
			update_option( 'lsch_plan_version', LSCH_PLAN_VERSION, false );
			update_option( 'lsch_access_model', LSCH_Policy::access_model(), false );

			$checkpoint['status'] = 'completed';
			$checkpoint['completed_at'] = gmdate( 'c' );
			update_option( 'lsch_activation_checkpoint', $checkpoint, false );
			LSCH_Events::audit(
				'plugin_activated',
				'system',
				'file05',
				array(
					'version'         => LSCH_VERSION,
					'schema'          => LSCH_SCHEMA_VERSION,
					'state_schema'    => LSCH_State::SCHEMA,
					'future18_schema' => LSCH_Future18::SCHEMA,
					'access_model'    => LSCH_Policy::access_model(),
				),
				'operations'
			);
		} catch ( Throwable $error ) {
			$checkpoint['status'] = 'failed';
			$checkpoint['error_class'] = get_class( $error );
			$checkpoint['failed_at'] = gmdate( 'c' );
			update_option( 'lsch_activation_checkpoint', $checkpoint, false );
			self::rollback_created_pages( $checkpoint );
			deactivate_plugins( LSCH_BASENAME );
			wp_die(
				esc_html__( 'File 05 activation failed safely. Review the activation checkpoint and System Check before retrying.', 'learn-sabri-classical-homeopathy' ),
				esc_html__( 'File 05 activation failed', 'learn-sabri-classical-homeopathy' ),
				array( 'back_link' => true )
			);
		}
	}

	public static function ensure_pages() {
		$definitions = array(
			'home' => array(
				'slug'    => 'learn',
				'title'   => __( 'Learn Sabri Classical Homeopathy', 'learn-sabri-classical-homeopathy' ),
				'content' => '[lsch_learning_home]',
			),
			'dashboard' => array(
				'slug'    => 'learn-dashboard',
				'title'   => __( 'My Learning', 'learn-sabri-classical-homeopathy' ),
				'content' => '[lsch_learning_dashboard]',
			),
			'mastery' => array(
				'slug'    => 'learn-mastery',
				'title'   => __( 'Mastery and Clinical Education Center', 'learn-sabri-classical-homeopathy' ),
				'content' => '[lsch_mastery_center]',
			),
		);
		$map = (array) get_option( 'lsch_page_map', array() );
		$checkpoint = (array) get_option( 'lsch_activation_checkpoint', array() );
		$foundation = (array) get_option( 'spf_page_map', array() );

		if ( ! empty( $foundation['learn'] ) && 'page' === get_post_type( absint( $foundation['learn'] ) ) && 'trash' !== get_post_status( absint( $foundation['learn'] ) ) ) {
			$map['home'] = absint( $foundation['learn'] );
			unset( $definitions['home'] );
		}

		foreach ( $definitions as $key => $definition ) {
			$id = isset( $map[ $key ] ) ? absint( $map[ $key ] ) : 0;
			if ( $id && 'page' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) && 'file05-learning' === get_post_meta( $id, '_lsch_managed_owner', true ) ) {
				continue;
			}
			$conflict = get_page_by_path( $definition['slug'] );
			if ( $conflict && 'file05-learning' !== get_post_meta( $conflict->ID, '_lsch_managed_owner', true ) ) {
				$definition['slug'] .= '-file05';
			}
			$id = wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => $definition['title'],
					'post_name'      => $definition['slug'],
					'post_content'   => $definition['content'],
					'comment_status' => 'closed',
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				throw new RuntimeException( $id->get_error_message() );
			}
			update_post_meta( $id, '_lsch_managed_owner', 'file05-learning' );
			update_post_meta( $id, '_lsch_managed_key', $key );
			$map[ $key ] = $id;
			$checkpoint['created_pages'][] = $id;
		}
		update_option( 'lsch_page_map', $map, false );
		update_option( 'lsch_activation_checkpoint', $checkpoint, false );
		return $map;
	}

	private static function rollback_created_pages( array $checkpoint ) {
		foreach ( (array) ( $checkpoint['created_pages'] ?? array() ) as $page_id ) {
			if ( 'file05-learning' === get_post_meta( $page_id, '_lsch_managed_owner', true ) ) {
				wp_delete_post( absint( $page_id ), true );
			}
		}
	}

	public static function deactivate( $network_wide = false ) {
		unset( $network_wide );
		LSCH_Operations::unschedule();
		flush_rewrite_rules( false );
	}
}
