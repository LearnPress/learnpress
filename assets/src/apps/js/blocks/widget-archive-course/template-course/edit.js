import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const template = [
		[ 'learnpress/media-course-archive-course', {} ],
		[ 'learnpress/title-course-archive-course', {} ],
		[
			'core/group',
			{
				metadata: {
					name: 'Category Instructor',
				},
				className: 'course-instructor-category',
				layout: {
					type: 'constrained',
				},
			},
			[
				[ 'learnpress/instructor-course-archive-course', {} ],
				[ 'learnpress/category-course-archive-course', {} ],
			],
		],
		[
			'core/group',
			{
				metadata: {
					name: 'Meta Course',
				},
				className: 'course-wrap-meta',
				layout: {
					type: 'constrained',
				},
			},
			[
				[ 'learnpress/level-course-archive-course', {} ],
				[ 'learnpress/duration-course-archive-course', {} ],
				[ 'learnpress/lesson-course-archive-course', {} ],
				[ 'learnpress/quiz-course-archive-course', {} ],
				[ 'learnpress/student-course-archive-course', {} ],
			],
		],
		[ 'learnpress/description-course-archive-course', {} ],
		[
			'core/group',
			{
				metadata: {
					name: 'Course Info',
				},
				className: 'course-info',
				layout: {
					type: 'constrained',
				},
			},
			[
				[ 'learnpress/price-course-archive-course', {} ],
				[ 'learnpress/button-course-archive-course', {} ],
			],
		],
	];

	const renderInnerBlocks = () => {
		const blocks = [];
		for ( let i = 0; i < 3; i++ ) {
			blocks.push(
				<div className="course-item" key={ i }>
					<InnerBlocks template={ template } templateLock={ false } />
				</div>,
			);
		}
		return blocks;
	};

	return (
		<>
			<div { ...blockProps }>
				<div className="template-course">{ renderInnerBlocks() }</div>

				<div className="gutenberg-pagination">
					<div className="pagination-number">
						<nav className="learn-press-pagination navigation pagination">
							<ul className="page-numbers">
								<li>
									<span className="prev page-numbers">
										<i className="lp-icon-arrow-left"></i>
									</span>
								</li>
								<li>
									<span
										aria-current="page"
										className="page-numbers current"
									>
										{ '1' }
									</span>
								</li>
								<li>
									<span className="page-numbers">{ '2' }</span>
								</li>
								<li>
									<span className="page-numbers">{ '3' }</span>
								</li>
								<li>
									<i className="lp-icon-arrow-right"></i>
								</li>
							</ul>
						</nav>
					</div>

					<div className="pagination-load-more">
						<button className="courses-btn-load-more learn-press-pagination lp-button courses-btn-load-more-no-css">
							{ 'Load more' }
						</button>
					</div>
				</div>
			</div>
		</>
	);
};
