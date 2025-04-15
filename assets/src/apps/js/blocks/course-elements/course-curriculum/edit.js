import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="lp-course-curriculum">
					<h3 className="lp-course-curriculum__title">
						{ 'Curriculum' }
					</h3>
					<div className="course-curriculum-info">
						<ul className="course-curriculum-info__left">
							<li className="course-count-section">
								{ '1 Sections' }
							</li>
							<li className="course-count-lesson">
								{ '14 Lessons' }
							</li>
							<li className="course-duration">
								<span className="course-duration">
									{ '10 Weeks' }
								</span>
							</li>
						</ul>
						<div className="course-curriculum-info__right">
							<span className="course-toggle-all-sections">
								{ 'Expand all sections' }
							</span>
							<span className="course-toggle-all-sections lp-collapse lp-hidden">
								{ 'Collapse all sections' }
							</span>
						</div>
					</div>
					<div className="course-curriculum">
						<ul className="course-sections">
							<li className="course-section">
								<div className="course-section-header">
									<div className="section-toggle">
										<i className="lp-icon-angle-down"></i>
										<i className="lp-icon-angle-up"></i>
									</div>
									<div className="course-section-info">
										<div className="course-section__title">
											{ 'Section 1' }
										</div>
										<div className="course-section__description">
											{
												'Dic atqui arbitratu expectare galloni dico praeposatum cupiditates iucundissime supremum omnisque naturales orestem malitiae'
											}
										</div>
									</div>
									<div className="section-count-items">
										{ '14' }
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</>
	);
};
