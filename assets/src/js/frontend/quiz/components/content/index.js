/**
 * Quizz Content.
 * Edit: Use React hook.
 *
 * @author nhamdv - ThimPress
 */
import { select } from '@wordpress/data';

const Content = () => {
	const content = select( 'learnpress/quiz' ).getData( 'content' );

	return (
		<div className="quiz-content" dangerouslySetInnerHTML={ { __html: content } } />
	);
};

export default Content;
