import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<h3> { 'Instructor' } </h3>
				<div className="wrapper">
					<div className="avatar"></div>
					<div className="info">
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
					</div>
				</div>
			</div>
		</>
	);
};
