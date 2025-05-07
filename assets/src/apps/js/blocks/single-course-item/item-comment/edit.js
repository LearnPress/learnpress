import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div className="learn-press-comments">
				<div className="comment-respond">
					<h3 className="comment-reply-title">Leave a Reply</h3>
					<p class="comment-form-comment">
						<label for="comment">
							Comment <span class="required">*</span>
						</label>{ ' ' }
						<textarea name="comment" cols="45" rows="8" maxlength="65525"></textarea>
					</p>
					<p class="form-submit wp-block-button">
						<input
							name="submit"
							type="submit"
							class="submit wp-element-button"
							value="Post Comment"
						/>
					</p>
				</div>
			</div>
		</div>
	);
};

export default Edit;
