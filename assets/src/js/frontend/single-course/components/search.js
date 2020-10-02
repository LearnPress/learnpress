
export const searchCourseContent = () => {
	const popup = document.querySelector( '#popup-course' );
	const list = document.querySelector( '#learn-press-course-curriculum' );

	if ( popup && list ) {
		const items = list.querySelector( '.curriculum-sections' );
		const form = popup.querySelector( '.search-course' );
		const search = popup.querySelector( '.search-course input[type="text"]' );

		if ( ! search || ! items || ! form ) {
			return;
		}

		const sections = items.querySelectorAll( 'li.section' );
		const dataItems = items.querySelectorAll( 'li.course-item' );

		const dataSearch = [];

		dataItems.forEach( ( item ) => {
			const itemID = item.dataset.id;
			const name = item.querySelector( '.item-name' );

			dataSearch.push( {
				id: itemID,
				name: name ? name.textContent.toLowerCase() : '',
			} );
		} );

		const submit = ( event ) => {
			event.preventDefault();

			const inputVal = search.value;

			form.classList.add( 'searching' );

			if ( ! inputVal ) {
				form.classList.remove( 'searching' );
			}

			const outputs = [];

			dataSearch.forEach( ( i ) => {
				if ( ! inputVal || i.name.match( inputVal.toLowerCase() ) ) {
					outputs.push( i.id );

					dataItems.forEach( ( c ) => {
						if ( outputs.indexOf( c.dataset.id ) !== -1 ) {
							c.classList.remove( 'hide-if-js' );
						} else {
							c.classList.add( 'hide-if-js' );
						}
					} );
				}
			} );

			sections.forEach( ( section ) => {
				const listItem = section.querySelectorAll( '.course-item' );
				const isTrue = [];

				listItem.forEach( ( a ) => {
					if ( outputs.includes( a.dataset.id ) ) {
						isTrue.push( a.dataset.id );
					}
				} );

				if ( isTrue.length === 0 ) {
					section.classList.add( 'hide-if-js' );
				} else {
					section.classList.remove( 'hide-if-js' );
				}
			} );
		};

		const clear = form.querySelector( '.clear' );

		if ( clear ) {
			clear.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				search.value = '';

				submit( e );
			} );
		}

		form.addEventListener( 'submit', submit );
		search.addEventListener( 'keyup', submit );
	}
};
