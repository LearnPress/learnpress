/**
 * Settings tab JS handler for Course Builder.
 *
 * @since 4.3.x
 * @version 1.2.0
 */
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderSettings {
	constructor() {
		this.form = null;
		this.debounceTimer = null;
		this.debounceDelay = 600;
		this.isSaving = false;
		this.isDirty = false;
		this.shouldRetryOnCompleted = false;
		this.lastSavedState = '';
		this.mediaUploader = null;

		this.init();
	}

	static selectors = {
		elForm: '#lp-cb-settings-form',
		elCheckbox: 'input[name="hide_instructor_access_admin_screen"]',
		elBadge: '[data-setting-badge]',
		elHeaderLogoLink: '.lp-cb-top-header__logo a',
		elLogoSetting: '[data-cb-logo-setting]',
		elDefaultLogoTemplate: '#lp-cb-default-logo-template',
		elLogoPreviewDefault: '[data-cb-logo-preview-default]',
		elLogoPreviewImage: '[data-cb-logo-preview-image]',
		elLogoChooseBtn: '[data-cb-logo-choose]',
		elLogoRemoveBtn: '[data-cb-logo-remove]',
		elLogoIdInput: 'input[name="course_builder_logo_id"]',
		elLogoRemoveInput: 'input[name="course_builder_logo_remove"]',
	};

	init() {
		this.form = document.querySelector( BuilderSettings.selectors.elForm );
		if ( ! this.form ) {
			return;
		}

		this.cacheElements();
		this.updateBadge( this.getCurrentValue() );
		this.hydrateDefaultLogoPreview();
		this.setLogoVisibility( this.getLogoId() > 0 );
		this.lastSavedState = this.getStateFingerprint();
		this.events();
	}

	cacheElements() {
		this.headerLogoLink = document.querySelector( BuilderSettings.selectors.elHeaderLogoLink );
		this.logoSetting = this.form?.querySelector( BuilderSettings.selectors.elLogoSetting );
		this.logoPreviewDefault = this.form?.querySelector(
			BuilderSettings.selectors.elLogoPreviewDefault
		);
		this.logoPreviewImage = this.form?.querySelector(
			BuilderSettings.selectors.elLogoPreviewImage
		);
		this.logoChooseBtn = this.form?.querySelector( BuilderSettings.selectors.elLogoChooseBtn );
		this.logoRemoveBtn = this.form?.querySelector( BuilderSettings.selectors.elLogoRemoveBtn );
		this.logoIdInput = this.form?.querySelector( BuilderSettings.selectors.elLogoIdInput );
		this.logoRemoveInput = this.form?.querySelector( BuilderSettings.selectors.elLogoRemoveInput );
		this.defaultLogoURL = this.logoSetting?.dataset?.cbDefaultLogoUrl || '';
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
			if ( e.target.matches( BuilderSettings.selectors.elCheckbox ) ) {
				this.handleSettingChange();
			}
		} );

		if ( this.logoChooseBtn ) {
			this.logoChooseBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.openLogoMediaUploader();
			} );
		}

		if ( this.logoRemoveBtn ) {
			this.logoRemoveBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.removeLogo();
			} );
		}
	}

	handleSettingChange() {
		this.isDirty = true;
		if ( this.isSaving ) {
			this.shouldRetryOnCompleted = true;
		}
		this.updateBadge( this.getCurrentValue() );
		this.queueSave();
	}

	queueSave() {
		window.clearTimeout( this.debounceTimer );
		this.debounceTimer = window.setTimeout( () => {
			this.flushSave();
		}, this.debounceDelay );
	}

	flushSave() {
		if ( ! this.isDirty ) {
			return;
		}

		const currentState = this.getStateFingerprint();
		if ( currentState === this.lastSavedState ) {
			this.isDirty = false;
			return;
		}

		if ( this.isSaving ) {
			return;
		}

		this.isSaving = true;
		this.shouldRetryOnCompleted = false;
		lpToastify.show( 'Updating settings...', 'info', { duration: 1800 } );

		window.lpAJAXG.fetchAJAX( this.getPayloadForSave(), {
			success: ( response ) => {
				if ( response?.status !== 'success' ) {
					this.handleSaveError( response?.message || 'Could not save changes.' );
					return;
				}

				this.applySaveResponse( response );
				this.isDirty = false;
				this.lastSavedState = this.getStateFingerprint();
				this.updateBadge( this.getCurrentValue() );
				lpToastify.show( response?.message || 'Saved', 'success' );
			},
			error: ( error ) => {
				this.handleSaveError( error?.message || error || 'An error occurred.' );
			},
			completed: () => {
				this.isSaving = false;
				if ( this.shouldRetryOnCompleted ) {
					this.queueSave();
				}
			},
		} );
	}

	getPayloadForSave() {
		return {
			action: 'save_global_settings',
			args: { id_url: 'save-global-settings' },
			hide_instructor_access_admin_screen: this.getCurrentValue(),
			course_builder_logo_id: this.getLogoId(),
			course_builder_logo_remove: this.getLogoRemoveValue(),
		};
	}

	applySaveResponse( response ) {
		const data = response?.data || {};
		const savedLogoID = parseInt( data.course_builder_logo_id ?? this.getLogoId(), 10 ) || 0;
		const savedLogoSrc = data.course_builder_logo_url || '';

		if ( this.logoIdInput ) {
			this.logoIdInput.value = savedLogoID;
		}

		if ( this.logoRemoveInput ) {
			this.logoRemoveInput.value = 'no';
		}

		if ( savedLogoID > 0 && savedLogoSrc ) {
			this.setLogoImageSource( savedLogoSrc );
			this.setLogoVisibility( true );
		} else if ( savedLogoID === 0 ) {
			this.clearLogoUI();
		}

		this.syncHeaderLogo( savedLogoID > 0 ? savedLogoSrc : '' );
	}

	handleSaveError( message ) {
		lpToastify.show( message, 'error' );
	}

	getCurrentValue() {
		const checkbox = this.form?.querySelector( BuilderSettings.selectors.elCheckbox );
		return checkbox && checkbox.checked ? 'yes' : 'no';
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

	getStateFingerprint() {
		return JSON.stringify( {
			hide_instructor_access_admin_screen: this.getCurrentValue(),
			course_builder_logo_id: this.getLogoId(),
			course_builder_logo_remove: this.getLogoRemoveValue(),
		} );
	}

	getLogoId() {
		return parseInt( this.logoIdInput?.value || '0', 10 ) || 0;
	}

	getLogoRemoveValue() {
		return this.logoRemoveInput?.value === 'yes' ? 'yes' : 'no';
	}

	openLogoMediaUploader() {
		if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
			lpToastify.show( 'Media library is unavailable.', 'error' );
			return;
		}

		if ( this.mediaUploader ) {
			this.mediaUploader.open();
			return;
		}

		this.mediaUploader = wp.media( {
			title: 'Select logo image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		} );

		this.mediaUploader.on( 'select', () => {
			const attachment = this.mediaUploader.state().get( 'selection' ).first().toJSON();
			const imageURL = attachment?.sizes?.full?.url || attachment?.url || '';
			if ( ! imageURL ) {
				return;
			}

			if ( this.logoIdInput ) {
				this.logoIdInput.value = attachment?.id || 0;
			}

			if ( this.logoRemoveInput ) {
				this.logoRemoveInput.value = 'no';
			}

			this.setLogoImageSource( imageURL );
			this.setLogoVisibility( true );
			this.handleSettingChange();
		} );

		this.mediaUploader.open();
	}

	setLogoImageSource( source ) {
		if ( this.logoPreviewImage ) {
			if ( source ) {
				this.logoPreviewImage.src = source;
			} else {
				this.logoPreviewImage.removeAttribute( 'src' );
			}
		}
	}

	setLogoVisibility( visible ) {
		if ( this.logoPreviewImage ) {
			this.logoPreviewImage.classList.toggle( 'is-hidden', ! visible );
		}

		if ( this.logoPreviewDefault ) {
			this.logoPreviewDefault.classList.toggle( 'is-hidden', visible );
		}

		if ( this.logoRemoveBtn ) {
			this.logoRemoveBtn.classList.toggle( 'is-hidden', ! visible );
		}
	}

	hydrateDefaultLogoPreview() {
		if ( ! this.logoPreviewDefault || this.logoPreviewDefault.children.length > 0 ) {
			return;
		}

		const defaultLogoTemplate = document.querySelector(
			BuilderSettings.selectors.elDefaultLogoTemplate
		);
		const defaultLogoSVG =
			defaultLogoTemplate?.content?.querySelector( 'svg' ) ||
			defaultLogoTemplate?.querySelector( 'svg' ) ||
			document.querySelector( '.lp-cb-top-header__logo svg' );

		if ( ! defaultLogoSVG && ! this.defaultLogoURL ) {
			return;
		}

		const svgPreviewImage = document.createElement( 'img' );
		svgPreviewImage.classList.add( 'lp-cb-logo-setting__preview-default-image' );
		svgPreviewImage.setAttribute( 'aria-hidden', 'true' );
		svgPreviewImage.alt = 'Course Builder default logo';
		if ( defaultLogoSVG ) {
			svgPreviewImage.src = `data:image/svg+xml;charset=UTF-8,${ encodeURIComponent(
				defaultLogoSVG.outerHTML
			) }`;
		} else {
			svgPreviewImage.src = this.defaultLogoURL;
		}

		this.logoPreviewDefault.appendChild( svgPreviewImage );
	}

	removeLogo() {
		if ( ! this.logoIdInput ) {
			return;
		}

		this.clearLogoUI();
		if ( this.logoRemoveInput ) {
			this.logoRemoveInput.value = 'yes';
		}
		this.handleSettingChange();
	}

	clearLogoUI() {
		if ( this.logoPreviewImage ) {
			this.logoPreviewImage.src = '';
		}

		if ( this.logoIdInput ) {
			this.logoIdInput.value = 0;
		}

		this.setLogoVisibility( false );
	}

	syncHeaderLogo( source = '' ) {
		if ( ! this.headerLogoLink ) {
			return;
		}

		const logoSource = source || this.defaultLogoURL;
		if ( ! logoSource ) {
			return;
		}

		let logoImage = this.headerLogoLink.querySelector( '.lp-cb-top-header__logo-image' );
		if ( ! logoImage ) {
			logoImage = document.createElement( 'img' );
			logoImage.classList.add( 'lp-cb-top-header__logo-image' );
			logoImage.alt = 'Course Builder';
			logoImage.loading = 'eager';
			logoImage.decoding = 'async';
			this.headerLogoLink.textContent = '';
			this.headerLogoLink.appendChild( logoImage );
		}

		logoImage.removeAttribute( 'srcset' );
		logoImage.removeAttribute( 'sizes' );
		logoImage.src = logoSource;
	}
}
