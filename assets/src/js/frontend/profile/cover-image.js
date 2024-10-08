import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';

const profileCoverImage = () => {
	console.log( 'profileCoverImage' );
	const btnUploadID = 'lp-upload-cover-image',
		inputFileID = 'lp-cover-image-file',
		coverImageID = 'lp-cover-image',
		btnSaveID = 'lp-save-cover-image';
	let cropper;
	const eleWrapper = document.querySelector( '#lp-cover-image-upload' );
	if ( ! eleWrapper ) {
		return;
	}
	eleWrapper.addEventListener( 'click', (e) => {
		let target = e.target;
		if ( target.id == btnUploadID ) {
			eleWrapper.querySelector( `#${inputFileID}` ).click();
		} else if ( target.id == btnSaveID ) {
			if ( undefined !== cropper ) {
				const canvas = cropper.getCroppedCanvas({});
				let coverWrapper = document.querySelector( '.wrapper-profile-header' );
				if ( coverWrapper ) {
					coverWrapper.style.backgroundImage = `url(${canvas.toDataURL('image/png')})`;
					coverWrapper.style.backgroundRepeat = 'no-repeat';
					coverWrapper.style.backgroundPosition = 'center';
					coverWrapper.style.backgroundSize = 'cover';
				}
				canvas.toBlob((blob) => {
					const formData = new FormData();
					formData.append( 'image', blob,'cover.png' );
					uploadRequest( formData );
				}, 'image/png');
			}
		}
	} );
	eleWrapper.addEventListener( 'change', (event) => {
		let target = event.target;
		if ( target.id == inputFileID ) {
			let inputFile = eleWrapper.querySelector( `#${inputFileID}` );
			const file = inputFile.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const allowType = ['image/png', 'image/jpeg', 'image/webp'];
			if ( allowType.indexOf(file.type) < 0 ) {
				return;
			}
			const reader = new FileReader(),
			image = eleWrapper.querySelector( `#${coverImageID}` );

			reader.onload = function(e) {
		        image.src = e.target.result;
		        // Destroy previous cropper instance if any
		        if (cropper) {
		          cropper.destroy();
		        }
		        // Initialize cropper
		        cropper = new Cropper(image, {
		          aspectRatio: 4.17,
		          viewMode: 1,
		          zoomOnWheel: false,
		        });
		    };
		    reader.readAsDataURL(file);
		}
	} );
	const uploadRequest = ( formData ) => {
		let btnSave = eleWrapper.querySelector( `#${btnSaveID}` );
		btnSave.classList.add( 'loading' );
		btnSave.disabled = true;
		fetch(`${lpData.lp_rest_url}lp/v1/profile/upload-cover-image`, {
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
		        window.location.href = window.location.href;
		    }).finally(() => { btnSave.classList.remove( 'loading' ); btnSave.disabled = false; }).catch(err => console.log(err));
	}
}
export default profileCoverImage;