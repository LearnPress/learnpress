import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-requirements extra-box">
					<h3 className="extra-box__title">{ __( 'Requirements', 'learnpress' ) }</h3>
					<ul>
						<li>
							{
								'Afflueret videsne commoventur debilitas etsi adridens habitus placuit hoc conatum deinde fruentem dirigentes longam sapientem'
							}
						</li>
						<li>
							{
								'Lucullo summas debeatis varietate indoctum vitae cavere cornibus avaritias sequamini persequi assignatum polemoni'
							}
						</li>
						<li>
							{
								'Laboro excelsiores meo utebare causam arripuit levem motu seditione egregio malitias istam'
							}
						</li>
					</ul>
				</div>
			</div>
		</>
	);
};
