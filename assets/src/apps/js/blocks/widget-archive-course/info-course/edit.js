import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-info">
					<span className="course-price">
						<strong>{ '$5' }</strong>
					</span>
					<div className="course-readmore"> { 'Read more' }</div>
				</div>
			</div>
		</>
	);
};
