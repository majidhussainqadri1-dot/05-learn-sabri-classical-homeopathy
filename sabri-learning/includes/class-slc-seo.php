<?php
/** Escaped structured data for public, published learning resources. */

defined( 'ABSPATH' ) || exit;

final class SLC_SEO {
	public function hooks() {
		add_action( 'wp_head', array( $this, 'schema' ), 20 );
	}

	public function schema() {
		$data = null;
		if ( is_singular( SLC_Content::LESSON ) && 'publish' === get_post_status( get_queried_object_id() ) ) {
			$id     = get_queried_object_id();
			$author = absint( get_post_field( 'post_author', $id ) );
			$data   = array( '@context' => 'https://schema.org', '@type' => array( 'CreativeWork', 'LearningResource' ), 'name' => get_the_title( $id ), 'description' => wp_strip_all_tags( get_the_excerpt( $id ) ), 'learningResourceType' => 'Lesson', 'educationalLevel' => SLC_Content::term( $id, SLC_Content::LEVEL ), 'about' => SLC_Content::term( $id, SLC_Content::TOPIC ), 'inLanguage' => 'en-US', 'isAccessibleForFree' => true, 'datePublished' => get_the_date( DATE_W3C, $id ), 'dateModified' => get_the_modified_date( DATE_W3C, $id ), 'url' => get_permalink( $id ), 'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $author ), 'url' => SLC_Permissions::profile_url( $author ) ), 'publisher' => array( '@type' => 'Organization', 'name' => 'Sabri Social Homeopathy Platform', 'url' => home_url( '/' ) ) );
		} elseif ( is_singular( SLC_Content::BOOK ) && 'publish' === get_post_status( get_queried_object_id() ) ) {
			$id   = get_queried_object_id();
			$data = array( '@context' => 'https://schema.org', '@type' => array( 'Book', 'LearningResource' ), 'name' => get_the_title( $id ), 'description' => wp_strip_all_tags( get_the_excerpt( $id ) ), 'inLanguage' => 'en-US', 'isAccessibleForFree' => true, 'url' => get_permalink( $id ), 'publisher' => array( '@type' => 'Organization', 'name' => 'Sabri Social Homeopathy Platform', 'url' => home_url( '/' ) ) );
		} else {
			$pages = (array) get_option( 'spf_page_map', array() );
			if ( ! empty( $pages['learn'] ) && is_page( absint( $pages['learn'] ) ) ) {
				$data = array( '@context' => 'https://schema.org', '@type' => 'Course', 'name' => 'Learn Sabri Classical Homeopathy', 'description' => 'A structured public learning foundation for classical homeopathy books and lessons.', 'availableLanguage' => 'en-US', 'isAccessibleForFree' => true, 'provider' => array( '@type' => 'Organization', 'name' => 'Sabri Social Homeopathy Platform', 'url' => home_url( '/' ) ) );
			}
		}
		if ( ! $data ) {
			return;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
	}
}
