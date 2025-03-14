/**
 * Register block single course.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, BlockControls, AlignmentToolbar, useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
// eslint-disable-next-line import/default
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

registerBlockType( 'learnpress/course-title', {
	...metadata,
	edit( props ) {
		// eslint-disable-next-line react-hooks/rules-of-hooks
		const blockProps = useBlockProps();
		const { attributes, setAttributes, context } = props;
		const { align } = attributes;
		// eslint-disable-next-line react-hooks/rules-of-hooks

		let course;
		if ( context[ 'learnpress/courseData' ] ) {
			course = context[ 'learnpress/courseData' ];
		}

		// Test if the course is loaded

		console.log( 'context', context );

		return (
			<div { ...blockProps }>
				<BlockControls>
					<AlignmentToolbar
						value={ align }
						onChange={ ( newAlign ) => {
							setAttributes( { align: newAlign } );
						} }
					/>
				</BlockControls>

				{ ! course && 'Course Title' }
				{ course && 'No Course' }
				{ course && (
					<div style={ { textAlign: align } }>{ course.title.rendered }</div>
				)
				}

				<ServerSideRender
					displayAsDropdown={ true }
					LoadingResponsePlaceholder={ __( 'test', 'learnpress' ) }
					block="learnpress/course-title"
				/>
			</div>
		)
		;
	},
	save() {
		return null;
	},
},
);
