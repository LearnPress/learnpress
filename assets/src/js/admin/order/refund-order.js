import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

/**
 * Handle admin approve/deny refund actions.
 *
 * @since 4.3.9
 * @version 1.0.0
 */
export class RefundOrder {
	constructor() {
		this.isRequesting = false;
		this.isReloading = false;
	}

	static selectors = {
		panel: '.order-data-refund-request',
		action: '.lp-admin-refund-order-action',
	};

	init() {
		this.events();
	}

	events() {
		if ( RefundOrder._loadedEvents ) {
			return;
		}

		RefundOrder._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: RefundOrder.selectors.action,
				class: this,
				callBack: this.handleAction.name,
			},
		] );
	}

	getPanelData( panel ) {
		const orderTotal = parseFloat( panel.dataset.orderTotal || '0' );

		return {
			orderId: parseInt( panel.dataset.orderId || '0', 10 ),
			orderTotal: Number.isNaN( orderTotal ) ? 0 : orderTotal,
			orderTotalFormatted: panel.dataset.orderTotalFormatted || '',
			confirmTitle: panel.dataset.confirmTitle || 'Approve refund?',
			confirmText: panel.dataset.confirmText || '',
			messageLabel: panel.dataset.messageLabel || 'Message to payer',
			messagePlaceholder: panel.dataset.messagePlaceholder || '',
			amountLabel: panel.dataset.amountLabel || 'Refund amount',
			amountInvalid: panel.dataset.amountInvalid || 'Invalid refund amount.',
			confirmButton: panel.dataset.confirmButton || 'Approve Refund',
			cancelButton: panel.dataset.cancelButton || 'Cancel',
		};
	}

	setLoadingState( panel, isLoading ) {
		panel.querySelectorAll( RefundOrder.selectors.action ).forEach( ( button ) => {
			button.disabled = isLoading;
		} );
	}

	openApproveModal( data ) {
		const content = document.createElement( 'div' );
		const messageLabel = document.createElement( 'label' );
		const message = document.createElement( 'textarea' );
		const amountLabel = document.createElement( 'label' );
		const amount = document.createElement( 'input' );

		content.className = 'lp-admin-refund-modal__form';

		if ( data.confirmText ) {
			const confirmText = document.createElement( 'p' );
			confirmText.className = 'lp-admin-refund-modal__description';
			confirmText.textContent = data.confirmText;
			content.append( confirmText );
		}

		messageLabel.textContent = data.messageLabel;
		messageLabel.htmlFor = 'lp-admin-refund-message';
		messageLabel.className = 'swal2-input-label';
		message.id = 'lp-admin-refund-message';
		message.className = 'swal2-textarea';
		message.placeholder = data.messagePlaceholder;

		amountLabel.textContent = `${ data.amountLabel } (${ data.orderTotalFormatted })`;
		amountLabel.htmlFor = 'lp-admin-refund-amount';
		amountLabel.className = 'swal2-input-label';
		amount.id = 'lp-admin-refund-amount';
		amount.className = 'swal2-input';
		amount.type = 'number';
		amount.min = '0.01';
		amount.max = data.orderTotal.toString();
		amount.step = '0.01';
		amount.value = data.orderTotal.toFixed( 2 );

		content.append( messageLabel, message, amountLabel, amount );

		return SweetAlert.fire( {
			icon: 'warning',
			title: data.confirmTitle,
			html: content,
			showCancelButton: true,
			confirmButtonText: data.confirmButton,
			cancelButtonText: data.cancelButton,
			focusConfirm: false,
			customClass: {
				popup: 'lp-admin-refund-modal',
				htmlContainer: 'lp-admin-refund-modal__content',
				actions: 'lp-admin-refund-modal__actions',
			},
			preConfirm: () => {
				const refundAmount = parseFloat( amount.value );
				if (
					Number.isNaN( refundAmount ) ||
					refundAmount <= 0 ||
					refundAmount > data.orderTotal
				) {
					SweetAlert.showValidationMessage( data.amountInvalid );
					return false;
				}

				return {
					note: message.value.trim(),
					refundAmount,
				};
			},
		} );
	}

	sendAction( actionButton, panel, refundAction, refundAmount = 0, note = '' ) {
		const data = this.getPanelData( panel );

		if ( ! data.orderId ) {
			lpToastify.show( 'Invalid order.', 'error' );
			return;
		}

		this.isRequesting = true;
		this.setLoadingState( panel, true );
		lpUtils.lpSetLoadingEl( actionButton, 1 );

		window.lpAJAXG.fetchAJAX(
			{
				action: 'admin_handle_request_refund',
				order_id: data.orderId,
				refund_action: refundAction,
				refund_amount: refundAmount,
				note,
			},
			{
				success: ( response ) => {
					const { status, message, data } = response;

					if ( status !== 'success' ) {
						throw new Error( message );
					}

					lpToastify.show( message, 'success' );
					this.isReloading = true;
					window.setTimeout( () => window.location.reload(), 1200 );
				},
				error: ( error ) => {
					const messageResponse =
						error?.message || error || 'Refund action failed.';
					lpToastify.show( messageResponse, 'error' );
				},
				completed: () => {
					if ( this.isReloading ) {
						return;
					}

					this.isRequesting = false;
					this.setLoadingState( panel, false );
					lpUtils.lpSetLoadingEl( actionButton, 0 );
				},
			},
		);
	}

	async handleAction( args ) {
		const { e, target } = args;
		e.preventDefault();

		const actionButton = target.closest( RefundOrder.selectors.action );
		const panel = actionButton?.closest( RefundOrder.selectors.panel );
		if ( ! actionButton || ! panel || this.isRequesting ) {
			return;
		}

		const refundAction = actionButton.dataset.refundAction || '';
		let amount = '';
		let note = '';

		if ( 'reject' === refundAction ) {
			return this.sendAction( actionButton, panel, refundAction );
		}

		const result = await this.openApproveModal( this.getPanelData( panel ) );
		if ( result.isConfirmed && result.value ) {
			amount = result.value.refundAmount;
			note = result.value.note;
			this.sendAction( actionButton, panel, refundAction, amount, note );
		}
	}
}

const refundOrder = () => {
	const refundOrderHandle = new RefundOrder();

	lpUtils.lpOnElementReady( RefundOrder.selectors.action, () => {
		refundOrderHandle.init();
	} );
};

export default refundOrder;
