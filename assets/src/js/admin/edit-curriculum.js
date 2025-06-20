/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */

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

	const elSections = lpEditCurriculumShare.elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.clone)` );

	elToggleAllSections.classList.toggle( `${ className.elCollapse }` );

	elSections.forEach( ( el ) => {
		const shouldCollapse = elToggleAllSections.classList.contains( `${ className.elCollapse }` );
		el.classList.toggle( `${ className.elCollapse }`, shouldCollapse );
	} );
};

// Update count items in each section and all sections
const updateCountItems = ( elSection ) => {
	const elEditCurriculum = lpEditCurriculumShare.elEditCurriculum;
	const elCountItemsAll = elEditCurriculum.querySelector( '.total-items' );
	const elItemsAll = elEditCurriculum.querySelectorAll( `${ className.elSectionItem }:not(.clone)` );
	const itemsAllCount = elItemsAll.length;

	elCountItemsAll.dataset.count = itemsAllCount;
	elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;

	// Count items in section
	const elSectionItemsCount = elSection.querySelector( '.section-items-counts' );

	const elItems = elSection.querySelectorAll( `${ className.elSectionItem }:not(.clone)` );
	const itemsCount = elItems.length;

	elSectionItemsCount.dataset.count = itemsCount;
	elSectionItemsCount.querySelector( '.count' ).textContent = itemsCount;
	// End count items in section
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	/*** Event of Section ***/
	// Click button add new section
	sectionEdit.addSection( e, target );
	// Click icon edit to focus section title input
	sectionEdit.setFocusTitleInput( e, target );
	// Click toggle section
	sectionEdit.toggleSection( e, target );
	// Click button to update section description
	sectionEdit.updateSectionDescription( e, target );
	// Click button to cancel update section description
	sectionEdit.cancelSectionDescription( e, target );
	// Click button delete section
	sectionEdit.deleteSection( e, target );
	// Click button update section title
	sectionEdit.updateSectionTitle( e, target );
	// Click button cancel update section title
	sectionEdit.cancelSectionTitle( e, target );
	/*** End Event of Section ***/

	/*** Event of Section Item ***/
	// Click button for item type to add a new item by type
	sectionItemEdit.addItemType( e, target );
	// Click button to cancel adding item type
	sectionItemEdit.cancelAddItemType( e, target );
	// Click button add to add item to section
	sectionItemEdit.addItemToSection( e, target );
	// Click to show popup list of items to choose and add to section
	sectionItemEdit.showPopupItemsToSelect( e, target );
	// Click to show list of items selected
	sectionItemEdit.showItemsSelected( e, target );
	// Load items by type
	sectionItemEdit.chooseTabItemsType( e, target );
	// Click button update to update item title
	sectionItemEdit.updateTitle( e, target );
	// Click button cancel update item title
	sectionItemEdit.cancelUpdateTitle( e, target );
	// Click icon delete item from section
	sectionItemEdit.deleteItem( e, target );
	// Choose item to add to the list of items selected before adding to section
	sectionItemEdit.selectItemsFromList( e, target );
	// Add items selected to section
	sectionItemEdit.addItemsSelectedToSection( e, target );
	// CClick button to go back to the list of items
	sectionItemEdit.backToSelectItems( e, target );
	// Click li item to remove item from selected list
	sectionItemEdit.removeItemSelected( e, target );
	// Click icon eye to toggle preview item
	sectionItemEdit.updatePreviewItem( e, target );
	/*** End Event of Section Item ***/

	// Collapse/Expand all sections
	toggleSectionAll( e, target );
} );
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		// Add new section
		sectionEdit.addSection( e, target );
		// Update section title
		sectionEdit.updateSectionTitle( e, target );
		// Update section description
		sectionEdit.updateSectionDescription( e, target );
		// Add item to section
		sectionItemEdit.addItemToSection( e, target );
		// Update item title
		sectionItemEdit.updateTitle( e, target );
	}
} );
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	// Typing change section title
	sectionEdit.changeTitleBeforeAdd( e, target );
	// Typing section title
	sectionEdit.changeTitle( e, target );
	// Typing section description
	sectionEdit.changeDescription( e, target );
	// Typing item title
	sectionItemEdit.changeTitle( e, target );
	// Typing item title to add new item
	sectionItemEdit.changeTitleAddNew( e, target );
	// Typing search title item to select
	sectionItemEdit.searchTitleItemToSelect( e, target );
} );
// Event focus in
document.addEventListener( 'focusin', ( e ) => {
	sectionEdit.focusTitleNewInput( e, e.target );
	sectionEdit.focusTitleInput( e, e.target );
	sectionItemEdit.focusTitleInput( e, e.target );
} );
// Event focus out
document.addEventListener( 'focusout', ( e ) => {
	sectionEdit.focusTitleNewInput( e, e.target, false );
	sectionEdit.focusTitleInput( e, e.target, false );
	sectionItemEdit.focusTitleInput( e, e.target, false );
} );
// Check if it has change when close tab or refresh page
window.addEventListener( 'beforeunload', function( e ) {
	if ( Object.keys( lpEditCurriculumShare.hasChange ).length === 0 ) {
		return;
	}

	e.preventDefault();
	e.returnValue = '';
} );

// Element root ready.
lpEditCurriculumShare.lpUtils.lpOnElementReady( `${ className.idElEditCurriculum }`, ( elEditCurriculum ) => {
	const elCurriculumSections = elEditCurriculum.querySelector( `${ className.elCurriculumSections }` );
	const elLPTarget = elEditCurriculum.closest( `${ className.LPTarget }` );
	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

	lpEditCurriculumShare.setVariables(
		{
			courseId: dataSend.args.course_id,
			elEditCurriculum,
			elCurriculumSections,
			elLPTarget,
			updateCountItems,
			hasChange: {},
		}
	);

	// Set variables use for section edit
	sectionEdit.init();
	sectionEdit.sortAbleSection();

	// Set variables use for edit section item
	sectionItemEdit.init();
	sectionItemEdit.sortAbleItem();
	// Share sortAbleItem function, for when create new section, will call this function to sort items in section.
	lpEditCurriculumShare.setVariable( 'sortAbleItem', sectionItemEdit.sortAbleItem );
} );
