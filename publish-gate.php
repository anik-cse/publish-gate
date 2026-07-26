<?php
/**
 * Plugin Name:       Publish Gate – Publishing Checklist & Content Rules
 * Description:       A lightweight Block Editor quality-control gateway that enforces pre-flight publication rules, blocks unverified posts, and provides role-based permission overrides.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mir Monoarul Alam
 * Author URI:        https://profiles.wordpress.org/mirmpro/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       publish-gate
 * Domain Path:       /languages
 *
 * @package Publish_Gate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'PUBLISH_GATE_VERSION', '1.0.0' );
define( 'PUBLISH_GATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PUBLISH_GATE_URL', plugin_dir_url( __FILE__ ) );
define( 'PUBLISH_GATE_BASENAME', plugin_basename( __FILE__ ) );

// Autoload includes.
require_once PUBLISH_GATE_PATH . 'includes/class-publish-gate-core.php';
require_once PUBLISH_GATE_PATH . 'includes/class-publish-gate-permissions.php';
require_once PUBLISH_GATE_PATH . 'includes/class-publish-gate-settings.php';
require_once PUBLISH_GATE_PATH . 'includes/class-publish-gate-guard.php';
require_once PUBLISH_GATE_PATH . 'includes/class-publish-gate-rest.php';

/**
 * Initialize the plugin on plugins_loaded.
 */
function publish_gate_init() {
	Publish_Gate_Core::get_instance();
}
add_action( 'plugins_loaded', 'publish_gate_init' );

/**
 * Plugin activation hook.
 */
function publish_gate_activate() {
	// Set default rules if not already set.
	if ( false === get_option( 'publish_gate_rules' ) ) {
		$defaults = Publish_Gate_Settings::get_default_rules();
		update_option( 'publish_gate_rules', $defaults );
	}
}
register_activation_hook( __FILE__, 'publish_gate_activate' );
