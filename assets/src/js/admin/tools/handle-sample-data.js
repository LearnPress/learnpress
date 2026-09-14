/**
 * Handle install/uninstall sample course data on the Tools page.
 *
 * @since 4.4.5
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export default class HandleSampleData {
	static selectors = {
		wrapper: '.lp-install-sample',
		form: '.lp-form-handle-sample-data',
		elBtnHandleSampleData: '.lp-btn-install-sample-handle',
		elTriggerToggle: '.lp-install-sample__toggle-options',
		elMessage: '.lp-install-sample-message',
	};

	constructor() {
		this.wrapper = null;
	}

	init() {
		this.wrapper = document.querySelector(
			HandleSampleData.selectors.wrapper
		);
		if ( ! this.wrapper ) {
			return;
		}

		this.preventFormSubmit();
		this.events();
	}

	preventFormSubmit() {
		const form = this.wrapper.querySelector(
			HandleSampleData.selectors.form
		);
		if ( form ) {
			form.addEventListener( 'submit', ( e ) => {
				e.preventDefault();
			} );
		}
	}

	events() {
		if ( HandleSampleData._loadedEvents ) {
			return;
		}

		HandleSampleData._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: HandleSampleData.selectors.elBtnHandleSampleData,
				class: this,
				callBack: this.handleAction.name,
			},
			{
				selector: HandleSampleData.selectors.elTriggerToggle,
				callBack: ( args ) => {
					const { e, target } = args;
					const elForm = this.wrapper.querySelector(
						HandleSampleData.selectors.form
					);
					if ( elForm ) {
						elForm.classList.toggle( 'lp-hidden' );
						const textShow = target.dataset.showText;
						const textHide = target.dataset.hideText;

						target.textContent = elForm.classList.contains(
							'lp-hidden'
						)
							? textShow
							: textHide;
					}
				},
			},
		] );
	}

	handleAction( args ) {
		const { e, target } = args;
		const button = target.closest(
			HandleSampleData.selectors.elBtnHandleSampleData
		);

		e.preventDefault();
		const elMessage = this.wrapper.querySelector(
			HandleSampleData.selectors.elMessage
		);

		const message = button.dataset.message;
		if ( ! message || ! confirm( message ) ) {
			return;
		}

		elMessage.innerHTML = '';
		lpUtils.lpSetLoadingEl( button, true );

		const wrapper = button.closest( HandleSampleData.selectors.wrapper );
		const elForm = wrapper.querySelector( HandleSampleData.selectors.form );

		let dataSend = lpUtils.getDataOfForm( elForm );
		dataSend.action = button.dataset.action;
		dataSend.id_url = 'handle-sample-data';

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				if ( 'success' === status ) {
					this.wrapper.querySelector(
						HandleSampleData.selectors.elMessage
					).innerHTML = data.html;
				} else {
					throw new Error( message );
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( button, false );
				setTimeout(() => {
					elMessage.innerHTML = '';
				}, 3000);
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
