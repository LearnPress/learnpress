/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import Sortable from 'sortablejs';

let elLPTarget;
let elSectionCreating;
let elNewSectionItem;
let dataSend;
let elCurriculumSections;

const toastify = Toastify( {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
} );

const className = {
	btnNewSection: 'lp-btn-add-section',
	btnSelectItemType: 'lp-btn-select-item-type',
	btnAddItem: 'lp-btn-add-item',
	elNewSectionItem: 'new-section-item',
	elSectionDesInput: 'section-description-input',
	LPTarget: '.lp-target',
};

// Add new section
const addSection = ( e, target ) => {
	const elAddNewSection = target.closest( '.add-new-section' );
	if ( ! elAddNewSection ) {
		return;
	}

	e.preventDefault();

	const elInputTitleSection = elAddNewSection.querySelector( 'input[name="new_section"]' );
	const titleSectionValue = elInputTitleSection.value.trim();
	if ( titleSectionValue.length === 0 ) {
		toastify.options.text = 'Please enter a title for the new section.';
		toastify.options.className += 'error';
		toastify.showToast();
		return;
	}

	// Add and set data for new section
	const newSection = elSectionCreating.cloneNode( true );
	const titleNewSection = newSection.querySelector( 'input[name="section-title-input"]' );
	titleNewSection.value = titleSectionValue;
	elAddNewSection.insertAdjacentElement( 'beforebegin', newSection );
	// End

	// Call ajax to add new section
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;
			const { content } = response.data;

			toastify.options.text = message;
			toastify.options.className += status;
			toastify.showToast();
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			//console.log( 'completed' );
		},
	};

	dataSend.args.action = 'add_section';
	dataSend.args.title = titleSectionValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
/**
 * Select item type to add to section
 * @param e
 * @param target
 */
const selectItemToAddSection = ( e, target ) => {
	const elBtnSelectItem = target.closest( `.${ className.btnSelectItemType }` );
	if ( ! elBtnSelectItem ) {
		return;
	}

	const itemType = elBtnSelectItem.dataset.itemType;
	const itemPlaceholder = elBtnSelectItem.dataset.placeholder;
	const itemBtnAddText = elBtnSelectItem.dataset.buttonAddText;

	// Insert input item type to add
	const elSection = elBtnSelectItem.closest( '.section' );
	const elSectionItems = elSection.querySelector( '.section-list-items' );
	const elNewSectionItemClone = elNewSectionItem.cloneNode( true );
	lpUtils.lpShowHideEl( elNewSectionItemClone, 1 );
	elNewSectionItemClone.dataset.itemType = itemType;
	const elLabelItemType = elNewSectionItemClone.querySelector( 'label' );
	elLabelItemType.classList.add( itemType );
	const elInputItemType = elNewSectionItemClone.querySelector( 'input[name="new_item"]' );
	elInputItemType.setAttribute( 'placeholder', itemPlaceholder );
	const elBtnAddItem = elNewSectionItemClone.querySelector( `.${ className.btnAddItem }` );
	elBtnAddItem.textContent = itemBtnAddText;
	elSectionItems.insertAdjacentElement( 'beforeend', elNewSectionItemClone );
};
/**
 * Add item to section
 * @param e
 * @param target
 */
const addItemToSection = ( e, target ) => {
	const elNewSectionItem = target.closest( `.${ className.elNewSectionItem }` );
	if ( ! elNewSectionItem ) {
		return;
	}

	e.preventDefault();

	const elSection = elNewSectionItem.closest( '.section' );
	const sectionId = elSection.dataset.sectionId;
	const elInputTitleItem = elNewSectionItem.querySelector( 'input[name="new_item"]' );
	const titleItemValue = elInputTitleItem.value.trim();
	const typeItemValue = elNewSectionItem.dataset.itemType;

	// Call ajax to add item to section
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;
			const { content } = response.data;

			toastify.options.text = message;
			toastify.options.className += status;
			toastify.showToast();
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			//console.log( 'completed' );
		},
	};

	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'add_item_to_section';
	dataSend.args.item_title = titleItemValue;
	dataSend.args.item_type = typeItemValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const updateSectionDescription = ( e, target ) => {
	const elSectionDesInput = target.closest( `.${ className.elSectionDesInput }` );
	if ( ! elSectionDesInput ) {
		return;
	}

	const elSection = elSectionDesInput.closest( '.section' );
	const sectionId = elSection.dataset.sectionId;
	const sectionDesValue = elSectionDesInput.value.trim();
	if ( sectionDesValue.length === 0 ) {
		toastify.options.text = 'Please enter a description for the section.';
		toastify.options.className += 'error';
		toastify.showToast();
		return;
	}

	// Call ajax to update section description
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;
			const { content } = response.data;

			toastify.options.text = message;
			toastify.options.className += status;
			toastify.showToast();
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			//console.log( 'completed' );
		},
	};

	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'update_section';
	dataSend.args.section_description = sectionDesValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const toggleSection = ( e, target ) => {
	const elBtnCollapse = target.closest( '.collapse' );
	if ( ! elBtnCollapse ) {
		return;
	}

	const elSection = elBtnCollapse.closest( '.section' );
	const elSectionCollapse = elSection.querySelector( '.section-collapse' );

	if ( elSection.classList.contains( 'open' ) ) {
		elSection.classList.remove( 'open' );
		elSection.classList.add( 'close' );
		lpUtils.lpShowHideEl( elSectionCollapse, 0 );
	} else {
		elSection.classList.remove( 'close' );
		elSection.classList.add( 'open' );
		lpUtils.lpShowHideEl( elSectionCollapse, 1 );
	}
};
const sortAbleRow = ( elCurriculumSections ) => {
	new Sortable( elCurriculumSections, {
		handle: '.movable',
		animation: 150,
		onEnd: ( evt ) => {
			const elSection = evt.item;
			const sectionId = elSection.dataset.sectionId;
			const newPosition = evt.newIndex;

			// Call ajax to update section position
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;
					const { content } = response.data;

					toastify.options.text = message;
					toastify.options.className += status;
					toastify.showToast();
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
				},
			};

			dataSend.args.section_id = sectionId;
			dataSend.args.action = 'update_section_position';
			dataSend.args.new_position = newPosition;
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		},
	} );

	const elSectionListItems = elCurriculumSections.querySelectorAll( '.section-list-items' );
	elSectionListItems.forEach( ( elSectionListItem ) => {
		new Sortable( elSectionListItem, {
			handle: '.drag',
			animation: 150,
			group: {
				name: 'shared',
			},
			onEnd: ( evt ) => {

			},
		} );
	} );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	if ( target.classList.contains( `${ className.btnNewSection }` ) ) {
		addSection( e, target );
	}

	// Collapse/Expand section
	toggleSection( e, target );
	// Select item type to add
	selectItemToAddSection( e, target );
	// Add item to section
	if ( target.classList.contains( `${ className.btnAddItem }` ) ) {
		addItemToSection( e, target );
	}

	// Cancel add item to section
	if ( target.classList.contains( 'lp-btn-add-item-cancel' ) ) {
		const elNewSectionItem = target.closest( `.${ className.elNewSectionItem }` );
		if ( elNewSectionItem ) {
			elNewSectionItem.remove();
		}
	}
} );

document.addEventListener( 'submit', ( e ) => {
	const target = e.target;
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		addSection( e, target );
		addItemToSection( e, target );
		updateSectionDescription( e, target );

		e.preventDefault();
	}
} );

document.addEventListener( 'focus', ( e ) => {
	console.log( 'focus', e.target );
} );

// Element root ready.
lpUtils.lpOnElementReady( '#admin-editor-lp_course', ( elAdminEditor ) => {
	elLPTarget = elAdminEditor.closest( `${ className.LPTarget }` );
	dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

	elSectionCreating = elAdminEditor.querySelector( '.section' );
	elNewSectionItem = elAdminEditor.querySelector( `.${ className.elNewSectionItem }` );
	elCurriculumSections = elAdminEditor.querySelector( '.curriculum-sections' );

	sortAbleRow( elCurriculumSections );
} );
