import SweetAlert from 'sweetalert2';
import * as lpToastify from '../../lpToastify';
import * as lpUtils from '../../utils.js';

/**
 * Order Refund Script
 *
 * Handle refund action on profile orders list.
 *
 * @since 4.3.5
 * @version 1.0.0
 */
export class OrderRefund {
	constructor() {
		this.isRequesting = false;
	}

	static selectors = {
		actionRefund: '.lp-refund-order-action',
	};

	init() {
		this.events();
	}

	events() {
		if ( OrderRefund._loadedEvents ) {
			return;
		}

		OrderRefund._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: OrderRefund.selectors.actionRefund,
				class: this,
				callBack: this.handleRefundClick.name,
			},
		] );
	}

	getAjaxHandle() {
		const ajaxHandle = window.lpAJAXG;
		if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			return null;
		}

		return ajaxHandle;
	}

	setActionLoadingState( actionLink, isLoading ) {
		if ( ! actionLink ) {
			return;
		}

		if ( isLoading ) {
			actionLink.dataset.refundSubmitting = 'yes';
		} else {
			delete actionLink.dataset.refundSubmitting;
		}

		lpUtils.lpSetLoadingEl( actionLink, isLoading ? 1 : 0 );
	}

	getActionData( actionLink ) {
		const reasonMin = parseInt( actionLink.dataset.reasonMin || '10', 10 );

		return {
			orderId: parseInt( actionLink.dataset.orderId || '0', 10 ),
			requireReason: actionLink.dataset.requireReason === 'yes',
			reasonMin: Number.isNaN( reasonMin ) ? 10 : reasonMin,
			reasonPrompt: actionLink.dataset.reasonPrompt || '',
			reasonPlaceholder: actionLink.dataset.reasonPlaceholder || '',
			reasonRequired: actionLink.dataset.reasonRequired || '',
			confirmTitle: actionLink.dataset.confirmTitle || '',
			confirmText: actionLink.dataset.confirmText || '',
			confirmButton: actionLink.dataset.confirmButton || '',
			cancelButton: actionLink.dataset.cancelButton || '',
		};
	}

	openReasonModal( data ) {
		return SweetAlert.fire( {
			title: data.reasonPrompt,
			input: 'textarea',
			inputPlaceholder: data.reasonPlaceholder,
			inputAutoTrim: true,
			showCancelButton: true,
			confirmButtonText: data.confirmButton,
			cancelButtonText: data.cancelButton,
			inputValidator: ( value ) => {
				const reason = ( value || '' ).trim();
				if ( ! reason.length ) {
					return data.reasonRequired;
				}

				return undefined;
			},
		} );
	}

	openConfirmModal( data ) {
		return SweetAlert.fire( {
			icon: 'warning',
			title: data.confirmTitle,
			text: data.confirmText,
			showCancelButton: true,
			confirmButtonText: data.confirmButton,
			cancelButtonText: data.cancelButton,
		} );
	}

	sendRefundRequest( actionLink, data, reason = '' ) {
		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			lpToastify.show( 'Refund action is unavailable right now.', 'error' );
			return;
		}

		if ( ! data.orderId ) {
			lpToastify.show( 'Invalid order.', 'error' );
			return;
		}

		this.isRequesting = true;
		this.setActionLoadingState( actionLink, true );

		const dataSend = {
			action: 'request_refund_order',
			order_id: data.orderId,
			reason,
		};

		ajaxHandle.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { status, message, data } = response;

				if ( status !== 'success' ) {
					throw new Error( message );
				}

				lpToastify.show( message, 'success' );
				setTimeout( () => {
					window.location.reload();
				}, 1200 );
			},
			error: ( error ) => {
				const message = error?.message || error || 'Refund request failed.';
				lpToastify.show( message, 'error' );
			},
			completed: () => {
				this.isRequesting = false;
				this.setActionLoadingState( actionLink, false );
			},
		} );
	}

	async handleRefundClick( args ) {
		const { e, target } = args;
		e.preventDefault();

		const actionLink = target.closest( OrderRefund.selectors.actionRefund );
		if ( ! actionLink ) {
			return;
		}

		if (
			this.isRequesting ||
			actionLink.dataset.refundSubmitting === 'yes' ||
			actionLink.classList.contains( 'loading' )
		) {
			return;
		}

		const actionData = this.getActionData( actionLink );
		let reason = '';

		if ( actionData.requireReason ) {
			const reasonResult = await this.openReasonModal( actionData );
			if ( ! reasonResult.isConfirmed ) {
				return;
			}

			reason = ( reasonResult.value || '' ).trim();
		}

		const confirmResult = await this.openConfirmModal( actionData );
		if ( ! confirmResult.isConfirmed ) {
			return;
		}

		this.sendRefundRequest( actionLink, actionData, reason );
	}
}

const orderRefund = () => {
	const orderRefundHandle = new OrderRefund();

	lpUtils.lpOnElementReady( OrderRefund.selectors.actionRefund, () => {
		orderRefundHandle.init();
	} );
};

export default orderRefund;
