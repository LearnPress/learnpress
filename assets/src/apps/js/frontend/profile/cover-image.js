import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';

const profileCoverImage = () => {
	console.log( 'profileCoverImage' );
	const btnUploadID = 'lp-upload-cover-image',
		inputFileID = 'lp-cover-image-file',
		coverImageID = 'lp-cover-image';
	let cropper;
	const eleWrapper = document.querySelector( '#lp-cover-image-upload' );
	if ( ! eleWrapper ) {
		return;
	}
	eleWrapper.addEventListener( 'click', (e) => {
		let target = e.target;
		if ( target.id == btnUploadID ) {
			eleWrapper.querySelector( `#${inputFileID}` ).click();
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
		        });
		    };
		    reader.readAsDataURL(file);
		}
	} );
}
export default profileCoverImage;