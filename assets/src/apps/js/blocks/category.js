import { getCategories, setCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

setCategories( [
	...getCategories().filter( ( { slug } ) => slug !== 'learnpress' ),
	{
		slug: 'learnpress',
		title: __( 'LearnPress', 'learnpress' ),
	},
] );
