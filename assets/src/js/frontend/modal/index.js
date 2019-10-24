import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {_x} from '@wordpress/i18n';

import store from './store';

class Modal extends Component {
    confirm = (c) => (event) => {
        const {
            confirm
        } = this.props;

        confirm(c);
    };

    render() {
        const {
            isShow,
            message,
            children
        } = this.props;

        const styles = {
            display: isShow ? 'block' : 'none'
        }

        return <React.Fragment>
            <div>
                <div id="lp-modal-overlay" style={styles}></div>
                <div id="lp-modal-window" style={styles}>
                    <div id="lp-modal-content" dangerouslySetInnerHTML={ {__html: message} }></div>
                    <div id="lp-modal-buttons">
                    <button className="lp-button modal-button-ok" onClick={ this.confirm('yes') }><span>{ _x('OK', 'button confirm ok', 'learnpress') }</span></button>
                    <button className="lp-button modal-button-cancel"
                            onClick={ this.confirm('no') }><span>{ _x('Cancel', 'button confirm cancel', 'learnpress') }</span></button>
                    </div>
                </div>
            </div>
            {children}
        </React.Fragment>
    }
}

export default compose([
    withSelect((select) => {
        const {
            isOpen,
            getMessage
        } = select('learnpress/modal');

        return {
            isShow: isOpen(),
            message: getMessage()
        }
    }),
    withDispatch((dispatch) => {
        const {
            show,
            hide,
            confirm
        } = dispatch('learnpress/modal');

        return {
            show,
            hide,
            confirm
        }
    })
])(Modal);