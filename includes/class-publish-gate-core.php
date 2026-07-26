<?php
/**
 * Core orchestrator for Publish Gate.
 *
 * @package Publish_Gate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Publish_Gate_Core
 */
class Publish_Gate_Core {

	/**
	 * @var Publish_Gate_Core|null
	 */
	private static $instance = null;

	/**
	 * @return Publish_Gate_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new Publish_Gate_Permissions();
		new Publish_Gate_Settings();
		new Publish_Gate_Guard();
		new Publish_Gate_REST();

		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'plugin_action_links_' . PUBLISH_GATE_BASENAME, array( $this, 'add_settings_link' ) );
	}


	public function register_meta() {
		register_post_meta(
			'post',
			'_publish_gate_passed_status',
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
			'_publish_gate_override_reason',
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
		$asset_file = PUBLISH_GATE_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'publish-gate-sidebar',
			PUBLISH_GATE_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'publish-gate-sidebar-style',
			PUBLISH_GATE_URL . 'build/index.css',
			array(),
			$asset['version']
		);

		$permissions = new Publish_Gate_Permissions();
		$rules       = Publish_Gate_Settings::get_rules();

		wp_localize_script(
			'publish-gate-sidebar',
			'publishGateData',
			array(
				'rules'       => $rules,
				'permissions' => $permissions->get_current_user_rules(),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'restUrl'     => esc_url_raw( rest_url( 'publish-gate/v1' ) ),
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
			esc_url( admin_url( 'options-general.php?page=publish-gate' ) ),
			esc_html__( 'Settings', 'publish-gate' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
