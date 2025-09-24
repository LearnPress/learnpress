import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-avatar">
					<img
						src="/wp-content/plugins/learnpress/assets/images/no-image.png"
						alt="Instructor avatar placeholder"
						height={ 300 }
						width={ 300 }
						style={ { aspectRatio: '1 / 1', objectFit: 'cover' } }
					></img>
				</div>
			</div>
		</>
	);
};
