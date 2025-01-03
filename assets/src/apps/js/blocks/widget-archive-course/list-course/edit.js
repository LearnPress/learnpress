import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<SelectControl
						label="Layout"
						value={ props.attributes.layout ?? 'list' }
						options={ [
							{ label: 'List', value: 'list' },
							{ label: 'Grid', value: 'grid' },
						] }
						onChange={ ( value ) => props.setAttributes( { layout: value ? value : 'list' } ) }
					/>

					<ToggleControl
						label="Custom Layout"
						help={ 'When enabled, loading AJAX Courses will be disabled.' }
						checked={ props.attributes.custom ? true : false }
						onChange={ ( value ) => props.setAttributes( { custom: value ? value : '' } ) }
					/>

					{ ! props.attributes.custom ? <ToggleControl
						label="Load Ajax"
						help={ 'Do not apply AJAX when reloading the Course Archive page.' }
						checked={ props.attributes.load ? true : false }
						onChange={ ( value ) => props.setAttributes( { load: value ? value : '' } ) }
					/> : '' }

					{ ! props.attributes.custom ? <SelectControl
						label="Pagination"
						value={ props.attributes.pagination ?? 'number' }
						options={ [
							{ label: 'Number', value: 'number' },
							{ label: 'Load More', value: 'load-more' },
							{ label: 'Infinite Scroll', value: 'infinite' },
						] }
						onChange={ ( value ) => props.setAttributes( { pagination: value ? value : 'number' } ) }
					/> : '' }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className={ 'list-course ' + props.attributes.layout }>
					<InnerBlocks />
				</div>
			</div>
		</>
	);
};
