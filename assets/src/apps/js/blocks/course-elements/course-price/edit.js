import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const coursePrice =
		lpCourseData?.price ||
		'<span class="course-price"><span class="course-item-price"> <span class="origin-price">$5.00</span><span class="price">$4.00</span> </span></span>';

	return (
		<>
			<div { ...blockProps }>
				<RawHTML>{ coursePrice }</RawHTML>
			</div>
		</>
	);
};

export default Edit;
