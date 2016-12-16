;(function ($) {
	"use strict";
	$(document).ready(function () {
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
			var selected = $(this).val();
			$('.profile-avatar-hidden, .profile-avatar-current').each(function () {
				$(this).toggleClass('hide-if-js', function () {
					return !$(this).hasClass(selected);
				});
			});
		});

		$('#lp-menu-change-picture .menu-item-use-gravatar').click( function(event){
			$('#lp-profile_picture_type').val('gravatar').trigger('change');
			$('#lp-menu-change-picture .dropdown-menu li').show();
			$(this).hide();
		});
		
		$('#lp-menu-change-picture .menu-item-use-picture').click( function(event){
			var current_picture = $('#lp-user-profile-picture-data').attr('data-current');
			if( !current_picture ){
				$('#lp-button-choose-file').trigger('click');
//				$('#lpbox-upload-crop-profile-picture').slideDown();
//				LP.confirm(
//					{
//						'title':'Upload Picture',
//						'message':'Go to upload new profile picture now'
//					}, function(result){
//						if(result){
//							$('#lpbox-upload-crop-profile-picture').slideDown();
//						}
//					}
//				);
			} else {
				$('#lp-profile_picture_type').val('picture').trigger('change');
				$('#lp-menu-change-picture .dropdown-menu li').show();
			}
			$(this).hide();
		});

		$('#lp-menu-change-picture .menu-item-upload-picture').click( function(event){
			$('#lp-button-choose-file').trigger('click');
		});
		
		$('#lp-ocupload-picture').upload(
			{
				'name':'image',
				params:{from:'profile','action':'update','sub_action':'upload_avatar'},
				'onComplete':function(response){
					response = LP.parseJSON(response);
					console.log(response);
					if(response.return && response.avatar_tmp ) {
						/* Load Image in to crop */
						$('.image-editor').cropit('imageSrc',response.avatar_tmp);
						$('.image-editor').attr('avatar-filename',response.avatar_tmp_filename);
//						LP.alert(response.message);
						$('#lpbox-upload-crop-profile-picture').slideDown();
					} else if (!response.return){
						$('.image-editor').cropit('imageSrc','');
						$('.image-editor').attr('avatar-filename','');
						LP.alert(response.message);
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
			
			/** Crop avatar and create avatar thumbnail **/
			$.ajax({
					url     : LP.getUrl(),
					dataType: 'html',
					data    : datas,
					type    : 'post',
					success : function (response) {
						response = LP.parseJSON(response);
						var avatar_filename = response.avatar_filename;
						var avatar_url = response.avatar_url;
						$('#lp-user-profile-picture-data').val( avatar_filename );
						$('img.avatar').attr( 'src', avatar_url );
						$('img.avatar').attr( 'srcset', avatar_url );
						$('.profile-avatar-current img').attr( 'src', avatar_url );
						$('#lpbox-upload-crop-profile-picture').slideUp();
					}
				});
				return;
		});
		
		$('#lp-button-cancel-changes').click(function (event) {
			event.preventDefault();
			$('#lp-user-profile-picture-data').val( );
			$('#lpbox-upload-crop-profile-picture').slideUp();
		});


	});
})(jQuery);