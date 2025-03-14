const profileQuizTab = () => {
	const quizTabWrapper = document.querySelector( '#profile-subtab-quiz-content' );
	const lpTarget = quizTabWrapper.querySelector( '.lp-target' ),
	loadAjaxUrl = lpData.lp_rest_load_ajax;
	let dataObj = JSON.parse( lpTarget.dataset.send ), dataSend = { ...dataObj },
		elLoadingChange = lpTarget.closest( '.lp-load-ajax-element' ).querySelector( '.lp-loading-change' );
	
	quizTabWrapper.addEventListener( 'click', (e) => {
		let target = e.target;
		if ( target.classList.contains( 'page-numbers' ) ) {
			if ( target.tagName === 'A' ) {
				let currentPage = parseInt( quizTabWrapper.querySelector('.current').innerText ), paged;
				if ( target.classList.contains( 'prev' ) ) {
					paged = currentPage - 1;
				} else if ( target.classList.contains('next') ) {
					paged = currentPage + 1;
				} else {
					paged = parseInt( target.innerText );
				}
				dataSend.args.paged = paged;
				lpTarget.dataset.send = JSON.stringify( dataSend );
				getResponse();
			}
		} else if ( target.classList.contains('quiz-filter-type') && ! target.classList.contains( 'active' ) ) {
			let filterType = target.dataset.filter;
			dataSend.args.paged = 1;
			dataSend.args.type = filterType;
			lpTarget.dataset.send = JSON.stringify( dataSend );
			getResponse();
		}
	} );
	const getResponse = () => {
		if ( elLoadingChange ) {
			elLoadingChange.style.display = 'block';
		}
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpTarget.innerHTML = data.content;
			},
			error: ( error ) => {
				console.log( error );
			},
			completed: () => {
				if ( elLoadingChange ) {
					elLoadingChange.style.display = 'none';
				}
			},
		};
		window.lpAJAXG.fetchAPI( loadAjaxUrl, dataSend, callBack );
	}
}
export default profileQuizTab;