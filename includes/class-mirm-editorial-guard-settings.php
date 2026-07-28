<?php
/**
 * Admin settings page and rules management.
 *
 * @package MirM_Editorial_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MirM_Editorial_Guard_Settings
 */
class MirM_Editorial_Guard_Settings {

	/**
	 * Option key in wp_options for built-in rules.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'mirm_editorial_guard_rules';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Get the default built-in rules configuration.
	 *
	 * @return array
	 */
	public static function get_default_rules() {
		return array(
			'featured_image'  => array(
				'enabled'     => true,
				'label'       => __( 'Featured Image Required', 'mirm-editorial-guard' ),
				'description' => __( 'Post must have a featured image set.', 'mirm-editorial-guard' ),
				'config'      => array(),
				'custom'      => false,
			),
			'image_alt_text'  => array(
				'enabled'     => true,
				'label'       => __( 'Alt Text on All Images', 'mirm-editorial-guard' ),
				'description' => __( 'Every image block must have a non-empty alt attribute.', 'mirm-editorial-guard' ),
				'config'      => array(),
				'custom'      => false,
			),
			'min_word_count'  => array(
				'enabled'     => true,
				'label'       => __( 'Minimum Word Count', 'mirm-editorial-guard' ),
				'description' => __( 'Post body must meet the minimum word count.', 'mirm-editorial-guard' ),
				'config'      => array(
					'min_words' => 300,
				),
				'custom'      => false,
			),
			'no_placeholder'  => array(
				'enabled'     => true,
				'label'       => __( 'No Placeholder Text', 'mirm-editorial-guard' ),
				'description' => __( 'Body must not contain Lorem Ipsum or similar dummy text.', 'mirm-editorial-guard' ),
				'config'      => array(),
				'custom'      => false,
			),
			'title_not_empty' => array(
				'enabled'     => true,
				'label'       => __( 'Minimum Title Length', 'mirm-editorial-guard' ),
				'description' => __( 'Post title must meet the minimum word count.', 'mirm-editorial-guard' ),
				'config'      => array(
					'min_words' => 1,
				),
				'custom'      => false,
				'config'      => array( 'min_words' => 300 ),
			),
			'min_headings'    => array(
				'enabled'     => true,
				'label'       => __( 'Minimum Headings (Titles)', 'mirm-editorial-guard' ),
				'description' => __( 'Post must contain at least the specified number of headings (H1-H6).', 'mirm-editorial-guard' ),
				'config'      => array( 'min_count' => 3 ),
			),
			'title_not_empty' => array(
				'enabled'     => true,
				'label'       => __( 'Title Required', 'mirm-editorial-guard' ),
				'description' => __( 'The post title cannot be empty.', 'mirm-editorial-guard' ),
			),
			'require_featured_image' => array(
				'enabled'     => true,
				'label'       => __( 'Featured Image Required', 'mirm-editorial-guard' ),
				'description' => __( 'A featured image must be set.', 'mirm-editorial-guard' ),
			),
			'require_excerpt' => array(
				'enabled'     => true,
				'label'       => __( 'Excerpt Required', 'mirm-editorial-guard' ),
				'description' => __( 'A manual excerpt must be provided.', 'mirm-editorial-guard' ),
			),
			'content_contains' => array(
				'enabled'     => false,
				'label'       => __( 'Content Must Contain', 'mirm-editorial-guard' ),
				'description' => __( 'Post content must contain a specific text or pattern (e.g. "Copyright", "Subscribe").', 'mirm-editorial-guard' ),
				'config'      => array( 'pattern' => '', 'is_regex' => '0' ),
			),
			'content_not_contains' => array(
				'enabled'     => false,
				'label'       => __( 'Content Must NOT Contain', 'mirm-editorial-guard' ),
				'description' => __( 'Post content must NOT contain a specific text or pattern (e.g. "Lorem Ipsum", "test data").', 'mirm-editorial-guard' ),
				'config'      => array( 'pattern' => '', 'is_regex' => '0' ),
			),
			'min_categories' => array(
				'enabled'     => false,
				'label'       => __( 'Minimum Categories', 'mirm-editorial-guard' ),
				'description' => __( 'Post must have at least this many categories.', 'mirm-editorial-guard' ),
				'config'      => array( 'min_count' => 1 ),
			),
			'min_tags' => array(
				'enabled'     => false,
				'label'       => __( 'Minimum Tags', 'mirm-editorial-guard' ),
				'description' => __( 'Post must have at least this many tags.', 'mirm-editorial-guard' ),
				'config'      => array( 'min_count' => 1 ),
			),
			'custom_field_required' => array(
				'enabled'     => false,
				'label'       => __( 'Custom Field Required', 'mirm-editorial-guard' ),
				'description' => __( 'A specific custom meta field must be filled out (e.g. "_yoast_wpseo_title", "guest_author_name").', 'mirm-editorial-guard' ),
				'config'      => array( 'field_name' => '' ),
			),
			'max_word_count' => array(
				'enabled'     => false,
				'label'       => __( 'Maximum Word Count', 'mirm-editorial-guard' ),
				'description' => __( 'The post cannot exceed this many words.', 'mirm-editorial-guard' ),
				'config'      => array( 'max_words' => 1000 ),
			),
			'required_block' => array(
				'enabled'     => false,
				'label'       => __( 'Required Block(s)', 'mirm-editorial-guard' ),
				'description' => __( 'The post must contain specific block types. Separate multiple blocks with commas (e.g. "core/image, core/heading").', 'mirm-editorial-guard' ),
				'config'      => array( 'block_name' => 'core/image' ),
			),
		);
	}

	/**
	 * Get current built-in rules configuration with corrupted-option fallback.
	 *
	 * @return array
	 */
	public static function get_rules() {
		$rules = get_option( self::OPTION_KEY, false );

		// Corrupted option fallback: if empty, not an array, or false, use defaults.
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			$rules = self::get_default_rules();
			// Silently repair the option.
			update_option( self::OPTION_KEY, $rules );
		}

		// Ensure newly added default rules (like content_contains) exist in the saved array,
		// and always override label/description with the latest code-based definitions.
		$defaults = self::get_default_rules();
		$updated = false;
		foreach ( $defaults as $key => $default_rule ) {
			if ( ! isset( $rules[ $key ] ) ) {
				$rules[ $key ] = $default_rule;
				$updated = true;
			} else {
				// Always use the latest label and description from code
				if ( $rules[ $key ]['label'] !== $default_rule['label'] || $rules[ $key ]['description'] !== $default_rule['description'] ) {
					$rules[ $key ]['label'] = $default_rule['label'];
					$rules[ $key ]['description'] = $default_rule['description'];
					$updated = true;
				}
			}
		}
		
		if ( $updated ) {
			update_option( self::OPTION_KEY, $rules );
		}

		return $rules;
	}

	/**
	 * Add settings page under Settings menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'MirM Editorial Guard Settings', 'mirm-editorial-guard' ),
			__( 'MirM Editorial Guard', 'mirm-editorial-guard' ),
			'manage_options',
			'mirm-editorial-guard',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles for the settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_mirm-editorial-guard' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'mirm-editorial-guard-admin',
			MIRM_EDITORIAL_GUARD_URL . 'assets/admin.css',
			array(),
			MIRM_EDITORIAL_GUARD_VERSION
		);
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		register_setting(
			'mirm_editorial_guard_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_rules' ),
				'default'           => self::get_default_rules(),
			)
		);

		// Built-in rules section.
		add_settings_section(
			'mirm_editorial_guard_rules_section',
			__( 'Publication Rules', 'mirm-editorial-guard' ),
			array( $this, 'render_section_description' ),
			'mirm-editorial-guard'
		);

		// Register built-in rule fields.
		$rules = self::get_rules();
		foreach ( $rules as $rule_id => $rule ) {
			add_settings_field(
				'mirm_editorial_guard_rule_' . $rule_id,
				esc_html( $rule['label'] ),
				array( $this, 'render_rule_field' ),
				'mirm-editorial-guard',
				'mirm_editorial_guard_rules_section',
				array(
					'rule_id' => $rule_id,
					'rule'    => $rule,
				)
			);
		}
	}

	/**
	 * Sanitize the built-in rules array before saving.
	 *
	 * @param mixed $input Raw input from the form.
	 * @return array Sanitized rules.
	 */
	public function sanitize_rules( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_default_rules();
		}

		$defaults  = self::get_default_rules();
		$sanitized = array();

		foreach ( $defaults as $rule_id => $default_rule ) {
			$sanitized[ $rule_id ] = array(
				'enabled'     => ! empty( $input[ $rule_id ]['enabled'] ),
				'label'       => $default_rule['label'],
				'description' => $default_rule['description'],
				'config'      => isset( $default_rule['config'] ) ? $default_rule['config'] : array(),
			);

			// Sanitize per-rule config
			if ( isset( $input[ $rule_id ]['config'] ) && is_array( $input[ $rule_id ]['config'] ) ) {
				foreach ( $input[ $rule_id ]['config'] as $config_key => $config_value ) {
					$sanitized[ $rule_id ]['config'][ sanitize_key( $config_key ) ] = sanitize_text_field( $config_value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Render section description for built-in rules.
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Enable or disable individual pre-flight checks. Posts must pass all enabled checks before publishing.', 'mirm-editorial-guard' ) . '</p>';
	}

	/**
	 * Render an individual built-in rule field.
	 *
	 * @param array $args Field arguments containing rule_id and rule data.
	 */
	public function render_rule_field( $args ) {
		$rule_id      = $args['rule_id'];
		$rule         = $args['rule'];
		$defaults     = self::get_default_rules();
		$default_rule = isset( $defaults[ $rule_id ] ) ? $defaults[ $rule_id ] : array();
		$enabled      = ! empty( $rule['enabled'] );

		printf(
			'<label><input type="checkbox" name="%s[%s][enabled]" value="1" %s /> %s</label>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $rule_id ),
			checked( $enabled, true, false ),
			esc_html( $rule['description'] )
		);

		if ( isset( $default_rule['config']['min_words'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="number" name="%s[%s][config][min_words]" value="%d" class="small-text" /></label></div>',
				esc_html__( 'Minimum words:', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				absint( isset( $rule['config']['min_words'] ) ? $rule['config']['min_words'] : $default_rule['config']['min_words'] )
			);
		}

		if ( isset( $default_rule['config']['min_count'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="number" name="%s[%s][config][min_count]" value="%d" class="small-text" /></label></div>',
				esc_html__( 'Minimum count:', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				absint( isset( $rule['config']['min_count'] ) ? $rule['config']['min_count'] : $default_rule['config']['min_count'] )
			);
		}
		
		if ( isset( $default_rule['config']['pattern'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="text" name="%s[%s][config][pattern]" value="%s" class="regular-text" /></label></div>',
				esc_html__( 'Text/Pattern:', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				esc_attr( isset( $rule['config']['pattern'] ) ? $rule['config']['pattern'] : '' )
			);
			$is_regex = ! empty( $rule['config']['is_regex'] );
			printf(
				'<div style="margin-top: 8px;"><label><input type="checkbox" name="%s[%s][config][is_regex]" value="1" %s /> %s</label></div>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				checked( $is_regex, true, false ),
				esc_html__( 'Treat as Regex', 'mirm-editorial-guard' )
			);
		}
		
		if ( isset( $default_rule['config']['field_name'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="text" name="%s[%s][config][field_name]" value="%s" class="regular-text" placeholder="_yoast_wpseo_title" /></label></div>',
				esc_html__( 'Meta field key:', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				esc_attr( isset( $rule['config']['field_name'] ) ? $rule['config']['field_name'] : '' )
			);
		}

		if ( isset( $default_rule['config']['max_words'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="number" name="%s[%s][config][max_words]" value="%d" class="small-text" /></label></div>',
				esc_html__( 'Maximum words:', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				absint( isset( $rule['config']['max_words'] ) ? $rule['config']['max_words'] : $default_rule['config']['max_words'] )
			);
		}

		if ( isset( $default_rule['config']['block_name'] ) ) {
			printf(
				'<div style="margin-top: 8px;"><label>%s <input type="text" name="%s[%s][config][block_name]" value="%s" class="regular-text" placeholder="core/image, core/heading" /></label></div>',
				esc_html__( 'Block Name(s) (comma separated):', 'mirm-editorial-guard' ),
				esc_attr( self::OPTION_KEY ),
				esc_attr( $rule_id ),
				esc_attr( isset( $rule['config']['block_name'] ) ? $rule['config']['block_name'] : '' )
			);
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'mirm_editorial_guard_settings_group' );
				do_settings_sections( 'mirm-editorial-guard' );
				submit_button( __( 'Save Rules', 'mirm-editorial-guard' ) );
				?>
			</form>
		</div>
		<?php
	}
}
