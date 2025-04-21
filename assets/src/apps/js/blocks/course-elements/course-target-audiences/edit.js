import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-target extra-box">
					<h3 className="extra-box__title">{ __( 'Target audiences', 'learnpress' ) }</h3>
					<ul>
						<li>
							{
								'Mavis tolluntur redargueret spe fortior ames amicitia petitur cariorem similiora gaudeant'
							}
						</li>
						<li>
							{
								'Fuisse confirmandus materiam reges versuta improbos inconstantissime rationis antiocho stultorum sequetur dicimus emolumento video hanc'
							}
						</li>
						<li>{ 'Perfecit exquisita urbe asoti discere decimum existeret lyco morbo hi' }</li>
					</ul>
				</div>
			</div>
		</>
	);
};
