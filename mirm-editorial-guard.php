<?php
/**
 * Plugin Name:       MirM Editorial Guard – Publishing Checklist & Content Rules
 * Description:       A lightweight Block Editor quality-control gateway that enforces pre-flight publication rules, blocks unverified posts, and provides role-based permission overrides.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mir Monoarul Alam
 * Author URI:        https://profiles.wordpress.org/mirmpro/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mirm-editorial-guard
 * Domain Path:       /languages
 *
 * @package MirM_Editorial_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MIRM_EDITORIAL_GUARD_VERSION', '1.0.1' );
define( 'MIRM_EDITORIAL_GUARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'MIRM_EDITORIAL_GUARD_URL', plugin_dir_url( __FILE__ ) );
define( 'MIRM_EDITORIAL_GUARD_BASENAME', plugin_basename( __FILE__ ) );

// Autoload includes.
require_once MIRM_EDITORIAL_GUARD_PATH . 'includes/class-mirm-editorial-guard-core.php';
require_once MIRM_EDITORIAL_GUARD_PATH . 'includes/class-mirm-editorial-guard-permissions.php';
require_once MIRM_EDITORIAL_GUARD_PATH . 'includes/class-mirm-editorial-guard-settings.php';
require_once MIRM_EDITORIAL_GUARD_PATH . 'includes/class-mirm-editorial-guard-guard.php';
require_once MIRM_EDITORIAL_GUARD_PATH . 'includes/class-mirm-editorial-guard-rest.php';

/**
 * Initialize the plugin on plugins_loaded.
 */
function mirm_editorial_guard_init() {
	MirM_Editorial_Guard_Core::get_instance();
}
add_action( 'plugins_loaded', 'mirm_editorial_guard_init' );

/**
 * Plugin activation hook.
 */
function mirm_editorial_guard_activate() {
	// Set default rules if not already set.
	if ( false === get_option( 'mirm_editorial_guard_rules' ) ) {
		$defaults = MirM_Editorial_Guard_Settings::get_default_rules();
		update_option( 'mirm_editorial_guard_rules', $defaults );
	}
}
register_activation_hook( __FILE__, 'mirm_editorial_guard_activate' );
