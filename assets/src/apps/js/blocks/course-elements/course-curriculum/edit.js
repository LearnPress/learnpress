import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="lp-course-curriculum">
					<h3 className="lp-course-curriculum__title">{ __( 'Curriculum', 'learnpress' ) }</h3>
					<div className="course-curriculum-info">
						<ul className="course-curriculum-info__left">
							<li className="course-count-section">{ '2 Sections' }</li>
							<li className="course-count-lesson">{ '3 Lessons' }</li>
							<li className="course-duration">
								<span className="course-duration">{ '3 Weeks' }</span>
							</li>
						</ul>
						<div className="course-curriculum-info__right">
							<span className="course-toggle-all-sections">{ 'Expand all sections' }</span>
						</div>
					</div>
					<div className="course-curriculum">
						<ul className="course-sections">
							<li className="course-section">
								<div className="course-section-header">
									<div className="section-toggle">
										<i className="lp-icon-angle-up"></i>
									</div>
									<div className="course-section-info">
										<div className="course-section__title">{ 'Section 1' }</div>
									</div>
									<div className="section-count-items">{ '3' }</div>
								</div>
								<ul className="course-section__items">
									<li className="course-item">
										<a className="course-item__link">
											<div className="course-item__info">
												<span className="course-item-ico lp_lesson"></span>
											</div>
											<div className="course-item__content">
												<div className="course-item__left">
													<div className="course-item-title">{ 'What is LearnPress?' }</div>
												</div>
												<div className="course-item__right">
													<span className="duration">{ '20 Minutes' }</span>
												</div>
											</div>
											<div className="course-item__status">
												<span className="course-item-ico in-progress"></span>
											</div>
										</a>
									</li>
									<li className="course-item ">
										<a className="course-item__link">
											<div className="course-item__info">
												<span className="course-item-ico lp_lesson"></span>
											</div>
											<div className="course-item__content">
												<div className="course-item__left">
													<div className="course-item-title">{ 'How to use LearnPress?' }</div>
												</div>
												<div className="course-item__right">
													<span className="duration">{ '60 Minutes' }</span>
												</div>
											</div>
											<div className="course-item__status">
												<span className="course-item-ico preview"></span>
											</div>
										</a>
									</li>
									<li className="course-item ">
										<a className="course-item__link">
											<div className="course-item__info">
												<span className="course-item-ico lp_quiz"></span>
											</div>
											<div className="course-item__content">
												<div className="course-item__left">
													<div className="course-item-title">{ 'Demo the Quiz of LearnPress' }</div>
												</div>
												<div className="course-item__right">
													<span className="duration">{ '10 Minutes' }</span>
													<span className="question-count">{ '4 Questions' }</span>
												</div>
											</div>
											<div className="course-item__status">
												<span className="course-item-ico locked"></span>
											</div>
										</a>
									</li>
								</ul>
							</li>
							<li className="course-section lp-collapse">
								<div className="course-section-header">
									<div className="section-toggle">
										<i className="lp-icon-angle-down"></i>
									</div>
									<div className="course-section-info">
										<div className="course-section__title">{ 'Section 2' }</div>
										<div className="course-section__description">
											{
												'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua'
											}
										</div>
									</div>
									<div className="section-count-items">{ '10' }</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</>
	);
};

export default Edit;
