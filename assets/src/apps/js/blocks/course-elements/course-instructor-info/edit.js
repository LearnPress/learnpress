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
						<div className="instructor-avatar">
							<img src="https://placehold.co/160x160?text=Instructor" />
						</div>
						<div className="lp-section-instructor">
							<span className="instructor-display-name">
								{ __( 'Instructor Name', 'learnpress' ) }
							</span>
							<div className="lp-instructor-meta">
								<div className="instructor-item-meta">
									<span className="instructor-total-students">{ '2 Students' }</span>
								</div>
								<div className="instructor-item-meta">
									<span className="instructor-total-courses">{ '12 Courses' }</span>
								</div>
							</div>
							<div className="instructor-description">
								<p>
									{
										'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua'
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
