import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<nav className="learn-press-pagination navigation pagination">
					<ul className="page-numbers">
						<li>{ '1' }</li>
						<li>{ '2' }</li>
						<li>{ '3' }</li>
						<li><i className="lp-icon-arrow-right"></i></li>
					</ul>
				</nav>
			</div>
		</>
	);
};
