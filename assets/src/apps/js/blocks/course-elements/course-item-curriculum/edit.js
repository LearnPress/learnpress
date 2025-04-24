import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<div {...blockProps}>
			<Placeholder
				label={ __( 'Course item curriculum (Legacy)', 'learnpress' ) }
			>
				<div>
					{
						__(
							'Displays the course curriculum, including lessons, quizzes, and other learning items, organized by sections or topics!',
							'learnpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};
