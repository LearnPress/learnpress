/**
 * PayPal Webhook Script
 *
 * Handles the "Create webhook" and "Check webhook status" buttons in
 * LearnPress PayPal settings. Registers the LearnPress subscription-webhook
 * listener URL with PayPal, fills the returned webhook ID back into the
 * settings field, and reports whether the saved webhook is still alive.
 *
 * @since 4.4.1
 * @version 1.1.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';

export class PaypalWebhook {
	constructor() {
		this.elButton = null;
		this.isRequesting = false;
	}

	static selectors = {
		elButton: '#lp-paypal-create-webhook',
		elCheckButton: '#lp-paypal-check-webhook-status',
		elStatus: '#lp-paypal-webhook-status',
	};

	init() {
		this.elButton = document.querySelector( PaypalWebhook.selectors.elButton );
		if ( ! this.elButton ) {
			return;
		}

		this.events();
	}

	events() {
		if ( PaypalWebhook._loadedEvents ) {
			return;
		}

		PaypalWebhook._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: PaypalWebhook.selectors.elButton,
				class: this,
				callBack: this.createWebhook.name,
			},
			{
				selector: PaypalWebhook.selectors.elCheckButton,
				class: this,
				callBack: this.checkWebhookStatus.name,
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

	getConfig() {
		return window.lpPaypalWebhookSettings || {};
	}

	setButtonLoadingState( btn, isLoading ) {
		if ( ! btn ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btn, isLoading ? 1 : 0 );
		btn.disabled = !! isLoading;
	}

	setStatus( message = '', isError = false ) {
		const elStatus = document.querySelector( PaypalWebhook.selectors.elStatus );
		if ( ! elStatus ) {
			return;
		}

		elStatus.textContent = message;
		elStatus.style.color = isError ? '#b32d2e' : '#1e1e1e';
	}

	/**
	 * Run a webhook AJAX action with shared loading/status handling.
	 * @param btn
	 * @param action
	 * @param processingMessage
	 * @param onSuccess
	 */
	runWebhookAction( btn, action, processingMessage, onSuccess ) {
		if ( ! btn || this.isRequesting || btn.disabled ) {
			return;
		}

		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			return;
		}

		const i18n = this.getConfig().i18n || {};

		this.isRequesting = true;
		this.setButtonLoadingState( btn, true );
		this.setStatus( processingMessage || i18n.processing || 'Processing...', false );

		ajaxHandle.fetchAJAX(
			{ action },
			{
				success: ( response ) => {
					const { status, message, data } = response;
					if ( 'success' !== status ) {
						this.setStatus( message || i18n.request_failed || 'Request failed.', true );
						return;
					}

					onSuccess( message, data );
				},
				error: ( error ) => {
					console.error( error );
					this.setStatus( i18n.request_failed || 'Request failed.', true );
				},
				completed: () => {
					this.isRequesting = false;
					this.setButtonLoadingState( btn, false );
				},
			}
		);
	}

	/**
	 * Create (or reuse) the PayPal subscription webhook.
	 * @param args
	 */
	createWebhook( args ) {
		const { e } = args;
		if ( e ) {
			e.preventDefault();
		}

		const btn = args?.target?.closest( PaypalWebhook.selectors.elButton );
		const cfg = this.getConfig();
		const i18n = cfg.i18n || {};

		this.runWebhookAction(
			btn,
			cfg.action || 'paypal_create_subscription_webhook',
			i18n.processing,
			( message, data ) => {
				if ( cfg.webhook_id_field && data?.webhook_id ) {
					const elWebhookId = document.querySelector(
						`[name="${ cfg.webhook_id_field }"]`
					);
					if ( elWebhookId ) {
						elWebhookId.value = data.webhook_id;
					}
				}

				this.setStatus( message || i18n.created || 'Webhook created.', false );
			}
		);
	}

	/**
	 * Check whether the saved PayPal subscription webhook is still alive.
	 * @param args
	 */
	checkWebhookStatus( args ) {
		const { e } = args;
		if ( e ) {
			e.preventDefault();
		}

		const btn = args?.target?.closest( PaypalWebhook.selectors.elCheckButton );
		const cfg = this.getConfig();
		const i18n = cfg.i18n || {};

		this.runWebhookAction(
			btn,
			cfg.checkAction || 'paypal_check_subscription_webhook_status',
			i18n.checking,
			( message ) => {
				this.setStatus( message || i18n.created || 'Webhook is active.', false );
			}
		);
	}
}

// Auto-initialize when DOM is available (for standalone page load).
const paypalWebhook = new PaypalWebhook();

lpUtils.lpOnElementReady( PaypalWebhook.selectors.elButton, () => {
	paypalWebhook.init();
} );
