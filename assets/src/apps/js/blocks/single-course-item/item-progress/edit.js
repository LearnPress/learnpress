import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div class="items-progress">
				<span class="number">
					<span class="items-completed">43</span> of 86 items
				</span>
				<div class="learn-press-progress">
					<div
						class="learn-press-progress__active"
						data-value="50%"
						style={ { left: '-50%' } }
					></div>
				</div>
			</div>
		</div>
	);
};

export default Edit;
