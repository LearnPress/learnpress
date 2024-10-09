import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';
import {
	lpAddQueryArgs,
	lpGetCurrentURLNoParam,
	listenElementViewed,
	listenElementCreated,
	lpFetchAPI,
} from '../../utils.js';
import API from '../../api.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const profileCoverImage = () => {
	let cropper;

	const fetchAPI = ( formData ) => {
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				Toastify( {
					text: message,
					//backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)',
					gravity: lpData.toast.gravity, // `top` or `bottom`
					position: lpData.toast.position, // `left`, `center` or `right`
					className: status,
					close: lpData.toast.close == 1,
					stopOnFocus: lpData.toast.stopOnFocus == 1,
					duration: lpData.toast.duration,
				} ).showToast();

				const elBtnSave = document.querySelector( '.lp-btn-save-cover-image' );
				const elImagePreview = document.querySelector( '.lp-cover-image-preview' );

				if ( 'remove' === data.action ) {
					console.log( 'remove' );
					const elBtnRemove = document.querySelector( '.lp-btn-remove-cover-image' );
					const elBtnChoose = document.querySelector( '.lp-btn-choose-cover-image' );

					const elImageEmpty = document.querySelector( '.lp-cover-image-empty' );
					const elCoverImageBackground = document.querySelector( '.lp-user-cover-image_background' );

					if ( elBtnRemove ) {
						elBtnRemove.style.display = 'none';
					}
					if ( elBtnChoose ) {
						elBtnChoose.style.display = 'none';
					}
					if ( elImagePreview ) {
						elImagePreview.style.display = 'none';
						elImagePreview.src = '';
					}
					if ( elImageEmpty ) {
						elImageEmpty.style.display = 'flex';
					}
					if ( elCoverImageBackground ) {
						elCoverImageBackground.style.backgroundImage = 'none';
						elCoverImageBackground.style.height = '0';
					}
				} else if ( elImagePreview ) {
					elImagePreview.style.display = 'block';
					elImagePreview.src = data.url;
					cropper.destroy();
				}

				if ( elBtnSave ) {
					elBtnSave.style.display = 'none';
				}
			},
			error: ( error ) => {
				console.log( error );
			},
			completed: () => {
				//console.log( 'completed' );
			},
		};

		const url = API.frontend.apiProfileCoverImage;
		const option = { headers: {} };
		if ( 0 !== parseInt( lpData.user_id ) ) {
			option.headers[ 'X-WP-Nonce' ] = lpData.nonce;
		}

		option.method = 'POST';
		option.body = formData;

		lpFetchAPI( url, option, callBack );
	};

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;

		const formCoverImage = target.closest( '.lp-user-cover-image' );
		const elAction = formCoverImage.querySelector( 'input[name=action]' );
		if ( formCoverImage ) {
			const elInputFile = formCoverImage.querySelector( 'input[name=lp-cover-image-file]' );
			if ( target.classList.contains( 'lp-btn-choose-cover-image' ) ) {
				e.preventDefault();
				elInputFile.click();
			}
			if ( target.classList.contains( 'lp-btn-remove-cover-image' ) ) {
				e.preventDefault();
				if ( cropper ) {
					cropper.destroy();
					cropper = undefined;
				}
				elAction.value = 'remove';
				const elBtnSave = formCoverImage.querySelector( '.lp-btn-save-cover-image' );
				elBtnSave.click();
			}
			if ( target.classList.contains( 'lp-cover-image-empty' ) ) {
				e.preventDefault();
				elInputFile.click();
			}
		}
	} );
	document.addEventListener( 'change', ( e ) => {
		const target = e.target;

		const formCoverImage = target.closest( '.lp-user-cover-image' );
		if ( formCoverImage ) {
			const elInputFile = formCoverImage.querySelector( 'input[name=lp-cover-image-file]' );
			if ( target.classList.contains( 'lp-cover-image-file' ) ) {
				e.preventDefault();
				const file = elInputFile.files[ 0 ];
				const reader = new FileReader();
				const elImagePreview = formCoverImage.querySelector( '.lp-cover-image-preview' );
				const elImageEmpty = formCoverImage.querySelector( '.lp-cover-image-empty' );
				const elBtnRemove = formCoverImage.querySelector( '.lp-btn-remove-cover-image' );
				const elAction = formCoverImage.querySelector( 'input[name=action]' );
				elImagePreview.style.display = 'block';
				elImageEmpty.style.display = 'none';
				elBtnRemove.style.display = 'block';
				elAction.value = 'upload';

				reader.onload = function( e ) {
					elImagePreview.src = e.target.result;
					// Destroy previous cropper instance if any
					if ( cropper ) {
						cropper.destroy();
					}
					// Initialize cropper
					cropper = new Cropper( elImagePreview, {
						aspectRatio: 5.16,
						viewMode: 1,
						zoomOnWheel: false,
					} );
				};
				reader.readAsDataURL( file );

				const elBtnSave = formCoverImage.querySelector( '.lp-btn-save-cover-image' );
				const elBtnChoose = formCoverImage.querySelector( '.lp-btn-choose-cover-image' );
				elBtnSave.style.display = 'block';
				elBtnChoose.style.display = 'block';
			}
		}
	} );
	document.addEventListener( 'submit', ( e ) => {
		const target = e.target;

		if ( target.classList.contains( 'lp-user-cover-image' ) ) {
			e.preventDefault();
			const formCoverImage = target.closest( '.lp-user-cover-image' );
			const formData = new FormData( formCoverImage );

			if ( undefined !== cropper ) {
				const canvas = cropper.getCroppedCanvas( {} );
				const elCoverImageBackground = document.querySelector( '.lp-user-cover-image_background' );
				if ( elCoverImageBackground ) {
					elCoverImageBackground.style.backgroundImage = `url(${ canvas.toDataURL( 'image/png' ) })`;
					elCoverImageBackground.style.backgroundRepeat = 'no-repeat';
					elCoverImageBackground.style.backgroundSize = 'contain';
					elCoverImageBackground.style.height = '250px';
				}

				canvas.toBlob( ( blob ) => {
					formData.append( 'image', blob, 'cover.png' );

					fetchAPI( formData );
				}, 'image/png' );
			} else {
				fetchAPI( formData );
			}
		}
	} );
};

export default profileCoverImage;
