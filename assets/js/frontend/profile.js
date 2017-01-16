;(function ($) {
	'use strict';

	var UserProfile = function (args) {
		this.view = new UserProfile.View({
			model: new UserProfile.Model(args)
		});
	}

	UserProfile.View = Backbone.View.extend({
		events        : {
			'click #lp-remove-upload-photo': '_removePhoto',
			'click #lp-upload-photo'       : '_upload',
			'click .lp-cancel-upload'      : '_cancel'
		},
		el            : '#lp-user-profile-form',
		uploader      : null,
		initialize    : function () {
			console.log()
			_.bindAll(this, 'filesAdded', 'uploadProgress', 'uploadError', 'fileUploaded', 'crop');
			this._getUploader();
		},
		_removePhoto  : function (e) {
			e.preventDefault();
			this.$('.profile-picture').toggle().filter('.profile-avatar-current').remove();
			this.$('#lp-remove-upload-photo').hide();
			this.$('#submit').prop('disabled', false);
		},
		_upload       : function (e) {
			e.preventDefault();
		},
		_cancel       : function (e) {
			e.preventDefault();
			this.$crop && this.$crop.remove();
			this.$('.lp-avatar-preview').removeClass('croping');
		},
		filesAdded    : function (up, files) {
			var that = this;
			up.files.splice(0, up.files.length - 1);
			that.$('.lp-avatar-preview').addClass('uploading');
			that.$('.lp-avatar-upload-progress-value').width(0);
			that.uploader.start();
		},
		uploadProgress: function (up, file) {
			this.$('.lp-avatar-upload-progress-value').css('width', file.percent + "%");
		},
		uploadError   : function (up, err) {
			this.$('.lp-avatar-preview').addClass('upload-error').removeClass('uploading');
			this.$('.lp-avatar-upload-error').html(err);
		},
		fileUploaded  : function (up, file, info) {
			this.$('.lp-avatar-preview').removeClass('upload-error').removeClass('uploading');
			var that = this,
				response = LP.parseJSON(info.response);
			if (response.url) {
				this.avatar = response.url;
				$("<img/>") // Make in memory copy of image to avoid css issues
					.attr("src", response.url)
					.load(function () {
						that.model.set($.extend(response, {
							width : this.width,
							height: this.height
						}));
						that.crop()
					});
			}
		},
		crop          : function () {
			this.model.set('r', Math.random())
			new UserProfile.Crop(this);
			this.$('#submit').prop('disabled', false);
		},
		_getUploader  : function () {
			if (this.uploader) {
				return this.uploader;
			}
			this.uploader = new plupload.Uploader({
				runtimes      : 'html5,flash,silverlight,html4',
				browse_button : 'lp-upload-photo',
				container     : $('#lp-user-edit-avatar').get(0),
				url           : LP_Settings.ajax.addQueryVar('action', 'learnpress_upload-user-avatar'),
				filters       : {
					max_file_size: '10mb',
					mime_types   : [
						{title: "Image", extensions: "png,jpg,bmp,gif"}
					]
				},
				file_data_name: 'lp-upload-avatar',
				init          : {
					PostInit      : function () {
					},
					FilesAdded    : this.filesAdded,
					UploadProgress: this.uploadProgress,
					FileUploaded  : this.fileUploaded,
					Error         : this.uploadError
				}
			});
			this.uploader.init();
			return this.uploader;
		}
	});
	UserProfile.Model = Backbone.Model.extend({});
	UserProfile.Crop = function ($view) {
		var self = this,
			data = $view.model.toJSON(),
			$crop = $(LP.template('tmpl-crop-user-avatar')(data));
		$crop.appendTo($view.$('.lp-avatar-preview').addClass('croping'));
		$view.$crop = $crop;
		var $img = $crop.find('img'),
			wx = 0,
			hx = 0,
			lx = 0,
			tx = 0,
			nw = 0,
			nh = 0;
		this.initCrop = function () {
			var r1 = data.viewWidth / data.viewHeight,
				r2 = data.width / data.height;

			if (r1 >= r2) {
				wx = data.viewWidth;
				hx = data.height * data.viewWidth / data.width;
				lx = 0;
				tx = -(hx - data.viewHeight) / 2
			} else {
				hx = data.viewHeight;
				wx = data.width * data.viewHeight / data.height;
				tx = 0;
				lx = -(wx - data.viewWidth) / 2;
			}
			nw = wx;
			nh = hx;
			$img.draggable({
				drag: function (e, ui) {
					if (ui.position.left > 0) {
						ui.position.left = 0;
					}
					if (ui.position.top > 0) {
						ui.position.top = 0;
					}
					var xx = data.viewWidth - nw,
						yy = data.viewHeight - nh;
					if (xx > ui.position.left) {
						ui.position.left = xx;
					}
					if (yy > ui.position.top) {
						ui.position.top = yy;
					}
					$(document.body).addClass('profile-dragging');
				},
				stop: function (e, ui) {
					lx = parseInt($img.css('left'));
					tx = parseInt($img.css('top'));
					dd = (Math.abs(lx) + data.viewWidth / 2) / nw;
					bb = (Math.abs(tx) + data.viewHeight / 2) / nh;
					self.update({
						width : nw,
						height: nh,
						top   : tx,
						left  : lx
					});
					$(document.body).removeClass('profile-dragging');
				}
			});
			var dd = (Math.abs(lx) + data.viewWidth / 2) / wx,
				bb = (Math.abs(tx) + data.viewHeight / 2) / hx;
			$crop.find('.lp-zoom > div').slider({
				create: function () {
					self.update({
						width : wx,
						height: hx,
						top   : tx,
						left  : lx
					});
				},
				slide : function (e, ui) {
					nw = wx + (ui.value / 100) * data.width * 2;
					nh = hx + (ui.value / 100) * data.height * 2;
					var nl = data.viewWidth / 2 - (nw * dd),// parseInt((data.viewWidth - nw) / 2),
						nt = data.viewHeight / 2 - nh * bb;//parseInt((data.viewHeight - nh) / 2);
					if (nl > 0) {
						nl = 0;
					}
					if (nt > 0) {
						nt = 0;
					}
					var xx = parseInt(data.viewWidth - nw),
						yy = parseInt(data.viewHeight - nh);
					if (xx > nl) {
						nl = lx = xx;
					}
					if (yy > nt) {
						nt = tx = yy;
					}
					self.update({
						width : nw,
						height: nh,
						top   : nt,
						left  : nl
					});
					$(document.body).addClass('profile-resizing');
				},
				stop: function(){
					$(document.body).removeClass('profile-resizing');
				}
			});
		}
		this.update = function (args) {
			$img.css({
				width : args.width,
				height: args.height,
				top   : args.top,
				left  : args.left
			});
			var r = args.width / data.width,
				left = parseInt(Math.abs(args.left / r)),
				top = parseInt(Math.abs(args.top / r)),
				right = left + parseInt(data.viewWidth / r),
				bottom = top + parseInt(data.viewHeight / r);
			var cropData = $.extend(args, {
				width : data.viewWidth,
				height: data.viewHeight,
				r     : r,
				points: [left, top, right, bottom].join(',')
			});
			$crop.find('input[name^="lp-user-avatar-crop"]').each(function () {
				var $input = $(this),
					name = $input.data('name');
				if (name != 'name') {
					$input.val(cropData[name]);
				}
			});
		}
		this.initCrop();
	}


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
	$(document).ready(function () {
		var $form = $('#lp-user-profile-form form'),
			oldData = $form.serialize(),
			timer = null,
			$passwordForm = $form.find('#lp-profile-edit-password-form');

		function _checkData() {
			return $form.serialize() != oldData;
		}

		function _timerCallback() {
			$form.find('#submit').prop('disabled', !_checkData());
		}

		if ($passwordForm.length == 0) {
			$form.on('keyup change', 'input, textarea, select', function () {
				timer && clearTimeout(timer);
				timer = setTimeout(_timerCallback, 300);
			});
		} else {
			$passwordForm.on('change keyup', 'input', function (e) {
				var $target = $(e.target),
					targetName = $target.attr('name'),
					$oldPass = $form.find('#pass0'),
					$newPass = $form.find('#pass1'),
					$confirmPass = $form.find('#pass2'),
					match = !(($newPass.val() || $confirmPass.val()) && $newPass.val() != $confirmPass.val());
				$form.find('#lp-password-not-match').toggleClass('hide-if-js', match);
				$form.find('#submit').prop('disabled', !match || !$oldPass.val() || !$newPass.val() || !$confirmPass.val());
			});
		}
		// avatar
		new UserProfile({
			viewWidth : parseInt(LP_Settings.avatar_size['width']),
			viewHeight: parseInt(LP_Settings.avatar_size['height'])
		});
	});

	return;
	$(document).on('click', '#lp-remove-upload-photo', function () {


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
			$('.profile-avatar-hidden, .profile-avatar-current').each(function () {
				$(this).toggleClass('hide-if-js', $(this).hasClass('avatar-' + avata_type));
			});
			$('.menu-item-use-gravatar, .menu-item-use-picture').each(function () {
				$(this).toggleClass('lp-menu-item-selected', $(this).hasClass('menu-item-use-' + avata_type));
			});
		});

		$('#lp-menu-change-picture .menu-item-use-gravatar').click(function (event) {
			$('#lp-profile_picture_type').val('gravatar').trigger('change');
			$('#lpbox-upload-crop-profile-picture').slideUp();
		});

		$('#lp-menu-change-picture .menu-item-use-picture').click(function (event) {
			var current_picture = $('#lp-user-profile-picture-data').attr('data-current');
			if (!current_picture) {
//				$('#lp-button-choose-file').trigger('click');
				$('#lpbox-upload-crop-profile-picture').slideDown();
			} else {
				$('#lp-profile_picture_type').val('picture').trigger('change');
				$('#lpbox-upload-crop-profile-picture').slideUp();
			}
		});

		$('#lp-menu-change-picture .menu-item-upload-picture').click(function (event) {
//			$('#lp-button-choose-file').trigger('click');
			$('#lpbox-upload-crop-profile-picture').slideDown();
		});

		$('#lp-ocupload-picture').upload(
			{
				'name'      : 'image',
				params      : {from: 'profile', 'action': 'update', 'sub_action': 'upload_avatar'},
				'onSubmit'  : function () {
					LP.blockContent();
				},
				'onComplete': function (response) {
					response = LP.parseJSON(response);
					console.log(response);
					if (response.return && response.avatar_tmp) {
						/* Load Image in to crop */
						$('.image-editor').cropit('imageSrc', response.avatar_tmp);
						$('.image-editor').attr('avatar-filename', response.avatar_tmp_filename);
						$('#lpbox-upload-crop-profile-picture').slideDown();
						LP.unblockContent();
						$('body, html').css('overflow', 'visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						var message = '<div class="learn-press-message success"><p>' + response.message + '</p></div>';
						$('.user-profile-picture.info-field').prepend(message);
					} else if (!response.return) {
						$('.image-editor').cropit('imageSrc', '');
						$('.image-editor').attr('avatar-filename', '');
						LP.unblockContent();
						$('body, html').css('overflow', 'visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						var message = '<div class="learn-press-message error"><p>' + response.message + '</p></div>';
						$('.user-profile-picture.info-field').prepend(message);

					}
				}
			}
		);

		$('#lp-button-choose-file').click(function (event) {
			event.preventDefault();
			$('#lp-ocupload-picture').parent().find('form input[name="image"]').trigger('click');
		});

		$('#lpbox-upload-crop-profile-picture .image-editor').cropit();


		$('#lp-button-apply-changes').click(function (event) {
			event.preventDefault();
			var zoom = $('.image-editor').cropit('zoom');
			var offset = $('.image-editor').cropit('offset');
			var avatar_filename = $('.image-editor').attr('avatar-filename');
			var datas = {
				from             : 'profile',
				'action'         : 'update',
				'sub_action'     : 'crop_avatar',
				'avatar_filename': avatar_filename,
				'zoom'           : zoom,
				'offset'         : offset
			};
			/** Create avatar, thumbnail and update picture option **/
			$.ajax({
				url       : LP.getUrl(),
				dataType  : 'html',
				data      : datas,
				type      : 'post',
				beforeSend: function () {
					LP.blockContent();
				},
				success   : function (response) {
					response = LP.parseJSON(response);
					var avatar_url = response.avatar_url;
					$('.profile-picture.avatar-gravatar img').attr('src', avatar_url);
					$('#lp-profile_picture_type').val('picture').trigger('change');
					$('#lpbox-upload-crop-profile-picture').slideUp();
					LP.unblockContent();
					$('body, html').css('overflow', 'visible');
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

		$('#learn-press-user-profile-edit-form form#your-profile input[name="submit"]').on('click', function (event) {
			event.preventDefault();
			var check_form = true;
			var check_focus = false;
			/**
			 * VALIDATE FORM
			 */
//			console.log( '' === $('#your-profile #nickname' ).val());
			if ('' === $('#your-profile #nickname').val()) {
				if (0 === $('#your-profile #nickname').next('span.error').length) {
					$('<span class="error">' + lp_profile_translation.msg_field_is_required + '</span>').insertAfter($('#your-profile #nickname'));
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
						$('<span class="error">' + lp_profile_translation.msg_field_is_required + '</span>').insertAfter($('#your-profile #pass1'));
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
						$('<span class="error">' + lp_profile_translation.msg_field_is_required + '</span>').insertAfter($('#your-profile #pass2'));
					}
					check_form = false;
					if (!check_focus) {
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
					url       : LP.getUrl(),
					dataType  : 'html',
					data      : datas,
					type      : 'post',
					beforeSend: function () {
						LP.blockContent();
					},
					success   : function (response) {
						response = LP.parseJSON(response);
						$('#your-profile #pass0, #your-profile #pass1, #your-profile #pass2').val('');
						$('#user_profile_password_form').slideUp();
						LP.unblockContent();
						$('body, html').css('overflow', 'visible');
						$('.user-profile-picture.info-field .learn-press-message').remove();
						$('.user-profile-picture.info-field').prepend(response.message);
						$('html, body').animate({
							scrollTop: $('.user-profile-picture.info-field .learn-press-message').offset().top - 100
						}, 500);
					}
				});
			}
		});

		$('#learn-press-user-profile-edit-form #your-profile input#nickname').on('change', function () {
			if ('' === $(this).val()) {
				if (0 === $(this).next('span.error').length) {
					;
					$('<span class="error">' + lp_profile_translation.msg_field_is_required + '</span>').insertAfter($(this));
				}
			} else {
				$(this).next('span.error').remove();
			}
		});

		$('#learn-press-user-profile-edit-form #your-profile input#pass1').on('change', function () {

		});

		$('#learn-press-user-profile-edit-form #your-profile input#pass2').on('keyup', function () {
			var pass1 = $('#your-profile input#pass1').val();
			if (pass1 !== $(this).val()) {
				if (0 === $(this).next('span.error').length) {
					;
					$('<span class="error">' + lp_profile_translation.confim_pass_not_match + '</span>').insertAfter($(this));
				}
			} else {
				$(this).next('span.error').remove();
			}
		});

	});
})(jQuery);
