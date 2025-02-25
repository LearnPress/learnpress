import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="wrapper-instructor-total-students">
					<span className="lp-ico lp-icon-students"></span>
					<span className="instructor-total-students">
						{ '99 Students' }
					</span>
				</div>
			</div>
		</>
	);
};
