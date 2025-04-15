import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="lp-section-instructor">
					<h3 className="section-title">{ 'Instructor' }</h3>
					<div className="lp-instructor-info">
						<div className="instructor-avatar"></div>
						<div className="lp-section-instructor">
							<span className="instructor-display-name">
								{ 'Instructor Name' }
							</span>
							<div className="instructor-description">
								<p>
									{
										'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.'
									}
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
