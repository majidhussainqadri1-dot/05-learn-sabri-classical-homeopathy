<?php
/** WordPress privacy export, erasure, anonymization, and legal-hold controls. */

defined( 'ABSPATH' ) || exit;

final class SLC_Privacy {
	public function hooks() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
	}

	public function exporters( $items ) {
		$items['sabri-learning'] = array( 'exporter_friendly_name' => __( 'Sabri learning activity', 'sabri-learning' ), 'callback' => array( $this, 'export' ) );
		return $items;
	}

	public function erasers( $items ) {
		$items['sabri-learning'] = array( 'eraser_friendly_name' => __( 'Sabri learning activity', 'sabri-learning' ), 'callback' => array( $this, 'erase' ) );
		return $items;
	}

	public function export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}
		global $wpdb;
		$per_page = 50;
		$offset   = max( 0, ( absint( $page ) - 1 ) * $per_page );
		$data     = array();
		$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT r.* FROM {$wpdb->prefix}slc_progress r WHERE r.user_id=%d ORDER BY r.id ASC LIMIT %d OFFSET %d", $user->ID, $per_page, $offset ) );
		foreach ( $rows as $row ) {
			$data[] = array( 'group_id' => 'sabri-learning-progress', 'group_label' => __( 'Sabri Learning Progress', 'sabri-learning' ), 'item_id' => 'progress-' . $row->id, 'data' => array( array( 'name' => __( 'Lesson', 'sabri-learning' ), 'value' => get_the_title( $row->lesson_id ) ), array( 'name' => __( 'Status', 'sabri-learning' ), 'value' => $row->status ), array( 'name' => __( 'Score', 'sabri-learning' ), 'value' => $row->score . '%' ), array( 'name' => __( 'Updated', 'sabri-learning' ), 'value' => $row->updated_at ) ) );
		}
		if ( 1 === absint( $page ) ) {
			$bookmarks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}slc_bookmarks WHERE user_id=%d ORDER BY id", $user->ID ) );
			foreach ( $bookmarks as $bookmark ) {
				$data[] = array( 'group_id' => 'sabri-learning-bookmarks', 'group_label' => __( 'Sabri Learning Bookmarks', 'sabri-learning' ), 'item_id' => 'bookmark-' . $bookmark->id, 'data' => array( array( 'name' => __( 'Lesson', 'sabri-learning' ), 'value' => get_the_title( $bookmark->lesson_id ) ), array( 'name' => __( 'Created', 'sabri-learning' ), 'value' => $bookmark->created_at ) ) );
			}
			$lessons = get_posts( array( 'post_type' => SLC_Content::LESSON, 'post_status' => array( 'publish', 'pending', 'draft', 'private' ), 'author' => $user->ID, 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true ) );
			foreach ( $lessons as $lesson ) {
				$data[] = array( 'group_id' => 'sabri-learning-authored', 'group_label' => __( 'Authored Learning Lessons', 'sabri-learning' ), 'item_id' => 'lesson-' . $lesson->ID, 'data' => array( array( 'name' => __( 'Title', 'sabri-learning' ), 'value' => $lesson->post_title ), array( 'name' => __( 'Publication status', 'sabri-learning' ), 'value' => $lesson->post_status ), array( 'name' => __( 'Workflow state', 'sabri-learning' ), 'value' => get_post_meta( $lesson->ID, '_slc_workflow_state', true ) ), array( 'name' => __( 'Topic', 'sabri-learning' ), 'value' => SLC_Content::term( $lesson->ID, SLC_Content::TOPIC ) ), array( 'name' => __( 'Level', 'sabri-learning' ), 'value' => SLC_Content::term( $lesson->ID, SLC_Content::LEVEL ) ) ) );
			}
			$consents = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}slc_consents WHERE author_id=%d ORDER BY id", $user->ID ) );
			foreach ( $consents as $consent ) {
				$data[] = array( 'group_id' => 'sabri-learning-consents', 'group_label' => __( 'Patient Case Consent Records', 'sabri-learning' ), 'item_id' => 'consent-' . $consent->id, 'data' => array( array( 'name' => __( 'Lesson', 'sabri-learning' ), 'value' => get_the_title( $consent->lesson_id ) ), array( 'name' => __( 'Policy version', 'sabri-learning' ), 'value' => $consent->policy_version ), array( 'name' => __( 'Consent source', 'sabri-learning' ), 'value' => $consent->consent_source ), array( 'name' => __( 'Permitted scope', 'sabri-learning' ), 'value' => $consent->scope ), array( 'name' => __( 'Evidence reference', 'sabri-learning' ), 'value' => $consent->evidence_reference ), array( 'name' => __( 'Confirmed', 'sabri-learning' ), 'value' => $consent->confirmed_at ), array( 'name' => __( 'Withdrawn', 'sabri-learning' ), 'value' => $consent->withdrawn_at ? $consent->withdrawn_at : __( 'No', 'sabri-learning' ) ) ) );
			}
		}
		return array( 'data' => $data, 'done' => count( $rows ) < $per_page );
	}

	public function erase( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user || $page > 1 ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		global $wpdb;
		$removed  = false;
		$retained = false;
		$messages = array();
		$legal_hold = (bool) apply_filters( 'slc_user_legal_hold', false, $user->ID );

		$removed = $wpdb->delete( $wpdb->prefix . 'slc_progress', array( 'user_id' => $user->ID ), array( '%d' ) ) > 0 || $removed;
		$removed = $wpdb->delete( $wpdb->prefix . 'slc_bookmarks', array( 'user_id' => $user->ID ), array( '%d' ) ) > 0 || $removed;

		if ( $legal_hold ) {
			$retained   = true;
			$messages[] = __( 'Authored lessons, consent records, and audit entries were retained under an active legal hold.', 'sabri-learning' );
		} else {
			$founder = SLC_Permissions::founder_id();
			$lessons = get_posts( array( 'post_type' => SLC_Content::LESSON, 'post_status' => array( 'publish', 'pending', 'draft', 'private' ), 'author' => $user->ID, 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
			foreach ( $lessons as $lesson_id ) {
				if ( 'publish' === get_post_status( $lesson_id ) ) {
					if ( 'patient-case-learning' === SLC_Content::term( $lesson_id, SLC_Content::TOPIC, 'slug' ) ) {
						wp_update_post( array( 'ID' => $lesson_id, 'post_status' => 'private', 'post_author' => $founder ? $founder : 0 ) );
						update_post_meta( $lesson_id, '_slc_workflow_state', 'hidden' );
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}slc_consents SET withdrawn_at=COALESCE(withdrawn_at,UTC_TIMESTAMP()),withdrawn_by=0 WHERE lesson_id=%d", $lesson_id ) );
					} else {
						wp_update_post( array( 'ID' => $lesson_id, 'post_author' => $founder ? $founder : 0 ) );
					}
					update_post_meta( $lesson_id, '_slc_author_erased', current_time( 'mysql', true ) );
					$retained = true;
				} else {
					wp_delete_post( $lesson_id, true );
					$removed = true;
				}
			}
			$wpdb->update( $wpdb->prefix . 'slc_consents', array( 'author_id' => 0, 'evidence_reference' => '[erased]' ), array( 'author_id' => $user->ID ), array( '%d', '%s' ), array( '%d' ) );
			$wpdb->update( $wpdb->prefix . 'slc_audit_log', array( 'actor_id' => 0 ), array( 'actor_id' => $user->ID ), array( '%d' ), array( '%d' ) );
			$messages[] = __( 'Unpublished lessons were removed. Published lessons were retained for editorial integrity and reassigned without personal attribution; audit and consent identifiers were anonymized.', 'sabri-learning' );
		}
		SLC_Dependencies::audit( 'privacy_erasure', array( 'user_id' => $user->ID, 'legal_hold' => $legal_hold ) );
		return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => $messages, 'done' => true );
	}
}
