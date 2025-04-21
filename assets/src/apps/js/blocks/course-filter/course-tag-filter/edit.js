import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<strong>{ __( 'Tags', 'learnpress' ) }</strong>
				<div className="lp-course-filter__field">
					<input type="checkbox"></input>
					<label>{ 'Tag 1' }</label>
					<span className="count">{ '25' }</span>
				</div>
				<div className="lp-course-filter__field">
					<input type="checkbox"></input>
					<label>{ 'Tag 2' }</label>
					<span className="count">{ '25' }</span>
				</div>
				<div className="lp-course-filter__field">
					<input type="checkbox"></input>
					<label>{ 'Tag 3' }</label>
					<span className="count">{ '25' }</span>
				</div>
			</div>
		</>
	);
};
