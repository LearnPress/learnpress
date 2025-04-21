import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="is-layout-flex c-gap-4">
					<i className="lp-icon-share-alt"></i>
					<span>{ __( 'Share', 'learnpress' ) }</span>
				</div>
			</div>
		</>
	);
};
