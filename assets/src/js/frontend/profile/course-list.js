import { addQueryArgs } from '@wordpress/url';

const contentAreaNode = document.querySelector( '.lp-content-area' );

let containerNode;
let query = {};

export default function CourseList() {
	containerNode = document.querySelector( '.learnpress-course-container' );

	if ( ! containerNode ) {
		return;
	}
	const data = JSON.parse( containerNode.querySelector( '.lp_profile_data' ).value );

	query = { ...data, c_author: data?.userID, template: 'user_not_publish' };
	getCourses( query );
	pagination();
}

const getCourses = ( queryParam ) => {
	containerNode.style.opacity = 0.4;

	wp.apiFetch( {
		path: addQueryArgs( 'lp/v1/courses/archive-course', queryParam ),
		method: 'GET',
	} ).then( ( res ) => {
		if ( res.data.content !== undefined ) {
			containerNode.innerHTML = res.data.content;
		}

		//pagination
		if ( contentAreaNode ) {
			const paginationNode = document.querySelector( '.learn-press-pagination' );
			if ( paginationNode ) {
				paginationNode.remove();
			}
			if ( res.data.pagination !== undefined ) {
				contentAreaNode.insertAdjacentHTML( 'beforeend', res.data.pagination );
			}
		}
	} ).catch( ( err ) => {
		console.log( err );
	} ).finally( () => {
		if ( queryParam.paged ) {
			const optionScroll = { behavior: 'smooth' };
			containerNode.scrollIntoView( optionScroll );
		}
		containerNode.style.opacity = 1;
	} );
};

const pagination = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		const pagination = target.closest( '.learn-press-pagination' );

		if ( ! pagination ) {
			return;
		}

		let pageLinkNode;
		if ( target.tagName.toLowerCase() === 'a' ) {
			pageLinkNode = target;
		}else if(target.closest('a.page-numbers')){
			pageLinkNode = target.closest('a.page-numbers');
		}else{
			return;
		}

		event.preventDefault();

		const currentPage = parseInt(pagination.querySelector('.current').innerHTML);
		let paged;

		if(pageLinkNode.classList.contains('next')){
			paged = currentPage + 1;
		}else if(pageLinkNode.classList.contains('prev')){
			paged = currentPage - 1;
		}else{
			paged = pageLinkNode.innerHTML;
		}

		query = { ...query, paged };
		getCourses( query );
	} );
};
