import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-social">
					<i className="lp-user-ico lp-icon-facebook"></i>
					<i className="lp-user-ico lp-icon-twitter"></i>
					<i className="lp-user-ico lp-icon-youtube-play"></i>
					<i className="lp-user-ico lp-icon-linkedin"></i>
				</div>
			</div>
		</>
	);
};
