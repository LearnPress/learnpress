import {Component} from '@wordpress/element';
import {default as ButtonHint} from '../buttons/button-hint';
import {default as ButtonCheck} from '../buttons/button-check';
import {MaybeShowButton} from '../buttons';

const Buttons = function Buttons(props) {
    const {
        question
    } = props;

    const buttons = {
        'instant-check': () => {
            return <MaybeShowButton type="check" Button={ ButtonCheck } question={question}/>
        },
        'hint': () => {
            return <MaybeShowButton type="hint" Button={ ButtonHint } question={question}/>
        }
    }

    return <React.Fragment>
        {
            LP.config.questionFooterButtons().map((name)=>{
                return <React.Fragment key={ `button-${name}` }>{ buttons[name] && buttons[name]() }</React.Fragment>
            })
        }
    </React.Fragment>
};

export default Buttons;