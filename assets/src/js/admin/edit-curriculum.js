/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
let elLPTarget;

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
	BTN_ADD_NEW_SECTION: 'lp-btn-add-new-section',
	classLPTarget: '.lp-target',
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

	// Call ajax to add new section
	console.log( 'Add new section', titleSectionValue );

	const callBack = {
		success: ( response ) => {
			//console.log( 'response', response );
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			//console.log( 'completed' );
		},
	};

	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
	dataSend.args.action = 'add_section';
	dataSend.args.title = titleSectionValue;
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

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	if ( target.classList.contains( `${ className.BTN_ADD_NEW_SECTION }` ) ) {
		addSection( e, target );
	}

	// Collapse/Expand section
	toggleSection( e, target );
} );

document.addEventListener( 'submit', ( e ) => {
	const target = e.target;
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		addSection( e, target );
	}
} );

document.addEventListener( 'focus', ( e ) => {
	console.log( 'focus', e.target );
} );

// Element root ready.
lpUtils.lpOnElementReady( '#admin-editor-lp_course', ( elAdminEditor ) => {
	elLPTarget = elAdminEditor.closest( `${ className.classLPTarget }` );
} );
