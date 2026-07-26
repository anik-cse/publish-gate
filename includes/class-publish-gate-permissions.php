<?php
/**
 * Role-based permission management.
 *
 * @package Publish_Gate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Publish_Gate_Permissions
 */
class Publish_Gate_Permissions {

	/**
	 * @var array
	 */
	private $critical_rules = array( 'title_not_empty', 'no_placeholder' );

	/**
	 * Get rules with bypass permissions for the current user.
	 *
	 * @return array Associative array of rules with 'bypassable' flag.
	 */
	public function get_current_user_rules() {
		$rules       = Publish_Gate_Settings::get_rules();
		$user        = wp_get_current_user();
		$user_rules  = array();

		foreach ( $rules as $rule_id => $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			$bypassable = $this->can_user_bypass_rule( $user, $rule_id );

			$user_rules[ $rule_id ] = array(
				'id'          => $rule_id,
				'label'       => isset( $rule['label'] ) ? $rule['label'] : $rule_id,
				'description' => isset( $rule['description'] ) ? $rule['description'] : '',
				'critical'    => in_array( $rule_id, $this->critical_rules, true ),
				'bypassable'  => $bypassable,
				'config'      => isset( $rule['config'] ) ? $rule['config'] : array(),
			);
		}

		/**
		 * Filter the rules returned for the current user.
		 *
		 * @param array    $user_rules Computed rules with bypass flags.
		 * @param \WP_User $user       Current WordPress user object.
		 */
		return apply_filters( 'publish_gate_user_rules', $user_rules, $user );
	}

	/**
	 * Check if a user can bypass a specific rule.
	 *
	 * Hierarchy:
	 * - administrator: bypass ALL rules
	 * - editor: bypass non-critical rules only
	 * - author/contributor: bypass NONE
	 *
	 * @param \WP_User $user    The user to check.
	 * @param string   $rule_id The rule identifier.
	 * @return bool Whether the user can bypass this rule.
	 */
	private function can_user_bypass_rule( $user, $rule_id ) {
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		}

		if ( in_array( 'editor', (array) $user->roles, true ) ) {
			return ! in_array( $rule_id, $this->critical_rules, true );
		}

		return false;
	}

	/**
	 * Check if the current user can perform an override.
	 *
	 * @return bool
	 */
	public static function current_user_can_override() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return false;
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' );
	}
}
