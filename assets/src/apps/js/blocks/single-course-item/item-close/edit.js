import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<a class="back-course" aria-label="Back to course">
				<i class="lp-icon-times"></i>
			</a>
		</div>
	);
};

export default Edit;
