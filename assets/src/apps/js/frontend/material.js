import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

export default function lpMaterialsLoad ( postID = '' ) {
	console.log('loaded');
	const Sekeleton = () => {
		const elementMaterial = document.querySelector( '.course-material-table' );

		if ( ! elementMaterial ) {
			return;
		}

		getResponse( elementMaterial );
	};
	const getResponse = async ( ele ) => {
		const itemID = postID || lpGlobalSettings.post_id || '';
		try {
			const page = 1;
			const response = await apiFetch( {
				path: addQueryArgs( `lp/v1/material/item-materials/${itemID}`, {
					page: page,
				} ),
				method: 'GET',
			} );
			console.log( 'res' );
			console.log( response );
			const { data, status, message } = response;
			// let section_ids = data.section_ids;
			console.log( 'stt' );
			console.log( status );
			console.log( 'data' );
			console.log( data );
			console.log( 'message' );
			console.log( message )
			if ( status !== 200 ) {
				throw new Error( message || 'Error' );
			}
		} catch ( error ) {
			console.log( error.message );
		}
	};
	Sekeleton();
}