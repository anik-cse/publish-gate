/**
 * Renders a single rule's status.
 */

import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { scrollToBlock } from '../utils/scrollToBlock';

const RuleItem = ( { result } ) => {
	const { ruleId, label, passed, message, blockClientIds, panelToFocus } = result;

	// Note: these might be undefined if not in a standard post editor context (e.g. site editor).
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' ) || {};
	const { toggleEditorPanelOpened } = useDispatch( 'core/editor' ) || {};

	const handleFocusBlock = ( clientId ) => {
		scrollToBlock( clientId );
	};

	const handleFocusPanel = ( panelName ) => {
		if ( openGeneralSidebar ) {
			openGeneralSidebar( 'edit-post/document' );
		}
		if ( toggleEditorPanelOpened ) {
			// Some WP versions require true to force open, others toggle.
			toggleEditorPanelOpened( panelName );
		}
	};

	return (
		<div className="mirm-editorial-guard-rule">
			{ /* Status Icon */ }
			<div
				className={ `mirm-editorial-guard-rule__icon mirm-editorial-guard-rule__icon--${ passed ? 'passed' : 'failed' }` }
			>
				{ passed ? '✓' : '✗' }
			</div>

			{ /* Content */ }
			<div className="mirm-editorial-guard-rule__content">
				<p className="mirm-editorial-guard-rule__label">{ label }</p>
				<p className="mirm-editorial-guard-rule__message">{ message }</p>

				{ /* Jump to Block buttons for block-specific failures */ }
				{ ! passed && blockClientIds && blockClientIds.length > 0 && (
					<>
						{ blockClientIds.map( ( clientId, index ) => (
							<Button
								key={ clientId }
								variant="link"
								className="mirm-editorial-guard-rule__focus-btn"
								onClick={ () => handleFocusBlock( clientId ) }
							>
								Jump to Block { blockClientIds.length > 1 ? `#${ index + 1 }` : '' }
							</Button>
						) ) }
					</>
				) }

				{ /* Jump to Panel button for document-level settings failures */ }
				{ ! passed && panelToFocus && (
					<Button
						variant="link"
						className="mirm-editorial-guard-rule__focus-btn"
						onClick={ () => handleFocusPanel( panelToFocus ) }
					>
						Fix in Settings
					</Button>
				) }
			</div>
		</div>
	);
};

export default RuleItem;
