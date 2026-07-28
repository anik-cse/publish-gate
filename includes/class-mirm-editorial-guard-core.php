<?php
/**
 * Core orchestrator for MirM Editorial Guard.
 *
 * @package MirM_Editorial_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MirM_Editorial_Guard_Core
 */
class MirM_Editorial_Guard_Core {

	/**
	 * @var MirM_Editorial_Guard_Core|null
	 */
	private static $instance = null;

	/**
	 * @return MirM_Editorial_Guard_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new MirM_Editorial_Guard_Permissions();
		new MirM_Editorial_Guard_Settings();
		new MirM_Editorial_Guard_Guard();
		new MirM_Editorial_Guard_REST();

		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'plugin_action_links_' . MIRM_EDITORIAL_GUARD_BASENAME, array( $this, 'add_settings_link' ) );
	}


	public function register_meta() {
		register_post_meta(
			'post',
			'_mirm_editorial_guard_passed_status',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			'_mirm_editorial_guard_override_reason',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}


	public function enqueue_editor_assets() {
		$asset_file = MIRM_EDITORIAL_GUARD_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'mirm-editorial-guard-sidebar',
			MIRM_EDITORIAL_GUARD_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'mirm-editorial-guard-sidebar-style',
			MIRM_EDITORIAL_GUARD_URL . 'build/index.css',
			array(),
			$asset['version']
		);

		$permissions = new MirM_Editorial_Guard_Permissions();
		$rules       = MirM_Editorial_Guard_Settings::get_rules();

		wp_localize_script(
			'mirm-editorial-guard-sidebar',
			'mirmEditorialGuardData',
			array(
				'rules'       => $rules,
				'permissions' => $permissions->get_current_user_rules(),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'restUrl'     => esc_url_raw( rest_url( 'mirm-editorial-guard/v1' ) ),
				'canOverride' => current_user_can( 'publish_posts' ) && ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ),
			)
		);
	}

	/**
	 * @param array $links
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=mirm-editorial-guard' ) ),
			esc_html__( 'Settings', 'mirm-editorial-guard' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
