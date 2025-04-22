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
			validTemplates.includes( currentTemplate ) &&
			previousTemplate &&
			previousTemplate !== currentTemplate
		) {
			window.location.reload();
		}

		if (
			previousTemplate &&
			validTemplates.includes( previousTemplate ) &&
			previousTemplate !== currentTemplate
		) {
			window.location.reload();
		}

		previousTemplate = currentTemplate;
	};

	wp?.data?.subscribe( () => {
		checkAndReload();
	} );
} );
