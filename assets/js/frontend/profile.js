/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/profile.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/profile.js":
/*!*******************************************!*\
  !*** ./assets/src/js/frontend/profile.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function (_$) {
  'use strict';

  var UserProfile = function UserProfile(args) {
    this.view = new UserProfile.View({
      model: new UserProfile.Model(args)
    });
  };

  UserProfile.View = Backbone.View.extend({
    events: {
      'click #lp-remove-upload-photo': '_removePhoto',
      'click #lp-upload-photo': '_upload',
      'click .lp-cancel-upload': '_cancel',
      'click .lp-save-upload': '_save'
    },
    el: '#lp-user-edit-avatar',
    uploader: null,
    initialize: function initialize() {
      _.bindAll(this, 'filesAdded', 'uploadProgress', 'uploadError', 'fileUploaded', 'crop');

      this._getUploader();
    },
    _save: function _save(e) {
      e.preventDefault();
      var self = this;

      _$.ajax({
        url: '?lp-ajax=save-uploaded-user-avatar',
        data: this.$('.lp-avatar-crop-image').serializeJSON(),
        type: 'post',
        success: function success(response) {
          response = LP.parseJSON(response);

          if (!response.success) {
            return;
          } // Remove crop element


          self.$('.lp-avatar-crop-image').remove(); // try to find avatar element and change the image

          _$('.lp-user-profile-avatar').html(response.avatar);

          self.$().attr('data-custom', 'yes');
          self.$('.profile-picture').toggleClass('profile-avatar-current').filter('.profile-avatar-current').html(response.avatar);
        }
      });
    },
    $: function $(selector) {
      return selector ? _$(this.$el).find(selector) : _$(this.$el);
    },
    _removePhoto: function _removePhoto(e) {
      e.preventDefault();

      if (!confirm('Remove?')) {
        return;
      } // TODO: ajax to remove


      this.$().removeAttr('data-custom');
      this.$('.profile-picture').toggleClass('profile-avatar-current'); //this.$('#lp-remove-upload-photo').hide();

      this.$('#submit').prop('disabled', false);

      _$('.lp-user-profile-avatar').html(this.$('.profile-avatar-current').find('img').clone());
    },
    _upload: function _upload(e) {
      e.preventDefault();
    },
    _cancel: function _cancel(e) {
      e.preventDefault();
      this.$crop && this.$crop.remove();
      this.$('.lp-avatar-preview').removeClass('croping');
    },
    filesAdded: function filesAdded(up, files) {
      var that = this;
      up.files.splice(0, up.files.length - 1);
      that.$('.lp-avatar-preview').addClass('uploading');
      that.$('.lp-avatar-upload-progress-value').width(0);
      that.uploader.start();
    },
    uploadProgress: function uploadProgress(up, file) {
      this.$('.lp-avatar-upload-progress-value').css('width', file.percent + "%");
    },
    uploadError: function uploadError(up, err) {
      this.$('.lp-avatar-preview').addClass('upload-error').removeClass('uploading');
      this.$('.lp-avatar-upload-error').html(err);
    },
    fileUploaded: function fileUploaded(up, file, info) {
      this.$('.lp-avatar-preview').removeClass('upload-error').removeClass('uploading');
      var that = this,
          response = LP.parseJSON(info.response);

      if (response.url) {
        this.avatar = response.url;

        _$("<img/>") // Make in memory copy of image to avoid css issues
        .attr("src", response.url).load(function () {
          that.model.set(_$.extend(response, {
            width: this.width,
            height: this.height
          }));
          that.crop();
        });
      }
    },
    crop: function crop() {
      this.model.set('r', Math.random());
      new UserProfile.Crop(this);
      this.$('#submit').prop('disabled', false);
    },
    _getUploader: function _getUploader() {
      if (this.uploader) {
        return this.uploader;
      }

      this.uploader = new plupload.Uploader({
        runtimes: 'html5,flash,silverlight,html4',
        browse_button: 'lp-upload-photo',
        container: _$('#lp-user-edit-avatar').get(0),
        url: (typeof lpGlobalSettings !== 'undefined' ? lpGlobalSettings.ajax : '').addQueryVar('action', 'learnpress_upload-user-avatar'),
        filters: {
          max_file_size: '10mb',
          mime_types: [{
            title: "Image",
            extensions: "png,jpg,bmp,gif"
          }]
        },
        file_data_name: 'lp-upload-avatar',
        init: {
          PostInit: function PostInit() {},
          FilesAdded: this.filesAdded,
          UploadProgress: this.uploadProgress,
          FileUploaded: this.fileUploaded,
          Error: this.uploadError
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
        $crop = _$(LP.template('tmpl-crop-user-avatar')(data));

    $crop.appendTo($view.$('#profile-avatar-uploader')); //$crop.appendTo($view.$('.lp-avatar-preview').addClass('croping'));

    $view.$crop = $crop;
    var $img = $crop.find('img'),
        wx = 0,
        hx = 0,
        lx = 0,
        tx = 0,
        nw = 0,
        nh = 0,
        maxWidth = 870;

    this.initCrop = function () {
      var r1 = data.viewWidth / data.viewHeight,
          r2 = data.width / data.height;

      if (r1 >= r2) {
        wx = data.viewWidth;
        hx = data.height * data.viewWidth / data.width;
        lx = 0;
        tx = -(hx - data.viewHeight) / 2;
      } else {
        hx = data.viewHeight;
        wx = data.width * data.viewHeight / data.height;
        tx = 0;
        lx = -(wx - data.viewWidth) / 2;
      }

      nw = wx;
      nh = hx;
      $img.draggable({
        drag: function drag(e, ui) {
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

          _$(document.body).addClass('profile-dragging');
        },
        stop: function stop(e, ui) {
          lx = parseInt($img.css('left'));
          tx = parseInt($img.css('top'));
          dd = (Math.abs(lx) + data.viewWidth / 2) / nw;
          bb = (Math.abs(tx) + data.viewHeight / 2) / nh;
          self.update({
            width: nw,
            height: nh,
            top: tx,
            left: lx
          });

          _$(document.body).removeClass('profile-dragging');
        }
      });
      var dd = (Math.abs(lx) + data.viewWidth / 2) / wx,
          bb = (Math.abs(tx) + data.viewHeight / 2) / hx;
      $crop.find('.lp-zoom > div').slider({
        create: function create() {
          self.update({
            width: wx,
            height: hx,
            top: tx,
            left: lx
          });
        },
        slide: function slide(e, ui) {
          nw = wx + ui.value / 100 * data.width * 2;
          nh = hx + ui.value / 100 * data.height * 2;
          var nl = data.viewWidth / 2 - nw * dd,
              // parseInt((data.viewWidth - nw) / 2),
          nt = data.viewHeight / 2 - nh * bb; //parseInt((data.viewHeight - nh) / 2);

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
            width: nw,
            height: nh,
            top: nt,
            left: nl
          });

          _$(document.body).addClass('profile-resizing');

          console.log(ui.value, data);
        },
        stop: function stop() {
          _$(document.body).removeClass('profile-resizing');
        }
      });
    };

    this.update = function (args) {
      $img.css({
        width: args.width,
        height: args.height,
        top: args.top,
        left: args.left
      });
      var r = args.width / data.width,
          left = parseInt(Math.abs(args.left / r)),
          top = parseInt(Math.abs(args.top / r)),
          right = left + parseInt(data.viewWidth / r),
          bottom = top + parseInt(data.viewHeight / r);

      var cropData = _$.extend(args, {
        width: data.viewWidth,
        height: data.viewHeight,
        r: r,
        points: [left, top, right, bottom].join(',')
      });

      $crop.find('input[name^="lp-user-avatar-crop"]').each(function () {
        var $input = _$(this),
            name = $input.data('name');

        if (name != 'name' && cropData[name] !== undefined) {
          $input.val(cropData[name]);
        }
      });
    };

    this.initCrop();
  };

  _$(document).on('submit', '#learn-press-form-login', function (e) {
    var $form = _$(this),
        data = $form.serialize();

    $form.find('.learn-press-error, .learn-press-notice, .learn-press-message').fadeOut();
    $form.find('input').attr('disabled', true);
    LP.doAjax({
      data: {
        'lp-ajax': 'login',
        data: data
      },
      success: function success(response, raw) {
        LP.showMessages(response.message, $form, 'LOGIN_ERROR');

        if (response.result == 'error') {
          $form.find('input').attr('disabled', false);

          _$('#learn-press-form-login input[type="text"]').focus();
        }

        if (response.redirect) {
          LP.reload(response.redirect);
        }
      },
      error: function error() {
        LP.showMessages('', $form, 'LOGIN_ERROR');
        $form.find('input').attr('disabled', false);

        _$('#learn-press-form-login input[type="text"]').focus();
      }
    });
    return false;
  });

  _$(document).on('click', '.table-orders .cancel-order', function (e) {
    e.preventDefault();

    var _this = _$(this),
        _href = _this.attr('href');

    LP.alert(learn_press_js_localize.confirm_cancel_order, function (confirm) {
      if (confirm) {
        window.location.href = _href;
      }
    });
    return false;
  });

  _$(document).ready(function () {
    var $form = _$('#lp-user-profile-form form'),
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
        var $target = _$(e.target),
            targetName = $target.attr('name'),
            $oldPass = $form.find('#pass0'),
            $newPass = $form.find('#pass1'),
            $confirmPass = $form.find('#pass2'),
            match = !(($newPass.val() || $confirmPass.val()) && $newPass.val() != $confirmPass.val());

        $form.find('#lp-password-not-match').toggleClass('hide-if-js', match);
        $form.find('#submit').prop('disabled', !match || !$oldPass.val() || !$newPass.val() || !$confirmPass.val());
      });
    }

    var args = {};

    if (typeof lpProfileUserSettings !== 'undefined') {
      args.viewWidth = parseInt(lpProfileUserSettings.avatar_size['width']);
      args.viewHeight = parseInt(lpProfileUserSettings.avatar_size['height']);
    } // avatar


    new UserProfile(args);
    Profile.recoverOrder();
  }).on('click', '.btn-load-more-courses', function (event) {
    var $button = _$(this);

    var paged = $button.data('paged') || 1;
    var pages = $button.data('pages') || 1;
    var container = $button.data('container');

    var $container = _$('#' + container);

    var url = $button.data('url');
    paged++;
    $button.data('paged', paged).prop('disabled', true).removeClass('btn-ajax-off').addClass('btn-ajax-on');

    if (!url) {
      var seg = window.location.href.split('?');

      if (seg[0].match(/\/([0-9]+)\//)) {
        url = seg[0].replace(/\/([0-9]+)\//, paged);
      } else {
        url = seg[0] + paged;
      }

      if (seg[1]) {
        url += '?' + seg[1];
      }
    } else {
      url = url.addQueryVar('current_page', paged);
    }

    _$.ajax({
      url: url,
      data: $button.data('args'),
      success: function success(response) {
        $container.append(_$(response).find('#' + container).children());

        if (paged >= pages) {
          $button.remove();
        } else {
          $button.prop('disabled', false).removeClass('btn-ajax-on').addClass('btn-ajax-off');
        }
      }
    });
  });

  var Profile = {
    recoverOrder: function recoverOrder(e) {
      var $wrap = _$('.order-recover'),
          $buttonRecoverOrder = $wrap.find('.button-recover-order'),
          $input = $wrap.find('input[name="order-key"]');

      function recoverOrder() {
        $buttonRecoverOrder.addClass('disabled').attr('disabled', 'disabled');
        $wrap.find('.learn-press-message').remove();

        _$.post({
          url: '',
          data: $wrap.serializeJSON(),
          success: function success(response) {
            response = LP.parseJSON(response);

            if (response.message) {
              var $msg = _$('<div class="learn-press-message icon"><i class="fa"></i> ' + response.message + '</div>');

              if (response.result == 'error') {
                $msg.addClass('error');
              }

              $wrap.prepend($msg);
            }

            if (response.redirect) {
              window.location.href = response.redirect;
            }

            $buttonRecoverOrder.removeClass('disabled').removeAttr('disabled', '');
          }
        });
      }

      $buttonRecoverOrder.on('click', recoverOrder);
      $input.on('change', function () {
        $buttonRecoverOrder.prop('disabled', !this.value);
      });
    }
  };
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=profile.js.map