<?php
/**
 * Server-side publish interception.
 *
 * @package MirM_Editorial_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MirM_Editorial_Guard_Guard
 */
class MirM_Editorial_Guard_Guard {


	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'intercept_publish' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'display_blocked_notice' ) );
	}

	/**
	 * Intercept post status transitions to block unauthorized publishing.
	 *
	 * If a post transitions to 'publish' without having passed MirM Editorial Guard checks,
	 * it is reverted to 'draft' (unless the user has override permissions with a reason set).
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function intercept_publish( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status ) {
			return;
		}

		$guarded_types = apply_filters( 'mirm_editorial_guard_guarded_post_types', array( 'post' ) );
		if ( ! in_array( $post->post_type, $guarded_types, true ) ) {
			return;
		}

		if ( $old_status === $new_status ) {
			return;
		}

		$passed_status = get_post_meta( $post->ID, '_mirm_editorial_guard_passed_status', true );

		if ( 'passed' === $passed_status || 'overridden' === $passed_status ) {
			return;
		}

		$override_reason = get_post_meta( $post->ID, '_mirm_editorial_guard_override_reason', true );
		if ( ! empty( $override_reason ) && MirM_Editorial_Guard_Permissions::current_user_can_override() ) {
			update_post_meta( $post->ID, '_mirm_editorial_guard_passed_status', 'overridden' );
			return;
		}

		// Use wp_update_post to avoid infinite loop — remove our hook first.
		remove_action( 'transition_post_status', array( $this, 'intercept_publish' ), 10 );

		wp_update_post(
			array(
				'ID'          => $post->ID,
				'post_status' => 'draft',
			)
		);

		add_action( 'transition_post_status', array( $this, 'intercept_publish' ), 10, 3 );

		set_transient(
			'mirm_editorial_guard_blocked_' . get_current_user_id(),
			$post->ID,
			60
		);
	}

	/**
	 * Display admin notice when a post is blocked from publishing.
	 */
	public function display_blocked_notice() {
		$blocked_post_id = get_transient( 'mirm_editorial_guard_blocked_' . get_current_user_id() );

		if ( ! $blocked_post_id ) {
			return;
		}

		delete_transient( 'mirm_editorial_guard_blocked_' . get_current_user_id() );

		$post_title = get_the_title( $blocked_post_id );

		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: %s: Post title */
				esc_html__( 'MirM Editorial Guard blocked "%s" from publishing. Please complete all pre-flight checks in the editor sidebar before publishing.', 'mirm-editorial-guard' ),
				esc_html( $post_title )
			)
		);
	}
}
