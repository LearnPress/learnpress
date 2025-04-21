import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="featured-review__title">{ __( 'Featured Review', 'learnpress' ) }</div>
				<div className="featured-review__stars">
					<i className="lp-icon-star"></i>
					<i className="lp-icon-star"></i>
					<i className="lp-icon-star"></i>
					<i className="lp-icon-star"></i>
					<i className="lp-icon-star"></i>
				</div>
				<div className="featured-review__content">
					<p>
						{
							'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua'
						}
					</p>
				</div>
			</div>
		</>
	);
};
