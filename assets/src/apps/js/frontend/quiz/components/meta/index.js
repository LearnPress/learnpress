/**
 * Quiz Meta.
 * Edit: Use React Hook.
 *
 * @author Nhamdv - ThimPress
 */
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { default as formatDuration } from '../duration';
const { Hook } = LP;

const Meta = () => {
	const getData = ( attr ) => {
		return select( 'learnpress/quiz' ).getData( attr );
	};

	const metaFields = Hook.applyFilters( 'quiz-meta-fields', {
		duration: {
			title: __( 'Duration:', 'learnpress' ),
			name: 'duration',
			content: formatDuration( getData( 'duration' ) ) || '--',
		},
		passingGrade: {
			title: __( 'Passing grade:', 'learnpress' ),
			name: 'passing-grade',
			content: getData( 'passingGrade' ) || '--',
		},
		questionsCount: {
			title: __( 'Questions:', 'learnpress' ),
			name: 'questions-count',
			content: getData( 'questionIds' ) ? getData( 'questionIds' ).length : 0,
		},
	} );

	return (
		metaFields && (
			<>
				<ul className="quiz-intro">
					{ Object.values( metaFields ).map( ( field, i ) => {
						const id = field.name || i;

						return (
							<li key={ `quiz-intro-field-${ i }` } className={ `quiz-intro-item quiz-intro-item--${ id }` }>
								<div className="quiz-intro-item__title" dangerouslySetInnerHTML={ { __html: field.title } } />
								<span className="quiz-intro-item__content" dangerouslySetInnerHTML={ { __html: field.content } } />
							</li>
						);
					} ) }
				</ul>
			</>
		)
	);
};

export default Meta;
