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
				path: '/mirm-editorial-guard/v1/override',
				method: 'POST',
				data: {
					post_id: postId,
					reason: overrideReason.trim(),
				},
			} );

			// Update post meta in the editor store.
			editPost( {
				meta: {
					_mirm_editorial_guard_passed_status: 'overridden',
					_mirm_editorial_guard_override_reason: overrideReason.trim(),
				},
			} );

			// Unlock saving.
			unlockPostSaving( 'mirm-editorial-guard-lock' );
			setOverrideSuccess( true );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'MirM Editorial Guard override failed:', error );
		} finally {
			setIsOverriding( false );
		}
	};

	// Loading state.
	if ( isEvaluating ) {
		return (
			<div className="mirm-editorial-guard-spinner">
				<Spinner />
				Evaluating rules…
			</div>
		);
	}

	return (
		<div className="mirm-editorial-guard-panel">
			{ /* Status Header */ }
			<div className="mirm-editorial-guard-panel__header">
				{ allPassed ? (
					<span className="mirm-editorial-guard-panel__status mirm-editorial-guard-panel__status--passed">
						✅ All checks passed
					</span>
				) : (
					<span className="mirm-editorial-guard-panel__status mirm-editorial-guard-panel__status--failed">
						❌ { failedCount } check{ failedCount !== 1 ? 's' : '' } failed
					</span>
				) }
			</div>

			{ /* Rules List */ }
			<ul className="mirm-editorial-guard-panel__rules-list">
				{ results.map( ( result ) => (
					<li key={ result.ruleId }>
						<RuleItem result={ result } />
					</li>
				) ) }
			</ul>

			{ /* Override Section (only for admins/editors when checks fail) */ }
			{ canOverride && ! allPassed && ! overrideSuccess && (
				<div className="mirm-editorial-guard-panel__override-section">
					<label className="mirm-editorial-guard-panel__override-label">
						Override Reason (required):
					</label>
					<TextareaControl
						className="mirm-editorial-guard-panel__override-reason"
						value={ overrideReason }
						onChange={ setOverrideReason }
						placeholder="Explain why you're bypassing these checks…"
						__nextHasNoMarginBottom
					/>
					<Button
						variant="primary"
						className="mirm-editorial-guard-panel__override-btn"
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
				<div className="mirm-editorial-guard-panel__override-success">
					✅ Override recorded — you may now publish.
				</div>
			) }
		</div>
	);
};

export default SidebarPanel;
