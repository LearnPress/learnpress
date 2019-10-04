import {Component} from '@wordpress/element';
import {default as ButtonHint} from '../buttons/button-hint';
import {default as ButtonCheck} from '../buttons/button-check';
import {MaybeShowButton} from '../buttons';

const Buttons = function Buttons(props) {
    const {
        question
    } = props;

    return <React.Fragment>
        <MaybeShowButton type="hint" Button={ ButtonHint } question={question}/>
        <MaybeShowButton type="check" Button={ ButtonCheck } question={question}/>
    </React.Fragment>
};

export default Buttons;