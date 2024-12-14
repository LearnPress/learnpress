import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
};
