document.addEventListener( 'DOMContentLoaded', function () {
	const validTemplates = [
		'single-lp_course',
		'archive-lp_course',
		'taxonomy-course_tag',
		'taxonomy-course_category',
	];

	let previousTemplate = null;
	const checkAndReload = () => {
		const currentTemplate =
			wp?.data?.select( 'core/editor' )?.getEditedPostAttribute( 'slug' ) ||
			wp?.data?.select( 'core/editor' )?.getCurrentPostId();

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

	wp?.data?.subscribe( () => {
		checkAndReload();
	} );
} );
