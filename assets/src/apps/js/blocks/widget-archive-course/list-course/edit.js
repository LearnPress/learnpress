import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<ul className="lp-list-courses">
					<li className="course">
						<div className="course-item">
							<div className="course-thumbnail"></div>
							<div className="course-content">
								<div className="title">{ 'Title' }</div>
								<div className="course-instructor-category">
									<div className="instructor">
										{ 'by ' }
										<strong>{ 'Instructor' }</strong>
									</div>
									<div className="category">
										{ 'in ' }
										<strong>{ 'Category' }</strong>
									</div>
								</div>
								<div className="course-description">
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
								</div>
								<div className="course-info">
									<span className="course-price"> { '$5' }</span>
									<div className="course-readmore"> { 'Read more' }</div>
								</div>
							</div>
						</div>
					</li>
					<li className="course">
						<div className="course-item">
							<div className="course-thumbnail"></div>
							<div className="course-content">
								<div className="title">{ 'Title' }</div>
								<div className="course-instructor-category">
									<div className="instructor">
										{ 'by ' }
										<strong>{ 'Instructor' }</strong>
									</div>
									<div className="category">
										{ 'in ' }
										<strong>{ 'Category' }</strong>
									</div>
								</div>
								<div className="course-description">
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
								</div>
								<div className="course-info">
									<span className="course-price"> { '$5' }</span>
									<div className="course-readmore"> { 'Read more' }</div>
								</div>
							</div>
						</div>
					</li>
					<li className="course">
						<div className="course-item">
							<div className="course-thumbnail"></div>
							<div className="course-content">
								<div className="title">{ 'Title' }</div>
								<div className="course-instructor-category">
									<div className="instructor">
										{ 'by ' }
										<strong>{ 'Instructor' }</strong>
									</div>
									<div className="category">
										{ 'in ' }
										<strong>{ 'Category' }</strong>
									</div>
								</div>
								<div className="course-description">
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
									<div className="line"></div>
								</div>
								<div className="course-info">
									<span className="course-price"> { '$5' }</span>
									<div className="course-readmore"> { 'Read more' }</div>
								</div>
							</div>
						</div>
					</li>
				</ul>
			</div>
		</>
	);
};
