/**
 * Settings tab JS handler for Course Builder.
 *
 * @since 4.3.x
 * @version 1.0.0
 */
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderSettings {
	constructor() {
		this.form = null;
		this.debounceTimer = null;
		this.debounceDelay = 600;
		this.isSaving = false;
		this.isDirty = false;
		this.lastSavedValue = 'no';

		this.init();
	}

	static selectors = {
		elForm: '#lp-cb-settings-form',
		elCheckbox: 'input[name="enable_cb_admin_mode"]',
		elStatus: '[data-setting-status]',
		elBadge: '[data-setting-badge]',
	};

	init() {
		this.form = document.querySelector( BuilderSettings.selectors.elForm );
		if ( ! this.form ) {
			return;
		}

		this.lastSavedValue = this.getCurrentValue();
		this.updateBadge( this.lastSavedValue );
		this.events();
	}

	events() {
		if ( BuilderSettings._loadedEvents ) {
			return;
		}
		BuilderSettings._loadedEvents = true;

		if ( ! this.form ) {
			return;
		}

		this.form.addEventListener( 'change', ( e ) => {
			if ( ! e.target.matches( BuilderSettings.selectors.elCheckbox ) ) {
				return;
			}

			this.handleSettingChange();
		} );
	}

	handleSettingChange() {
		const value = this.getCurrentValue();
		this.isDirty = true;
		this.updateBadge( value );
		this.setStatus( 'pending', 'Saving automatically...' );
		this.queueSave();
	}

	queueSave() {
		window.clearTimeout( this.debounceTimer );
		this.debounceTimer = window.setTimeout( () => {
			this.flushSave();
		}, this.debounceDelay );
	}

	flushSave() {
		const value = this.getCurrentValue();

		if ( ! this.isDirty ) {
			return;
		}

		if ( value === this.lastSavedValue ) {
			this.isDirty = false;
			this.setStatus( 'saved', 'Saved' );
			return;
		}

		if ( this.isSaving ) {
			return;
		}

		this.isSaving = true;
		this.setStatus( 'saving', lpData?.i18n?.saving || 'Saving...' );

		const dataSend = {
			action: 'save_global_settings',
			args: { id_url: 'save-global-settings' },
			enable_cb_admin_mode: value,
		};

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				if ( response?.status !== 'success' ) {
					this.handleSaveError( response?.message || 'Could not save changes.' );
					return;
				}

				this.lastSavedValue = value;
				this.isDirty = false;
				this.updateBadge( value );
				this.setStatus( 'saved', response?.message || 'Saved' );
			},
			error: ( error ) => {
				this.handleSaveError( error?.message || error || 'An error occurred.' );
			},
			completed: () => {
				this.isSaving = false;

				if ( this.getCurrentValue() !== this.lastSavedValue ) {
					this.isDirty = true;
					this.queueSave();
				}
			},
		} );
	}

	handleSaveError( message ) {
		this.setStatus( 'error', message );
		lpToastify.show( message, 'error' );
	}

	getCurrentValue() {
		const checkbox = this.form?.querySelector( BuilderSettings.selectors.elCheckbox );
		return checkbox && checkbox.checked ? 'yes' : 'no';
	}

	setStatus( state, message ) {
		const statusEl = this.form?.querySelector( BuilderSettings.selectors.elStatus );
		if ( ! statusEl ) {
			return;
		}

		statusEl.dataset.state = state;
		statusEl.textContent = message;
	}

	updateBadge( value ) {
		const badgeEl = this.form?.querySelector( BuilderSettings.selectors.elBadge );
		if ( ! badgeEl ) {
			return;
		}

		const isEnabled = value === 'yes';
		badgeEl.dataset.state = isEnabled ? 'enabled' : 'disabled';
		badgeEl.textContent = isEnabled ? 'Enabled' : 'Disabled';
	}
}
