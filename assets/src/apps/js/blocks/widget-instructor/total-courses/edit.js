import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="wrapper-instructor-total-courses">
					<span className="lp-ico lp-icon-courses"></span>
					<span className="instructor-total-courses">
						{ '99 Courses' }
					</span>
				</div>
			</div>
		</>
	);
};
