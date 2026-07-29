<?php
/**
 * Plugin Name: Learn Sabri Classical Homeopathy
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Public books, structured lessons, progress, bookmarks and knowledge checks for classical homeopathy learning.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dr. Allama Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: sabri-learning
 */

defined( 'ABSPATH' ) || exit;

define( 'SLC_VERSION', '0.1.0' );
define( 'SLC_FILE', __FILE__ );
define( 'SLC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLC_URL', plugin_dir_url( __FILE__ ) );

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

function slc_start_plugin() {
	( new SLC_Plugin() )->run();
}
add_action( 'plugins_loaded', 'slc_start_plugin' );

