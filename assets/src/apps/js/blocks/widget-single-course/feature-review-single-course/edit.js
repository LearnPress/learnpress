import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<strong>
					{ 'Featured Review' }
					<div className="featured-review__stars">
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
						<i className="lp-icon-star"></i>
					</div>
					<div className="featured-review__content">
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
					</div>
				</strong>
			</div>
		</>
	);
};
