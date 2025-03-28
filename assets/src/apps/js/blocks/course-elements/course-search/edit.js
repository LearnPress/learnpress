import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-search-wrapper">
					<input placeholder="Search courses..."></input>
					<button name="lp-btn-search-courses">
						<i className="lp-icon-search"></i>
					</button>
				</div>
			</div>
		</>
	);
};
