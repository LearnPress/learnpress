import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const items = Array.from( { length: 20 }, ( _, i ) => `Item ${ i + 1 }` ); // Giả lập danh sách dữ liệu
	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<TextControl
						label="Course Per Page"
						type="number"
						min="1"
						max="8"
						onChange={ ( value ) => {
							props.setAttributes( {
								perPage: value ? parseInt( value, 10 ) : 4,
							} );

							updatePerPageChildBlocks(
								value ? parseInt( value, 10 ) : 4,
							);
						} }
						value={ props.attributes.perPage ?? 4 }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="related-course">
					<h3>{ 'Related Course' }</h3>
					<div className="list-course">
						{ items
							.slice( 0, props.attributes?.perPage )
							.map( ( item, index ) => (
								<div className="course-item" key={ index }></div>
							) ) }
					</div>
				</div>
			</div>
		</>
	);
};
