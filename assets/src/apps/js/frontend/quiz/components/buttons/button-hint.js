import { Component } from '@wordpress/element';
import { withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

class ButtonHint extends Component {
	/**
	 * Callback to show hint of question
	 */
	showHint = () => {
		const { showHint, question } = this.props;

		showHint( question.id, ! question.showHint );
	};

	render() {
		const { question } = this.props;

		return (
			question.hint ? (
				<button className="btn-show-hint" onClick={ this.showHint }>
					<span>{ __( 'Hint', 'learnpress' ) }</span>
				</button>
			) : ''
		);
	}
}

export default compose(
	withDispatch( ( dispatch, { id } ) => {
		const {
			showHint,
		} = dispatch( 'learnpress/quiz' );

		return {
			showHint( id, show ) {
				showHint( id, show );
			},
		};
	} )
)( ButtonHint );
