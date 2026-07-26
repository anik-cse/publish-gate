/**
 * scrollToBlock — Focus utility for "Jump to Block" feature.
 *
 * Selects a block by clientId, scrolls it into view,
 * and applies a temporary highlight animation.
 *
 * @package Publish_Gate
 */

import { dispatch, select } from '@wordpress/data';

/**
 * Scroll to a specific block and briefly highlight it.
 *
 * @param {string} clientId The Gutenberg block clientId to focus.
 */
export const scrollToBlock = ( clientId ) => {
	if ( ! clientId ) {
		return;
	}

	// 1. Select the block in the editor.
	dispatch( 'core/block-editor' ).selectBlock( clientId );

	// 2. Wait a tick for the editor to update selection, then scroll.
	requestAnimationFrame( () => {
		const blockElement = document.querySelector(
			`[data-block="${ clientId }"]`
		);

		if ( ! blockElement ) {
			return;
		}

		// 3. Scroll the block into view.
		blockElement.scrollIntoView( {
			behavior: 'smooth',
			block: 'center',
		} );

		// 4. Add highlight class.
		blockElement.classList.add( 'publish-gate-highlight' );

		// 5. Remove highlight after animation completes (2s).
		setTimeout( () => {
			blockElement.classList.remove( 'publish-gate-highlight' );
		}, 2000 );
	} );
};
