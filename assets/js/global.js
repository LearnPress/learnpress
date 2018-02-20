/**
 * Common functions/utils used in all page
 */
if (typeof window.LP == 'undefined') {
	window.LP = window.LearnPress = {};
}
// jQuery cookie plugin
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		module.exports = factory(require('jquery'));
	} else {
		factory(jQuery);
	}
}(function ($) {
	var pluses = /\+/g;
	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}
	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}
	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}
	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}
		try {
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch (e) {
		}
	}
	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}
	var config = $.cookie = function (key, value, options) {
		if (arguments.length > 1 && !$.isFunction(value)) {
			options = $.extend({}, config.defaults, options);
			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
			}
			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '',
				options.path ? '; path=' + options.path : '',
				options.domain ? '; domain=' + options.domain : '',
				options.secure ? '; secure' : ''
			].join(''));
		}
		var result = key ? undefined : {},
			cookies = document.cookie ? document.cookie.split('; ') : [],
			i = 0,
			l = cookies.length;
		for (; i < l; i++) {
			var parts = cookies[i].split('='),
				name = decode(parts.shift()),
				cookie = parts.join('=');
			if (key === name) {
				result = read(cookie, value);
				break;
			}
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}
		return result;
	};
	config.defaults = {};
	$.removeCookie = function (key, options) {
		$.cookie(key, '', $.extend({}, options, {expires: -1}));
		return !$.cookie(key);
	};
}));
(function ($) {
	$.fn.serializeJSON = function () {
		var unIndexed = $(this).serializeArray(),
			indexed = {},
			validate = /(\[([a-zA-Z0-9_-]+)?\]?)/g,
			arrayKeys = {},
			end = false;
		$.each(unIndexed, function () {
			var that = this,
				match = this.name.match(/^([0-9a-zA-Z_-]+)/);
			if (!match) {
				return;
			}
			var keys = this.name.match(validate),
				objPath = "indexed['" + match[0] + "']";

			if (keys) {
				if (typeof indexed[match[0]] != 'object') {
					indexed[match[0]] = {};
				}

				$.each(keys, function (i, prop) {
					prop = prop.replace(/\]|\[/g, '');
					var rawPath = objPath.replace(/'|\[|\]/g, ''),
						objExp = '',
						preObjPath = objPath;

					if (prop == '') {
						if (arrayKeys[rawPath] == undefined) {
							arrayKeys[rawPath] = 0;
						} else {
							arrayKeys[rawPath]++;
						}
						objPath += "['" + arrayKeys[rawPath] + "']";
					} else {
						if (!isNaN(prop)) {
							arrayKeys[rawPath] = prop;
						}
						objPath += "['" + prop + "']";
					}
					try {
						if (i == keys.length - 1) {
							objExp = objPath + "=that.value;";
							end = true;
						} else {
							objExp = objPath + "={}";
							end = false;
						}

						var evalString = "" +
							"if( typeof " + objPath + " == 'undefined'){" + objExp + ";" +
							"}else{" +
							"if(end){" +
							"if(typeof " + preObjPath + "!='object'){" + preObjPath + "={};}" +
							objExp +
							"}" +
							"}";
						eval(evalString);
					} catch (e) {
						console.log('Error:' + e + "\n" + objExp);
					}
				})
			} else {
				indexed[match[0]] = this.value;
			}
		});
		return indexed;
	};
	$.fn.tooltip = function (options) {
		options = $.extend({}, {
			offset: [0, 0]
		}, options || {});
		return $.each(this, function () {
			var $el = $(this),
				content = $el.data('content');
			if (!content || ($el.data('tooltip') != undefined)) {
				return;
			}
			var $tooltip = null;
			$el.hover(function (e) {
				$tooltip = $('<div class="learn-press-tooltip-bubble"/>').html(content).appendTo($('body')).hide();
				var position = $el.offset();
				if ($.isArray(options.offset)) {
					var top = options.offset[1],
						left = options.offset[0];
					if ($.isNumeric(left)) {
						position.left += left;
					} else {

					}
					if ($.isNumeric(top)) {
						position.top += top;
					} else {

					}
				}
				$tooltip.css({
					top : position.top,
					left: position.left
				});
				$tooltip.fadeIn();
			}, function () {
				$tooltip && $tooltip.remove();
			});
			$el.data('tooltip', true);
		});
	};
	$.fn.hasEvent = function (name) {
		var events = $(this).data('events');
		if (typeof events.LP == 'undefined') {
			return false;
		}
		for (i = 0; i < events.LP.length; i++) {
			if (events.LP[i].namespace == name) {
				return true;
			}
		}
		return false;
	};
	$.fn.dataToJSON = function () {
		var json = {};
		$.each(this[0].attributes, function () {
			var m = this.name.match(/^data-(.*)/);
			if (m) {
				json[m[1]] = this.value;
			}
		});
		return json;
	};

	String.prototype.getQueryVar = function (name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(this);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};
	String.prototype.addQueryVar = function (name, value) {
		var url = this,
			m = url.split('#');
		url = m[0];
		if (name.match(/\[/)) {
			url += url.match(/\?/) ? '&' : '?';
			url += name + '=' + value;
		} else {
			if ((url.indexOf('&' + name + '=') != -1) || (url.indexOf('?' + name + '=') != -1)) {
				url = url.replace(new RegExp(name + "=([^&#]*)", 'g'), name + '=' + value);
			} else {
				url += url.match(/\?/) ? '&' : '?';
				url += name + '=' + value;
			}
		}
		return url + (m[1] ? '#' + m[1] : '');
	};
	String.prototype.removeQueryVar = function (name) {
		var url = this;
		var m = url.split('#');
		url = m[0];
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "([\[][^=]*)?=([^&#]*)", 'g');
		url = url.replace(regex, '');
		return url + (m[1] ? '#' + m[1] : '');
	};

	if ($.isEmptyObject("") == false) {
		$.isEmptyObject = function (a) {
			for (prop in a) {
				if (a.hasOwnProperty(prop)) {
					return false;
				}
			}
			return true;
		};
	}

	LP.MessageBox = {
		/*
		 *
		 */
		$block         : null,
		$window        : null,
		events         : {},
		instances      : [],
		instance       : null,
		quickConfirm   : function (elem, args) {
			var $e = $(elem);
			$('[learn-press-quick-confirm]').each(function () {
				( $ins = $(this).data('quick-confirm') ) && ( console.log($ins), $ins.destroy() );
			});
			!$e.attr('learn-press-quick-confirm') && $e.attr('learn-press-quick-confirm', 'true').data('quick-confirm',
				new (function (elem, args) {
					var $elem = $(elem),
						$div = $('<span class="learn-press-quick-confirm"></span>').insertAfter($elem), //($(document.body)),
						offset = $(elem).position() || {left: 0, top: 0},
						timerOut = null,
						timerHide = null,
						n = 3,
						hide = function () {
							$div.fadeOut('fast', function () {
								$(this).remove();
								$div.parent().css('position', '');
							});
							$elem.removeAttr('learn-press-quick-confirm').data('quick-confirm', undefined);
							stop();
						},
						stop = function () {
							timerHide && clearInterval(timerHide);
							timerOut && clearInterval(timerOut);
						},
						start = function () {
							timerOut = setInterval(function () {
								if (--n == 0) {
									hide.call($div[0]);
									$.isFunction(args.onCancel) && args.onCancel(args.data);
									stop();
								}
								$div.find('span').html(' (' + n + ')');
							}, 1000);

							timerHide = setInterval(function () {
								if (!$elem.is(':visible') || $elem.css("visibility") == 'hidden') {
									stop();
									$div.remove();
									$div.parent().css('position', '');
									$.isFunction(args.onCancel) && args.onCancel(args.data);
								}
							}, 350);
						};
					args = $.extend({
						message : '',
						data    : null,
						onOk    : null,
						onCancel: null,
						offset  : {top: 0, left: 0}
					}, args || {});
					$div.html(args.message || $elem.attr('data-confirm-remove') || 'Are you sure?').append('<span> (' + n + ')</span>').css({});
					$div.click(function () {
						$.isFunction(args.onOk) && args.onOk(args.data);
						hide();
					}).hover(function () {
						stop();
					}, function () {
						start();
					});
					//$div.parent().css('position', 'relative');
					$div.css({
						left: ( ( offset.left + $elem.outerWidth() ) - $div.outerWidth() ) + args.offset.left,
						top : offset.top + $elem.outerHeight() + args.offset.top + 5
					}).hide().fadeIn('fast');
					start();

					this.destroy = function () {
						$div.remove();
						$elem.removeAttr('learn-press-quick-confirm').data('quick-confirm', undefined);
						;
						stop();

					};
				})(elem, args)
			);
		},
		show           : function (message, args) {
			//this.hide();
			$.proxy(function () {
				args = $.extend({
					title   : '',
					buttons : '',
					events  : false,
					autohide: false,
					message : message,
					data    : false,
					id      : LP.uniqueId(),
					onHide  : null
				}, args || {});

				this.instances.push(args);
				this.instance = args;

				var $doc = $(document),
					$body = $(document.body);
				if (!this.$block) {
					this.$block = $('<div id="learn-press-message-box-block"></div>').appendTo($body);

				}
				if (!this.$window) {
					this.$window = $('<div id="learn-press-message-box-window"><div id="message-box-wrap"></div> </div>').insertAfter(this.$block);
					this.$window.click(function () {
					});
				}
				//this.events = args.events || {};
				this._createWindow(message, args.title, args.buttons);
				this.$block.show();
				this.$window.show().attr('instance', args.id);
				$(window)
					.bind('resize.message-box', $.proxy(this.update, this))
					.bind('scroll.message-box', $.proxy(this.update, this));
				this.update(true);
				if (args.autohide) {
					setTimeout(function () {
						LP.MessageBox.hide();
						$.isFunction(args.onHide) && args.onHide.call(LP.MessageBox, args);
					}, args.autohide);
				}
			}, this)();
		},
		blockUI        : function (message) {

			message = (message !== false ? ( message ? message : 'Wait a moment' ) : '') + '<div class="message-box-animation"></div>';
			this.show(message);
		},
		hide           : function (delay, instance) {
			if (instance) {
				this._removeInstance(instance.id);
			} else if (this.instance) {
				this._removeInstance(this.instance.id);
			}
			if (this.instances.length == 0) {
				if (this.$block) {
					this.$block.hide();
				}
				if (this.$window) {
					this.$window.hide();
				}
				$(window)
					.unbind('resize.message-box', this.update)
					.unbind('scroll.message-box', this.update);
			} else {
				if (this.instance) {
					this._createWindow(this.instance.message, this.instance.title, this.instance.buttons);
				}
			}

		},
		update         : function (force) {
			var that = this,
				$wrap = this.$window.find('#message-box-wrap'),
				timer = $wrap.data('timer'),
				_update = function () {
					LP.Hook.doAction('learn_press_message_box_before_resize', that);
					var $content = $wrap.find('.message-box-content').css("height", "").css('overflow', 'hidden'),
						width = $wrap.outerWidth(),
						height = $wrap.outerHeight(),
						contentHeight = $content.height(),
						windowHeight = $(window).height(),
						top = $wrap.offset().top;
					if (contentHeight > windowHeight - 50) {
						$content.css({
							height: windowHeight - 25
						});
						height = $wrap.outerHeight();
					} else {
						$content.css("height", "").css('overflow', '');
					}
					$wrap.css({
						marginTop: ( $(window).height() - height ) / 2
					});
					LP.Hook.doAction('learn_press_message_box_resize', height, that);
				};
			if (force) _update();
			timer && clearTimeout(timer);
			timer = setTimeout(_update, 250);
		},
		_removeInstance: function (id) {
			for (var i = 0; i < this.instances.length; i++) {
				if (this.instances[i].id == id) {

					this.instances.splice(i, 1);

					var len = this.instances.length;
					if (len) {
						this.instance = this.instances[len - 1];
						this.$window.attr('instance', this.instance.id);
					} else {
						this.instance = false;
						this.$window.removeAttr('instance');
					}
					break;
				}
			}
		},
		_getInstance   : function (id) {
			for (var i = 0; i < this.instances.length; i++) {
				if (this.instances[i].id == id) {
					return this.instances[i];
					break;
				}
			}
		},
		_createWindow  : function (message, title, buttons) {
			var $wrap = this.$window.find('#message-box-wrap').html('');
			if (title) {
				$wrap.append('<h3 class="message-box-title">' + title + '</h3>');
			}
			$wrap.append($('<div class="message-box-content"></div>').html(message));
			if (buttons) {
				var $buttons = $('<div class="message-box-buttons"></div>');
				switch (buttons) {
					case 'yesNo':
						$buttons.append(this._createButton(LP_Settings.localize.button_yes, 'yes'));
						$buttons.append(this._createButton(LP_Settings.localize.button_no, 'no'));
						break;
					case 'okCancel':
						$buttons.append(this._createButton(LP_Settings.localize.button_ok, 'ok'));
						$buttons.append(this._createButton(LP_Settings.localize.button_cancel, 'cancel'));
						break;
					default:
						$buttons.append(this._createButton(LP_Settings.localize.button_ok, 'ok'));
				}
				$wrap.append($buttons);
			}
		},
		_createButton  : function (title, type) {
			var $button = $('<button type="button" class="button message-box-button message-box-button-' + type + '">' + title + '</button>'),
				callback = 'on' + ( type.substr(0, 1).toUpperCase() + type.substr(1) );
			$button.data('callback', callback).click(function () {
				var instance = $(this).data('instance'),
					callback = instance.events[$(this).data('callback')];
				if ($.type(callback) == 'function') {
					if (callback.apply(LP.MessageBox, [instance]) === false) {
						return;
					} else {
						LP.MessageBox.hide(null, instance);
					}
				} else {
					LP.MessageBox.hide(null, instance);
				}
			}).data('instance', this.instance);
			return $button;
		}
	};
	LP.Hook = {
		hooks       : {action: {}, filter: {}},
		addAction   : function (action, callable, priority, tag) {
			this.addHook('action', action, callable, priority, tag);
			return this;
		},
		addFilter   : function (action, callable, priority, tag) {
			this.addHook('filter', action, callable, priority, tag);
			return this;
		},
		doAction    : function (action) {
			this.doHook('action', action, arguments);
			return this;
		},
		applyFilters: function (action) {
			return this.doHook('filter', action, arguments);
		},
		removeAction: function (action, tag) {
			this.removeHook('action', action, tag);
			return this;
		},
		removeFilter: function (action, priority, tag) {
			this.removeHook('filter', action, priority, tag);
			return this;
		},
		addHook     : function (hookType, action, callable, priority, tag) {
			if (undefined == this.hooks[hookType][action]) {
				this.hooks[hookType][action] = [];
			}
			var hooks = this.hooks[hookType][action];
			if (undefined == tag) {
				tag = action + '_' + hooks.length;
			}
			this.hooks[hookType][action].push({tag: tag, callable: callable, priority: priority});
			return this;
		},
		doHook      : function (hookType, action, args) {

			// splice args from object into array and remove first index which is the hook name
			args = Array.prototype.slice.call(args, 1);

			if (undefined != this.hooks[hookType][action]) {
				var hooks = this.hooks[hookType][action], hook;
				//sort by priority
				hooks.sort(function (a, b) {
					return a["priority"] - b["priority"];
				});
				for (var i = 0; i < hooks.length; i++) {
					hook = hooks[i].callable;
					if (typeof hook != 'function')
						hook = window[hook];
					if ('action' == hookType) {
						hook.apply(null, args);
					} else {
						args[0] = hook.apply(null, args);
					}
				}
			}
			if ('filter' == hookType) {
				return args[0];
			}
			return this;
		},
		removeHook  : function (hookType, action, priority, tag) {
			if (undefined != this.hooks[hookType][action]) {
				var hooks = this.hooks[hookType][action];
				for (var i = hooks.length - 1; i >= 0; i--) {
					if ((undefined == tag || tag == hooks[i].tag) && (undefined == priority || priority == hooks[i].priority)) {
						hooks.splice(i, 1);
					}
				}
			}
			return this;
		}
	};
	LP = $.extend({
		setUrl            : function (url, ember, title) {
			if (url) {
				history.pushState({}, title, url);
				LP.Hook.doAction('learn_press_set_location_url', url);
			}
		},
		toggleGroupSection: function (el, target) {
			var $el = $(el),
				isHide = $el.hasClass('hide-if-js');
			if (isHide) {
				$el.hide().removeClass('hide-if-js');
			}
			$el.removeClass('hide-if-js').slideToggle(function () {
				var $this = $(this);
				if ($this.is(':visible')) {
					$(target).addClass('toggle-on').removeClass('toggle-off');
				} else {
					$(target).addClass('toggle-off').removeClass('toggle-on');
				}
			});
		},
		overflow          : function (el, v) {
			var $el = $(el),
				overflow = $el.css('overflow');
			if (v) {
				$el.css('overflow', v).data('overflow', overflow);
			} else {
				$el.css('overflow', $el.data('overflow'));
			}
		},
		getUrl            : function () {
			return window.location.href;
		},
		addQueryVar       : function (name, value, url) {
			return (url == undefined ? window.location.href : url).addQueryVar(name, value);
		},
		removeQueryVar    : function (name, url) {
			return (url == undefined ? window.location.href : url).removeQueryVar(name);
		},
		reload            : function (url) {
			if (!url) {
				url = window.location.href;
			}
			window.location.href = url;
		},

		parseResponse: function (response, type) {
			var m = response.match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/);
			if (m) {
				response = m[1];
			}
			return (type || "json") == "json" ? this.parseJSON(response) : response;
		},
		parseJSON    : function (data) {
			var m = data.match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/);
			try {
				if (m) {
					data = $.parseJSON(m[1]);
				} else {
					data = $.parseJSON(data);
				}
			} catch (e) {
				data = {};
			}
			return data;
		},
		ajax         : function (args) {
			var type = args.type || 'post',
				dataType = args.dataType || 'json',
				data = args.action ? $.extend(args.data, {'lp-ajax': args.action}) : args.data,
				beforeSend = args.beforeSend || function () {
					},
				url = args.url || window.location.href;
//                        console.debug( beforeSend );
			$.ajax({
				data      : data,
				url       : url,
				type      : type,
				dataType  : 'html',
				beforeSend: beforeSend.apply(null, args),
				success   : function (raw) {
					var response = LP.parseResponse(raw, dataType);
					$.isFunction(args.success) && args.success(response, raw);
				},
				error     : function () {
					$.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
				}
			});
		},
		doAjax       : function (args) {
			var type = args.type || 'post',
				dataType = args.dataType || 'json',
				action = ( ( args.prefix == undefined ) || 'learnpress_') + args.action,
				data = args.action ? $.extend(args.data, {action: action}) : args.data;

			$.ajax({
				data    : data,
				url     : ( args.url || window.location.href ),
				type    : type,
				dataType: 'html',
				success : function (raw) {
					var response = LP.parseResponse(raw, dataType);
					$.isFunction(args.success) && args.success(response, raw);
				},
				error   : function () {
					$.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
				}
			});
		},

		funcArgs2Array   : function (args) {
			var arr = [];
			for (var i = 0; i < args.length; i++) {
				arr.push(args[i]);
			}
			return arr;
		},
		addFilter        : function (action, callback) {
			var $doc = $(document),
				event = 'LP.' + action;
			$doc.on(event, callback);
			LP.log($doc.data('events'));
			return this;
		},
		applyFilters     : function () {
			var $doc = $(document),
				action = arguments[0],
				args = this.funcArgs2Array(arguments);
			if ($doc.hasEvent(action)) {
				args[0] = 'LP.' + action;
				return $doc.triggerHandler.apply($doc, args);
			}
			return args[1];
		},
		addAction        : function (action, callback) {
			return this.addFilter(action, callback);
		},
		doAction         : function () {
			var $doc = $(document),
				action = arguments[0],
				args = this.funcArgs2Array(arguments);
			if ($doc.hasEvent(action)) {
				args[0] = 'LP.' + action;
				$doc.trigger.apply($doc, args);
			}
		},
		toElement        : function (element, args) {
			if ($(element).length == 0) {
				return;
			}
			args = $.extend({
				delay   : 300,
				duration: 'slow',
				offset  : 50,
				callback: null
			}, args || {});
			$('body, html')
				.fadeIn(10)
				.delay(args.delay)
				.animate({
					scrollTop: $(element).offset().top - args.offset
				}, args.duration, args.callback);
		},
		uniqueId         : function (prefix, more_entropy) {
			if (typeof prefix === 'undefined') {
				prefix = '';
			}

			var retId;
			var formatSeed = function (seed, reqWidth) {
				seed = parseInt(seed, 10)
					.toString(16); // to hex str
				if (reqWidth < seed.length) { // so long we split
					return seed.slice(seed.length - reqWidth);
				}
				if (reqWidth > seed.length) { // so short we pad
					return Array(1 + (reqWidth - seed.length))
							.join('0') + seed;
				}
				return seed;
			};

			// BEGIN REDUNDANT
			if (!this.php_js) {
				this.php_js = {};
			}
			// END REDUNDANT
			if (!this.php_js.uniqidSeed) { // init seed with big random int
				this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
			}
			this.php_js.uniqidSeed++;

			retId = prefix; // start with prefix, add current milliseconds hex string
			retId += formatSeed(parseInt(new Date()
					.getTime() / 1000, 10), 8);
			retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
			if (more_entropy) {
				// for more entropy we add a float lower to 10
				retId += (Math.random() * 10)
					.toFixed(8)
					.toString();
			}

			return retId;
		},
		log              : function () {
			//if (typeof LEARN_PRESS_DEBUG != 'undefined' && LEARN_PRESS_DEBUG && console) {
			for (var i = 0, n = arguments.length; i < n; i++) {
				console.log(arguments[i]);
			}
			//}
		},
		blockContent     : function () {
			if ($('#learn-press-block-content').length == 0) {
				$(LP.template('learn-press-template-block-content', {})).appendTo($('body'));
			}
			LP.hideMainScrollbar().addClass('block-content');
			$(document).trigger('learn_press_block_content');
		},
		unblockContent   : function () {
			setTimeout(function () {
				LP.showMainScrollbar().removeClass('block-content');
				$(document).trigger('learn_press_unblock_content');
			}, 350);
		},
		hideMainScrollbar: function (el) {
			if (!el) {
				el = 'html, body';
			}
			var $el = $(el);
			$el.each(function () {
				var $root = $(this),
					overflow = $root.css('overflow');
				$root.css('overflow', 'hidden').attr('overflow', overflow);
			});
			return $el;
		},
		showMainScrollbar: function (el) {
			if (!el) {
				el = 'html, body';
			}
			var $el = $(el);
			$el.each(function () {
				var $root = $(this),
					overflow = $root.attr('overflow');
				$root.css('overflow', overflow).removeAttr('overflow');
			});
			return $el;
		},
		template         : _.memoize(function (id, data) {
			var compiled,
				options = {
					evaluate   : /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape     : /\{\{([^\}]+?)\}\}(?!\})/g,
					variable   : 'data'
				};

			var tmpl = function (data) {
				compiled = compiled || _.template($('#' + id).html(), null, options);
				return compiled(data);
			};
			return data ? tmpl(data) : tmpl;
		}, function (a, b) {
			return a + '-' + JSON.stringify(b);
		}),
		alert            : function (localize, callback) {
			var title = '',
				message = '';
			if (typeof localize == 'string') {
				message = localize;
			} else {
				if (typeof localize['title'] != 'undefined') {
					title = localize['title'];
				}
				if (typeof localize['message'] != 'undefined') {
					message = localize['message'];
				}
			}
			$.alerts.alert(message, title, function (e) {
				LP._on_alert_hide();
				callback && callback(e);
			});
			this._on_alert_show();
		},
		confirm          : function (localize, callback) {
			var title = '',
				message = '';

			if (typeof localize == 'string') {
				message = localize;
			} else {
				if (typeof localize['title'] != 'undefined') {
					title = localize['title'];
				}
				if (typeof localize['message'] != 'undefined') {
					message = localize['message'];
				}
			}
			$.alerts.confirm(message, title, function (e) {
				LP._on_alert_hide();
				callback && callback(e);
			});
			this._on_alert_show();

		},
		_on_alert_show   : function () {
			var $container = $('#popup_container'),
				$placeholder = $('<span id="popup_container_placeholder" />').insertAfter($container).data('xxx', $container);
			$container.stop().css('top', '-=50').css('opacity', '0').animate({
				top    : '+=50',
				opacity: 1
			}, 250);
		},
		_on_alert_hide   : function () {
			var $holder = $("#popup_container_placeholder"),
				$container = $holder.data('xxx');
			if ($container) {
				$container.replaceWith($holder);
			}
			$container.appendTo($(document.body))
			$container.stop().animate({
				top    : '+=50',
				opacity: 0
			}, 250, function () {
				$(this).remove();
			});
		},
		sendMessage      : function (data, object, targetOrigin, transfer) {
			if ($.isPlainObject(data)) {
				data = JSON.stringify(data);
			}
			object = object || window;
			targetOrigin = targetOrigin || '*';
			object.postMessage(data, targetOrigin, transfer);
		},
		receiveMessage   : function (event, b) {
			var target = event.origin || event.originalEvent.origin,
				data = event.data || event.originalEvent.data || '';
			if (typeof data === 'string' || data instanceof String) {
				if (data.indexOf('{') == 0) {
					data = LP.parseJSON(data);
				}
			}
			LP.Hook.doAction('learn_press_receive_message', data, target);
		}
	}, LP);

	$.fn.rows = function () {
		var h = $(this).height();
		var lh = $(this).css('line-height').replace("px", "");
		$(this).attr({height: h, 'line-height': lh});
		return Math.floor(h / parseInt(lh));
	};

	$.fn.checkLines = function (p) {
		return this.each(function () {
			var $e = $(this),
				rows = $e.rows();

			p.call(this, rows);
		});
	};

	$.fn.findNext = function (selector) {
		var $selector = $(selector),
			$root = this.first(),
			index = $selector.index($root),
			$next = $selector.eq(index + 1);
		return $next.length ? $next : false;
	};

	$.fn.findPrev = function (selector) {
		var $selector = $(selector),
			$root = this.first(),
			index = $selector.index($root),
			$prev = $selector.eq(index - 1);
		return $prev.length ? $prev : false;
	};

	$.each(['progress'], function (i, property) {
		$.Tween.propHooks[property] = {
			get: function (tween) {
				return $(tween.elem).css('transform');
			},
			set: function (tween) {
				/*var style = tween.elem.style;
				 var p_begin = parseColor($(tween.elem).css(property));
				 var p_end = parseColor(tween.end);
				 tween.run = function(progress) {
				 style[property] = calculateColor(p_begin, p_end, progress);
				 }*/
				if (tween.now < 180) {
					$(this).find('.progress-circle').removeClass('gt-50');
				} else {
					$(this).find('.progress-circle').addClass('gt-50');
				}
				$(tween.elem).find('.fill').css({
					transform: 'rotate(' + tween.end + 'deg)'
				});
			}
		};
	});

	$.fn.progress = function (v) {
		return this.each(function () {
			var t = parseInt(v / 100 * 360),
				timer = null,
				$this = $(this);

			if (t < 180) {
				$this.find('.progress-circle').removeClass('gt-50');
			} else {
				$this.find('.progress-circle').addClass('gt-50');
			}
			$this.find('.fill').css({
				transform: 'rotate(' + t + 'deg)'
			});

		});
	};

	function __initSubtabs() {
		$('.learn-press-subtabs').each(function () {
			var $tabContainer = $(this),
				$tabs = $tabContainer.find('a'),
				current = null;
			$tabs.click(function (e) {
				var $tab = $(this),
					$contentID = $tab.attr('href');
				$tab.parent().addClass('current').siblings().removeClass('current');
				current = $($contentID).addClass('current');
				current.siblings().removeClass('current');
				//LP.setUrl($contentID);
				e.preventDefault();
			}).filter(function () {
				return $(this).attr('href') == window.location.hash;
			}).trigger('click');
			if (!current) {
				$tabs.first().trigger('click');
			}
		});
	}

	$(document).ready(function () {
		if (typeof $.alerts != 'undefined') {
			$.alerts.overlayColor = '#000';
			$.alerts.overlayOpacity = 0.5;
		}

		$('body')
			.on('click', '.learn-press-nav-tabs li a', function (e) {
				e.preventDefault();
				var $tab = $(this);
				$tab.closest('li').addClass('active').siblings().removeClass('active');
				$($tab.attr('data-tab')).addClass('active').siblings().removeClass('active');
			});
		$('.learn-press-nav-tabs li.active a').trigger('click');

		///
		(function () {
			var timer = null,
				callback = function () {
					$('.auto-check-lines').checkLines(function (r) {
						if (r > 1) {
							$(this).removeClass('single-lines');
						} else {
							$(this).addClass('single-lines');
						}
						$(this).attr('rows', r);
					});
				};
			$(window).on('resize.check-lines', function () {
				if (timer) {
					timer && clearTimeout(timer);
					timer = setTimeout(callback, 300);
				} else {
					callback();
				}
			});
		})();

		$(document).on('click', '[data-block-content="yes"]', function () {
			LP.blockContent();
		});

		$('.learn-press-tooltip, .lp-passing-conditional').tooltip({offset: [24, 24]});

		$('.learn-press-icon').tooltip({offset: [30, 30]});

		$('.learn-press-message[data-autoclose]').each(function () {
			var $el = $(this), delay = parseInt($el.data('autoclose'));
			if (delay) {
				setTimeout(function ($el) {
					$el.fadeOut();
				}, delay, $el);
			}
		});

		//$(window).on("message onmessage", LP.receiveMessage, false);
		window.addEventListener("message", LP.receiveMessage, false);
	});
	LearnPress = LP;
})(jQuery);