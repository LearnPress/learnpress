import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';
import * as Util from '../../utils.js';
import API from '../../api.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const profileCoverImage = () => {
	const lpSet = new Set();
	let cropper;
	let elBtnSave, elBtnRemove, elBtnChoose, elBtnCancel,
		elImagePreview, elCoverImageBackground, elImgCoverImageBackground, elImageEmpty,
		formCoverImage, elInputFile, elAction, imgUrlOriginal;
	const className = {
		formCoverImage: 'lp-user-cover-image',
		BtnChooseCoverImage: 'lp-btn-choose-cover-image',
		BtnSaveCoverImage: 'lp-btn-save-cover-image',
		BtnRemoveCoverImage: 'lp-btn-remove-cover-image',
		BtnCancelCoverImage: 'lp-btn-cancel-cover-image',
		BtnToEditCoverImage: 'lp-btn-to-edit-cover-image',
		CoverImagePreview: 'lp-cover-image-preview',
		CoverImageEmpty: 'lp-cover-image-empty',
		CoverImageBackground: 'lp-user-cover-image_background',
		InputFile: 'lp-cover-image-file',
		loading: 'loading',
		hidden: 'lp-hidden',
	};

	/**
	 * Get elements to use.
	 */
	const getElements = () => {
		elBtnSave = formCoverImage.querySelector( `.${ className.BtnSaveCoverImage }` );
		elBtnChoose = formCoverImage.querySelector( `.${ className.BtnChooseCoverImage }` );
		elBtnRemove = formCoverImage.querySelector( `.${ className.BtnRemoveCoverImage }` );
		elBtnCancel = formCoverImage.querySelector( `.${ className.BtnCancelCoverImage }` );
		elImagePreview = formCoverImage.querySelector( `.${ className.CoverImagePreview }` );
		elCoverImageBackground = document.querySelector( `.${ className.CoverImageBackground }` );
		elImgCoverImageBackground = elCoverImageBackground.querySelector( `img` );
		elImageEmpty = formCoverImage.querySelector( `.${ className.CoverImageEmpty }` );
		elAction = formCoverImage.querySelector( 'input[name=action]' );
		elInputFile = formCoverImage.querySelector( 'input[name=lp-cover-image-file]' );

		if ( ! lpSet.has( 'everClick' ) ) {
			imgUrlOriginal = elImagePreview.src;
			lpSet.add( 'everClick' );
		}
	};

	const fetchAPI = ( formData ) => {
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				Toastify( {
					text: message,
					gravity: lpData.toast.gravity, // `top` or `bottom`
					position: lpData.toast.position, // `left`, `center` or `right`
					className: `${ lpData.toast.classPrefix } ${ status }`,
					close: lpData.toast.close == 1,
					stopOnFocus: lpData.toast.stopOnFocus == 1,
					duration: lpData.toast.duration,
				} ).showToast();

				if ( 'remove' === data.action ) {
					Util.lpShowHideEl( elBtnRemove, 0 );
					Util.lpShowHideEl( elBtnChoose, 0 );
					elImagePreview.src = '';
					Util.lpShowHideEl( elImagePreview, 0 );
					Util.lpShowHideEl( elImageEmpty, 1 );
					if ( elCoverImageBackground ) {
						Util.lpShowHideEl( elCoverImageBackground, 0 );
					}
				} else if ( 'upload' === data.action ) {
					Util.lpShowHideEl( elImagePreview, 1 );
					elImagePreview.src = data.url;
					imgUrlOriginal = data.url;
					cropper.destroy();
				}

				imgUrlOriginal = elImagePreview.src;
			},
			error: ( error ) => {
				console.log( error );
			},
			completed: () => {
				Util.lpShowHideEl( elBtnSave, 0 );
				Util.lpSetLoadingEl( elBtnSave, 0 );
				Util.lpSetLoadingEl( elBtnRemove, 0 );
				Util.lpShowHideEl( elBtnCancel, 0 );

				if ( ! elImagePreview.src || elImagePreview.src === window.location.href ) {
					Util.lpShowHideEl( elBtnRemove, 0 );
				} else {
					Util.lpShowHideEl( elBtnRemove, 1 );
				}
			},
		};

		const url = API.frontend.apiProfileCoverImage;
		const option = { headers: {} };
		if ( 0 !== parseInt( lpData.user_id ) ) {
			option.headers[ 'X-WP-Nonce' ] = lpData.nonce;
		}

		option.method = 'POST';
		option.body = formData;

		Util.lpFetchAPI( url, option, callBack );
	};

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;

		if ( target.classList.contains( className.BtnToEditCoverImage ) ) {
			formCoverImage = document.querySelector( `.${ className.formCoverImage }` );
			if ( ! formCoverImage ) {
				return;
			}

			const isCorrectSection = target.dataset.sectionCorrect == 1;
			if ( isCorrectSection ) {
				e.preventDefault();
				formCoverImage.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}

		formCoverImage = target.closest( `.${ className.formCoverImage }` );
		if ( ! formCoverImage ) {
			return;
		}

		getElements();
		if ( target.classList.contains( className.BtnChooseCoverImage ) ) {
			e.preventDefault();
			elInputFile.click();
		}
		if ( target.classList.contains( className.BtnSaveCoverImage ) ) {
			Util.lpSetLoadingEl( elBtnSave, 1 );
		}
		if ( target.classList.contains( className.BtnCancelCoverImage ) ) {
			e.preventDefault();
			cropper.destroy();
			elImagePreview.src = imgUrlOriginal;
			if ( imgUrlOriginal === window.location.href ) {
				Util.lpShowHideEl( elImageEmpty, 1 );
				Util.lpShowHideEl( elBtnChoose, 0 );
				Util.lpShowHideEl( elImagePreview, 0 );
			} else {
				Util.lpShowHideEl( elBtnRemove, 1 );
			}

			Util.lpShowHideEl( elBtnSave, 0 );
			Util.lpShowHideEl( elBtnCancel, 0 );
		}
		if ( target.classList.contains( className.BtnRemoveCoverImage ) ) {
			e.preventDefault();
			target.classList.add( 'loading' );
			if ( cropper ) {
				cropper.destroy();
				cropper = undefined;
			}
			elAction.value = 'remove';
			elBtnSave.click();
		}
		if ( target.classList.contains( className.CoverImageEmpty ) ) {
			e.preventDefault();
			elInputFile.click();
		}
	} );
	document.addEventListener( 'change', ( e ) => {
		const target = e.target;

		formCoverImage = target.closest( `.${ className.formCoverImage }` );
		if ( ! formCoverImage ) {
			return;
		}

		getElements();
		if ( target.classList.contains( className.InputFile ) ) {
			e.preventDefault();
			const file = target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			const allowType = [ 'image/png', 'image/jpeg', 'image/webp' ];
			if ( allowType.indexOf( file.type ) < 0 ) {
				return;
			}

			elAction.value = 'upload';
			Util.lpShowHideEl( elImagePreview, 1 );
			Util.lpShowHideEl( elImageEmpty, 0 );
			Util.lpShowHideEl( elBtnRemove, 0 );
			Util.lpShowHideEl( elBtnSave, 1 );
			Util.lpShowHideEl( elBtnChoose, 1 );
			Util.lpShowHideEl( elBtnCancel, 1 );

			const reader = new FileReader();
			reader.onload = function( e ) {
				elImagePreview.src = e.target.result;
				// Destroy previous cropper instance if any
				if ( cropper ) {
					cropper.destroy();
				}
				// Initialize cropper
				cropper = new Cropper( elImagePreview, {
					aspectRatio: lpData.coverImageRatio,
					viewMode: 1,
					zoomOnWheel: false,
				} );
			};
			reader.readAsDataURL( file );
		}
	} );
	document.addEventListener( 'submit', ( e ) => {
		const target = e.target;

		if ( target.classList.contains( className.formCoverImage ) ) {
			e.preventDefault();
			const formData = new FormData( target );

			if ( undefined !== cropper ) {
				const canvas = cropper.getCroppedCanvas( {} );
				if ( elCoverImageBackground ) {
					const dataUrl = canvas.toDataURL( 'image/png' );
					elCoverImageBackground.style.backgroundImage = `url(${ dataUrl })`;
					elImgCoverImageBackground.src = dataUrl;
					Util.lpShowHideEl( elCoverImageBackground, 1 );
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
	document.addEventListener( 'DOMContentLoaded', ( e ) => {
		const elBtnToEditCoverImage = document.querySelector( `.${ className.BtnToEditCoverImage }` );
		const formCoverImage = document.querySelector( `.${ className.formCoverImage }` );

		if ( elBtnToEditCoverImage && formCoverImage ) {
			const isCorrectSection = elBtnToEditCoverImage.dataset.sectionCorrect == 1;
			if ( isCorrectSection ) {
				formCoverImage.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}
	} );
};

export default profileCoverImage;
