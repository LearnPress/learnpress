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

})(jQuery);
