<?php
/** Non-destructive default uninstall. Destructive purge requires two explicit controls. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$purge = defined( 'LSCH_ALLOW_DESTRUCTIVE_PURGE' ) && true === LSCH_ALLOW_DESTRUCTIVE_PURGE && 'PURGE-FILE05-DATA' === get_option( 'lsch_destructive_purge_confirmation' );
if ( ! $purge ) {
	update_option( 'lsch_uninstalled_retained', array( 'time' => gmdate( 'c' ), 'version' => get_option( 'lsch_version', '' ) ), false );
	return;
}

global $wpdb;
$tables = array(
	'enrollments', 'progress', 'bookmarks', 'notes', 'attempts', 'submissions',
	'staff_assignments', 'completions', 'related_links', 'case_consents',
	'reminders', 'outbox', 'inbox', 'jobs', 'audit_log', 'request_keys',
	'saved_searches', 'corrections', 'value_events',
);
foreach ( $tables as $suffix ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lsch_{$suffix}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$future18_tables = array( 'mastery', 'review_queue', 'practice', 'pathways', 'portfolio', 'mentorship', 'cpd', 'change_impacts' );
foreach ( $future18_tables as $suffix ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lsch_f18_{$suffix}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$pages = (array) get_option( 'lsch_page_map', array() );
foreach ( $pages as $page_id ) {
	if ( 'file05-learning' === get_post_meta( $page_id, '_lsch_managed_owner', true ) ) {
		wp_delete_post( absint( $page_id ), true );
	}
}

foreach (
	array(
		'lsch_schema_version', 'lsch_state_schema_version', 'lsch_future18_schema', 'lsch_version',
		'lsch_plan_version', 'lsch_access_model', 'lsch_page_map',
		'lsch_activation_checkpoint', 'lsch_runtime_failure', 'lsch_safe_mode',
		'lsch_safe_mode_reason', 'lsch_legacy_migration', 'lsch_last_reconcile',
		'lsch_destructive_purge_confirmation',
	) as $option
) {
	delete_option( $option );
}
