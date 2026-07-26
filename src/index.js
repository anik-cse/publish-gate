/**
 * Publish Gate — Gutenberg Sidebar Plugin
 *
 * Registers the editor sidebar plugin, subscribes to store changes,
 * and controls post saving lock based on rule evaluation results.
 *
 * @package Publish_Gate
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect, useDispatch, subscribe } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { createElement } from '@wordpress/element';

/**
 * Inline shield icon SVG (avoids @wordpress/icons dependency).
 */
const ShieldIcon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
		<path d="M12 3.176l-7 3.5v4.574c0 4.418 2.99 8.55 7 9.574 4.01-1.024 7-5.156 7-9.574V6.676l-7-3.5zm5 8.074c0 3.497-2.33 6.774-5 7.784-2.67-1.01-5-4.287-5-7.784V7.824l5-2.5 5 2.5v3.426z" />
		<path d="M11 10.5l-1.5 1.5 2.5 2.5 4-4-1.5-1.5-2.5 2.5-1-1z" />
	</svg>
);
import SidebarPanel from './components/SidebarPanel';
import { evaluateRules } from './utils/evaluateRules';
import './index.scss';

/**
 * Main Publish Gate sidebar component.
 *
 * Subscribes to editor data, evaluates rules in real time,
 * and locks/unlocks post saving accordingly.
 */
const PublishGateSidebar = () => {
	const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );
	const [ results, setResults ] = useState( [] );
	const [ isEvaluating, setIsEvaluating ] = useState( true );
	const prevResultsRef = useRef( '' );

	const { postTitle, postExcerpt, featuredMedia, categories, tags, blocks, postId, postType } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			const blockEditor = select( 'core/block-editor' );

			return {
				postTitle: editor.getEditedPostAttribute( 'title' ) || '',
				postExcerpt: editor.getEditedPostAttribute( 'excerpt' ) || '',
				featuredMedia: editor.getEditedPostAttribute( 'featured_media' ) || 0,
				categories: editor.getEditedPostAttribute( 'categories' ) || [],
				tags: editor.getEditedPostAttribute( 'tags' ) || [],
				blocks: blockEditor.getBlocks(),
				postId: editor.getCurrentPostId(),
				postType: editor.getCurrentPostType(),
			};
		},
		[]
	);

	// Get rules configuration from localized data.
	const rulesConfig = window.publishGateData?.rules || {};
	const canOverride = window.publishGateData?.canOverride || false;

	useEffect( () => {
		// Only gate 'post' type by default.
		const guardedTypes = window.publishGateData?.guardedTypes || [ 'post' ];
		if ( ! guardedTypes.includes( postType ) ) {
			unlockPostSaving( 'publish-gate-lock' );
			return;
		}

		const postData = {
			title: postTitle,
			excerpt: postExcerpt,
			featured_media: featuredMedia,
			categories: categories,
			tags: tags,
		};

		const newResults = evaluateRules( rulesConfig, postData, blocks );
		const resultsKey = JSON.stringify( newResults );

		// Only update if results actually changed (avoids re-render loops).
		if ( resultsKey !== prevResultsRef.current ) {
			prevResultsRef.current = resultsKey;
			setResults( newResults );
			setIsEvaluating( false );

			const hasCriticalFailure = newResults.some(
				( r ) => ! r.passed
			);

			if ( hasCriticalFailure ) {
				lockPostSaving( 'publish-gate-lock' );
			} else {
				unlockPostSaving( 'publish-gate-lock' );
			}
		}
	}, [ postTitle, postExcerpt, featuredMedia, categories, tags, blocks, postType, rulesConfig ] );

	const allPassed = results.length > 0 && results.every( ( r ) => r.passed );
	const failedCount = results.filter( ( r ) => ! r.passed ).length;

	const titleText = allPassed
		? `Publish Gate ✅`
		: `Publish Gate (${ failedCount } issue${ failedCount !== 1 ? 's' : '' })`;

	return (
		<>
			<PluginSidebarMoreMenuItem target="publish-gate-sidebar" icon={ <ShieldIcon /> }>
				{ titleText }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="publish-gate-sidebar"
				title="Publish Gate"
				icon={ <ShieldIcon /> }
			>
				<SidebarPanel
					results={ results }
					isEvaluating={ isEvaluating }
					allPassed={ allPassed }
					canOverride={ canOverride }
					postId={ postId }
				/>
			</PluginSidebar>
		</>
	);
};

// Register the plugin.
registerPlugin( 'publish-gate', {
	render: PublishGateSidebar,
} );
