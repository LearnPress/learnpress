import {addQueryArgs} from '@wordpress/url';

const instructorNode = document.querySelector('.lp-instructors');
let contentAreaNode, containerNode;
let query = {};

export default function InstructorList() {
	if (!instructorNode) {
		return;
	}
	contentAreaNode = document.querySelector('.lp-content-area');
	containerNode = document.querySelector('.lp-instructor-list');

	query = {paged: 1};
	getInstructors(query, true);
	pagination();
}

const getInstructors = (queryParam, firstLoad = false) => {
	containerNode.style.opacity = 0.4;

	wp.apiFetch({
		path: addQueryArgs('lp/v1/instructors', queryParam),
		method: 'GET',
	}).then((res) => {
		if (res.data.content !== undefined) {
			containerNode.innerHTML = res.data.content;
		}

		//pagination
		if (contentAreaNode) {
			const paginationNode = document.querySelector('.learn-press-pagination');
			if (paginationNode) {
				paginationNode.remove();
			}
			if (res.data.pagination !== undefined) {
				contentAreaNode.insertAdjacentHTML('beforeend', res.data.pagination);
			}
		}
	}).catch((err) => {
		console.log(err);
	}).finally(() => {
		if (queryParam.paged) {
			const optionScroll = {behavior: 'smooth'};
			instructorNode.scrollIntoView(optionScroll);
		}

		if (firstLoad === false) {
			const urlPush = addQueryArgs(document.location.origin + document.location.pathname, queryParam);
			window.history.pushState('', '', urlPush);
		}

		containerNode.style.opacity = 1;
	});
};

const pagination = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		const pagination = target.closest('.learn-press-pagination');

		if (!pagination) {
			return;
		}

		let pageLinkNode;
		if (target.tagName.toLowerCase() === 'a') {
			pageLinkNode = target;
		} else if (target.closest('a.page-numbers')) {
			pageLinkNode = target.closest('a.page-numbers');
		} else {
			return;
		}

		event.preventDefault();

		const currentPage = parseInt(pagination.querySelector('.current').innerHTML);
		let paged;

		if (pageLinkNode.classList.contains('next')) {
			paged = currentPage + 1;
		} else if (pageLinkNode.classList.contains('prev')) {
			paged = currentPage - 1;
		} else {
			paged = pageLinkNode.innerHTML;
		}

		query = {...query, paged};
		getInstructors(query);
	});
};
