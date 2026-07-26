/**
 * Client-side rule evaluation engine.
 */

import { serialize } from '@wordpress/blocks';

/**
 * Recursively collect blocks of a given type.
 */
const findBlocksByType = ( blocks, blockName ) => {
	let found = [];

	for ( const block of blocks ) {
		if ( block.name === blockName ) {
			found.push( block );
		}

		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			found = found.concat( findBlocksByType( block.innerBlocks, blockName ) );
		}
	}

	return found;
};

/**
 * Extract plain text content from blocks.
 */
const extractTextFromBlocks = ( blocks ) => {
	const html = serialize( blocks );
	const tempDiv = document.createElement( 'div' );
	tempDiv.innerHTML = html;
	return tempDiv.textContent || tempDiv.innerText || '';
};

/**
 * Evaluate all enabled rules against post data and blocks.
 */
export const evaluateRules = ( rulesConfig, postData, blocks ) => {
	const results = [];

	for ( const [ ruleId, rule ] of Object.entries( rulesConfig ) ) {
		if ( ! rule.enabled ) {
			continue;
		}

		let result;

		switch ( ruleId ) {
			case 'featured_image':
				result = evaluateFeaturedImage( rule, postData );
				break;

			case 'image_alt_text':
				result = evaluateImageAltText( rule, blocks );
				break;

			case 'min_word_count':
				result = evaluateMinWordCount( rule, blocks );
				break;

			case 'no_placeholder':
				result = evaluateNoPlaceholder( rule, blocks );
				break;

			case 'title_not_empty':
				result = evaluateTitleNotEmpty( rule, postData );
				break;

			case 'excerpt_required':
				result = evaluateExcerptRequired( rule, postData );
				break;

			case 'min_headings':
				result = evaluateMinHeadings( rule, blocks );
				break;

			case 'content_contains':
				result = evaluateContentContains( rule.config || {}, blocks );
				break;

			case 'content_not_contains':
				result = evaluateContentNotContains( rule.config || {}, blocks );
				break;

			case 'min_categories':
				result = {
					passed: true,
					message: 'Category count is validated on publish.',
				};
				break;

			case 'min_tags':
				result = {
					passed: true,
					message: 'Tag count is validated on publish.',
				};
				break;

			case 'custom_field_required':
				result = {
					passed: true,
					message: 'Custom field is validated on publish.',
				};
				break;

			case 'max_word_count':
				result = evaluateMaxWordCount( rule.config || {}, blocks );
				break;

			case 'required_block':
				result = evaluateRequiredBlock( rule.config || {}, blocks );
				break;

			default:
				result = {
					passed: true,
					message: 'Unknown rule — skipped.',
				};
		}

		results.push( {
			ruleId,
			label: rule.label || ruleId,
			...result,
		} );
	}

	return results;
};



/**
 * Check if featured image is set.
 */
const evaluateFeaturedImage = ( rule, postData ) => {
	const passed = postData.featured_media > 0;
	return {
		passed,
		message: passed
			? 'Featured image is set.'
			: 'A featured image is required before publishing.',
		panelToFocus: 'featured-image',
	};
};

/**
 * Check all image blocks for alt text.
 */
const evaluateImageAltText = ( rule, blocks ) => {
	const imageBlocks = findBlocksByType( blocks, 'core/image' );

	if ( imageBlocks.length === 0 ) {
		return {
			passed: true,
			message: 'No image blocks found.',
		};
	}

	const missingAlt = imageBlocks.filter( ( block ) => {
		const alt = block.attributes?.alt || '';
		return ! alt.trim();
	} );

	if ( missingAlt.length === 0 ) {
		return {
			passed: true,
			message: `All ${ imageBlocks.length } image(s) have alt text.`,
		};
	}

	return {
		passed: false,
		message: `${ missingAlt.length } image(s) missing alt text.`,
		blockClientIds: missingAlt.map( ( block ) => block.clientId ),
	};
};

/**
 * Check minimum word count.
 */
const evaluateMinWordCount = ( rule, blocks ) => {
	const minWords = rule.config?.min_words || 300;
	const text = extractTextFromBlocks( blocks ).trim();
	const wordCount = text ? text.split( /\s+/ ).length : 0;
	const passed = wordCount >= minWords;

	return {
		passed,
		message: passed
			? `Word count: ${ wordCount } (meets minimum of ${ minWords }).`
			: `Word count: ${ wordCount } (minimum ${ minWords } required).`,
	};
};

/**
 * Check for placeholder/dummy text (Lorem Ipsum).
 * Optimized with early exit for performance on large posts.
 */
const evaluateNoPlaceholder = ( rule, blocks ) => {
	const text = extractTextFromBlocks( blocks );
	const hasPlaceholder = /lorem\s+ipsum/i.test( text );

	return {
		passed: ! hasPlaceholder,
		message: ! hasPlaceholder
			? 'No placeholder text detected.'
			: 'Placeholder text (Lorem Ipsum) detected — please replace with real content.',
	};
};

/**
 * Check that the post title meets the minimum length.
 */
const evaluateTitleNotEmpty = ( rule, postData ) => {
	const minWords = rule.config?.min_words || 1;
	const title = postData.title.trim();
	const wordCount = title ? title.split( /\s+/ ).length : 0;
	const passed = wordCount >= minWords;

	return {
		passed,
		message: passed
			? `Title word count: ${ wordCount } (meets minimum of ${ minWords }).`
			: `Title word count: ${ wordCount } (minimum ${ minWords } required).`,
	};
};

/**
 * Check that the post excerpt is not empty.
 */
const evaluateExcerptRequired = ( rule, postData ) => {
	const passed = postData.excerpt.trim().length > 0;
	return {
		passed,
		message: passed
			? 'Excerpt is set.'
			: 'An excerpt is required before publishing.',
		panelToFocus: 'post-excerpt',
	};
};

/**
 * Check that the post contains a minimum number of headings.
 */
const evaluateMinHeadings = ( rule, blocks ) => {
	const minCount = rule.config?.min_count || 3;
	const headings = findBlocksByType( blocks, 'core/heading' );
	const count = headings.length;
	const passed = count >= minCount;

	return {
		passed,
		message: passed
			? `Found ${ count } heading(s) (meets minimum of ${ minCount }).`
			: `Found ${ count } heading(s) (minimum ${ minCount } required).`,
	};
};

/* ===== Custom Rule Evaluators ===== */

/**
 * Check if content contains a specific pattern.
 */
const evaluateContentContains = ( config, blocks ) => {
	const pattern = config.pattern || '';
	if ( ! pattern ) {
		return { passed: true, message: 'No pattern configured.' };
	}

	const text = extractTextFromBlocks( blocks );
	let found = false;

	if ( config.is_regex === '1' || config.is_regex === true ) {
		try {
			const regex = new RegExp( pattern, 'i' );
			found = regex.test( text );
		} catch ( e ) {
			return { passed: true, message: 'Invalid regex pattern.' };
		}
	} else {
		found = text.toLowerCase().includes( pattern.toLowerCase() );
	}

	return {
		passed: found,
		message: found
			? `Content contains "${ pattern }".`
			: `Content must contain "${ pattern }".`,
	};
};

/**
 * Check if content does NOT contain a specific pattern.
 */
const evaluateContentNotContains = ( config, blocks ) => {
	const pattern = config.pattern || '';
	if ( ! pattern ) {
		return { passed: true, message: 'No pattern configured.' };
	}

	const text = extractTextFromBlocks( blocks );
	let found = false;

	if ( config.is_regex === '1' || config.is_regex === true ) {
		try {
			const regex = new RegExp( pattern, 'i' );
			found = regex.test( text );
		} catch ( e ) {
			return { passed: true, message: 'Invalid regex pattern.' };
		}
	} else {
		found = text.toLowerCase().includes( pattern.toLowerCase() );
	}

	return {
		passed: ! found,
		message: ! found
			? `Content does not contain "${ pattern }".`
			: `Content must NOT contain "${ pattern }".`,
	};
};

/**
 * Check minimum heading count (H2/H3).
 */
const evaluateMinHeadingCount = ( config, blocks ) => {
	const minCount = parseInt( config.min_count, 10 ) || 1;
	const headings = findBlocksByType( blocks, 'core/heading' ).filter( ( block ) => {
		const level = block.attributes?.level || 2;
		return level >= 2 && level <= 3;
	} );

	const count = headings.length;
	const passed = count >= minCount;

	return {
		passed,
		message: passed
			? `Found ${ count } heading(s) (minimum ${ minCount }).`
			: `Found ${ count } heading(s) (minimum ${ minCount } required).`,
	};
};

/**
 * Check maximum word count.
 */
const evaluateMaxWordCount = ( config, blocks ) => {
	const maxWords = parseInt( config.max_words, 10 ) || 5000;
	const text = extractTextFromBlocks( blocks ).trim();
	const wordCount = text ? text.split( /\s+/ ).length : 0;
	const passed = wordCount <= maxWords;

	return {
		passed,
		message: passed
			? `Word count: ${ wordCount } (within limit of ${ maxWords }).`
			: `Word count: ${ wordCount } (maximum ${ maxWords } allowed).`,
	};
};

/**
 * Check if a required block type is present.
 */
const evaluateRequiredBlock = ( config, blocks ) => {
	const blockNameRaw = config.block_name || '';
	const requiredBlocks = blockNameRaw
		.split( ',' )
		.map( ( b ) => b.trim() )
		.filter( Boolean );

	if ( requiredBlocks.length === 0 ) {
		return { passed: true, message: 'No block type configured.' };
	}

	const missingBlocks = requiredBlocks.filter( ( blockName ) => {
		const found = findBlocksByType( blocks, blockName );
		return found.length === 0;
	} );

	const passed = missingBlocks.length === 0;

	return {
		passed,
		message: passed
			? `Required block(s) found.`
			: `Missing required block(s): ${ missingBlocks.join( ', ' ) }`,
	};
};
