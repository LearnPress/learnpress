import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';
import * as Util from '../../utils.js';
// import API from '../../api.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const profileAvatarImage = () => {
	const lpAvatarWrapper = document.querySelector( '#learnpress-avatar-upload' );
	if ( ! lpAvatarWrapper ) {
		return;
	}
	let cropper, avatarPreviewSrc, imgUrlOriginal;
	let avatarForm = lpAvatarWrapper.querySelector( '.lp_avatar__form' );
	const btnRemove = lpAvatarWrapper.querySelector( '.lp-btn-remove-avatar' ),
		btnReplace = lpAvatarWrapper.querySelector( '.lp-btn-choose-avatar' ),
		btnSave = lpAvatarWrapper.querySelector( '.lp-btn-save-avatar' ),
		btnCancel = lpAvatarWrapper.querySelector( '.lp-btn-cancel-avatar' ),
		avatarPreviewImage = lpAvatarWrapper.querySelector( '.lp-avatar-image' ),
		avatarInputFile = lpAvatarWrapper.querySelector( '#avatar-file' ),
		profileAvatar = document.querySelector( '.wrapper-profile-header .user-avatar img' );
	const avatarRatio = parseFloat( ( lpProfileSettings.avatar_dimensions.width / lpProfileSettings.avatar_dimensions.height ).toFixed( 2 ) );

	lpAvatarWrapper.addEventListener( 'click', ( e ) => {
		const target = e.target;
		if ( target === btnReplace ) {
			e.preventDefault();
			avatarInputFile.click();
		} else if ( target === btnSave ) {
			Util.lpSetLoadingEl( btnSave, 1 );
			btnSave.disabled = true;
			if ( undefined !== cropper ) {
				const canvas = cropper.getCroppedCanvas( {
					width: lpProfileSettings.avatar_dimensions.width,
					height: lpProfileSettings.avatar_dimensions.height,
				} );
				const newCropSrc = canvas.toDataURL( 'image/png' );
				if ( profileAvatar ) {
					profileAvatar.src = newCropSrc;
				}
				avatarPreviewImage.src = newCropSrc;
				const formData = new FormData();
				formData.append( 'file', newCropSrc );
				uploadAvatar( formData );
			}
		} else if ( target === btnCancel ) {
			e.preventDefault();
			cropper.destroy();
			avatarPreviewImage.src = imgUrlOriginal;
			if ( imgUrlOriginal === window.location.href ) {
				Util.lpShowHideEl( avatarForm, 1 );
				Util.lpShowHideEl( btnReplace, 0 );
				Util.lpShowHideEl( avatarPreviewImage, 0 );
			} else {
				Util.lpShowHideEl( btnRemove, 1 );
			}

			Util.lpShowHideEl( btnSave, 0 );
			Util.lpShowHideEl( btnCancel, 0 );
		} else if ( target === btnRemove ) {
			btnRemove.disabled = true;
			Util.lpSetLoadingEl( btnRemove, 1 );
			removeAvatar();
		}
	} );
	lpAvatarWrapper.addEventListener( 'change', ( e ) => {
		const target = e.target;
		if ( target === avatarInputFile ) {
			const file = avatarInputFile.files[ 0 ];
			if ( ! file ) {
				return;
			}

			const allowType = [ 'image/png', 'image/jpeg', 'image/webp' ];
			if ( allowType.indexOf( file.type ) < 0 ) {
				return;
			}

			const reader = new FileReader();
			reader.onload = function( e ) {
				avatarPreviewImage.src = e.target.result;
				// Destroy previous cropper instance if any
				if ( cropper ) {
					cropper.destroy();
				}
				// Initialize cropper
				cropper = new Cropper( avatarPreviewImage, {
					aspectRatio: avatarRatio,
					viewMode: 1,
					zoomOnWheel: false,
				} );
			};

			reader.readAsDataURL( file );
			if ( ! avatarPreviewImage.classList.contains( 'lp-hidden' ) ) {
				Util.lpShowHideEl( avatarPreviewImage, 1 );
			}
			Util.lpShowHideEl( avatarForm, 0 );
			Util.lpShowHideEl( btnSave, 1 );
			Util.lpShowHideEl( btnReplace, 1 );
			Util.lpShowHideEl( btnCancel, 1 );
			Util.lpShowHideEl( btnRemove, 0 );
		}
	} );
	const uploadAvatar = ( formData ) => {
		fetch( `${ lpData.lp_rest_url }lp/v1/profile/upload-avatar`, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': lpData.nonce,
			},
			body: formData,
		} ) // wrapped
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( res.status === 'error' ) {
					throw new Error( res.message );
				}

				Util.lpShowHideEl( avatarPreviewImage, 1 );
				showMessage( 'success', res.message );
				if ( undefined !== cropper ) {
					cropper.destroy();
				}

				imgUrlOriginal = avatarPreviewImage.src;
			} ).finally( () => {
				Util.lpShowHideEl( btnSave, 0 );
				btnSave.disabled = false;
				Util.lpSetLoadingEl( btnSave, 0 );
				Util.lpShowHideEl( btnCancel, 0 );
				Util.lpShowHideEl( btnRemove, 1 );
			} ).catch( ( err ) => console.log( err ) );
	};
	const removeAvatar = () => {
		fetch( `${ lpData.lp_rest_url }lp/v1/profile/remove-avatar`, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': lpData.nonce,
			},
		} ) // wrapped
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( res.status === 'error' ) {
					throw new Error( res.message );
				}
				showMessage( 'success', res.message );
				Util.lpShowHideEl( avatarPreviewImage, 0 );
				Util.lpShowHideEl( avatarForm, 1 );
				imgUrlOriginal = avatarPreviewSrc = '';
				profileAvatar.src = lpProfileSettings.default_avatar;
				// window.location.href = window.location.href;
			} ).finally( () => {
				btnRemove.disabled = false;
				Util.lpShowHideEl( btnRemove, 0 );
				Util.lpSetLoadingEl( btnRemove, 0 );
				Util.lpShowHideEl( btnReplace, 0 );
			} ).catch( ( err ) => console.log( err ) );
	};

	const showMessage = ( status, message ) => {
		Toastify( {
			text: message,
			gravity: lpData.toast.gravity, // `top` or `bottom`
			position: lpData.toast.position, // `left`, `center` or `right`
			className: `${ lpData.toast.classPrefix } ${ status }`,
			close: lpData.toast.close == 1,
			stopOnFocus: lpData.toast.stopOnFocus == 1,
			duration: lpData.toast.duration,
		} ).showToast();
	};

	Util.lpOnElementReady( '.lp-avatar-image', () => {
		imgUrlOriginal = avatarPreviewImage.src;
	} );

	Util.lpOnElementReady( '#learnpress-avatar-upload', ( e ) => {
		e.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	} );
};
export default profileAvatarImage;
