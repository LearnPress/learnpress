import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div class="course-item-nav" data-nav="all">
				<div class="prev">
					<div class="course-item-nav__name">Lesson 1</div>
					<a>Prev</a>
				</div>

				<div class="next">
					<div class="course-item-nav__name">Lesson 3</div>
					<a>Next</a>
				</div>
			</div>
		</div>
	);
};

export default Edit;
