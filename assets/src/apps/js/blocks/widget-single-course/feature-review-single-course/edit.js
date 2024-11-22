import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<TextControl
						label="Course ID"
						value={ props.attributes.courseId }
						help="The default value is the current course id"
						type="number"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<strong>
					{ 'Featured Review' }
					<div className="featured-review__stars">
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
					</div>
					<div className="featured-review__content">
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
					</div>
				</strong>
			</div>
		</>
	);
};
