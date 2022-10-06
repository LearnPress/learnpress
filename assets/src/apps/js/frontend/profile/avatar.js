import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import Cropper from 'react-easy-crop';

export default function Avatar() {
	const [ file, setFile ] = useState();
	const [ width, setWidth ] = useState( 0 );
	const [ height, setHeight ] = useState( 0 );
	const [ crop, setCrop ] = useState( { x: 0, y: 0 } );
	const [ rotation, setRotation ] = useState( 0 );
	const [ croppedAreaPixels, setCroppedAreaPixels ] = useState( null );
	const [ naturalWidth, setNaturalWidth ] = useState( 0 );
	const [ naturalHeight, setNaturalHeight ] = useState( 0 );
	const [ loading, setLoading ] = useState( false );
	const [ skeleton, setSkeleton ] = useState( true );
	const [ uploadError, setUploadError ] = useState( '' );
	const [ notice, setNotice ] = useState( {
		type: '',
		message: '',
	} );

	const fileInput = useRef();

	useEffect( () => {
		async function getAvatar() {
			setSkeleton( true );
			try {
				const response = await apiFetch( {
					method: 'GET',
					path: 'lp/v1/profile/get-avatar',
				} );

				setWidth( response?.data?.width ? parseInt( response.data.width ) : 0 );
				setHeight( response?.data?.height ? parseInt( response.data.height ) : 0 );
				setFile( response?.data?.url ? response.data.url : '' );
			} catch ( error ) {
				setNotice( {
					type: 'error',
					message: error.message,
				} );
			}
			setSkeleton( false );
		}

		getAvatar();
	}, [] );

	function readFile( file ) {
		return new Promise( ( resolve ) => {
			const reader = new FileReader();
			reader.addEventListener( 'load', () => resolve( reader.result ), false );
			reader.readAsDataURL( file );
		} );
	}

	const onCropComplete = useCallback( ( croppedArea, croppedAreaPixels ) => {
		setCroppedAreaPixels( croppedAreaPixels );
	}, [] );

	const base64Resize = ( base64, width, height ) => {
		return new Promise( ( resolve, reject ) => {
			const canvas = document.createElement( 'canvas' );
			const img = document.createElement( 'img' );
			img.src = base64;
			img.setAttribute( 'crossOrigin', 'anonymous' );
			img.onload = () => {
				if ( img.naturalWidth > width || img.naturalHeight > height ) {
					canvas.width = width;
					canvas.height = height;
					const ctx = canvas.getContext( '2d' );
					ctx.drawImage( img, 0, 0, width, height );
					resolve( canvas.toDataURL( 'image/jpeg' ) );
				}

				resolve( base64 );
			};
			img.onerror = ( err ) => reject( err );
		} );
	};

	const updateAvatar = useCallback( async () => {
		setLoading( { save: true } );

		try {
			const croppedImage = await getCroppedImg(
				file,
				croppedAreaPixels,
				rotation,
			);

			const imageResize = await base64Resize( croppedImage, width, height );

			const response = await apiFetch( {
				path: 'lp/v1/profile/upload-avatar',
				method: 'POST',
				data: { file: imageResize || '' },
			} );

			const { data, status, message } = await response;

			if ( status === 'success' ) {
				window.location.reload();
			}

			setNotice( {
				type: status,
				message,
			} );
		} catch ( e ) {
			setNotice( {
				type: 'error',
				message: e.message || '',
			} );
		}

		setLoading( { save: false } );
	}, [ croppedAreaPixels, rotation ] );

	const setFileInput = async ( fileUpload ) => {
		const file = await readFile( fileUpload );

		const img = new Image();
		img.src = await file;
		img.onload = await function() {
			setNaturalWidth( img.naturalWidth );
			setNaturalHeight( img.naturalHeight );

			let error = '';
			if ( parseInt( fileUpload.size ) > 2097152 ) {
				error = __( 'The file size is too large. You need to upload a file < 2MB.', 'learnpress' );
			} else if ( img.naturalWidth < width || img.naturalHeight < height ) {
				error = sprintf( __( 'The image size must be greater than or equal to %1$sx%2$spx', 'learnpress' ), width, height );
			}

			if ( error ) {
				setUploadError( error );
			} else {
				setUploadError( '' );
				setFile( file );
			}
		};
	};

	async function removeAvatar() {
		if ( confirm( __( 'Are you sure you want to remove your avatar?', 'learnpress' ) ) ) {
			setLoading( { remove: true } );
			try {
				const response = await apiFetch( {
					path: 'lp/v1/profile/remove-avatar',
					method: 'POST',
				} );

				const { data, status, message } = await response;

				setNotice( {
					type: status,
					message,
				} );

				setFile( '' );
			} catch ( e ) {
				setNotice( {
					type: 'error',
					message: e.message || '',
				} );
			}
			setLoading( { remove: false } );
		}
	}

	return (
		<div className="learnpress_avatar">
			{ ! skeleton ? (
				<>
					{ file && ! uploadError && (
						<>
							{ naturalHeight && naturalWidth ? (
								<div className="learnpress_avatar__cropper">
									<div style={ { position: 'relative', width: naturalWidth, height: naturalHeight, zIndex: 9999, maxWidth: '100%', maxHeight: '800px' } }>
										<Cropper
											image={ file }
											crop={ crop }
											zoom="1"
											cropSize={ { width, height } }
											onCropChange={ setCrop }
											onCropComplete={ onCropComplete }
										/>
									</div>

									<div>
										<button className={ `learnpress_avatar__button learnpress_avatar__button--save ${ loading?.save ? 'learnpress_avatar__button--loading' : '' }` } onClick={ updateAvatar }>{ __( 'Save', 'learnpress' ) }</button>
									</div>
								</div>
							) : (
								<div className="learnpress_avatar__cropper">
									<img src={ file } alt="" />

									<div>
										<button className={ `learnpress_avatar__button learnpress_avatar__button--replace` } onClick={ () => fileInput.current && fileInput.current.click() }>{ __( 'Replace', 'learnpress' ) }</button>
										<button className={ `learnpress_avatar__button learnpress_avatar__button--remove ${ loading?.remove ? 'learnpress_avatar__button--loading' : '' }` } onClick={ removeAvatar }>{ __( 'Remove', 'learnpress' ) }</button>
									</div>
								</div>
							) }
						</>
					) }

					<form style={ { display: ! file ? '' : 'none' } }>
						<div className="learnpress_avatar__form">
							<div className="learnpress_avatar__form-group">
								<label htmlFor="avatar-file">
									<div className="learnpress_avatar__form__upload">
										<div>
											<span><svg viewBox="64 64 896 896" focusable="false" data-icon="plus" width="1em" height="1em" fill="currentColor" aria-hidden="true"><defs><style></style></defs><path d="M482 152h60q8 0 8 8v704q0 8-8 8h-60q-8 0-8-8V160q0-8 8-8z"></path><path d="M176 474h672q8 0 8 8v60q0 8-8 8H176q-8 0-8-8v-60q0-8 8-8z"></path></svg></span>
											<div>{ __( 'Upload', 'learnpress' ) }</div>
										</div>
									</div>
									<input ref={ fileInput } type="file" id="avatar-file" accept="image/*" onChange={ ( e ) => setFileInput( e.target.files && e.target.files.length > 0 ? e.target.files[ 0 ] : '' ) } />
								</label>
							</div>
						</div>
					</form>

					{ uploadError && (
						<div className={ `lp-ajax-message error` } style={ { display: 'block' } }>{ uploadError }</div>
					) }

					{ ! uploadError && notice && notice.type && notice.message && (
						<div className={ `lp-ajax-message ${ notice.type }` } style={ { display: 'block' } }>{ notice.message }</div>
					) }
				</>
			) : (
				<ul className="lp-skeleton-animation">
					<li style={ { width: 200, height: 200 } }></li>
					<li style={ { width: 200, height: 20 } }></li>
					<li style={ { width: 200, height: 20 } }></li>
				</ul>
			) }
		</div>
	);
}

// Link: https://codesandbox.io/s/q8q1mnr01w
const createImage = ( url ) =>
	new Promise( ( resolve, reject ) => {
		const image = new Image();
		image.addEventListener( 'load', () => resolve( image ) );
		image.addEventListener( 'error', ( error ) => reject( error ) );
		image.setAttribute( 'crossOrigin', 'anonymous' ); // needed to avoid cross-origin issues on CodeSandbox
		image.src = url;
	} );

function getRadianAngle( degreeValue ) {
	return ( degreeValue * Math.PI ) / 180;
}

/**
 * Returns the new bounding area of a rotated rectangle.
 *
 * @param  width
 * @param  height
 * @param  rotation
 */
function rotateSize( width, height, rotation ) {
	const rotRad = getRadianAngle( rotation );

	return {
		width:
Math.abs( Math.cos( rotRad ) * width ) + Math.abs( Math.sin( rotRad ) * height ),
		height:
Math.abs( Math.sin( rotRad ) * width ) + Math.abs( Math.cos( rotRad ) * height ),
	};
}

/**
 * This function was adapted from the one in the ReadMe of https://github.com/DominicTobias/react-image-crop
 *
 * @param  imageSrc
 * @param  pixelCrop
 * @param  rotation
 * @param  flip
 */
async function getCroppedImg(
	imageSrc,
	pixelCrop,
	rotation = 0,
	flip = { horizontal: false, vertical: false }
) {
	const image = await createImage( imageSrc );
	const canvas = document.createElement( 'canvas' );
	const ctx = canvas.getContext( '2d' );

	if ( ! ctx ) {
		return null;
	}

	const rotRad = getRadianAngle( rotation );

	// calculate bounding box of the rotated image
	const { width: bBoxWidth, height: bBoxHeight } = rotateSize(
		image.width,
		image.height,
		rotation
	);

	// set canvas size to match the bounding box
	canvas.width = bBoxWidth;
	canvas.height = bBoxHeight;

	// translate canvas context to a central location to allow rotating and flipping around the center
	ctx.translate( bBoxWidth / 2, bBoxHeight / 2 );
	ctx.rotate( rotRad );
	ctx.scale( flip.horizontal ? -1 : 1, flip.vertical ? -1 : 1 );
	ctx.translate( -image.width / 2, -image.height / 2 );

	// draw rotated image
	ctx.drawImage( image, 0, 0 );

	// croppedAreaPixels values are bounding box relative
	// extract the cropped image using these values
	const data = ctx.getImageData(
		pixelCrop.x,
		pixelCrop.y,
		pixelCrop.width,
		pixelCrop.height
	);

	// set canvas width to final desired crop size - this will clear existing context
	canvas.width = pixelCrop.width;
	canvas.height = pixelCrop.height;

	// paste generated rotate image at the top left corner
	ctx.putImageData( data, 0, 0 );

	// As Base64 string
	return canvas.toDataURL( 'image/jpeg' );
}
