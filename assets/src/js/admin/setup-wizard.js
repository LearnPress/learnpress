/**
 * Setup Wizard Script
 *
 * @since 4.2.8.6
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';

class SetupWizard {
	static selectors = {
		elSetupForm: '.lp-setup-wizard-form',
		elPreviewPrice: '#preview-price',
		elCurrency: 'select[name="settings[currency][currency]"]',
	};

	init() {
		this.elSetupForm = document.querySelector( SetupWizard.selectors.elSetupForm );
		if ( ! this.elSetupForm ) {
			return;
		}

		this.resetNextButtons();
		this.events();
		this.initToggles();
		this.syncEmailMaster();
		this.syncCurrencyPreset();
		this.initDemoCourseImporter();
	}

	events() {
		window.addEventListener( 'pageshow', () => this.resetNextButtons() );

		this.elSetupForm.querySelector( SetupWizard.selectors.elCurrency )?.addEventListener( 'change', () => {
			this.syncCurrencyPreset();
		} );

		document.addEventListener( 'click', ( e ) => {
			const nextButton = e.target.closest( '.button-next' );
			if ( nextButton ) {
				e.preventDefault();
				this.saveStep( nextButton );
				return;
			}

			const preset = e.target.closest( '[data-currency-preset]' );
			if ( ! preset ) {
				return;
			}

			const currency = this.elSetupForm.querySelector( SetupWizard.selectors.elCurrency );
			if ( ! currency ) {
				return;
			}

			currency.value = preset.dataset.currencyPreset;
			currency.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

	}

	resetNextButtons() {
		this.elSetupForm.querySelectorAll( '.button-next' ).forEach( ( button ) => {
			button.removeAttribute( 'aria-disabled' );
			lpUtils.lpSetLoadingEl( button, 0 );
		} );
	}

	initToggles() {
		lpUtils.toggleEnable( ( toggle, isEnabled ) => {
			const input = toggle.querySelector( '.lp-toggle-enable__input' );
			if ( input?.classList.contains( 'lp-setup-email-master' ) ) {
				this.elSetupForm.querySelectorAll( '.lp-setup-email-notification' ).forEach( ( notification ) => {
					this.setToggleState( notification, isEnabled );
				} );
			} else if ( input?.classList.contains( 'lp-setup-email-notification' ) ) {
				this.syncEmailMaster();
			}
		} );
	}

	setToggleState( input, isEnabled ) {
		input.checked = isEnabled;
		input.value = isEnabled ? '1' : '0';
		input.closest( '.lp-toggle-enable' )?.classList.toggle( 'is-enabled', isEnabled );
	}

	initDemoCourseImporter() {
		this.demoCourse = this.elSetupForm.querySelector( '#lp-setup-demo-course' );
		if ( ! this.demoCourse ) {
			return;
		}

		this.demoCourseIndex = 0;
		this.demoCourse.querySelector( '#install-sample-course' )?.addEventListener( 'click', () => this.importDemoCourse() );
		this.demoCourse.querySelector( '.lp-setup-demo-course__retry' )?.addEventListener( 'click', () => this.importDemoCourse() );
	}

	importDemoCourse() {
		if ( ! this.demoCourse || ! window.lpAJAXG ) {
			return;
		}

		this.demoCourse.dataset.state = 'importing';
		this.demoCourse.querySelectorAll( 'button' ).forEach( ( button ) => {
			button.disabled = true;
			lpUtils.lpSetLoadingEl( button, 1 );
		} );

		window.lpAJAXG.fetchAJAX(
			{
				action: this.demoCourse.dataset.action,
				index: this.demoCourseIndex,
				id_url: 'setup-demo-course',
			},
			{
				success: ( response ) => {
					if ( 'success' !== response.status ) {
						throw new Error( response.message );
					}

					this.updateDemoCourseProgress( response.data );
					if ( response.data.complete ) {
						this.completeDemoCourseImport( response.data );
					} else {
						this.demoCourseIndex = response.data.index;
						this.importDemoCourse();
					}
				},
				error: ( error ) => {
					this.demoCourse.dataset.state = 'error';
					const message = this.demoCourse.querySelector( '.lp-setup-demo-course__error-message' );
					if ( message ) {
						message.textContent = error?.message || String( error );
					}
				},
				completed: () => {
					if ( 'importing' === this.demoCourse.dataset.state ) {
						return;
					}

					this.demoCourse.querySelectorAll( 'button' ).forEach( ( button ) => {
						button.disabled = false;
						lpUtils.lpSetLoadingEl( button, 0 );
					} );
				},
			}
		);
	}

	updateDemoCourseProgress( data ) {
		const status = this.demoCourse.querySelector( '.lp-setup-demo-course__status-text' );
		const percent = this.demoCourse.querySelector( '.lp-setup-demo-course__progress .lp-setup-demo-course__percent' );
		const bar = this.demoCourse.querySelector( '.lp-setup-demo-course__progress .lp-setup-demo-course__bar span' );
		if ( status ) {
			status.textContent = `${ this.demoCourse.dataset.importingLabel } (${ data.index }/${ data.total }): ${ data.title }`;
		}
		if ( percent ) {
			percent.textContent = `${ data.percent }%`;
		}
		if ( bar ) {
			bar.style.width = `${ data.percent }%`;
		}
	}

	completeDemoCourseImport( data ) {
		this.demoCourse.dataset.state = 'complete';
		const count = this.demoCourse.querySelector( '.lp-setup-demo-course__complete-count' );
		if ( count ) {
			count.textContent = data.total;
		}
		const viewCourses = this.demoCourse.querySelector( '.lp-setup-demo-course__complete a' );
		if ( viewCourses && data.view_url ) {
			viewCourses.href = data.view_url;
		}
		const finishButton = this.elSetupForm.querySelector( '.lp-setup-footer-bar .button-next' );
		if ( finishButton ) {
			const finishButtonLabel = finishButton.querySelector( '.lp-setup-button__label' );
			if ( finishButtonLabel ) {
				finishButtonLabel.textContent = this.demoCourse.dataset.finishLabel;
			}
		}
	}

	syncEmailMaster() {
		const master = this.elSetupForm.querySelector( '.lp-setup-email-master' );
		const notifications = [ ...this.elSetupForm.querySelectorAll( '.lp-setup-email-notification' ) ];
		if ( ! master || ! notifications.length ) {
			return;
		}

		const enabledCount = notifications.filter( ( input ) => input.checked ).length;
		this.setToggleState( master, enabledCount === notifications.length );
	}

	syncCurrencyPreset() {
		const currency = this.elSetupForm.querySelector( SetupWizard.selectors.elCurrency );
		if ( ! currency ) {
			return;
		}

		this.elSetupForm.querySelectorAll( '[data-currency-preset]' ).forEach( ( preset ) => {
			preset.classList.toggle( 'is-active', preset.dataset.currencyPreset === currency.value );
		} );
	}

	saveStep( button ) {
		if ( ! window.lpAJAXG || 'true' === button.getAttribute( 'aria-disabled' ) ) {
			return;
		}

		const loadingStartedAt = Date.now();
		const minimumLoadingDuration = 200;
		let isNavigating = false;

		lpUtils.lpSetLoadingEl( button, 1 );
		button.setAttribute( 'aria-disabled', 'true' );
		const dataSend = lpUtils.getDataOfForm( this.elSetupForm );
		dataSend.action = 'lp_setup_wizard_save_step';
		dataSend.id_url = 'setup-wizard-save-step';

		try {
			window.lpAJAXG.fetchAJAX( dataSend, {
				success: ( response ) => {
					if ( 'success' !== response.status ) {
						throw new Error( response.message );
					}

					isNavigating = true;
					const loadingDuration = Date.now() - loadingStartedAt;
					const redirectDelay = Math.max( 0, minimumLoadingDuration - loadingDuration );

					window.setTimeout( () => {
						window.location.href = button.dataset.nextUrl;
					}, redirectDelay );
				},
				error: ( error ) => {
					window.alert( error?.message || String( error ) );
				},
				completed: () => {
					if ( isNavigating ) {
						return;
					}

					button.removeAttribute( 'aria-disabled' );
					lpUtils.lpSetLoadingEl( button, 0 );
				},
			} );
		} catch ( error ) {
			button.removeAttribute( 'aria-disabled' );
			lpUtils.lpSetLoadingEl( button, 0 );
			window.alert( error?.message || String( error ) );
		}
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	new SetupWizard().init();
} );
