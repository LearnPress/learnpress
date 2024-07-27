import { AdminUtilsFunctions, Api, Utils } from '../utils-admin.js';

const addCoursesToOrder = () => {
	let elModalSearchCourses;
	let elBtnAddOrderItem;
	let elOrderDetails, modalSearchItems, modalContainer, elModalSearchItem;

	const getAllElements = () => {
		elOrderDetails = document.querySelector( '#learn-press-order' );
		elModalSearchCourses = document.querySelector( '#modal-search-items' );
		elBtnAddOrderItem = elOrderDetails.querySelector( '#learn-press-add-order-item' );
		modalSearchItems = document.querySelector( '#learn-press-modal-search-items' );
		modalContainer = document.querySelector( '#container-modal-search-items' );
	};

	const fetchCoursesAPI = ( keySearch = '', course_ids_exclude = '' ) => {
		const dataSend = {
			not_ids: course_ids_exclude,
		};

		AdminUtilsFunctions.fetchCourses( keySearch, dataSend, {
			before() {
				elModalSearchItem.classList.add( 'loading' );
			},
			success( response ) {
				const { data, status, message } = response;
				const { courses, total_pages } = data;

				if ( 'success' !== status ) {
					console.error( message );
				} else {
					elModalSearchItem.querySelector( '.search-results' ).innerHTML = renderSearchResult( courses );
				}
			},
			error( err ) {
				console.error( err );
			},
			completed() {
				elModalSearchItem.classList.remove( 'loading' );
			},
		} );
	};

	const renderSearchResult = ( courses ) => {
		let html = '';

		for ( let i = 0; i < courses.length; i++ ) {
			html += `
		<li class="lp-result-item" data-id="${ courses[ i ].ID }" data-type="lp_course" data-text="${ courses[ i ].post_title }">
			<label>
				<input type="checkbox" value="${ courses[ i ].ID }" name="selectedItems[]">
				<span class="lp-item-text">${ courses[ i ].post_title } (Course - #${ courses[ i ].ID })</span>
			</label>
		</li>`;
		}

		return html;
	};

	const showPopupSearchCourses = () => {
		modalContainer.style.display = 'block';
		fetchCoursesAPI();
	};

	// Events.
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;
		console.dir( target );
		if ( target.id === elBtnAddOrderItem.id ) {
			e.preventDefault();
			showPopupSearchCourses();
		}

		if ( target.classList.contains( 'close' ) && target.closest( '#modal-search-items' ) ) {
			e.preventDefault();
			modalContainer.style.display = 'none';
		}
	} );

	// DOMContentLoaded.
	document.addEventListener( 'DOMContentLoaded', () => {
		getAllElements();

		if ( ! elOrderDetails || ! elBtnAddOrderItem ) {
			return;
		}

		modalContainer.innerHTML = modalSearchItems.innerHTML;
		elModalSearchItem = modalContainer.querySelector( '#modal-search-items' );
		modalContainer.style.display = 'none';

		fetchCoursesAPI();
	} );
};

export default addCoursesToOrder;
