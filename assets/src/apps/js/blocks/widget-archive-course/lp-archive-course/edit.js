import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[ 'core/group', { className: 'lp-archive-courses' }, [
			[ 'learnpress/breadcrumb', {} ],
			[ 'core/group', { className: 'lp-content-area' }, [
				[ 'core/group', {}, [
					[ 'learnpress/breadcrumb', {} ],
				] ],
				[ 'learnpress/group-courses-archive-course', {}, [
					[ 'core/group', { className: 'lp-courses-bar', layout: { type: 'flex', flexWrap: 'nowrap' } }, [
						'core/heading ', { level: 1 },
					] ],
					[ 'learnpress/list-course-archive-course', {} ],
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
