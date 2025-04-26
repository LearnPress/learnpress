document.addEventListener( 'DOMContentLoaded', function () {
	const validTemplates = [
		'single-lp_course',
		'archive-lp_course',
		'taxonomy-course_tag',
		'taxonomy-course_category',
	];

	const getParamP = () => {
		const urlParams = new URLSearchParams( window.location.search );
		const paramP = urlParams.get( 'p' )?.replace( /^\/|\/$/g, '' );
		return paramP === 'template' ? paramP : null;
	};

	const debounce = ( func, wait ) => {
		let timeout;
		return ( ...args ) => {
			clearTimeout( timeout );
			timeout = setTimeout( () => func( ...args ), wait );
		};
	};

	let previousTemplate = null;
	const checkAndReload = () => {
		const currentTemplate =
			wp?.data?.select( 'core/editor' )?.getEditedPostAttribute( 'slug' ) ||
			wp?.data?.select( 'core/editor' )?.getCurrentPostId() ||
			getParamP();

		if ( ! currentTemplate || currentTemplate === previousTemplate ) return;

		if (
			currentTemplate !== 'home' &&
			previousTemplate &&
			previousTemplate !== currentTemplate &&
			( validTemplates.includes( currentTemplate ) || validTemplates.includes( previousTemplate ) )
		) {
			window.location.reload();
		}

		previousTemplate = currentTemplate;
	};

	const debouncedCheckAndReload = debounce( checkAndReload, 200 );

	wp?.data?.subscribe( () => {
		debouncedCheckAndReload();
	} );
} );
