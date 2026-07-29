<?php
/** Guarded uninstall. Data is retained unless both explicit controls authorize purge. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function slc_uninstall_site() {
	global $wpdb;
	$purge = defined( 'SLC_PURGE_ON_UNINSTALL' ) && true === SLC_PURGE_ON_UNINSTALL && 'yes' === get_option( 'slc_allow_destructive_uninstall', 'no' );
	wp_clear_scheduled_hook( 'slc_daily_maintenance' );
	delete_transient( 'slc_activation_notice' );
	if ( ! $purge ) {
		update_option( 'slc_uninstalled_retained_at', gmdate( 'c' ), false );
		return;
	}
	foreach ( array( 'progress', 'bookmarks', 'audit_log', 'consents', 'metrics', 'rate_limits' ) as $suffix ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}slc_{$suffix}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	$ids = get_posts( array( 'post_type' => array( 'slc_book', 'slc_lesson' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	$pages = (array) get_option( 'slc_page_map', array() );
	foreach ( $pages as $id ) {
		$id = absint( $id );
		if ( $id && '1' === get_post_meta( $id, '_slc_managed_page', true ) ) {
			wp_delete_post( $id, true );
		}
	}
	foreach ( array( 'slc_page_map', 'slc_version', 'slc_schema_version', 'slc_activation_state', 'slc_allow_destructive_uninstall', 'slc_uninstalled_retained_at' ) as $option ) {
		delete_option( $option );
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		slc_uninstall_site();
		restore_current_blog();
	}
} else {
	slc_uninstall_site();
}
