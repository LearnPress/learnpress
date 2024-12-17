import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[ 'learnpress/breadcrumb', {} ],
		[ 'learnpress/item-curriculum-course', {} ],
		[ 'learnpress/course-summary', {}, [
			[ 'learnpress/course-detail-info', {}, [
				[ 'learnpress/course-meta-primary', {} ],
				[ 'learnpress/title-single-course', {} ],
				[ 'learnpress/course-meta-secondary', {}, [
					[ 'learnpress/duration-single-course', {} ],
					[ 'learnpress/level-single-course', {} ],
					[ 'learnpress/lesson-single-course', {} ],
					[ 'learnpress/quiz-single-course', {} ],
					[ 'learnpress/student-single-course', {} ],
				] ],
			] ],
			[ 'learnpress/lp-content-area', {}, [
				[ 'learnpress/content-left', {}, [
					[ 'learnpress/tabs-single-course', {} ],
					[ 'learnpress/requirements-single-course', {} ],
					[ 'learnpress/features-single-course', {} ],
					[ 'learnpress/target-audiences-single-course', {} ],
					[ 'learnpress/comment', {} ],
				] ],
				[ 'learnpress/course-summary-sidebar', {}, [
					[ 'learnpress/image-single-course', {} ],
					[ 'learnpress/price-single-course', {} ],
					[ 'learnpress/btn-purchase-single-course', {} ],
					[ 'learnpress/time-single-course', {} ],
					[ 'learnpress/progress-single-course', {} ],
					[ 'learnpress/feature-review-single-course', {} ],
				] ],
			] ],
		] ],
	];
	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks template={ TEMPLATE } />
			</div>
		</>
	);
};
