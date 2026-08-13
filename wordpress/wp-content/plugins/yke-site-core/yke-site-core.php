<?php
/**
 * Plugin Name: YKE Site Core
 * Description: Site models, settings, shortcodes, and starter content hooks for Yunke Electrical.
 * Version: 0.8.3
 * Author: OpenAI Codex
 * Text Domain: yke-site-core
 *
 * @package YKE_Site_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YKE_SITE_CORE_VERSION', '0.9.61' );
define( 'YKE_SITE_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'YKE_SITE_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once YKE_SITE_CORE_PATH . 'includes/class-yke-site-core.php';

register_activation_hook( __FILE__, array( 'YKE_Site_Core', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'YKE_Site_Core', 'deactivate' ) );

YKE_Site_Core::boot();
