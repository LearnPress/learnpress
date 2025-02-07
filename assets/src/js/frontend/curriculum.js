/**
 * Handle curriculum
 *
 * @version 1.0.0
 * @since 4.2.7.6
 */

import { lpShowHideEl, lpOnElementReady } from '../utils.js';

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	const elSectionHeader = target.closest( '.course-section-header' );
	if ( elSectionHeader ) {
		const elSection = elSectionHeader.closest( '.course-section' );
		if ( ! elSection ) {
			return;
		}

		e.preventDefault();
		toggleSection( elSection );
	}

	if ( target.classList.contains( 'course-toggle-all-sections' ) ) {
		e.preventDefault();
		toggleSectionAll( target );
	}
} );

const toggleSectionAll = ( elToggleAllSections ) => {
	const elCurriculum = elToggleAllSections.closest( '.lp-course-curriculum' );
	const elSections = elCurriculum.querySelectorAll( '.course-section' );
	const elExpand = elCurriculum.querySelector( '.course-toggle-all-sections' );
	const elCollapse = elCurriculum.querySelector( '.course-toggle-all-sections.lp-collapse' );

	if ( elToggleAllSections.classList.contains( 'lp-collapse' ) ) {
		lpShowHideEl( elExpand, 1 );
		lpShowHideEl( elCollapse, 0 );

		elSections.forEach( ( el ) => {
			if ( ! el.classList.contains( 'lp-collapse' ) ) {
				el.classList.add( 'lp-collapse' );
			}
		} );
	} else {
		elSections.forEach( ( el ) => {
			lpShowHideEl( elExpand, 0 );
			lpShowHideEl( elCollapse, 1 );

			if ( el.classList.contains( 'lp-collapse' ) ) {
				el.classList.remove( 'lp-collapse' );
			}
		} );
	}
};

const toggleSection = ( elSection ) => {
	const elCurriculum = elSection.closest( '.lp-course-curriculum' );

	// Toggle section
	elSection.classList.toggle( 'lp-collapse' );

	// Check all sections collapsed
	checkAllSectionsCollapsed( elCurriculum );
};

const checkAllSectionsCollapsed = ( elCurriculum ) => {
	const elSections = elCurriculum.querySelectorAll( '.course-section' );
	const elExpand = elCurriculum.querySelector( '.course-toggle-all-sections' );
	const elCollapse = elCurriculum.querySelector( '.course-toggle-all-sections.lp-collapse' );

	let isAllCollapsed = false;
	elSections.forEach( ( el ) => {
		if ( el.classList.contains( 'lp-collapse' ) ) {
			isAllCollapsed = true;
		}
	} );

	if ( isAllCollapsed ) {
		lpShowHideEl( elExpand, 1 );
		lpShowHideEl( elCollapse, 0 );
	} else {
		lpShowHideEl( elExpand, 0 );
		lpShowHideEl( elCollapse, 1 );
	}
};

lpOnElementReady( '.lp-course-curriculum', ( elCurriculum ) => {
	checkAllSectionsCollapsed( elCurriculum );
} );
