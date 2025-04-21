import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<strong>{ __( 'Search', 'learnpress' ) }</strong>
				<div className="search-box"></div>
			</div>
		</>
	);
};
