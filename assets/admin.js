/**
 * Publish Gate — Admin Settings Page JavaScript
 *
 * Handles dynamic config field rendering for custom rule types,
 * AJAX add/delete of custom rules.
 *
 * @package Publish_Gate
 */

( function( $ ) {
	'use strict';

	var configTemplates = {
		content_contains: function() {
			return '<label>Text or pattern to search for:<br>' +
				'<input type="text" name="pg_config_pattern" class="regular-text" placeholder="e.g., call to action" /></label>' +
				'<br><label><input type="checkbox" name="pg_config_is_regex" value="1" /> Treat as regular expression</label>';
		},
		content_not_contains: function() {
			return '<label>Text or pattern that must NOT appear:<br>' +
				'<input type="text" name="pg_config_pattern" class="regular-text" placeholder="e.g., TODO|FIXME" /></label>' +
				'<br><label><input type="checkbox" name="pg_config_is_regex" value="1" /> Treat as regular expression</label>';
		},
		min_categories: function() {
			return '<label>Minimum number of categories:<br>' +
				'<input type="number" name="pg_config_min_count" value="1" min="1" max="100" class="small-text" /></label>';
		},
		min_tags: function() {
			return '<label>Minimum number of tags:<br>' +
				'<input type="number" name="pg_config_min_count" value="1" min="1" max="100" class="small-text" /></label>';
		},
		custom_field_required: function() {
			return '<label>Custom field (meta key) name:<br>' +
				'<input type="text" name="pg_config_field_name" class="regular-text" placeholder="e.g., seo_title" /></label>' +
				'<br><span class="description">The post must have this custom field set and non-empty.</span>';
		},
		min_heading_count: function() {
			return '<label>Minimum number of headings (H2/H3):<br>' +
				'<input type="number" name="pg_config_min_count" value="1" min="1" max="50" class="small-text" /></label>';
		},
		max_word_count: function() {
			return '<label>Maximum allowed word count:<br>' +
				'<input type="number" name="pg_config_max_words" value="5000" min="1" max="100000" class="small-text" /></label>';
		},
		required_block: function() {
			return '<label>Block type name:<br>' +
				'<input type="text" name="pg_config_block_name" class="regular-text" placeholder="e.g., core/quote" /></label>' +
				'<br><span class="description">e.g., core/quote, core/table, core/list, core/gallery, core/embed</span>';
		}
	};

	/**
	 * Show/hide config fields based on selected rule type.
	 */
	$( '#pg-new-rule-type' ).on( 'change', function() {
		var type = $( this ).val();
		var $configRow = $( '#pg-new-rule-config-row' );
		var $configContainer = $( '#pg-new-rule-config-container' );

		if ( type && configTemplates[ type ] ) {
			$configContainer.html( configTemplates[ type ]() );
			$configRow.show();
		} else {
			$configContainer.html( '' );
			$configRow.hide();
		}
	} );

	/**
	 * Collect config values from dynamic fields.
	 */
	function collectConfig() {
		var config = {};
		var type = $( '#pg-new-rule-type' ).val();

		switch ( type ) {
			case 'content_contains':
			case 'content_not_contains':
				config.pattern = $( 'input[name="pg_config_pattern"]' ).val() || '';
				config.is_regex = $( 'input[name="pg_config_is_regex"]' ).is( ':checked' ) ? '1' : '0';
				break;

			case 'min_categories':
			case 'min_tags':
			case 'min_heading_count':
				config.min_count = $( 'input[name="pg_config_min_count"]' ).val() || '1';
				break;

			case 'custom_field_required':
				config.field_name = $( 'input[name="pg_config_field_name"]' ).val() || '';
				break;

			case 'max_word_count':
				config.max_words = $( 'input[name="pg_config_max_words"]' ).val() || '5000';
				break;

			case 'required_block':
				config.block_name = $( 'input[name="pg_config_block_name"]' ).val() || '';
				break;
		}

		return config;
	}

	/**
	 * Prevent Enter key from submitting the main settings form while typing in the Add Rule section.
	 */
	$( '#publish-gate-add-rule' ).on( 'keydown', 'input, select', function( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			$( '#pg-add-rule-btn' ).click();
		}
	} );

	/**
	 * Add custom rule via AJAX.
	 */
	$( '#pg-add-rule-btn' ).on( 'click', function() {
		var label       = $( '#pg-new-rule-label' ).val().trim();
		var description = $( '#pg-new-rule-description' ).val().trim();
		var ruleType    = $( '#pg-new-rule-type' ).val();
		var config      = collectConfig();
		var $status     = $( '#pg-add-rule-status' );
		var $btn        = $( this );

		// Validate.
		if ( ! label ) {
			$status.text( 'Please enter a rule name.' ).removeClass( 'success' ).addClass( 'error' );
			return;
		}
		if ( ! ruleType ) {
			$status.text( 'Please select a rule type.' ).removeClass( 'success' ).addClass( 'error' );
			return;
		}

		$btn.prop( 'disabled', true );
		$( '#submit' ).prop( 'disabled', true ); // Prevent main form submission race condition
		$status.text( 'Adding rule…' ).removeClass( 'success error' );

		$.ajax( {
			url: publishGateAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'publish_gate_add_custom_rule',
				nonce: publishGateAdmin.nonce,
				label: label,
				description: description || label,
				rule_type: ruleType,
				config: config
			},
			success: function( response ) {
				if ( response.success ) {
					$status.text( publishGateAdmin.i18n.ruleAdded ).removeClass( 'error' ).addClass( 'success' );
					// Reload after a brief delay to show the new rule.
					setTimeout( function() {
						window.location.reload();
					}, 800 );
				} else {
					$status.text( response.data || publishGateAdmin.i18n.errorOccurred ).removeClass( 'success' ).addClass( 'error' );
					$btn.prop( 'disabled', false );
					$( '#submit' ).prop( 'disabled', false );
				}
			},
			error: function() {
				$status.text( publishGateAdmin.i18n.errorOccurred ).removeClass( 'success' ).addClass( 'error' );
				$btn.prop( 'disabled', false );
				$( '#submit' ).prop( 'disabled', false );
			}
		} );
	} );

	/**
	 * Delete custom rule via AJAX.
	 */
	$( document ).on( 'click', '.publish-gate-delete-rule', function() {
		var ruleId = $( this ).data( 'rule-id' );

		if ( ! confirm( publishGateAdmin.i18n.confirmDelete ) ) {
			return;
		}

		var $row = $( this ).closest( 'tr' );
		$row.css( 'opacity', '0.5' );

		$.ajax( {
			url: publishGateAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'publish_gate_delete_custom_rule',
				nonce: publishGateAdmin.nonce,
				rule_id: ruleId
			},
			success: function( response ) {
				if ( response.success ) {
					$row.fadeOut( 300, function() {
						$( this ).remove();
					} );
				} else {
					$row.css( 'opacity', '1' );
					alert( response.data || publishGateAdmin.i18n.errorOccurred );
				}
			},
			error: function() {
				$row.css( 'opacity', '1' );
				alert( publishGateAdmin.i18n.errorOccurred );
			}
		} );
	} );

} )( jQuery );
