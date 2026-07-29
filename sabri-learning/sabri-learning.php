<?php
/**
 * Plugin Name: Learn Sabri Classical Homeopathy
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Governed public books, structured lessons, progress, bookmarks, and knowledge checks for classical homeopathy learning.
 * Version: 1.0.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Dr. Allama Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: sabri-learning
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SLC_VERSION', '1.0.0' );
define( 'SLC_SCHEMA_VERSION', 2 );
define( 'SLC_FILE', __FILE__ );
define( 'SLC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLC_URL', plugin_dir_url( __FILE__ ) );

require_once SLC_DIR . 'includes/class-slc-dependencies.php';
require_once SLC_DIR . 'includes/class-slc-database.php';
require_once SLC_DIR . 'includes/class-slc-permissions.php';
require_once SLC_DIR . 'includes/class-slc-content.php';
require_once SLC_DIR . 'includes/class-slc-activator.php';
require_once SLC_DIR . 'includes/class-slc-publishing.php';
require_once SLC_DIR . 'includes/class-slc-catalog.php';
require_once SLC_DIR . 'includes/class-slc-learning.php';
require_once SLC_DIR . 'includes/class-slc-comments.php';
require_once SLC_DIR . 'includes/class-slc-admin.php';
require_once SLC_DIR . 'includes/class-slc-privacy.php';
require_once SLC_DIR . 'includes/class-slc-seo.php';
require_once SLC_DIR . 'includes/class-slc-plugin.php';

register_activation_hook( SLC_FILE, array( 'SLC_Activator', 'activate' ) );
register_deactivation_hook( SLC_FILE, array( 'SLC_Activator', 'deactivate' ) );

/**
 * Start the plugin only after its authoritative dependencies are available.
 */
function slc_start_plugin() {
	load_plugin_textdomain( 'sabri-learning', false, dirname( plugin_basename( SLC_FILE ) ) . '/languages' );

	if ( ! SLC_Dependencies::ready() ) {
		SLC_Dependencies::register_failure_notice();
		return;
	}

	try {
		SLC_Database::maybe_upgrade();
	} catch ( Throwable $error ) {
		update_option( 'slc_runtime_failure', array( 'time' => gmdate( 'c' ), 'error' => sanitize_text_field( $error->getMessage() ) ), false );
		SLC_Dependencies::audit( 'runtime_migration_failed', array( 'error' => $error->getMessage() ) );
		SLC_Dependencies::register_runtime_failure_notice();
		return;
	}
	delete_option( 'slc_runtime_failure' );
	( new SLC_Plugin() )->run();
}
add_action( 'plugins_loaded', 'slc_start_plugin', 30 );
