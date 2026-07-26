<?php
/**
 * Publish Gate Uninstall
 *
 * Cleans up all plugin data from the database when the plugin is deleted.
 *
 * @package Publish_Gate
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'publish_gate_rules' );
delete_option( 'publish_gate_custom_rules' );

// Clean up all post meta entries.
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => '_publish_gate_passed_status' ),
	array( '%s' )
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => '_publish_gate_override_reason' ),
	array( '%s' )
);

// Clean up any transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_publish_gate_' ) . '%'
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_publish_gate_' ) . '%'
	)
);
