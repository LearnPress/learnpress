import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-courses">
					<ul>
						<li className="item-course"></li>
						<li className="item-course"></li>
						<li className="item-course"></li>
					</ul>
				</div>
			</div>
		</>
	);
};
