import { lpAddQueryArgs } from '../../utils/utils';
let query = {};
if ( lpSkeletonParam ) {
	lpSkeletonParam = JSON.parse( lpSkeletonParam );
}
export default function InstructorList() {
	// Call API get instructors without wait element ready
	let htmlListItemInstructor = '';
	let htmlPagination = '';
	getInstructors( { ...lpSkeletonParam, paged: 1 }, true, function( res ) {
		htmlListItemInstructor = res.data.content;
		if ( res.data.pagination !== undefined ) {
			htmlPagination = res.data.pagination;
		}
	} );

	let totalTimeDetect = 0;
	const detectedElArchive = setInterval( function() {
		totalTimeDetect++;

		// Stop if detected more than 10 seconds
		if ( totalTimeDetect > 10000 ) {
			clearInterval( detectedElArchive );
		}

		const elListInstructors = document.querySelector( '.lp-list-instructors' );
		if ( elListInstructors && htmlListItemInstructor !== '' ) {
			clearInterval( detectedElArchive );
			const elUlListInstructors = document.querySelector( '.ul-list-instructors' );
			elListInstructors.classList.add( 'detected' );
			elUlListInstructors.innerHTML = htmlListItemInstructor;
			elListInstructors.insertAdjacentHTML( 'beforeend', htmlPagination );
		}
	}, 1 );

	// For case multiple ul list instructors on a page.
	/*document.addEventListener( 'DOMContentLoaded', function( event ) {
		const elListInstructors = document.querySelectorAll( '.lp-list-instructors:not(.detected)' );
		if ( elListInstructors.length > 0 ) {
			elListInstructors.forEach( function( el ) {
				const elUlListInstructors = el.querySelector( '.ul-list-instructors' );
				query = { paged: 1 };
				getInstructors( query, true, function( res ) {
					elUlListInstructors.innerHTML = res.data.content;

					if ( res.data.pagination !== undefined ) {
						el.insertAdjacentHTML( 'beforeend', res.data.pagination );
					}
				} );
			} );
		}
	} );*/

	pagination();
}

const getInstructors = ( queryParam, firstLoad = false, callBack ) => {
	const url = lpAddQueryArgs( urlListInstructorsAPI, queryParam );
	const paramsFetch = {
		method: 'GET',
	};

	fetch( url, paramsFetch )
		.then( ( response ) => response.json() )
		.then( ( res ) => {
			if ( res.data.content !== undefined ) {
				if ( callBack ) {
					callBack( res );
				}
			}
		} ).catch( ( error ) => {
			console.log( error );
		} ).finally( () => {
			if ( firstLoad === false ) {
				const urlPush = lpInstructorsUrl + '?paged=' + queryParam.paged;
				window.history.pushState( '', '', urlPush );
			}
		} );
};

const pagination = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		const elListInstructors = target.closest( '.lp-list-instructors' );
		const elUlListInstructors = elListInstructors.querySelector( '.ul-list-instructors' );
		const pagination = target.closest( '.learn-press-pagination' );

		if ( ! pagination || ! elListInstructors || ! elUlListInstructors ) {
			return;
		}

		let pageLinkNode;
		if ( target.tagName.toLowerCase() === 'a' ) {
			pageLinkNode = target;
		} else if ( target.closest( 'a.page-numbers' ) ) {
			pageLinkNode = target.closest( 'a.page-numbers' );
		} else {
			return;
		}

		event.preventDefault();

		const currentPage = parseInt( pagination.querySelector( '.current' ).innerHTML );
		let paged;

		if ( pageLinkNode.classList.contains( 'next' ) ) {
			paged = currentPage + 1;
		} else if ( pageLinkNode.classList.contains( 'prev' ) ) {
			paged = currentPage - 1;
		} else {
			paged = pageLinkNode.innerHTML;
		}

		query = { ...query, paged, ...lpSkeletonParam };
		getInstructors( query, false, function( res ) {
			elUlListInstructors.innerHTML = res.data.content;
			pagination.remove();
			if ( res.data.pagination !== undefined ) {
				elListInstructors.insertAdjacentHTML( 'beforeend', res.data.pagination );
			}
		} );
	} );
};
