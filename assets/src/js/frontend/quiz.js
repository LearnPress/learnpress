import Quiz from './quiz/index';

export default Quiz;

export const init = function init(elem, settings) {
    wp.element.render(
        <Quiz settings={ settings }/>,
        jQuery(elem)[0]
    )
}