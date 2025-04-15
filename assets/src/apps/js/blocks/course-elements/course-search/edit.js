import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<form className="block-search-courses" >
					<input type="search" placeholder="Search courses..." name="c_search" value="" />
					<button type="submit" disabled name="lp-btn-search-courses"><i className="lp-icon-search"></i></button>
				</form>
			</div>
		</>
	);
};
