import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
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
									: 0,
							} );
						} }
						value={ props.attributes.numberLevelCategory ?? 0 }
					/>

					<TextControl
						label="Class of list courses want to filter"
						onChange={ ( value ) => {
							props.setAttributes( {
								classListCoursesTarget: value ?? '',
							} );
						} }
						value={
							props.attributes.classListCoursesTarget ??
							'.lp-list-courses-default'
						}
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
						checked={
							props.attributes.hideCountZero ? true : false
						}
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
					<div className="line"></div>
					<div className="line"></div>
					<div className="line"></div>
					<div className="line"></div>
				</div>
			</div>
		</>
	);
};
