<?php
/**
 * Custom endpoints for the editor sidebar.
 *
 * @package MirM_Editorial_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MirM_Editorial_Guard_REST
 */
class MirM_Editorial_Guard_REST {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'mirm-editorial-guard/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		// GET /mirm-editorial-guard/v1/user-rules
		register_rest_route(
			self::NAMESPACE,
			'/user-rules',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_rules' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// POST /mirm-editorial-guard/v1/validate
		register_rest_route(
			self::NAMESPACE,
			'/validate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_post' ),
				'permission_callback' => function ( $request ) {
					$post_id = $request->get_param( 'post_id' );
					return current_user_can( 'edit_post', $post_id );
				},
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return is_numeric( $value ) && $value > 0;
						},
					),
				),
			)
		);

		// POST /mirm-editorial-guard/v1/override
		register_rest_route(
			self::NAMESPACE,
			'/override',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'override_publish' ),
				'permission_callback' => function ( $request ) {
					$post_id = $request->get_param( 'post_id' );
					if ( ! current_user_can( 'edit_post', $post_id ) ) {
						return false;
					}
					return MirM_Editorial_Guard_Permissions::current_user_can_override();
				},
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'reason'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_user_rules( $request ) {
		$permissions = new MirM_Editorial_Guard_Permissions();
		$user_rules  = $permissions->get_current_user_rules();

		return rest_ensure_response( $user_rules );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function validate_post( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error(
				'mirm_editorial_guard_invalid_post',
				__( 'Post not found.', 'mirm-editorial-guard' ),
				array( 'status' => 404 )
			);
		}

		$rules   = MirM_Editorial_Guard_Settings::get_rules();
		$results = array();

		foreach ( $rules as $rule_id => $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			$results[ $rule_id ] = $this->evaluate_rule_server_side( $rule_id, $rule, $post );
		}

		// Determine overall pass/fail.
		$all_passed = true;
		foreach ( $results as $result ) {
			if ( ! $result['passed'] ) {
				$all_passed = false;
				break;
			}
		}

		// Update post meta.
		if ( $all_passed ) {
			update_post_meta( $post_id, '_mirm_editorial_guard_passed_status', 'passed' );
		} else {
			delete_post_meta( $post_id, '_mirm_editorial_guard_passed_status' );
		}

		return rest_ensure_response(
			array(
				'post_id'    => $post_id,
				'all_passed' => $all_passed,
				'results'    => $results,
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function override_publish( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$reason  = $request->get_param( 'reason' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'mirm_editorial_guard_invalid_post',
				__( 'Post not found.', 'mirm-editorial-guard' ),
				array( 'status' => 404 )
			);
		}

		// Record override.
		update_post_meta( $post_id, '_mirm_editorial_guard_override_reason', $reason );
		update_post_meta( $post_id, '_mirm_editorial_guard_passed_status', 'overridden' );

		return rest_ensure_response(
			array(
				'post_id'  => $post_id,
				'status'   => 'overridden',
				'reason'   => $reason,
				'message'  => __( 'Override recorded. You may now publish.', 'mirm-editorial-guard' ),
			)
		);
	}

	/**
	 * @param string   $rule_id
	 * @param array    $rule
	 * @param \WP_Post $post
	 * @return array
	 */
	private function evaluate_rule_server_side( $rule_id, $rule, $post ) {
		switch ( $rule_id ) {
			case 'featured_image':
				$has_thumbnail = has_post_thumbnail( $post->ID );
				return array(
					'passed'  => $has_thumbnail,
					'message' => $has_thumbnail
						? __( 'Featured image is set.', 'mirm-editorial-guard' )
						: __( 'Featured image is missing.', 'mirm-editorial-guard' ),
				);

			case 'image_alt_text':
				return $this->check_image_alt_text( $post );

			case 'min_word_count':
				$min_words  = isset( $rule['config']['min_words'] ) ? absint( $rule['config']['min_words'] ) : 300;
				$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
				$passed     = $word_count >= $min_words;
				return array(
					'passed'  => $passed,
					'message' => $passed
						? sprintf(
							/* translators: %d: word count */
							__( 'Word count: %d (meets minimum).', 'mirm-editorial-guard' ),
							$word_count
						)
						: sprintf(
							/* translators: 1: current word count, 2: minimum required */
							__( 'Word count: %1$d (minimum %2$d required).', 'mirm-editorial-guard' ),
							$word_count,
							$min_words
						),
				);

			case 'no_placeholder':
				$has_placeholder = (bool) preg_match( '/lorem\s+ipsum/i', $post->post_content );
				return array(
					'passed'  => ! $has_placeholder,
					'message' => ! $has_placeholder
						? __( 'No placeholder text detected.', 'mirm-editorial-guard' )
						: __( 'Placeholder text (Lorem Ipsum) detected.', 'mirm-editorial-guard' ),
				);

			case 'title_not_empty':
				$min_words  = isset( $rule['config']['min_words'] ) ? absint( $rule['config']['min_words'] ) : 1;
				$title      = trim( $post->post_title );
				$word_count = empty( $title ) ? 0 : str_word_count( $title );
				$passed     = $word_count >= $min_words;
				if ( $passed ) {
					/* translators: %d: word count */
					$msg = sprintf( __( 'Title word count: %d (meets minimum).', 'mirm-editorial-guard' ), $word_count );
				} else {
					/* translators: 1: current word count, 2: minimum required */
					$msg = sprintf( __( 'Title word count: %1$d (minimum %2$d required).', 'mirm-editorial-guard' ), $word_count, $min_words );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'excerpt_required':
				$has_excerpt = ! empty( trim( $post->post_excerpt ) );
				return array(
					'passed'  => $has_excerpt,
					'message' => $has_excerpt
						? __( 'Excerpt is set.', 'mirm-editorial-guard' )
						: __( 'Excerpt is empty.', 'mirm-editorial-guard' ),
				);

			case 'min_headings':
				$min_count = isset( $rule['config']['min_count'] ) ? absint( $rule['config']['min_count'] ) : 3;
				
				// Parse blocks to count headings.
				$blocks = parse_blocks( $post->post_content );
				$count  = 0;
				
				$count_headings = function( $blocks ) use ( &$count_headings, &$count ) {
					foreach ( $blocks as $block ) {
						if ( 'core/heading' === $block['blockName'] ) {
							$count++;
						}
						if ( ! empty( $block['innerBlocks'] ) ) {
							$count_headings( $block['innerBlocks'] );
						}
					}
				};
				
				$count_headings( $blocks );
				$passed = $count >= $min_count;

				if ( $passed ) {
					/* translators: %d: number of headings */
					$msg = sprintf( __( 'Found %d heading(s) (meets minimum).', 'mirm-editorial-guard' ), $count );
				} else {
					/* translators: 1: found headings, 2: minimum required headings */
					$msg = sprintf( __( 'Found %1$d heading(s) (minimum %2$d required).', 'mirm-editorial-guard' ), $count, $min_count );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'content_contains':
				$pattern   = isset( $rule['config']['pattern'] ) ? $rule['config']['pattern'] : '';
				$is_regex  = ! empty( $rule['config']['is_regex'] );
				$content   = wp_strip_all_tags( $post->post_content );

				if ( empty( $pattern ) ) {
					return array( 'passed' => true, 'message' => __( 'No pattern configured.', 'mirm-editorial-guard' ) );
				}

				if ( $is_regex ) {
					$found = (bool) @preg_match( '/' . $pattern . '/i', $content );
				} else {
					$found = ( false !== stripos( $content, $pattern ) );
				}

				if ( $found ) {
					/* translators: %s: text pattern */
					$msg = sprintf( __( 'Content contains "%s".', 'mirm-editorial-guard' ), $pattern );
				} else {
					/* translators: %s: text pattern */
					$msg = sprintf( __( 'Content must contain "%s".', 'mirm-editorial-guard' ), $pattern );
				}
				return array(
					'passed'  => $found,
					'message' => $msg,
				);

			case 'content_not_contains':
				$pattern   = isset( $rule['config']['pattern'] ) ? $rule['config']['pattern'] : '';
				$is_regex  = ! empty( $rule['config']['is_regex'] );
				$content   = wp_strip_all_tags( $post->post_content );

				if ( empty( $pattern ) ) {
					return array( 'passed' => true, 'message' => __( 'No pattern configured.', 'mirm-editorial-guard' ) );
				}

				if ( $is_regex ) {
					$found = (bool) @preg_match( '/' . $pattern . '/i', $content );
				} else {
					$found = ( false !== stripos( $content, $pattern ) );
				}

				if ( ! $found ) {
					/* translators: %s: text pattern */
					$msg = sprintf( __( 'Content does not contain "%s".', 'mirm-editorial-guard' ), $pattern );
				} else {
					/* translators: %s: text pattern */
					$msg = sprintf( __( 'Content must NOT contain "%s".', 'mirm-editorial-guard' ), $pattern );
				}
				return array(
					'passed'  => ! $found,
					'message' => $msg,
				);

			case 'min_categories':
				$min_count  = isset( $rule['config']['min_count'] ) ? absint( $rule['config']['min_count'] ) : 1;
				$categories = wp_get_post_categories( $post->ID );
				$count      = is_array( $categories ) ? count( $categories ) : 0;
				$passed     = $count >= $min_count;

				if ( $passed ) {
					/* translators: 1: current categories count, 2: minimum required categories */
					$msg = sprintf( __( 'Post has %1$d categories (minimum %2$d).', 'mirm-editorial-guard' ), $count, $min_count );
				} else {
					/* translators: 1: current categories count, 2: minimum required categories */
					$msg = sprintf( __( 'Post has %1$d categories (minimum %2$d required).', 'mirm-editorial-guard' ), $count, $min_count );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'min_tags':
				$min_count = isset( $rule['config']['min_count'] ) ? absint( $rule['config']['min_count'] ) : 1;
				$tags      = wp_get_post_tags( $post->ID );
				$count     = is_array( $tags ) ? count( $tags ) : 0;
				$passed    = $count >= $min_count;

				if ( $passed ) {
					/* translators: 1: current tags count, 2: minimum required tags */
					$msg = sprintf( __( 'Post has %1$d tags (minimum %2$d).', 'mirm-editorial-guard' ), $count, $min_count );
				} else {
					/* translators: 1: current tags count, 2: minimum required tags */
					$msg = sprintf( __( 'Post has %1$d tags (minimum %2$d required).', 'mirm-editorial-guard' ), $count, $min_count );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'custom_field_required':
				$field_name = isset( $rule['config']['field_name'] ) ? sanitize_key( $rule['config']['field_name'] ) : '';

				if ( empty( $field_name ) ) {
					return array( 'passed' => true, 'message' => __( 'No field name configured.', 'mirm-editorial-guard' ) );
				}

				$value  = get_post_meta( $post->ID, $field_name, true );
				$passed = ! empty( $value );

				if ( $passed ) {
					/* translators: %s: custom field name */
					$msg = sprintf( __( 'Custom field "%s" is set.', 'mirm-editorial-guard' ), $field_name );
				} else {
					/* translators: %s: custom field name */
					$msg = sprintf( __( 'Custom field "%s" is required.', 'mirm-editorial-guard' ), $field_name );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'max_word_count':
				$max_words  = isset( $rule['config']['max_words'] ) ? absint( $rule['config']['max_words'] ) : 1000;
				$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
				$passed     = $word_count <= $max_words;
				if ( $passed ) {
					/* translators: %d: word count */
					$msg = sprintf( __( 'Word count: %d (meets maximum).', 'mirm-editorial-guard' ), $word_count );
				} else {
					/* translators: 1: current word count, 2: maximum allowed words */
					$msg = sprintf( __( 'Word count: %1$d (maximum %2$d allowed).', 'mirm-editorial-guard' ), $word_count, $max_words );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			case 'required_block':
				$block_name_raw = isset( $rule['config']['block_name'] ) ? sanitize_text_field( $rule['config']['block_name'] ) : 'core/image';
				$required_blocks = array_filter( array_map( 'trim', explode( ',', $block_name_raw ) ) );

				if ( empty( $required_blocks ) ) {
					return array( 'passed' => true, 'message' => __( 'No block specified.', 'mirm-editorial-guard' ) );
				}

				$blocks = parse_blocks( $post->post_content );
				
				// Collect all block names present in the post
				$found_blocks = array();
				$check_blocks = function( $blocks ) use ( &$check_blocks, &$found_blocks ) {
					foreach ( $blocks as $block ) {
						if ( ! empty( $block['blockName'] ) ) {
							$found_blocks[] = $block['blockName'];
						}
						if ( ! empty( $block['innerBlocks'] ) ) {
							$check_blocks( $block['innerBlocks'] );
						}
					}
				};
				
				$check_blocks( $blocks );

				$missing_blocks = array_diff( $required_blocks, $found_blocks );
				$passed = empty( $missing_blocks );

				if ( $passed ) {
					$msg = __( 'Required block(s) present.', 'mirm-editorial-guard' );
				} else {
					/* translators: %s: comma-separated list of missing block names */
					$msg = sprintf( __( 'Missing required block(s): %s', 'mirm-editorial-guard' ), implode( ', ', $missing_blocks ) );
				}
				return array(
					'passed'  => $passed,
					'message' => $msg,
				);

			default:
				return array(
					'passed'  => true,
					'message' => __( 'Unknown rule — skipped.', 'mirm-editorial-guard' ),
				);
		}
	}

	/**
	 * @param \WP_Post $post
	 * @return array
	 */
	private function check_image_alt_text( $post ) {
		$blocks = parse_blocks( $post->post_content );
		$missing = $this->find_images_missing_alt( $blocks );

		if ( empty( $missing ) ) {
			return array(
				'passed'  => true,
				'message' => __( 'All images have alt text.', 'mirm-editorial-guard' ),
			);
		}

		return array(
			'passed'  => false,
			'message' => sprintf(
				/* translators: %d: number of images missing alt text */
				_n(
					'%d image is missing alt text.',
					'%d images are missing alt text.',
					count( $missing ),
					'mirm-editorial-guard'
				),
				count( $missing )
			),
		);
	}

	/**
	 * @param array $blocks
	 * @return array
	 */
	private function find_images_missing_alt( $blocks ) {
		$missing = array();

		foreach ( $blocks as $index => $block ) {
			if ( 'core/image' === $block['blockName'] ) {
				$alt = isset( $block['attrs']['alt'] ) ? trim( $block['attrs']['alt'] ) : '';
				if ( empty( $alt ) ) {
					$missing[] = $index;
				}
			}

			// Recurse into inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_missing = $this->find_images_missing_alt( $block['innerBlocks'] );
				$missing       = array_merge( $missing, $inner_missing );
			}
		}

		return $missing;
	}


}
