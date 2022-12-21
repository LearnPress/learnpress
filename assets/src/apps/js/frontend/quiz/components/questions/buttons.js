import { default as ButtonHint } from '../buttons/button-hint';
import { default as ButtonCheck } from '../buttons/button-check';
import { MaybeShowButton } from '../buttons';
import { Fragment } from '@wordpress/element';

const Buttons = function Buttons( props ) {
	const {
		question,
	} = props;

	const buttons = {
		'instant-check': () => {
			return (
				<MaybeShowButton
					type="check"
					Button={ ButtonCheck }
					question={ question }
				/>
			);
		},
		hint: () => {
			return (
				<MaybeShowButton
					type="hint"
					Button={ ButtonHint }
					question={ question }
				/>
			);
		},
	};

	return (
		<>
			{ LP.config.questionFooterButtons().map( ( name ) => {
				return (
					<Fragment key={ `button-${ name }` }>
						{ buttons[ name ] && buttons[ name ]() }
					</Fragment>
				);
			} ) }
		</>
	);
};

export default Buttons;
