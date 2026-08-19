/**
 * Dropdown Pages
 *
 * @since 4.2.5.1
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class DropdownPages {
	static selectors = {
		elDropdown: '.learn-press-dropdown-pages',
		elSelect: 'select',
		elListWrap: '.list-pages-wrapper',
		elActions: '.quick-add-page-actions',
		elForm: '.quick-add-page-inline',
		elButtonQuickAdd: '.button-quick-add-page',
		elInput: '.quick-add-page-inline input[type="text"]',
		elButtonSubmit: '.quick-add-page-inline button',
		elButtonCancel: '.quick-add-page-inline a',
	};

	constructor() {
		this.elDropdowns = [];
	}

	init() {
		this.elDropdowns = document.querySelectorAll( DropdownPages.selectors.elDropdown );
		if ( ! this.elDropdowns.length ) {
			return;
		}

		this.events();
	}

	events() {
		// Check and attach events only once
		if ( DropdownPages._loadedEvents ) {
			return;
		}

		DropdownPages._loadedEvents = this;

		// Change events
		lpUtils.eventHandlers( 'change', [
			{
				selector: DropdownPages.selectors.elDropdown,
				class: this,
				callBack: this.handleChangeSelect.name,
			},
		] );

		// Click events
		lpUtils.eventHandlers( 'click', [
			{
				selector: DropdownPages.selectors.elButtonSubmit,
				class: this,
				callBack: this.handleSubmit.name,
			},
			{
				selector: DropdownPages.selectors.elButtonCancel,
				class: this,
				callBack: this.handleCancel.name,
			},
			{
				selector: DropdownPages.selectors.elButtonQuickAdd,
				class: this,
				callBack: this.handleQuickAdd.name,
			},
		] );

		// Keydown events
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: DropdownPages.selectors.elInput,
				class: this,
				callBack: this.handleInputEnter.name,
				checkIsEventEnter: true,
			},
			{
				selector: DropdownPages.selectors.elInput,
				class: this,
				callBack: this.handleInputEscape.name,
			},
		] );
	}

	handleChangeSelect( args ) {
		const { e } = args;
		const elSelect = e.target;
		if ( ! elSelect.matches( DropdownPages.selectors.elSelect ) ) {
			return;
		}

		const elDropdown = elSelect.closest( DropdownPages.selectors.elDropdown );
		if ( ! elDropdown ) {
			return;
		}

		const elActions = elDropdown.querySelector( DropdownPages.selectors.elActions );

		if ( elActions ) {
			elActions.classList.add( 'hide-if-js' );
		}

		if ( parseInt( elSelect.value, 10 ) ) {
			if ( elActions ) {
				const editLink = elActions.querySelector( 'a.edit-page' );
				const viewLink = elActions.querySelector( 'a.view-page' );
				if ( editLink ) {
					editLink.href = `post.php?post=${ elSelect.value }&action=edit`;
				}
				if ( viewLink ) {
					viewLink.href = `${ window.lpGlobalSettings.siteurl }?page_id=${ elSelect.value }`;
				}
				elActions.classList.remove( 'hide-if-js' );
			}
			elSelect.setAttribute( 'data-selected', elSelect.value );
		}
	}

	openQuickAddForm( elDropdown ) {
		const elListWrap = elDropdown.querySelector( DropdownPages.selectors.elListWrap );
		const elForm = elDropdown.querySelector( DropdownPages.selectors.elForm );

		if ( elListWrap ) {
			elListWrap.classList.add( 'hide-if-js' );
		}
		if ( elForm ) {
			elForm.classList.remove( 'hide-if-js' );
			const elInput = elForm.querySelector( 'input' );
			if ( elInput ) {
				elInput.value = '';
				elInput.focus();
			}
		}
	}

	handleSubmit( args ) {
		const { e } = args;
		e.preventDefault();

		const elButton = e.target.closest( DropdownPages.selectors.elButtonSubmit );
		if ( ! elButton ) {
			return;
		}

		const elForm = elButton.closest( DropdownPages.selectors.elForm );
		if ( ! elForm ) {
			return;
		}

		const elDropdown = elForm.closest( DropdownPages.selectors.elDropdown );
		if ( ! elDropdown ) {
			return;
		}

		const elInput = elForm.querySelector( 'input' );
		const elListWrap = elDropdown.querySelector( DropdownPages.selectors.elListWrap );
		const pageName = elInput ? elInput.value.trim() : '';

		if ( ! pageName ) {
			alert( 'Please enter the name of page' );
			if ( elInput ) {
				elInput.focus();
			}
			return;
		}

		elButton.disabled = true;

		let fieldName = '';
		const elFieldName = elDropdown.querySelector( 'select' );
		fieldName = elFieldName ? elFieldName.name : '';

		if ( ! window.lpGlobalSettings || ! window.lpGlobalSettings.ajax || ! window.lpDataAdmin || ! window.lpDataAdmin.nonce ) {
			elButton.disabled = false;
			return;
		}

		const formData = new FormData();
		formData.append( 'action', 'learnpress_create_page' );
		formData.append( 'page_name', pageName );
		formData.append( 'field_name', fieldName );
		formData.append( 'nonce', window.lpDataAdmin.nonce );

		fetch( window.lpGlobalSettings.ajax, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				const { message, status, data } = response;
				if ( status === 'success' ) {
					elForm.classList.add( 'hide-if-js' );

					lpToastify.show( message, 'success' );

					setTimeout( () => {
						window.location.reload();
					}, 1000 )
				} else {
					throw new Error( message );
				}
			} )
			.catch( ( error ) => {
				lpToastify.show( error.message, 'error' );
			} )
			.finally( () => {
				elButton.disabled = false;
				if ( elListWrap ) {
					elListWrap.classList.remove( 'hide-if-js' );
				}
			} );
	}

	addNewPageToList( args ) {
		const { ID, name, positions } = args;
		const option = document.createElement( 'option' );
		option.value = ID;
		option.textContent = name;

		const position = positions.indexOf( ID + '' );

		document.querySelectorAll( `${ DropdownPages.selectors.elDropdown } ${ DropdownPages.selectors.elSelect }` ).forEach( ( select ) => {
			const newOption = option.cloneNode( true );
			if ( position === 0 ) {
				const options = select.querySelectorAll( 'option' );
				for ( const opt of options ) {
					if ( parseInt( opt.value, 10 ) ) {
						opt.before( newOption );
						break;
					}
				}
			} else if ( position === positions.length - 1 ) {
				select.appendChild( newOption );
			} else {
				const prevOption = select.querySelector( `option[value="${ positions[ position - 1 ] }"]` );
				if ( prevOption ) {
					prevOption.after( newOption );
				} else {
					select.appendChild( newOption );
				}
			}
		} );
	}

	handleCancel( args ) {
		const { e } = args;
		e.preventDefault();

		const elCancel = e.target.closest( DropdownPages.selectors.elButtonCancel );
		if ( ! elCancel ) {
			return;
		}

		const elForm = elCancel.closest( DropdownPages.selectors.elForm );
		if ( ! elForm ) {
			return;
		}

		const elDropdown = elForm.closest( DropdownPages.selectors.elDropdown );
		if ( ! elDropdown ) {
			return;
		}

		const elSelect = elDropdown.querySelector( DropdownPages.selectors.elSelect );
		const elListWrap = elDropdown.querySelector( DropdownPages.selectors.elListWrap );
		const selected = elSelect ? elSelect.getAttribute( 'data-selected' ) : '';

		elForm.classList.add( 'hide-if-js' );
		if ( elSelect ) {
			elSelect.value = selected + '';
			elSelect.removeAttribute( 'disabled' );
			elSelect.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		if ( elListWrap ) {
			elListWrap.classList.remove( 'hide-if-js' );
		}
	}

	handleQuickAdd( args ) {
		const { e } = args;
		const elButton = e.target.closest( DropdownPages.selectors.elButtonQuickAdd );
		if ( ! elButton ) {
			return;
		}

		const elDropdown = elButton.closest( DropdownPages.selectors.elDropdown );
		if ( ! elDropdown ) {
			return;
		}

		this.openQuickAddForm( elDropdown );
	}

	handleInputEnter( args ) {
		const { e } = args;
		e.preventDefault();

		const elInput = e.target;
		const elForm = elInput.closest( DropdownPages.selectors.elForm );
		if ( ! elForm ) {
			return;
		}

		const elButton = elForm.querySelector( 'button' );
		if ( elButton ) {
			elButton.click();
		}
	}

	handleInputEscape( args ) {
		const { e } = args;
		if ( e.key !== 'Escape' ) {
			return;
		}

		const elInput = e.target;
		const elForm = elInput.closest( DropdownPages.selectors.elForm );
		if ( ! elForm ) {
			return;
		}

		const elCancel = elForm.querySelector( 'a' );
		if ( elCancel ) {
			elCancel.click();
		}
	}
}

