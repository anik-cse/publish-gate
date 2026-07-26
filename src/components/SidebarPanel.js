/**
 * Sidebar rule results and override panel.
 */

import { useState } from '@wordpress/element';
import { Button, TextareaControl, Spinner } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import RuleItem from './RuleItem';

const SidebarPanel = ( { results, isEvaluating, allPassed, canOverride, postId } ) => {
	const [ overrideReason, setOverrideReason ] = useState( '' );
	const [ isOverriding, setIsOverriding ] = useState( false );
	const [ overrideSuccess, setOverrideSuccess ] = useState( false );
	const { unlockPostSaving } = useDispatch( 'core/editor' );
	const { editPost } = useDispatch( 'core/editor' );

	const failedCount = results.filter( ( r ) => ! r.passed ).length;


	const handleOverride = async () => {
		if ( ! overrideReason.trim() ) {
			return;
		}

		setIsOverriding( true );

		try {
			await apiFetch( {
				path: '/publish-gate/v1/override',
				method: 'POST',
				data: {
					post_id: postId,
					reason: overrideReason.trim(),
				},
			} );

			// Update post meta in the editor store.
			editPost( {
				meta: {
					_publish_gate_passed_status: 'overridden',
					_publish_gate_override_reason: overrideReason.trim(),
				},
			} );

			// Unlock saving.
			unlockPostSaving( 'publish-gate-lock' );
			setOverrideSuccess( true );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Publish Gate override failed:', error );
		} finally {
			setIsOverriding( false );
		}
	};

	// Loading state.
	if ( isEvaluating ) {
		return (
			<div className="publish-gate-spinner">
				<Spinner />
				Evaluating rules…
			</div>
		);
	}

	return (
		<div className="publish-gate-panel">
			{ /* Status Header */ }
			<div className="publish-gate-panel__header">
				{ allPassed ? (
					<span className="publish-gate-panel__status publish-gate-panel__status--passed">
						✅ All checks passed
					</span>
				) : (
					<span className="publish-gate-panel__status publish-gate-panel__status--failed">
						❌ { failedCount } check{ failedCount !== 1 ? 's' : '' } failed
					</span>
				) }
			</div>

			{ /* Rules List */ }
			<ul className="publish-gate-panel__rules-list">
				{ results.map( ( result ) => (
					<li key={ result.ruleId }>
						<RuleItem result={ result } />
					</li>
				) ) }
			</ul>

			{ /* Override Section (only for admins/editors when checks fail) */ }
			{ canOverride && ! allPassed && ! overrideSuccess && (
				<div className="publish-gate-panel__override-section">
					<label className="publish-gate-panel__override-label">
						Override Reason (required):
					</label>
					<TextareaControl
						className="publish-gate-panel__override-reason"
						value={ overrideReason }
						onChange={ setOverrideReason }
						placeholder="Explain why you're bypassing these checks…"
						__nextHasNoMarginBottom
					/>
					<Button
						variant="primary"
						className="publish-gate-panel__override-btn"
						onClick={ handleOverride }
						isBusy={ isOverriding }
						disabled={ isOverriding || ! overrideReason.trim() }
					>
						{ isOverriding ? 'Overriding…' : 'Override & Allow Publish' }
					</Button>
				</div>
			) }

			{ /* Override Success */ }
			{ overrideSuccess && (
				<div className="publish-gate-panel__override-success">
					✅ Override recorded — you may now publish.
				</div>
			) }
		</div>
	);
};

export default SidebarPanel;
