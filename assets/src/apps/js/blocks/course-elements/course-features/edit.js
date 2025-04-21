import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-features extra-box">
					<h3 className="extra-box__title">{ __( 'Features', 'learnpress' ) }</h3>
					<ul>
						<li>
							{
								'Versum metuit commentatus neglegendi turpitudinis putandum pueris discipulum verso dicta hominibus ennius rhetoribus intellegunt'
							}
						</li>
						<li>
							{
								'Repellant tubulo nihilne vester philosophis eo negat exemplis que ullo egone cupido comparatio'
							}
						</li>
						<li>
							{
								'Saepe apparet rerum pollicetur obscurentur clamores instructus petendam accessio possit istarum descensio'
							}
						</li>
					</ul>
				</div>
			</div>
		</>
	);
};
