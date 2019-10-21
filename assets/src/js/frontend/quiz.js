import Quiz from './quiz/index';

const {modal:{default: Modal}} = LP;

export default Quiz;

export const init = function init(elem, settings) {
    wp.element.render(
        <Modal><Quiz settings={ settings }/></Modal>,
        jQuery(elem)[0]
    )
}