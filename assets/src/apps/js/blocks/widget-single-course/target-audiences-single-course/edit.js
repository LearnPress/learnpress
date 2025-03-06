import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<h3> { 'Target audiences' } </h3>
				<div className="line"></div>
				<div className="line"></div>
				<div className="line"></div>
			</div>
		</>
	);
};
