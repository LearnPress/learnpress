;(function ($) {
	'use strict';
	$(document).ready(function () {
		$('#user_profile_password_form input').attr('disabled', 'disabled');
		$(document).on('click', '.table-orders .cancel-order', function (e) {
			e.preventDefault();
			var _this = $(this),
				_href = _this.attr('href');
			LP.alert(learn_press_js_localize.confirm_cancel_order, function (confirm) {
				if (confirm) {
					window.location.href = _href;
				}
			});
			return false;
		});

		$(document).on('submit', '#learn-press-form-login', function (e) {
			var $form = $(this),
				data = $form.serialize();
			$form.find('.learn-press-error, .learn-press-notice, .learn-press-message').fadeOut();
			$form.find('input').attr('disabled', true);
			LP.doAjax({
				data   : {
					'lp-ajax': 'login',
					data     : data
				},
				success: function (response, raw) {
					LP.showMessages(response.message, $form, 'LOGIN_ERROR');
					if (response.result == 'error') {
						$form.find('input').attr('disabled', false);
						$('#learn-press-form-login input[type="text"]').focus();
					}
					if (response.redirect) {
						LP.reload(response.redirect);
					}
				},
				error  : function () {
					LP.showMessages('', $form, 'LOGIN_ERROR');
					$form.find('input').attr('disabled', false);
					$('#learn-press-form-login input[type="text"]').focus();
				}
			});
			return false;
		});

		$('#learn-press-toggle-password').click(function (e) {
			e.preventDefault();
			var $el = $('#user_profile_password_form');
			if ($el.hasClass('hide-if-js')) {
				$el.removeClass('hide-if-js').hide();
			}
			$el.slideToggle(function () {
				$el.find('input').attr('disabled', !$el.is(':visible'));
			});
		});

		$('#learn-press-form-login input[type="text"]').focus();
		/**
		 * Show hide dropdown menu
		 */
		$('.user-profile-edit-form').on('change', 'select[name="profile_picture_type"]', function () {
			var avata_type = $(this).val();
			$('.profile-avatar-hidden, .profile-avatar-current' ).each(function () {
				$(this).toggleClass('hide-if-js', $(this).hasClass('avatar-'+avata_type) );
			});
			$('.menu-item-use-gravatar, .menu-item-use-picture' ).each(function () {
				$(this).toggleClass('lp-menu-item-selected', $(this).hasClass('menu-item-use-'+avata_type));
			});
		});

		$('#lp-menu-change-picture .menu-item-use-gravatar').click( function(event){
			$('#lp-profile_picture_type').val('gravatar').trigger('change');
			$('#lpbox-upload-crop-profile-picture').slideUp();
		});
		
		$('#lp-menu-change-picture .menu-item-use-picture').click( function(event){
			var current_picture = $('#lp-user-profile-picture-data').attr('data-current');
			if( !current_picture ){
//				$('#lp-button-choose-file').trigger('click');
				$('#lpbox-upload-crop-profile-picture').slideDown();
			} else {
				$('#lp-profile_picture_type').val('picture').trigger('change');
				$('#lpbox-upload-crop-profile-picture').slideUp();
			}
		});

		$('#lp-menu-change-picture .menu-item-upload-picture').click( function(event){
//			$('#lp-button-choose-file').trigger('click');
			$('#lpbox-upload-crop-profile-picture').slideDown();
		});

		$('#lp-ocupload-picture').upload(
			{
				'name':'image',
				params:{from:'profile','action':'update','sub_action':'upload_avatar'},
				'onSubmit':function(){
					LP.blockContent();
				},
				'onComplete':function(response){
					response = LP.parseJSON(response);
					console.log(response);
					if( response.return && response.avatar_tmp ) {
						/* Load Image in to crop */
						$('.image-editor').cropit('imageSrc',response.avatar_tmp);
						$('.image-editor').attr('avatar-filename',response.avatar_tmp_filename);
						$('#lpbox-upload-crop-profile-picture').slideDown();
						LP.unblockContent();
						$('body, html').css('overflow','visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						var message = '<div class="learn-press-message success"><p>'+response.message+'</p></div>';
						$('.user-profile-picture.info-field').prepend(message);
					} else if ( !response.return ){
						$('.image-editor').cropit('imageSrc','');
						$('.image-editor').attr('avatar-filename','');
						LP.unblockContent();
						$('body, html').css('overflow','visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						var message = '<div class="learn-press-message error"><p>'+response.message+'</p></div>';
						$('.user-profile-picture.info-field').prepend(message);
						
					}
				}
			}
		);

		$('#lp-button-choose-file').click(function(event){
			event.preventDefault();
			$('#lp-ocupload-picture').parent().find('form input[name="image"]').trigger('click');
		});

		$('#lpbox-upload-crop-profile-picture .image-editor').cropit();

		
		$('#lp-button-apply-changes').click(function (event) {
			event.preventDefault();
			var zoom			= $('.image-editor').cropit('zoom');
			var offset			= $('.image-editor').cropit('offset');
			var avatar_filename = $('.image-editor').attr('avatar-filename');
			var datas = {
				from:'profile',
				'action':'update',
				'sub_action':'crop_avatar',
				'avatar_filename':avatar_filename,
				'zoom':zoom, 
				'offset':offset
			};
			/** Create avatar, thumbnail and update picture option **/
			$.ajax({
					url     : LP.getUrl(),
					dataType: 'html',
					data    : datas,
					type    : 'post',
					beforeSend: function(){
						LP.blockContent();
					},
					success : function (response) {
						response = LP.parseJSON(response);
						var avatar_url = response.avatar_url;
						$('.profile-picture.avatar-gravatar img').attr( 'src', avatar_url );
						$('#lp-profile_picture_type').val('picture').trigger('change');
						$('#lpbox-upload-crop-profile-picture').slideUp();
						LP.unblockContent();
						$('body, html').css('overflow','visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						$('.user-profile-picture.info-field').prepend(response.message);
					}
				});
				return;
		});
		
		$('#lp-button-cancel-changes').click(function (event) {
			event.preventDefault();
			$('#lpbox-upload-crop-profile-picture').slideUp();
		});

		$('#learn-press-user-profile-edit-form form#your-profile input[name="submit"]').on('click', function(event){
			event.preventDefault();
			var check_form	= true;
			var check_focus = false;
			/**
			 * VALIDATE FORM
			 */
//			console.log( '' === $('#your-profile #nickname' ).val());
			if ('' === $('#your-profile #nickname').val()) {
				if (0 === $('#your-profile #nickname').next('span.error').length) {
					$('<span class="error">'+lp_profile_translation.msg_field_is_required+'</span>').insertAfter($('#your-profile #nickname'));
				}
				check_form = false;
				//document.getElementById('nickname').focus();
				$('#your-profile #nickname').focus();
				check_focus = true;
			} else {
				$('#your-profile #nickname').next('span.error').remove();
			}

			if ('' !== $('#your-profile #pass0').val()) {
				if ('' === $('#your-profile #pass1').val()) {
					if (0 === $('#your-profile #pass1').next('span.error').length) {
						$('<span class="error">'+lp_profile_translation.msg_field_is_required+'</span>').insertAfter($('#your-profile #pass1'));
					}
					check_form = false;
					if (!check_focus) {
						$('#your-profile #pass1').focus();
						check_focus = true;
					}
				} else {
					$('#your-profile #pass1').next('span.error').remove();
				}

				if ('' === $('#your-profile #pass2').val()) {
					if (0 === $('#your-profile #pass2').next('span.error').length) {
						$('<span class="error">'+lp_profile_translation.msg_field_is_required+'</span>').insertAfter($('#your-profile #pass2'));
					}
					check_form = false;
					if ( !check_focus ) {
						$('#your-profile #pass2').focus();
						check_focus = true;
					}
				} else {
					$(this.pass2).next('span.error').remove();
				}
			}
			if (check_form) {
//				$('#learn-press-user-profile-edit-form form#your-profile').submit();
				var datas = $('#learn-press-user-profile-edit-form form#your-profile').serializeArray();
				$.ajax({
					url     : LP.getUrl(),
					dataType: 'html',
					data    : datas,
					type    : 'post',
					beforeSend: function(){
						LP.blockContent();
					},
					success : function (response) {
						response = LP.parseJSON(response);
						$('#your-profile #pass0, #your-profile #pass1, #your-profile #pass2').val('');
						$('#user_profile_password_form').slideUp();
						LP.unblockContent();
						$('body, html').css('overflow','visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						$('.user-profile-picture.info-field').prepend(response.message);
						$('html, body').animate({
							scrollTop: $('.user-profile-picture.info-field .learn-press-message').offset().top-100
						}, 500);
					}
				});
			}
		});

		$('#learn-press-user-profile-edit-form #your-profile input#nickname').on('change',function(){
			if(''===$(this).val()){
				if(0 === $(this).next('span.error').length){;
					$('<span class="error">'+lp_profile_translation.msg_field_is_required+'</span>').insertAfter($(this));
				}
			} else {
				$(this).next('span.error').remove();
			}
		});
		
		$('#learn-press-user-profile-edit-form #your-profile input#pass1').on('change',function(){
			
		});
		
		$('#learn-press-user-profile-edit-form #your-profile input#pass2').on('keyup',function(){
			var pass1 = $('#your-profile input#pass1').val();
			if( pass1 !== $(this).val() ){
				if(0 === $(this).next('span.error').length){;
					$('<span class="error">'+lp_profile_translation.confim_pass_not_match+'</span>').insertAfter($(this));
				}
			} else {
				$(this).next('span.error').remove();
			}
		});

	});
})(jQuery);
