import * as editCourse from './course-builder/edit-course.js';

const toggleAddCategoryForm = () => {
	const form = document.querySelector( '.cb-course-edit-terms__form-add-category' );
	const btnAddNew = document.querySelector( '.cb-course-edit-categories__wrapper .btn-add-new' );

	if ( form ) {
		const isVisible = form.style.display !== 'none';
		form.style.display = isVisible ? 'none' : 'flex';
		if ( btnAddNew ) {
			btnAddNew.textContent = isVisible ? 'Add New Category' : 'Cancel';
		}

		if ( ! isVisible ) {
			const input = form.querySelector( '.cb-course-edit-category__input' );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
};

const addNewCategory = ( e, target ) => {
	const elBtnUpdateCourse = target.closest( `${ className.elBtnUpdateCourse }` );
	const elBtnDraftCourse = target.closest( `${ className.elBtnDraftCourse }` );

	if ( ! elBtnUpdateCourse && ! elBtnDraftCourse ) {
		return;
	}

	const input = document.querySelector( '.cb-course-edit-category__input' );
	const categoryName = input ? input.value.trim() : '';

	if ( ! categoryName ) {
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'add_course_category' );
	formData.append( 'category_name', categoryName );

	const postId = document.querySelector( '.cb-set-featured-image' )?.getAttribute( 'data-post-id' );
	if ( postId ) {
		formData.append( 'post_id', postId );
	}

	fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Category added:', data );

			if ( data.success && data.data ) {
				const wrapper = document.querySelector( '.cb-course-edit-categories__wrapper' );
				const btnAddNew = wrapper.querySelector( '.btn-add-new' );

				const newCheckbox = document.createElement( 'div' );
				newCheckbox.className = 'cb-course-edit-categories__checkbox';
				newCheckbox.innerHTML = `
		  <input type="checkbox" name="course_categories[]" value="${ data.data.term_id }" 
				 id="course_category_${ data.data.term_id }" checked="checked">
		  <label for="course_category_${ data.data.term_id }">${ data.data.name }</label>
		`;

				wrapper.insertBefore( newCheckbox, btnAddNew );

				// Reset form
				input.value = '';
				toggleAddCategoryForm();

				alert( 'Category đã được thêm thành công!' );
			} else {
				alert( 'Lỗi: ' + ( data.message || 'Không thể thêm category' ) );
			}
		} )
		.catch( ( error ) => {
			console.error( 'Error adding category:', error );
			alert( 'Có lỗi xảy ra khi thêm category!' );
		} );
};

const toggleAddTagForm = () => {
	const form = document.querySelector( '.cb-course-edit-terms__form-add-term' );
	const btnAddNew = document.querySelector( '.cb-course-edit-terms__wrapper .btn-add-new' );

	if ( form ) {
		const isVisible = form.style.display !== 'none';
		form.style.display = isVisible ? 'none' : 'flex';
		if ( btnAddNew ) {
			btnAddNew.textContent = isVisible ? 'Add New Tag' : 'Cancel';
		}

		if ( ! isVisible ) {
			const input = form.querySelector( '.cb-course-edit-terms__input' );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
};

const addNewTag = () => {
	const input = document.querySelector( '.cb-course-edit-terms__input' );
	const tagName = input ? input.value.trim() : '';

	if ( ! tagName ) {
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'add_course_tag' );
	formData.append( 'tag_name', tagName );

	const postId = document.querySelector( '.cb-set-featured-image' )?.getAttribute( 'data-post-id' );
	if ( postId ) {
		formData.append( 'post_id', postId );
	}

	fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Tag added:', data );

			if ( data.success && data.data ) {
				const wrapper = document.querySelector( '.cb-course-edit-terms__wrapper' );
				const btnAddNew = wrapper.querySelector( '.btn-add-new' );

				const newCheckbox = document.createElement( 'div' );
				newCheckbox.className = 'cb-course-edit-terms__checkbox';
				newCheckbox.innerHTML = `
		  <input type="checkbox" name="course_terms[]" value="${ data.data.term_id }" 
				 id="course_category_${ data.data.term_id }" checked="checked">
		  <label for="course_category_${ data.data.term_id }">${ data.data.name }</label>
		`;

				wrapper.insertBefore( newCheckbox, btnAddNew );

				input.value = '';
				toggleAddTagForm();

				alert( 'Tag đã được thêm thành công!' );
			} else {
				alert( 'Lỗi: ' + ( data.message || 'Không thể thêm tag' ) );
			}
		} )
		.catch( ( error ) => {
			console.error( 'Error adding tag:', error );
			alert( 'Có lỗi xảy ra khi thêm tag!' );
		} );
};

document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	// Click update or draft course
	editCourse.updateCourse( e, target );
	//Click trash course
	editCourse.trashCourse( e, target );
	// Click set featured image
	editCourse.openMediaUploader( e, target );
	// Click remove featured image
	editCourse.removeFeaturedImage( e, target );
	//Click add new category
	editCourse.toggleAddCategoryForm( e, target );
	//Click add new term
	editCourse.toggleAddTagForm( e, target );
	//Click save new category
	addNewCategory( e, target );
	//Click save new term
	addNewTag( e, target );
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;

	// Event enter for add new category
	addNewCategory( e, target );
	// Event enter for add new term
	addNewTag( e, target );
} );
