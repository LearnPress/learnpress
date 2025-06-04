/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';
import * as lpEditCurriculumShare from './edit-curriculum/share.js';
import * as sectionEdit from './edit-curriculum/edit-section.js';
import * as sectionItemEdit from './edit-curriculum/edit-section-item.js';

const { className } = lpEditCurriculumShare;

// Toggle all sections
const toggleSectionAll = ( e, target ) => {
	const elToggleAllSections = target.closest( `${ className.elToggleAllSections }` );
	if ( ! elToggleAllSections ) {
		return;
	}

	const elEditCurriculum = lpEditCurriculumShare.elEditCurriculum;
	const elSections = elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.${ className.elSectionClone })` );

	elToggleAllSections.classList.toggle( `${ className.elCollapse }` );

	if ( elToggleAllSections.classList.contains( `${ className.elCollapse }` ) ) {
		elSections.forEach( ( el ) => {
			if ( ! el.classList.contains( `${ className.elCollapse }` ) ) {
				el.classList.add( `${ className.elCollapse }` );
			}
		} );
	} else {
		elSections.forEach( ( el ) => {
			if ( el.classList.contains( `${ className.elCollapse }` ) ) {
				el.classList.remove( `${ className.elCollapse }` );
			}
		} );
	}
};

// Update count items in section and all sections
const updateCountItems = ( elSection ) => {
	const elEditCurriculum = lpEditCurriculumShare.elEditCurriculum;
	const elCountItemsAll = elEditCurriculum.querySelector( '.total-items' );
	const elItemsAll = elEditCurriculum.querySelectorAll( `${ className.elSectionItem }:not(.${ className.elItemClone })` );
	elCountItemsAll.innerHTML = elItemsAll.length;

	// Count items in section
	const elCountItems = elSection.querySelector( '.section-items-counts' );
	const elItems = elSection.querySelectorAll( `${ className.elSectionItem }:not(.${ className.elItemClone })` );
	elCountItems.textContent = elItems.length;
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	/*** Event of Section ***/
	// Add new section
	sectionEdit.addSection( e, target );

	// Collapse/Expand section
	sectionEdit.toggleSection( e, target );

	// Click button to update section description
	sectionEdit.updateSectionDescription( e, target );

	// Cancel update section description
	sectionEdit.cancelSectionDescription( e, target );

	// Delete section
	sectionEdit.deleteSection( e, target );

	// Update section title
	sectionEdit.updateSectionTitle( e, target );

	// Cancel update section title
	sectionEdit.cancelSectionTitle( e, target );
	/*** End Event of Section ***/

	/*** Event of Section Item ***/
	// Select item type to add
	sectionItemEdit.addItemType( e, target );
	sectionItemEdit.cancelAddItemType( e, target );
	// Add item to section
	sectionItemEdit.addItemToSection( e, target );
	// Show popup select items
	sectionItemEdit.showPopupItemsToSelect( e, target );
	// Show items selected
	sectionItemEdit.showItemsSelected( e, target );
	// Load items by type
	sectionItemEdit.chooseTabItemsType( e, target );
	// Update item title
	sectionItemEdit.updateTitle( e, target );
	// Cancel update item title
	sectionItemEdit.cancelUpdateTitle( e, target );
	// Delete item from section
	sectionItemEdit.deleteItem( e, target );
	// Click to select items from list
	sectionItemEdit.selectItemsFromList( e, target );
	// Add items selected to section
	sectionItemEdit.addItemsSelectedToSection( e, target );
	// Back to select items
	sectionItemEdit.backToSelectItems( e, target );
	// Remove item selected
	sectionItemEdit.removeItemSelected( e, target );
	/*** End Event of Section Item ***/

	// Collapse/Expand all sections
	toggleSectionAll( e, target );
	return;

	// Select items from list to add to section
	showSelectItemFromList( e, target );

	// Choose item type
	chooseItemType( e, target );

	// Select items from list
	selectItemsFromList( e, target );

	// Add items selected to section
	addItemsSelectedToSection( e, target );

	// Delete item from section
	deleteItemFromSection( e, target );

	// Show items choice
	showItemsChoice( e, target );

	// Back to select items
	backToSelectItems( e, target );

	// Remove item selected
	removeItemSelected( e, target );

	// Add item to section
	if ( target.classList.contains( `${ className.btnAddItem }` ) ) {
		addItemToSection( e, target );
	}
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		// e.preventDefault();
		sectionEdit.addSection( e, target );
		sectionEdit.updateSectionTitle( e, target );
		sectionEdit.updateSectionDescription( e, target );

		sectionItemEdit.updateTitle( e, target );
	}
} );

document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	// Change section title
	sectionEdit.changeTitle( e, target );
	// Change section description
	sectionEdit.changeDescription( e, target );
	// Change item title
	sectionItemEdit.changeTitle( e, target );
} );

// Element root ready.
lpUtils.lpOnElementReady( `${ className.idElEditCurriculum }`, ( elEditCurriculum ) => {
	const elCurriculumSections = elEditCurriculum.querySelector( `${ className.elCurriculumSections }` );
	const elLPTarget = elEditCurriculum.closest( `${ className.LPTarget }` );
	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

	lpEditCurriculumShare.setVariables(
		{
			elEditCurriculum,
			elCurriculumSections,
			elLPTarget,
			dataSend,
			updateCountItems,
		}
	);

	// Set variables use for section edit
	sectionEdit.init();
	sectionEdit.sortAbleSection();

	// Set variables use for edit section item
	sectionItemEdit.init();
	sectionItemEdit.sortAbleItem();
} );
