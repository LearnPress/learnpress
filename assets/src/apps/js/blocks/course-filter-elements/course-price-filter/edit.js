import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<strong>{ __( 'Price', 'learnpress' ) }</strong>
				<div className="lp-course-filter__field">
					<input type="checkbox"></input>
					<label>{ 'Free' }</label>
					<span className="count">{ '25' }</span>
				</div>
				<div className="lp-course-filter__field">
					<input type="checkbox"></input>
					<label>{ 'Paid' }</label>
					<span className="count">{ '25' }</span>
				</div>
			</div>
		</>
	);
};
