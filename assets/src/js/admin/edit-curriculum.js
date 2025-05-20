/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

const className = {
	BTN_ADD_NEW_SECTION: 'lp-btn-add-new-section',
};

const addSection = ( e, target ) => {
	const elAddNewSection = target.closest( '.add-new-section' );
	if ( ! elAddNewSection ) {
		return;
	}

	e.preventDefault();

	const elInputTitleSection = elAddNewSection.querySelector( 'input[name="new_section"]' );

	console.log( 'addSection', elAddNewSection );
	console.log( 'title_value', elInputTitleSection.value );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	if ( target.classList.contains( `${ className.BTN_ADD_NEW_SECTION }` ) ) {
		addSection( e, target );
	}
} );

document.addEventListener( 'submit', ( e ) => {
	const target = e.target;
	// Stop submit form when entor
	if ( target.matches( 'form[name="post"]' ) ) {
		e.preventDefault();
	}
} );

document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		addSection( e, target );
	}
} );

document.addEventListener( 'focus', ( e ) => {
	console.log( 'focus', e.target );
} );
