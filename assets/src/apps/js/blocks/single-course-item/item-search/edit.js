import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div class="search-course">
				<input type="text" name="s" autocomplete="off" placeholder="Search for course content" />
				<button name="submit" aria-label="Search for course content">
					<i class="lp-icon-search"></i>
				</button>
			</div>
		</div>
	);
};

export default Edit;
