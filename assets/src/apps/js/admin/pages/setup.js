/**
 * Setup Wizard Script
 *
 * @since 4.2.8.6
 */
class SetupWizard {
	static selectors = {
		elSetupForm: '#learn-press-setup-form',
		elPreviewPrice: '#preview-price',
	};

	init() {
		this.elSetupForm = document.querySelector( SetupWizard.selectors.elSetupForm );
		if ( ! this.elSetupForm ) {
			return;
		}

		this.events();
	}

	events() {
		document.addEventListener( 'change', ( e ) => {
			if ( e.target.closest( 'input, select' ) ) {
				this.saveSettings();
			}
		} );
	}

	/* Post form data to current url, update preview price html with response */
	saveSettings() {
		const formData = new FormData( this.elSetupForm );

		fetch( window.location.href, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.text() )
			.then( ( html ) => {
				const elPreviewPrice = document.querySelector( SetupWizard.selectors.elPreviewPrice );
				if ( elPreviewPrice ) {
					elPreviewPrice.innerHTML = html;
				}
			} );
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	new SetupWizard().init();
} );
