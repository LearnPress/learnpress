import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( 'Show title', 'learnpress' ) }
						checked={ props.attributes.title ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								title: value ? true : false,
							} );
						} }
					/>

					<TextControl
						label={ __( 'Level of category to display on Frontend', 'learnpress' ) }
						type="number"
						min="1"
						onChange={ ( value ) => {
							props.setAttributes( {
								numberLevelCategory: value ? parseInt( value, 10 ) : 1,
							} );
						} }
						value={ props.attributes.numberLevelCategory ?? 1 }
					/>

					<ToggleControl
						label={ __( 'Load widget via REST', 'learnpress' ) }
						checked={ props.attributes.showInRest ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showInRest: value ? true : false,
							} );
						} }
					/>

					<ToggleControl
						label={ __( 'Hide field has count is zero', 'learnpress' ) }
						checked={ props.attributes.hideCountZero ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								hideCountZero: value ? true : false,
							} );
						} }
					/>

					<ToggleControl
						label={ __( 'Enable Keyword Search Suggestion', 'learnpress' ) }
						checked={ props.attributes.searchSuggestion ? true : false }
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
					<h3>{ props.attributes.title ? __( 'Course Filter', 'learnpress' ) : '' }</h3>
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
