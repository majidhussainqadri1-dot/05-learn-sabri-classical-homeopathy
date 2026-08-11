<?php
/**
 * Plugin Name: Learn Sabri Classical Homeopathy
 * Plugin URI: https://sabrihomeopathy.com/learn/
 * Description: Canonical curriculum, courses, lessons, enrollment, mastery, clinical-learning laboratories, progress, assessments, governed correction/citation/value tools and education integrations for the Sabri Social Homeopathy Platform.
 * Version: 4.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Dr. Allamah Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: learn-sabri-classical-homeopathy
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'LSCH_VERSION', '4.0.0' );
define( 'LSCH_SCHEMA_VERSION', 18 );
define( 'LSCH_PLAN_VERSION', 'SSH-F05-PLAN-2026-v1.1-future18-current-central-2026-08-10' );
define( 'LSCH_FILE', __FILE__ );
define( 'LSCH_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSCH_URL', plugin_dir_url( __FILE__ ) );
define( 'LSCH_BASENAME', plugin_basename( __FILE__ ) );

$lsch_files = array(
	'class-lsch-dependencies.php',
	'class-lsch-capabilities.php',
	'class-lsch-content.php',
	'class-lsch-database.php',
	'class-lsch-policy.php',
	'class-lsch-events.php',
	'class-lsch-services.php',
	'class-lsch-rest.php',
	'class-lsch-state.php',
	'class-lsch-value.php',
	'class-lsch-idempotency.php',
	'class-lsch-future18.php',
	'class-lsch-future18-rest.php',
	'class-lsch-frontend.php',
	'class-lsch-admin.php',
	'class-lsch-privacy.php',
	'class-lsch-operations.php',
	'class-lsch-activator.php',
	'class-lsch-plugin.php',
);
foreach ( $lsch_files as $lsch_file ) {
	require_once LSCH_DIR . 'includes/' . $lsch_file;
}
unset( $lsch_files, $lsch_file );

register_activation_hook( LSCH_FILE, array( 'LSCH_Activator', 'activate' ) );
register_deactivation_hook( LSCH_FILE, array( 'LSCH_Activator', 'deactivate' ) );

/** Start only after WordPress and declared platform contracts are available. */
function lsch_bootstrap() {
	load_plugin_textdomain( 'learn-sabri-classical-homeopathy', false, dirname( LSCH_BASENAME ) . '/languages' );

	if ( ! LSCH_Dependencies::runtime_ready() ) {
		LSCH_Dependencies::register_admin_notice();
		return;
	}

	try {
		LSCH_Database::maybe_upgrade();
		LSCH_State::maybe_upgrade();
		LSCH_Future18::maybe_upgrade();
	} catch ( Throwable $error ) {
		update_option(
			'lsch_runtime_failure',
			array(
				'time'    => gmdate( 'c' ),
				'code'    => 'database_upgrade_failed',
				'message' => sanitize_text_field( $error->getMessage() ),
			),
			false
		);
		LSCH_Dependencies::audit( 'runtime_upgrade_failed', array( 'error_class' => get_class( $error ) ) );
		LSCH_Dependencies::register_runtime_failure_notice();
		return;
	}

	delete_option( 'lsch_runtime_failure' );
	( new LSCH_Plugin() )->run();
}
add_action( 'plugins_loaded', 'lsch_bootstrap', 30 );
