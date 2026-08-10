<?php
/** Module composition and cross-file adapters. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Plugin {
	public function run() {
		add_action( 'init', array( 'LSCH_Content', 'register' ), 5 );
		LSCH_Idempotency::hooks();
		( new LSCH_REST() )->hooks();
		LSCH_State::hooks();
		LSCH_Value::hooks();
		( new LSCH_Frontend() )->hooks();
		( new LSCH_Admin() )->hooks();
		( new LSCH_Privacy() )->hooks();
		( new LSCH_Operations() )->hooks();

		add_filter( 'sabri_platform_modules', array( $this, 'module_contract' ) );
		add_filter( 'sabri_composer_content_adapters', array( $this, 'composer_adapter' ) );
		add_filter( 'sabri_search_connectors', array( $this, 'search_connector' ) );
		add_filter( 'sabri_network_context_providers', array( $this, 'community_context_provider' ) );
		add_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20, 3 );
		add_action( 'transition_post_status', array( $this, 'status_transition' ), 20, 3 );
	}

	public function module_contract( $modules ) {
		$modules = (array) $modules;
		$modules['file05-learning'] = array(
			'version'        => LSCH_VERSION,
			'schema'         => LSCH_SCHEMA_VERSION,
			'plan'           => LSCH_PLAN_VERSION,
			'owner'          => 'learning',
			'routes'         => array( '/learn/', '/learn/program/{slug}', '/learn/course/{slug}', '/learn/lesson/{slug}', '/learn/dashboard', '/learn/assessment/{id}' ),
			'rest_namespace' => LSCH_REST::NS,
			'access_model'   => LSCH_Policy::access_model(),
			'policy_ready'   => LSCH_Policy::central_policy_ready(),
			'health'         => array( 'callable' => array( 'LSCH_Operations', 'system_check' ) ),
			'contracts'      => array(
				'file00' => 'SMC_Contracts::assertions >= 1.2.2',
				'file26' => 'global-search-discovery-ranking-owner',
			),
		);
		return $modules;
	}

	public function composer_adapter( $adapters ) {
		$adapters = (array) $adapters;
		$adapters['learning_lesson.v3'] = array(
			'label'           => __( 'Learning Lesson', 'learn-sabri-classical-homeopathy' ),
			'owner'           => 'file05',
			'version'         => '3.0',
			'capability'      => LSCH_Capabilities::PUBLISH_LESSONS,
			'post_type'       => LSCH_Content::LESSON,
			'required_fields' => array( 'title', 'content', 'objectives', 'sources', 'reviewer', 'version', 'accessibility', 'safety' ),
			'validate'        => array( $this, 'validate_composer_payload' ),
		);
		return $adapters;
	}

	public function validate_composer_payload( $payload, $context = array() ) {
		unset( $context );
		if ( ! LSCH_Policy::central_policy_ready() ) {
			return new WP_Error( 'lsch_governing_policy_unavailable', __( 'Learning publication is paused until the current central policy contract is available.', 'learn-sabri-classical-homeopathy' ) );
		}
		$required = array( 'title', 'content', 'objectives', 'sources', 'reviewer', 'accessibility', 'safety' );
		foreach ( $required as $field ) {
			if ( empty( $payload[ $field ] ) ) {
				return new WP_Error( 'lsch_composer_missing_' . $field, sprintf( __( 'Learning lesson field is required: %s.', 'learn-sabri-classical-homeopathy' ), $field ) );
			}
		}
		return true;
	}

	/**
	 * File 05 exposes a bounded learning-only projection. File 26 remains the
	 * canonical global search/discovery/ranking owner.
	 */
	public function search_connector( $connectors ) {
		$connectors = (array) $connectors;
		$connectors['file05-learning.v3'] = array(
			'owner'              => 'file05',
			'version'            => '3.0',
			'global_rank_owner'  => 'file26',
			'public_types'       => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ),
			'query'              => rest_url( LSCH_REST::NS . '/catalog' ),
			'delete_event'       => 'LearningContentRemoved.v1',
			'correction_event'   => 'LearningContentCorrected.v1',
			'visibility_recheck' => array( 'LSCH_Policy', 'can_read_post' ),
			'why_metadata'       => true,
			'no_copied_truth'    => true,
		);
		return $connectors;
	}

	public function community_context_provider( $providers ) {
		$providers = (array) $providers;
		$providers['file05-learning.v2'] = array(
			'owner'        => 'file05',
			'object_types' => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::LESSON, LSCH_Content::COHORT ),
			'resolver'     => array( $this, 'community_context_card' ),
			'privacy'      => 'visibility-rechecked',
		);
		return $providers;
	}

	public function community_context_card( $object_type, $object_id, $viewer_id = 0 ) {
		$object_id = absint( $object_id );
		if ( LSCH_Content::object_type( $object_id ) !== $object_type || ! LSCH_Policy::can_read_post( $object_id, absint( $viewer_id ) ) ) {
			return null;
		}
		return array(
			'owner'             => 'file05',
			'type'              => $object_type,
			'id'                => $object_id,
			'title'             => get_the_title( $object_id ),
			'url'               => get_permalink( $object_id ),
			'version'           => LSCH_Content::version( $object_id ),
			'discussion_policy' => 'moderated-educational-context',
		);
	}

	public function lesson_saved( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! LSCH_Capabilities::can_author( $post->post_author ) && ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			$this->block_publication( $post_id, 'ineligible_author' );
			return;
		}

		$version = LSCH_Content::version( $post_id );
		if ( $update ) {
			update_post_meta( $post_id, '_lsch_version', $version + 1 );
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		if ( ! LSCH_Policy::central_policy_ready() ) {
			$this->block_publication( $post_id, 'central_policy_unavailable' );
			return;
		}
		foreach ( array( '_lsch_objectives', '_lsch_sources', '_lsch_accessibility', '_lsch_safety' ) as $key ) {
			if ( '' === trim( (string) get_post_meta( $post_id, $key, true ) ) ) {
				$this->block_publication( $post_id, 'missing_' . $key );
				return;
			}
		}
		if ( ! LSCH_Value::sources( $post_id ) ) {
			$this->block_publication( $post_id, 'source_record_required' );
			return;
		}
		if ( ! LSCH_Capabilities::is_founder( $post->post_author ) ) {
			$reviewer_id = absint( get_post_meta( $post_id, '_lsch_reviewer_id', true ) );
			if ( ! $reviewer_id || $reviewer_id === absint( $post->post_author ) || ! LSCH_Capabilities::can_review( $reviewer_id ) ) {
				$this->block_publication( $post_id, 'independent_reviewer_required' );
				return;
			}
			update_post_meta( $post_id, '_lsch_reviewer', (string) $reviewer_id );
		}
		$topics = wp_get_object_terms( $post_id, LSCH_Content::TOPIC, array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $topics ) && in_array( 'patient-case-learning', (array) $topics, true ) ) {
			if ( ! LSCH_Policy::valid_case_consent( $post_id ) ) {
				$this->block_publication( $post_id, 'patient_case_consent_required' );
				return;
			}
			wp_set_post_tags( $post_id, array( 'کامیاب کیس' ), true );
		}
	}

	private function block_publication( $post_id, $reason ) {
		remove_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20 );
		wp_update_post( array( 'ID' => absint( $post_id ), 'post_status' => 'pending' ) );
		add_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20, 3 );
		LSCH_Events::audit( 'lesson_publication_blocked', 'lesson', $post_id, array( 'reason' => sanitize_key( $reason ) ), 'editorial_governance' );
	}

	public function status_transition( $new, $old, $post ) {
		if ( $new === $old || ! in_array( $post->post_type, array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ), true ) ) {
			return;
		}
		LSCH_Events::audit( 'content_status_changed', $post->post_type, $post->ID, array( 'from' => $old, 'to' => $new ), 'editorial_governance' );
		if ( 'publish' === $new ) {
			LSCH_Events::publish(
				'LearningContentPublished.v1',
				$post->post_type,
				$post->ID,
				array( 'version' => LSCH_Content::version( $post->ID ), 'url' => get_permalink( $post->ID ) )
			);
		} elseif ( 'publish' === $old ) {
			LSCH_Events::publish(
				'LearningContentRemoved.v1',
				$post->post_type,
				$post->ID,
				array( 'previous_version' => LSCH_Content::version( $post->ID ), 'new_status' => $new )
			);
		}
	}
}
