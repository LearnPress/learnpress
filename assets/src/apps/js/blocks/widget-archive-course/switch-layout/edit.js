import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="courses-switch-layout-wrapper">
					<div className="switch-btn grid"></div>
					<div className="switch-btn list"></div>
				</div>
			</div>
		</>
	);
};
