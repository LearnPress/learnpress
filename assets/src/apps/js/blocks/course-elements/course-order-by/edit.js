import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="courses-order-by-wrapper">
					<span>{ 'Order By' }</span>
				</div>
			</div>
		</>
	);
};
