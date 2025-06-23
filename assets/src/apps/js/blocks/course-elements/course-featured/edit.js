import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;

	const label = __( 'Featured', 'learnpress' );

	return (
		<>
			<div { ...blockProps } style={ { ...blockProps.style, display: 'inline-block' } }>
				{ label }
			</div>
		</>
	);
};

export default Edit;
