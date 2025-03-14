/**
 * Handle curriculum
 *
 * @version 1.0.1
 * @since 4.2.7.6
 */

import { lpShowHideEl, lpOnElementReady } from '../utils.js';

// Events
/**
 * 1. Handle click section header
 */
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

/**
 * 1. Handle search title course
 */
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;

	// code compare html with name = search
	if ( target.name === 's' && target.closest( 'form.search-course' ) ) {
		const value = target.value;
		searchItemCourse( value );
	}
} );

/**
 * 1. Handle submit form search
 */
document.addEventListener( 'submit', ( e ) => {
	const target = e.target;

	// Stop enter form search
	if ( target.closest( 'form.search-course' ) ) {
		e.preventDefault();
	}
} );
// End events

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

// Search title item of course by text
const searchItemCourse = ( text ) => {
	const elCurriculum = document.querySelector( '.lp-course-curriculum' );
	const elSections = elCurriculum.querySelectorAll( '.course-section' );

	elSections.forEach( ( elSection ) => {
		let found = false;

		elSection.querySelectorAll( '.course-item' ).forEach( ( elItem ) => {
			const elSection = elItem.closest( '.course-section' );
			const titleItem = elItem.querySelector( '.course-item-title' ).textContent;

			if ( ! searchText( titleItem, text ) ) {
				lpShowHideEl( elItem, 0 );
				elItem.classList.add( 'lp-hide' );
			} else {
				found = true;
				lpShowHideEl( elItem, 1 );
				elSection.classList.remove( 'lp-collapse' );
			}
		} );

		if ( ! found ) {
			lpShowHideEl( elSection, 0 );
		} else {
			lpShowHideEl( elSection, 1 );
		}
	} );
};

const normalizeVietnamese = ( str ) => {
	return str.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
};

/**
 * Search string on text
 * Logic:
 * User enter text: "11 lesson"
 * JS will search string has word "lesson" and "11"
 * Result: "Lesson 11: Introduction"
 * Result: "11 lesson: Introduction"
 *
 * @param text
 * @param searchTerm
 */
const searchText = ( text, searchTerm ) => {
	const normalizedText = normalizeVietnamese( text.toLowerCase() );
	const searchTermArr = searchTerm.trim().split( ' ' );
	const length = searchTermArr.length;

	let found = 0;
	searchTermArr.forEach( ( term ) => {
		const normalizedSearchTerm = normalizeVietnamese( term.toLowerCase() );
		const regex = new RegExp( normalizedSearchTerm, 'gi' );
		if ( regex.test( normalizedText ) ) {
			found++;
		}
	} );

	return found === length;
};

// Scroll to item viewing
const scrollToItemViewing = ( elCurriculum ) => {
	const elItemCurrent = elCurriculum.querySelector( 'li.current' );
	if ( ! elItemCurrent ) {
		return;
	}

	elItemCurrent.scrollIntoView( {
		behavior: 'smooth',
	} );
};

lpOnElementReady( '.lp-course-curriculum', ( elCurriculum ) => {
	checkAllSectionsCollapsed( elCurriculum );

	// Set interval to check if item viewing is changed
	const interval = setInterval( () => {
		if ( document.readyState === 'complete' ) {
			clearInterval( interval );
			scrollToItemViewing( elCurriculum );
		}
	}, 300 );
} );
