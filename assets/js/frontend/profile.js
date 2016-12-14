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

		$('.user-profile-edit-form').on('change', 'select[name="profile_picture_type"]', function () {
			var selected = $(this).val();
			$('.profile-avatar-hidden, .profile-avatar-current').each(function () {
				$(this).toggleClass('hide-if-js', function () {
					return !$(this).hasClass(selected);
				});
			});
			$('#profile-picture-gravatar').toggleClass('hide-if-js', selected == 'picture');
			$('#profile-picture-picture').toggleClass('hide-if-js', selected != 'picture');
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

		$('#profile-picture-picture a.change-profile-picutre-text').click( function( event ){
			event.preventDefault();
			if($(this).attr('onupload')=='1'){
				$('#profile-picture-picture .image-editor').hide();
				$(this).attr('onupload','0');
			}else{
				if($('#profile-picture-picture .cropit-image-input').prop('disabled')){
					$('#profile-picture-picture .cropit-image-input').prop('disabled','');
				};
				$('#profile-picture-picture .image-editor').show();
				$('#lp-button-choose-file').upload(
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
							}
						}
					}
				);
				$(this).attr('onupload','1');
			}
		});

		$('#profile-picture-picture .image-editor').cropit();
		$('#profile-picture-picture .rotate-cw').click(function (event) {
			event.preventDefault();
			$('.image-editor').cropit('rotateCW');
		});

		$('#profile-picture-picture .rotate-ccw').click(function (event) {
			event.preventDefault();
			$('.image-editor').cropit('rotateCCW');
		});

		$('#profile-picture-picture .export').click(function (event) {
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
						$('#profile-picture-picture .image-editor').hide();
					}
				});
				return;
		});
		
		$('#profile-picture-picture .cancel').click(function (event) {
			event.preventDefault();
			$('#lp-user-profile-picture-data').val( );
			$('#profile-picture-picture .image-editor').hide();
		});


	});
})(jQuery);