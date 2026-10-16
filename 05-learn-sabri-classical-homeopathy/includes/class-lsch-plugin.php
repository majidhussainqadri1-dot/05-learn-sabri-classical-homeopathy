<?php
/** Module composition and cross-file adapters. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Plugin {
	public function run() {
		add_filter( 'user_has_cap', array( 'LSCH_Capabilities', 'filter_user_caps' ), 20, 4 );
		add_action( 'init', array( 'LSCH_Content', 'register' ), 5 );
		LSCH_Idempotency::hooks();
		( new LSCH_REST() )->hooks();
		LSCH_Future18::hooks();
		( new LSCH_Future18_REST() )->hooks();
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
		add_filter( 'wp_insert_post_data', array( $this, 'prepublish_lesson' ), 20, 2 );
		add_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20, 3 );
		add_action( 'transition_post_status', array( $this, 'status_transition' ), 20, 3 );
	}

	public function module_contract( $modules ) {
		$modules = (array) $modules;
		$modules['file05-learning'] = array(
			'version'        => LSCH_VERSION,
			'schema'         => LSCH_SCHEMA_VERSION,
			'future18_schema'=> LSCH_Future18::SCHEMA,
			'plan'           => LSCH_PLAN_VERSION,
			'owner'          => 'learning',
			'routes'         => array( '/learn/', '/learn/program/{slug}', '/learn/course/{slug}', '/learn/lesson/{slug}', '/learn/dashboard', '/learn/mastery', '/learn/assessment/{id}' ),
			'rest_namespace' => LSCH_REST::NS,
			'access_model'   => LSCH_Policy::access_model(),
			'policy_ready'   => LSCH_Policy::central_policy_ready(),
			'health'         => array( 'callable' => array( 'LSCH_Operations', 'system_check' ) ),
			'features'       => LSCH_Future18::feature_ids(),
			'contracts'      => array(
				'file00' => 'SMC_Contracts::assertions >= 1.2.2',
				'file06' => 'canonical-encyclopedia-knowledge-owner',
				'file12' => 'canonical-pdf-document-owner',
				'file15' => 'canonical-repertory-owner',
				'file16' => 'canonical-ai-answer-owner',
				'file17' => 'canonical-messaging-transport-owner',
				'file19' => 'canonical-notification-delivery-owner',
				'file26' => 'global-search-discovery-ranking-owner',
			),
		);
		return $modules;
	}

	public function composer_adapter( $adapters ) {
		$adapters = (array) $adapters;
		$adapters['learning_lesson.v4'] = array(
			'label'           => __( 'Learning Lesson', 'learn-sabri-classical-homeopathy' ),
			'owner'           => 'file05',
			'version'         => '4.0',
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

	/** File 26 remains the canonical global search/discovery/ranking owner. */
	public function search_connector( $connectors ) {
		$connectors = (array) $connectors;
		$connectors['file05-learning.v4'] = array(
			'owner'              => 'file05',
			'version'            => '4.0',
			'global_rank_owner'  => 'file26',
			'public_types'       => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ),
			'query'              => rest_url( LSCH_REST::NS . '/catalog' ),
			'delete_event'       => 'LearningContentRemoved.v1',
			'correction_event'   => 'LearningContentCorrected.v1',
			'visibility_recheck' => array( 'LSCH_Policy', 'publicly_visible' ),
			'why_metadata'       => true,
			'no_copied_truth'    => true,
		);
		return $connectors;
	}

	public function community_context_provider( $providers ) {
		$providers = (array) $providers;
		$providers['file05-learning.v4'] = array(
			'owner'        => 'file05',
			'object_types' => array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::LESSON, LSCH_Content::COHORT ),
			'resolver'     => array( $this, 'community_context_card' ),
			'privacy'      => 'visibility-rechecked',
		);
		return $providers;
	}

	public function community_context_card( $object_type, $object_id, $viewer_id = 0 ) {
		$object_id = absint( $object_id );
		if ( LSCH_Content::object_type( $object_id ) !== $object_type || ! ( $viewer_id ? LSCH_Policy::can_read_post( $object_id, $viewer_id ) : LSCH_Policy::publicly_visible( $object_id ) ) ) {
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

	/** Pre-commit publishing gate: never rely on a later save_post status rollback. */
	public function prepublish_lesson( $data, $postarr ) {
		if ( ! is_array( $data ) || LSCH_Content::LESSON !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) { return $data; }
		$postarr = is_array( $postarr ) ? $postarr : array();
		$id = absint( $postarr['ID'] ?? 0 );
		$stored = $id ? get_post( $id ) : null;
		$author = absint( $data['post_author'] ?? ( $stored ? $stored->post_author : 0 ) );
		// WordPress saves tax_input, tags_input and meta_input AFTER this filter.
		// Using the previously stored governance evidence would approve a different
		// revision/consent classification from the one committed by this request.
		$uncommitted_governance = false;
		if ( array_key_exists( 'tax_input', $postarr ) ) {
			$tax_input = $postarr['tax_input'];
			if ( ! is_array( $tax_input ) ) {
				$uncommitted_governance = true;
			} elseif ( array_key_exists( LSCH_Content::TOPIC, $tax_input ) ) {
				$requested_topics = $tax_input[ LSCH_Content::TOPIC ];
				$requested_topics = is_array( $requested_topics ) ? $requested_topics : explode( ',', (string) $requested_topics );
				$stored_topics = $id ? wp_get_object_terms( $id, LSCH_Content::TOPIC, array( 'fields' => 'ids' ) ) : false;
				if ( ! is_array( $stored_topics ) || ! is_array( $requested_topics ) || array_filter( $requested_topics, static function( $topic ) { return ! is_scalar( $topic ) || ( '' !== trim( (string) $topic ) && ! ctype_digit( (string) $topic ) ); } ) ) {
					$uncommitted_governance = true;
				} else {
					$requested_topic_ids = array_values( array_unique( array_filter( array_map( 'absint', $requested_topics ) ) ) );
					$stored_topic_ids = array_values( array_unique( array_filter( array_map( 'absint', $stored_topics ) ) ) );
					sort( $requested_topic_ids, SORT_NUMERIC );
					sort( $stored_topic_ids, SORT_NUMERIC );
					if ( $requested_topic_ids !== $stored_topic_ids ) { $uncommitted_governance = true; }
				}
			}
		}
		if ( array_key_exists( 'tags_input', $postarr ) ) {
			$tags = $postarr['tags_input'];
			if ( ! ( is_string( $tags ) || is_array( $tags ) ) || '' !== trim( is_array( $tags ) ? implode( ',', array_map( 'strval', $tags ) ) : $tags ) || ( $id && has_term( 'کامیاب کیس', 'post_tag', $id ) ) ) {
				$uncommitted_governance = true;
			}
		}
		if ( isset( $postarr['meta_input'] ) ) {
			if ( ! is_array( $postarr['meta_input'] ) ) {
				$uncommitted_governance = true;
			} else {
				foreach ( $postarr['meta_input'] as $key => $value ) {
					if ( 0 === strpos( (string) $key, '_lsch_' ) && ( ! is_scalar( $value ) || (string) get_post_meta( $id, $key, true ) !== (string) $value ) ) {
						$uncommitted_governance = true;
						break;
					}
				}
			}
		}
		// Admin governance metabox changes are persisted in save_post (also later).
		if ( isset( $_POST['lsch_meta'] ) ) {
			if ( ! is_array( $_POST['lsch_meta'] ) ) {
				$uncommitted_governance = true;
			} else {
				foreach ( $_POST['lsch_meta'] as $key => $value ) {
					if ( 0 === strpos( (string) $key, '_lsch_' ) && ( ! is_scalar( $value ) || (string) get_post_meta( $id, $key, true ) !== (string) $value ) ) {
						$uncommitted_governance = true;
						break;
					}
				}
			}
		}
		$reason = '';
		if ( ! $id || ! $stored ) { $reason = 'unpersisted_lesson_evidence'; }
		elseif ( $uncommitted_governance ) { $reason = 'uncommitted_lesson_governance'; }
		elseif ( ! LSCH_Policy::central_policy_ready() ) { $reason = 'central_policy_unavailable'; }
		elseif ( ! LSCH_Capabilities::is_founder( $author ) && ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) && !( LSCH_Capabilities::can_author( $author ) && user_can( $author, LSCH_Capabilities::PUBLISH_LESSONS ) ) ) {
			$reason = 'unauthorized_publication_actor';
		}
		elseif ( '' === trim( (string) ( $data['post_title'] ?? '' ) ) || '' === trim( wp_strip_all_tags( (string) ( $data['post_content'] ?? '' ) ) ) ) {
			$reason = 'lesson_body_or_title_missing';
		}
		else {
			foreach ( array( '_lsch_objectives', '_lsch_sources', '_lsch_accessibility', '_lsch_safety' ) as $key ) {
				if ( '' === trim( (string) get_post_meta( $id, $key, true ) ) ) { $reason = 'missing_' . $key; break; }
			}
			if ( '' === $reason && ! LSCH_Value::sources( $id ) ) { $reason = 'source_record_required'; }
			if ( '' === $reason && ! LSCH_Capabilities::is_founder( $author ) ) {
				$reviewer = absint( get_post_meta( $id, '_lsch_reviewer_id', true ) );
				if ( ! $reviewer || $reviewer === $author || ! LSCH_Capabilities::can_review( $reviewer ) ) { $reason = 'independent_reviewer_required'; }
			}
			if ( '' === $reason ) {
				$topics = wp_get_object_terms( $id, LSCH_Content::TOPIC, array( 'fields' => 'slugs' ) );
				if ( is_wp_error( $topics ) || ! is_array( $topics ) ) { $reason = 'patient_case_taxonomy_unavailable'; }
				elseif ( in_array( 'patient-case-learning', $topics, true ) ) {
					if ( ! LSCH_Policy::valid_case_consent( $id ) ) { $reason = 'patient_case_consent_required'; }
					elseif ( ! has_term( 'کامیاب کیس', 'post_tag', $id ) ) {
						$tagged = wp_set_post_tags( $id, array( 'کامیاب کیس' ), true );
						if ( is_wp_error( $tagged ) || ! has_term( 'کامیاب کیس', 'post_tag', $id ) ) { $reason = 'patient_case_tag_unverified'; }
					}
				}
			}
		}
		if ( '' !== $reason ) {
			$data['post_status'] = 'pending';
			LSCH_Events::audit( 'lesson_prepublication_deferred', 'lesson', $id, array( 'reason' => sanitize_key( $reason ) ), 'editorial_governance' );
		}
		return $data;
	}

	public function lesson_saved( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! LSCH_Capabilities::can_author( $post->post_author ) && ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			$this->block_publication( $post_id, 'ineligible_author' );
			return;
		}

		// post_updated may already have advanced this content's version.
		// Reuse the per-request guarded mutation to avoid a second bump on save_post_.
		if ( $update && ! LSCH_Content::bump_version( $post_id, 'lesson_save' ) ) {
			$this->block_publication( $post_id, 'content_version_unverified' );
			return;
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
			$reviewer_saved = update_post_meta( $post_id, '_lsch_reviewer', (string) $reviewer_id );
			if ( false === $reviewer_saved && (string) get_post_meta( $post_id, '_lsch_reviewer', true ) !== (string) $reviewer_id ) {
				$this->block_publication( $post_id, 'reviewer_evidence_unverified' );
				return;
			}
		}
		$topics = wp_get_object_terms( $post_id, LSCH_Content::TOPIC, array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $topics ) || ! is_array( $topics ) ) {
			$this->block_publication( $post_id, 'patient_case_taxonomy_unavailable' );
			return;
		}
		if ( in_array( 'patient-case-learning', $topics, true ) ) {
			if ( ! LSCH_Policy::valid_case_consent( $post_id ) ) {
				$this->block_publication( $post_id, 'patient_case_consent_required' );
				return;
			}
			$tags_saved = wp_set_post_tags( $post_id, array( 'کامیاب کیس' ), true );
			if ( is_wp_error( $tags_saved ) || ! has_term( 'کامیاب کیس', 'post_tag', $post_id ) ) {
				$this->block_publication( $post_id, 'patient_case_tag_unverified' );
				return;
			}
		}
	}

	private function block_publication( $post_id, $reason ) {
		$post_id = absint( $post_id );
		remove_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20 );
		try {
			$result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'pending' ), true );
		} finally {
			add_action( 'save_post_' . LSCH_Content::LESSON, array( $this, 'lesson_saved' ), 20, 3 );
		}
		$status = get_post_status( $post_id );
		if ( is_wp_error( $result ) || $post_id !== absint( $result ) || 'pending' !== $status ) {
			// A failed WordPress transition must never be audited as a successful block.
			// Staging must prove the pre-publication hook and cache containment path.
			LSCH_Events::audit( 'lesson_publication_block_persist_failed', 'lesson', $post_id, array( 'reason' => sanitize_key( $reason ), 'observed_status' => sanitize_key( (string) $status ) ), 'editorial_governance' );
			return new WP_Error( 'lsch_publication_block_persist_failed', __( 'Lesson publication could not be blocked in WordPress. Immediate operator containment is required.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 503 ) );
		}
		LSCH_Events::audit( 'lesson_publication_blocked', 'lesson', $post_id, array( 'reason' => sanitize_key( $reason ) ), 'editorial_governance' );
		return true;
	}

	public function status_transition( $new, $old, $post ) {
		if ( $new === $old || ! in_array( $post->post_type, array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ), true ) ) {
			return;
		}
		LSCH_Events::audit( 'content_status_changed', $post->post_type, $post->ID, array( 'from' => $old, 'to' => $new ), 'editorial_governance' );
		if ( 'publish' === $new ) {
			LSCH_Events::publish( 'LearningContentPublished.v1', $post->post_type, $post->ID, array( 'version' => LSCH_Content::version( $post->ID ), 'url' => get_permalink( $post->ID ) ) );
		} elseif ( 'publish' === $old ) {
			LSCH_Events::publish( 'LearningContentRemoved.v1', $post->post_type, $post->ID, array( 'previous_version' => LSCH_Content::version( $post->ID ), 'new_status' => $new ) );
		}
	}
}
