import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<input type="checkbox" id="sidebar-toggle" title="Show/Hide curriculum"></input>
		</div>
	);
};

export default Edit;
