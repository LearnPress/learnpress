import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const template = [
		[ 'learnpress/media-course-archive-course', {} ],
		[ 'learnpress/title-course-archive-course', {} ],
		[ 'learnpress/instructor-category-course-archive-course', {} ],
		[ 'learnpress/meta-course-archive-course', {} ],
		[ 'learnpress/info-course-archive-course', {} ],
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
