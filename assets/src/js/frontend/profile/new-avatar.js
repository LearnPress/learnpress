import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';
import * as Util from '../../utils.js';
// import API from '../../api.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
const profileAvatarImage = () => {
	const lpAvatarWrapper = document.querySelector('#learnpress-avatar-upload');
	if (!lpAvatarWrapper) {
		return;
	}
	let cropper, avatarPreviewSrc;
	const avatarPreviewWrapper = document.querySelector('.learnpress_avatar__cropper'),
		btnRemove = lpAvatarWrapper.querySelector('.learnpress_avatar__button--remove'),
		btnReplace = lpAvatarWrapper.querySelector('.learnpress_avatar__button--replace'),
		btnSave = lpAvatarWrapper.querySelector('.learnpress_avatar__button--save'),
		btnCancel = lpAvatarWrapper.querySelector('.learnpress_avatar__button--cancel'),
		avatarPreviewImage = lpAvatarWrapper.querySelector('.learnpress-avatar-image'),
		avatarInputFile = lpAvatarWrapper.querySelector('#avatar-file'),
		avatarForm = lpAvatarWrapper.querySelector('.learnpress-avatar-form'),
		profileAvatar = document.querySelector( '.wrapper-profile-header .user-avatar img' );
	const avatarRatio = parseFloat( ( lpProfileSettings.avatar_dimensions.width/lpProfileSettings.avatar_dimensions.height ).toFixed(2) );
	lpAvatarWrapper.addEventListener('click', (e) => {
		let target = e.target;
		if( target == btnReplace ) {
			Util.lpShowHideEl( avatarForm, 0 );
			avatarPreviewSrc = avatarPreviewImage.src;
			avatarInputFile.click();
			Util.lpShowHideEl( btnRemove, 0 );
			Util.lpShowHideEl( btnSave, 1 );
			Util.lpShowHideEl( btnCancel, 1 );
		} else if ( target == btnSave ) {
			Util.lpSetLoadingEl( btnSave, 1 );
			btnSave.disabled = true;
			if ( undefined !== cropper ) {
				const canvas = cropper.getCroppedCanvas({ width: lpProfileSettings.avatar_dimensions.width, height: lpProfileSettings.avatar_dimensions.height });
				let newCropSrc = canvas.toDataURL('image/png');
				if ( profileAvatar ) {
					profileAvatar.src = newCropSrc;
				}
				avatarPreviewImage.setAttribute( 'height', lpProfileSettings.avatar_dimensions.height );
				avatarPreviewImage.setAttribute( 'width', lpProfileSettings.avatar_dimensions.width );
				avatarPreviewImage.src = newCropSrc;
				const formData = new FormData();
				formData.append( 'file', newCropSrc );
				uploadAvatar( formData );
			}
		} else if ( target == btnCancel ) {
			if ( undefined === avatarPreviewSrc ) {
				Util.lpShowHideEl( avatarForm, 1 );
				Util.lpShowHideEl( avatarPreviewWrapper, 0 );
			} else {
				Util.lpShowHideEl( avatarForm, 0 );
				Util.lpShowHideEl( avatarPreviewWrapper, 1 );
				avatarPreviewImage.src = avatarPreviewSrc;
				Util.lpShowHideEl( btnCancel, 0 );
				Util.lpShowHideEl( btnSave, 0 );
				Util.lpShowHideEl( btnRemove, 1 );
			}
			if ( undefined !== cropper ) {
				cropper.destroy();
			}
		} else if ( target == btnRemove ) {
			Util.lpSetLoadingEl( btnRemove, 1 );
			btnRemove.disabled = true;
			removeAvatar();
		} else if( target == avatarInputFile ) {
			avatarInputFile.value = '';
			Util.lpShowHideEl( btnCancel, 1 );
			Util.lpShowHideEl( btnRemove, 0 );
		}
	});
	lpAvatarWrapper.addEventListener('change', (e) => {
		let target = event.target;
		if ( target == avatarInputFile ) {
			const file = avatarInputFile.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const allowType = ['image/png', 'image/jpeg', 'image/webp'];
			if ( allowType.indexOf(file.type) < 0 ) {
				return;
			}
			const reader = new FileReader();
			reader.onload = function(e) {
		        avatarPreviewImage.src = e.target.result;
		        // Destroy previous cropper instance if any
		        if (cropper) {
		          cropper.destroy();
		        }
		        // Initialize cropper
		        cropper = new Cropper( avatarPreviewImage, {
		          aspectRatio: avatarRatio,
		          viewMode: 1,
		          zoomOnWheel: false
		        } );
		    };
		    reader.readAsDataURL( file );
		    if ( ! avatarPreviewWrapper.classList.contains( 'lp-hiddenr' ) ) {
		    	Util.lpShowHideEl( avatarPreviewWrapper, 1 );
		    }
	    	Util.lpShowHideEl( avatarForm, 0 );
	    	Util.lpShowHideEl( btnSave, 1 );
		}
	});
	const uploadAvatar = ( formData ) => {
		fetch(`${lpData.lp_rest_url}lp/v1/profile/upload-avatar`, {
		        method: 'POST',
		        headers: {
		            'X-WP-Nonce': lpData.nonce
		        },
		        body: formData
		    }) // wrapped
		    .then((res) => res.json())
		    .then((res) => {
		        if (res.status == 'error') {
		           throw new Error(res.message);
		        }
		        showMessage( 'success', res.message );
		        if ( undefined !== cropper ) {
		        	cropper.destroy();
		        }
		        // window.location.href = window.location.href;
		    }).finally(() => {
		    	Util.lpSetLoadingEl( btnSave, 0 );
		    	btnSave.disabled = false;
		    	Util.lpShowHideEl( btnSave, 0 );
		    	Util.lpShowHideEl( btnCancel, 0 );
		    	Util.lpShowHideEl( btnRemove, 1 );
		    }).catch(err => console.log(err));
	}
	const removeAvatar = () => {
		fetch(`${lpData.lp_rest_url}lp/v1/profile/remove-avatar`, {
		        method: 'POST',
		        headers: {
		            'X-WP-Nonce': lpData.nonce
		        }
		    }) // wrapped
		    .then((res) => res.json())
		    .then((res) => {
		        if (res.status == 'error') {
		           throw new Error(res.message);
		        }
		        showMessage( 'success', res.message );
		        Util.lpShowHideEl( avatarPreviewWrapper, 0 );
		        Util.lpShowHideEl( avatarForm, 1 );
		        avatarPreviewSrc = undefined;
		        profileAvatar.src = lpProfileSettings.default_avatar;
		        // window.location.href = window.location.href;
		    }).finally(() => {
		    	btnRemove.disabled = false;
		    	Util.lpSetLoadingEl( btnRemove, 0 );
		    }).catch(err => console.log(err));
	}

	const showMessage = (status, message) => {
        Toastify({
            text: message,
            gravity: lpData.toast.gravity, // `top` or `bottom`
            position: lpData.toast.position, // `left`, `center` or `right`
            className: `${lpData.toast.classPrefix} ${status}`,
            close: lpData.toast.close == 1,
            stopOnFocus: lpData.toast.stopOnFocus == 1,
            duration: lpData.toast.duration,
        }).showToast();
    };
};
export default profileAvatarImage;
