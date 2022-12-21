
import { __ } from '@wordpress/i18n';

import edit from './edit';

import metadata from './block.json';

const { name } = metadata;

const settings = {
	title: 'LearnPress Template',
	keywords: [ 'learnpress', 'template' ],
	description: __( 'Renders LearnPress PHP templates.', 'learnpress' ),
	icon: 'archive',
	edit,
	save: () => null,
};

export { name, settings, metadata };
