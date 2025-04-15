import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="comment-respond">
					<h3 className="comment-reply-title">{ 'Leave a Reply' }</h3>
					<p className="comment-form-comment">
						<label htmlFor="comment">
							{ 'Comment' } <span className="required">*</span>
						</label>
						<textarea name="comment"></textarea>
					</p>
					<div className="form-submit wp-block-button">
						<input
							name="submit"
							type="submit"
							className="submit wp-block-button__link wp-element-button"
							value="Post Comment"
						/>
					</div>
				</div>
			</div>
		</>
	);
};
