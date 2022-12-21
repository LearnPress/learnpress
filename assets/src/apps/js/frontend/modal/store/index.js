import { registerStore } from '@wordpress/data';

import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import applyMiddlewares from './middlewares';

const { controls: dataControls } = LP.dataControls;

const store = registerStore( 'learnpress/modal', {
	reducer,
	selectors,
	actions,
	controls: {
		...dataControls,
	},
} );

applyMiddlewares( store );

export default store;
