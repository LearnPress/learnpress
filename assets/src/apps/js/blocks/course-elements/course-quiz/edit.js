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
	const courseQuiz =
		lpCourseData?.quiz ||
		'<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-puzzle-piece"></i><span>Quiz:</span></div><span class="info-meta-right"><div class="course-count-quiz"><div class="course-count-item lp_quiz">9 Quizzes</div></div></span></div>';

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
					__html: courseQuiz,
				} }
			></div>
		</>
	);
};
