import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="sub-content">
					<div className="statistic">
						<div className="box"></div>
						<div className="box"></div>
						<div className="box"></div>
						<div className="box"></div>
						<div className="box"></div>
						<div className="box"></div>
					</div>
					<div className="tabs"></div>
					<div className="progress-bar">
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
					</div>
				</div>
			</div>
		</>
	);
};
