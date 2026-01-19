/**
 * Course Builder - Form State Management
 * Track unsaved changes and handle tab navigation
 *
 * @since 4.3.0
 * @version 1.0.0
 */

export class BuilderFormState {
	constructor() {
		this.hasUnsavedChanges = false;
		this.formElements = [];
		this.originalValues = new Map();
		
		this.init();
	}

	init() {
		this.bindEvents();
		this.captureOriginalValues();
	}

	/**
	 * Capture original form values for comparison
	 */
	captureOriginalValues() {
		const forms = document.querySelectorAll( '.cb-section__course-edit, .lp-cb-tab-content' );
		
		forms.forEach( form => {
			const inputs = form.querySelectorAll( 'input, textarea, select' );
			inputs.forEach( input => {
				const key = input.name || input.id;
				if ( key ) {
					this.originalValues.set( key, this.getInputValue( input ) );
				}
			});
		});
	}

	/**
	 * Get input value based on type
	 */
	getInputValue( input ) {
		if ( input.type === 'checkbox' ) {
			return input.checked;
		}
		if ( input.type === 'radio' ) {
			return input.checked ? input.value : null;
		}
		return input.value;
	}

	/**
	 * Bind all events
	 */
	bindEvents() {
		// Track form changes
		document.addEventListener( 'input', this.handleFormChange.bind( this ) );
		document.addEventListener( 'change', this.handleFormChange.bind( this ) );
		
		// TinyMCE changes (if available)
		this.bindTinyMCEChanges();
		
		// Tab navigation warning
		document.addEventListener( 'click', this.handleTabClick.bind( this ) );
		
		// Browser navigation warning
		window.addEventListener( 'beforeunload', this.handleBeforeUnload.bind( this ) );
		
		// Reset state after successful save
		document.addEventListener( 'lp-course-builder-saved', this.resetState.bind( this ) );
	}

	/**
	 * Bind TinyMCE editor changes
	 */
	bindTinyMCEChanges() {
		// Wait for TinyMCE to be ready
		if ( typeof tinymce !== 'undefined' ) {
			tinymce.on( 'AddEditor', ( e ) => {
				e.editor.on( 'change', () => {
					this.markAsChanged();
				});
				e.editor.on( 'keyup', () => {
					this.markAsChanged();
				});
			});
		}
	}

	/**
	 * Handle form input changes
	 */
	handleFormChange( e ) {
		const target = e.target;

		// Ignore curriculum changes
		if ( target.closest( '#lp-course-edit-curriculum' ) ) {
			return;
		}
		
		// Check if target is within course builder forms
		if ( target.closest( '.cb-section__course-edit' ) || 
			 target.closest( '.lp-cb-tab-content' ) ||
			 target.closest( '.lp-form-setting-course' ) ) {
			this.markAsChanged();
		}
	}

	/**
	 * Mark form as having unsaved changes
	 */
	markAsChanged() {
		if ( ! this.hasUnsavedChanges ) {
			this.hasUnsavedChanges = true;
			this.updateSaveButtonState();
		}
	}

	/**
	 * Update save button visual state
	 */
	updateSaveButtonState() {
		const saveButtons = document.querySelectorAll( '.cb-btn-update, .lp-cb-save-btn' );
		
		saveButtons.forEach( btn => {
			if ( this.hasUnsavedChanges ) {
				btn.classList.add( 'has-changes' );
			} else {
				btn.classList.remove( 'has-changes' );
			}
		});
	}

	/**
	 * Handle tab navigation click
	 */
	handleTabClick( e ) {
		const tabLink = e.target.closest( '.lp-cb-tabs__item, .lp-cb-sidebar__item a' );
		
		if ( ! tabLink ) {
			return;
		}

		if ( tabLink.hasAttribute( 'data-tab-section' ) || 
			 tabLink.getAttribute( 'href' ) === '#' ) {
			return;
		}

		if ( tabLink.classList.contains( 'is-active' ) || 
			 tabLink.closest( '.is-active' ) ) {
			return;
		}

		if ( this.hasUnsavedChanges ) {
			const confirmLeave = confirm( 
				wp?.i18n?.__( 'You have unsaved changes. Are you sure you want to leave this page?', 'learnpress' ) ||
				'You have unsaved changes. Are you sure you want to leave this page?'
			);
			
			if ( ! confirmLeave ) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}
	}

	/**
	 * Handle browser back/forward/close
	 */
	handleBeforeUnload( e ) {
		if ( this.hasUnsavedChanges ) {
			e.preventDefault();
			e.returnValue = '';
			return '';
		}
	}

	/**
	 * Reset state after successful save
	 */
	resetState() {
		this.hasUnsavedChanges = false;
		this.updateSaveButtonState();
		this.captureOriginalValues();
	}

	/**
	 * Check if form has unsaved changes
	 */
	hasChanges() {
		return this.hasUnsavedChanges;
	}

	/**
	 * Manually set changed state
	 */
	setChanged( changed = true ) {
		this.hasUnsavedChanges = changed;
		this.updateSaveButtonState();
	}
}

// Export singleton instance
let formStateInstance = null;

export const getFormState = () => {
	if ( ! formStateInstance ) {
		formStateInstance = new BuilderFormState();
	}
	return formStateInstance;
};

export default BuilderFormState;
