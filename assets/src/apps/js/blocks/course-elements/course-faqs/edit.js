import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-faqs course-tab-panel-faqs">
					<h3 className="course-faqs__title">{ __( 'FAQs', 'learnpress' ) }</h3>
					<div className="course-faqs-box">
						<label className="course-faqs-box__title">
							{
								'Fortepossumus poterat varietatem nullus tum signa dissentiens abducas gaudio memini pervertere impudens?'
							}
						</label>
					</div>
					<div className="course-faqs-box">
						<label className="course-faqs-box__title">
							{
								'Homerus defecerit iure naturales praeponunt futuri avaritiamne celebrari parva vincla aetatulis extrema?'
							}
						</label>
					</div>
				</div>
			</div>
		</>
	);
};
