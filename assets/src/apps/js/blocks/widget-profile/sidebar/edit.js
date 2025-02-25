import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="profile-sidebar">
					<ul className="tabs">
						<li>
							<div className="line"></div>
						</li>
						<li>
							<div className="line"></div>
						</li>
						<li>
							<div className="line"></div>
						</li>
						<li>
							<div className="line"></div>
						</li>
					</ul>
				</div>
			</div>
		</>
	);
};
