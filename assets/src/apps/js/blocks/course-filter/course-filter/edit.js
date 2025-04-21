import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<TextControl
						label="Title"
						onChange={ ( value ) => {
							props.setAttributes( {
								title: value ?? '',
							} );
						} }
						value={ props.attributes.title ?? 'Course Filter' }
					/>

					<TextControl
						label="Level of category to display on Frontend"
						type="number"
						min="1"
						onChange={ ( value ) => {
							props.setAttributes( {
								numberLevelCategory: value
									? parseInt( value, 10 )
									: 1,
							} );
						} }
						value={ props.attributes.numberLevelCategory ?? 1 }
					/>

					<ToggleControl
						label="Load widget via REST"
						checked={ props.attributes.showInRest ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showInRest: value ? true : false,
							} );
						} }
					/>

					<ToggleControl
						label="Hide field has count is zero"
						checked={ props.attributes.hideCountZero ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								hideCountZero: value ? true : false,
							} );
						} }
					/>

					<ToggleControl
						label="Enable Keyword Search Suggestion"
						checked={
							props.attributes.searchSuggestion ? true : false
						}
						onChange={ ( value ) => {
							props.setAttributes( {
								searchSuggestion: value ? true : false,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="filter">
					<h3>{ props.attributes.title }</h3>
				</div>
				<div>
					<InnerBlocks
						allowedBlocks={ [
							'learnpress/course-search-filter',
							'learnpress/course-author-filter',
							'learnpress/course-level-filter',
							'learnpress/course-price-filter',
							'learnpress/course-categories-filter',
							'learnpress/course-tag-filter',
							'learnpress/course-review-filter',
							'learnpress/button-submit-filter',
							'learnpress/button-reset-filter',
						] }
					/>
				</div>
			</div>
		</>
	);
};
