function getCourseDataForUpdate() {
	const data = {};

	const titleInput = document.getElementById( 'title' );
	if ( titleInput ) {
		data.post_title = titleInput.value;
	} else {
		console.warn( 'Title input not found' );
		data.post_title = '';
	}

	const descEditor = document.getElementById( 'course_description_editor' );
	if ( descEditor ) {
		data.course_description = descEditor.value;
	} else {
		console.warn( 'Description editor not found' );
		data.course_description = '';
	}

	if ( typeof tinymce !== 'undefined' ) {
		const editor = tinymce.get( 'course_description_editor' );
		if ( editor ) {
			data.course_description = editor.getContent();
		}
	}

	data.course_categories = [];
	const categoryCheckboxes = document.querySelectorAll(
		'input[name="course_categories[]"]:checked'
	);
	categoryCheckboxes.forEach( ( checkbox ) => {
		data.course_categories.push( checkbox.value );
	} );

	data.course_terms = [];
	const tagCheckboxes = document.querySelectorAll( 'input[name="course_terms[]"]:checked' );
	tagCheckboxes.forEach( ( checkbox ) => {
		data.course_terms.push( checkbox.value );
	} );

	const thumbnailInput = document.getElementById( 'course_thumbnail_id' );
	if ( thumbnailInput ) {
		data.course_thumbnail_id = thumbnailInput.value;
	} else {
		console.warn( 'Thumbnail input not found' );
		data.course_thumbnail_id = '0';
	}

	const setImageBtn = document.querySelector( '.cb-set-featured-image' );
	if ( setImageBtn ) {
		data.post_id = setImageBtn.getAttribute( 'data-post-id' );
	} else {
		console.warn( 'Set featured image button not found' );
		data.post_id = '';
	}

	return data;
}

function createFormDataForUpdate() {
	const courseData = getCourseDataForUpdate();
	const formData = new FormData();

	formData.append( 'post_title', courseData.post_title || '' );
	formData.append( 'course_description', courseData.course_description || '' );
	formData.append( 'course_thumbnail_id', courseData.course_thumbnail_id || '0' );
	formData.append( 'post_id', courseData.post_id || '' );

	formData.append( 'action', 'update_course_builder' );

	if ( typeof lpCourseBuilder !== 'undefined' && lpCourseBuilder.nonce ) {
		formData.append( 'nonce', lpCourseBuilder.nonce );
	}

	if ( courseData.course_categories && courseData.course_categories.length > 0 ) {
		courseData.course_categories.forEach( ( catId ) => {
			formData.append( 'course_categories[]', catId );
		} );
	}

	if ( courseData.course_terms && courseData.course_terms.length > 0 ) {
		courseData.course_terms.forEach( ( tagId ) => {
			formData.append( 'course_terms[]', tagId );
		} );
	}

	return formData;
}

function updateCoursePost() {
	const formData = createFormDataForUpdate();

	console.log( 'Dữ liệu sẽ gửi:' );
	for ( let [ key, value ] of formData.entries() ) {
		console.log( key, value );
	}

	const updateBtn = document.querySelector( '.cb-btn-update' );
	if ( updateBtn ) {
		const originalText = updateBtn.textContent;
		updateBtn.textContent = 'Đang cập nhật...';
		updateBtn.style.pointerEvents = 'none';
		updateBtn.style.opacity = '0.6';

		fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Network response was not ok' );
				}
				return response.json();
			} )
			.then( ( data ) => {
				console.log( 'Cập nhật thành công:', data );
				alert( 'Course đã được cập nhật!' );
			} )
			.catch( ( error ) => {
				console.error( 'Lỗi khi cập nhật:', error );
				alert( 'Có lỗi xảy ra khi cập nhật course!' );
			} )
			.finally( () => {
				updateBtn.textContent = originalText;
				updateBtn.style.pointerEvents = 'auto';
				updateBtn.style.opacity = '1';
			} );
	} else {
		console.error( 'Update button không tìm thấy' );
	}
}

function saveDraftCourse() {
	const courseData = getCourseDataForUpdate();
	console.log( 'Lưu bản nháp:', courseData );
	alert( 'Đã lưu bản nháp!' );
}

function trashCourse() {
	if ( confirm( 'Bạn có chắc muốn xóa course này?' ) ) {
		const courseData = getCourseDataForUpdate();
		console.log( 'Xóa course ID:', courseData.post_id );
		alert( 'Course đã được chuyển vào thùng rác!' );
	}
}

// Hàm toggle form add category
function toggleAddCategoryForm() {
	const form = document.querySelector( '.cb-course-edit-terms__form-add-category' );
	const btnAddNew = document.querySelector( '.cb-course-edit-categories__wrapper .btn-add-new' );

	if ( form ) {
		const isVisible = form.style.display !== 'none';
		form.style.display = isVisible ? 'none' : 'flex';
		if ( btnAddNew ) {
			btnAddNew.textContent = isVisible ? 'Add New Category' : 'Cancel';
		}

		// Focus vào input khi hiển thị
		if ( ! isVisible ) {
			const input = form.querySelector( '.cb-course-edit-category__input' );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
}

// Hàm thêm category mới
function addNewCategory() {
	const input = document.querySelector( '.cb-course-edit-category__input' );
	const categoryName = input ? input.value.trim() : '';

	if ( ! categoryName ) {
		alert( 'Vui lòng nhập tên category!' );
		return;
	}

	console.log( 'Thêm category mới:', categoryName );

	// Tạo FormData để gửi
	const formData = new FormData();
	formData.append( 'action', 'add_course_category' );
	formData.append( 'category_name', categoryName );

	const postId = document.querySelector( '.cb-set-featured-image' )?.getAttribute( 'data-post-id' );
	if ( postId ) {
		formData.append( 'post_id', postId );
	}

	// Gửi AJAX request
	fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Category added:', data );

			if ( data.success && data.data ) {
				// Thêm checkbox mới vào danh sách
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
}

function toggleAddTagForm() {
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
}

function addNewTag() {
	const input = document.querySelector( '.cb-course-edit-terms__input' );
	const tagName = input ? input.value.trim() : '';

	if ( ! tagName ) {
		alert( 'Vui lòng nhập tên tag!' );
		return;
	}

	console.log( 'Thêm tag mới:', tagName );

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
}
document.addEventListener( 'DOMContentLoaded', function () {
	const container = document.getElementById( 'lp-course-builder-content' );
	if ( ! container ) {
		console.error( 'Course builder container không tìm thấy!' );
		return;
	}

	const updateBtn = document.querySelector( '.cb-btn-update' );
	if ( updateBtn ) {
		updateBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			updateCoursePost();
		} );
	}

	const draftBtn = document.querySelector( '.cb-btn-darft' );
	if ( draftBtn ) {
		draftBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			saveDraftCourse();
		} );
	}

	const trashBtn = document.querySelector( '.cb-btn-trash' );
	if ( trashBtn ) {
		trashBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			trashCourse();
		} );
	}

	// Nút "Add New Category"
	const btnAddCategory = document.querySelector(
		'.cb-course-edit-categories__wrapper .btn-add-new'
	);
	if ( btnAddCategory ) {
		btnAddCategory.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			toggleAddCategoryForm();
		} );
		console.log( '✓ Add Category button đã gắn sự kiện' );
	}

	// Nút "Add" trong form add category
	const btnSaveCategory = document.querySelector( '.cb-course-edit-category__btn-save' );
	if ( btnSaveCategory ) {
		btnSaveCategory.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			addNewCategory();
		} );
		console.log( '✓ Save Category button đã gắn sự kiện' );
	}

	// Enter key trong input category
	const inputCategory = document.querySelector( '.cb-course-edit-category__input' );
	if ( inputCategory ) {
		inputCategory.addEventListener( 'keypress', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				addNewCategory();
			}
		} );
		console.log( '✓ Category input Enter key đã gắn sự kiện' );
	}

	// ===== THÊM MỚI: Events cho Add Tag =====

	// Nút "Add New Tag"
	const btnAddTag = document.querySelector( '.cb-course-edit-terms__wrapper .btn-add-new' );
	if ( btnAddTag ) {
		btnAddTag.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			toggleAddTagForm();
		} );
		console.log( '✓ Add Tag button đã gắn sự kiện' );
	}

	// Nút "Add" trong form add tag
	const btnSaveTag = document.querySelector( '.cb-course-edit-terms__btn-save' );
	if ( btnSaveTag ) {
		btnSaveTag.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			addNewTag();
		} );
		console.log( '✓ Save Tag button đã gắn sự kiện' );
	}

	// Enter key trong input tag
	const inputTag = document.querySelector( '.cb-course-edit-terms__input' );
	if ( inputTag ) {
		inputTag.addEventListener( 'keypress', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				addNewTag();
			}
		} );
		console.log( '✓ Tag input Enter key đã gắn sự kiện' );
	}
} );
