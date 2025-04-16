import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const [ classLabel, setClassLabel ] = useState(
		attributes.showLabel ? '' : 'hidden-label',
	);
	const [ classIcon, setClassIcon ] = useState(
		attributes.showIcon ? '' : 'hidden-icon',
	);
	const { lpCourseData } = context;
	const courseLesson =
		lpCourseData?.lesson ||
		'<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-file-o"></i><span>Lesson:</span></div><span class="info-meta-right"><div class="course-count-lesson"><div class="course-count-item lp_lesson">5 Lessons</div></div></span></div>';

	useEffect( () => {
		setClassLabel( attributes.showLabel ? '' : 'hidden-label' );
		setClassIcon( attributes.showIcon ? '' : 'hidden-icon' );
	}, [ attributes.showLabel, attributes.showIcon ] );

	const blockProps = useBlockProps( {
		className: `${ classLabel } ${ classIcon }`.trim(),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show Label"
						checked={ props.attributes.showLabel ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showLabel: value ? true : false,
							} );
						} }
					/>
					<ToggleControl
						label="Show Icon"
						checked={ props.attributes.showIcon ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showIcon: value ? true : false,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>

			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseLesson,
				} }
			></div>
		</>
	);
};
