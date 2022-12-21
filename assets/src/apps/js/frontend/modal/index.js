/**
 * Create Modal popup.
 * Edit: Use React Hook.
 *
 * @author Nhamdv - ThimPress.
 */

import store from './store';
import { __ } from '@wordpress/i18n';
import { useSelect, dispatch } from '@wordpress/data';

const Modal = ( { children } ) => {
	const { show, hide, confirm } = dispatch( 'learnpress/modal' );

	const isShow = useSelect( ( select ) => {
		const isOpen = select( 'learnpress/modal' ).isOpen();
		return isOpen;
	} );

	const message = useSelect( ( select ) => {
		const getMessage = select( 'learnpress/modal' ).getMessage();
		return getMessage;
	} );

	const dataConfirm = ( c ) => ( event ) => {
		confirm( c );
	};

	const styles = {
		display: isShow ? 'block' : 'none',
	};

	return (
		<>
			<div>
				<div id="lp-modal-overlay" style={ styles }></div>
				<div id="lp-modal-window" style={ styles }>
					<div id="lp-modal-content" dangerouslySetInnerHTML={ { __html: message } }></div>
					<div id="lp-modal-buttons">
						<button className="lp-button modal-button-ok" onClick={ dataConfirm( 'yes' ) }>
							<span>{ __( 'OK', 'learnpress' ) }</span>
						</button>
						<button className="lp-button modal-button-cancel" onClick={ dataConfirm( 'no' ) }>
							<span>{ __( 'Cancel', 'learnpress' ) }</span>
						</button>
					</div>
				</div>
			</div>
			{ children }
		</>
	);
};

export default Modal;
