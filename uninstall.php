<?php
/**
 * MirM Editorial Guard Uninstall
 *
 * Cleans up all plugin data from the database when the plugin is deleted.
 *
 * @package MirM_Editorial_Guard
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'mirm_editorial_guard_rules' );
delete_option( 'mirm_editorial_guard_custom_rules' );

// Clean up all post meta entries.
delete_post_meta_by_key( '_mirm_editorial_guard_passed_status' );
delete_post_meta_by_key( '_mirm_editorial_guard_override_reason' );

// Clean up any transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_mirm_editorial_guard_' ) . '%'
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_mirm_editorial_guard_' ) . '%'
	)
);
