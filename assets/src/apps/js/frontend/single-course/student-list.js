
const studentListCourse = () => {
	const elementStudentList = document.querySelector( '.learnpress-course-student-list' );

	if ( ! elementStudentList ) {
		return;
	}

	const skeleton = elementStudentList.querySelector( '.lp-skeleton-animation' );
	const eleParents = elementStudentList.querySelector( '.content-student-list' );

	const Sekeleton = () => {
		const extraQuery = {
			status: '',
			paged: 1,
		};

		getResponse( eleParents, extraQuery );

		const select = document.querySelector( '.students-list-filter' );

		if ( select != null ) {
			select.addEventListener( 'change', function() {
				eleParents.innerHTML = '';
				skeleton.style.display = 'block';
				extraQuery.status = this.value;
				getResponse( eleParents, extraQuery );
			} );
		}
	};

	const getResponse = async ( ele, extraQuery, append = false, viewMoreEle ) => {
		const courseID = ele.dataset.id;
		const statusFilter = extraQuery?.status || '';
		const paged = extraQuery?.paged || 1;

		try {
			const response = await wp.apiFetch( {
				path: addQueryArgs( 'lp/v1/lazy-load/student-list', {
					courseId: courseID || lpGlobalSettings.post_id || '',
					status: statusFilter,
					paged,
				} ),
				method: 'GET',
			} );

			const { data, status, message } = response;

			if ( status === 'error' ) {
				throw new Error( message || 'Error' );
			}

			if ( append ) {
				ele.innerHTML += data.content;
			} else {
				ele.innerHTML = data.content;
			}

			if ( viewMoreEle ) {
				viewMoreEle.classList.remove( 'loading' );

				const paged = viewMoreEle.dataset.paged;
				const numberPage = viewMoreEle.dataset.number;

				if ( numberPage <= paged ) {
					viewMoreEle.remove();
				}

				viewMoreEle.dataset.paged = parseInt( paged ) + 1;
			}
			viewMoreStudentList( ele, extraQuery );
		} catch ( error ) {
			ele.insertAdjacentHTML( 'beforeend', `<div class="learn-press-message error" style="display:block">${ error.message || 'Error: Query lp/v1/lazy-load/course-student-list' }</div>` );
		}

		skeleton.style.display = 'none';
	};

	const viewMoreStudentList = ( ele, extraQuery ) => {
		const viewMoreEle = ele.querySelector( '.lp_student_list_button button.lp-button' );

		if ( viewMoreEle ) {
			viewMoreEle.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				const paged = viewMoreEle && viewMoreEle.dataset.paged;

				viewMoreEle.classList.add( 'loading' );

				getResponse( ele.querySelector( 'ul.students' ), { ...extraQuery, ...{ paged } }, true, viewMoreEle );
			} );
		}
	};

	Sekeleton();
};
