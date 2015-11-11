/**
 * Common functions/utils used in all page
 */
if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;
(function ($) {
	$.fn.hasEvent = function (name) {
		var events = $(this).data('events');
		if (typeof events.LearnPress == 'undefined') {
			return false;
		}
		for (i = 0; i < events.LearnPress.length; i++) {
			if (events.LearnPress[i].namespace == name) {
				return true;
			}
		}
		return false;
	}
	$.fn.dataToJSON = function(){
		var json = {};
		$.each(this[0].attributes, function(){
			var m = this.name.match(/^data-(.*)/);
			if( m ) {
				json[m[1]] = this.value;
			}
		});
		return json;
	}
	LearnPress.MessageBox = {
		/*
		 *
		 */
		$block       : null,
		$window      : null,
		events       : {},
		instances	 : [],
		instance: null,
		show         : function (message, args) {
			//this.hide();
			$.proxy(function () {
				args = $.extend({
					title: '',
					buttons: '',
					events: false,
					autohide: false,
					message: message,
					data: false,
					id: LearnPress.uniqueId(),
					onHide: null
				}, args || {});

				this.instances.push(args)
				this.instance = args;

				var $doc = $(document),
					$body = $(document.body);
				if (!this.$block) {
					this.$block = $('<div id="learn-press-message-box-block"></div>').appendTo($body);

				}
				if (!this.$window) {
					this.$window = $('<div id="learn-press-message-box-window"><div id="message-box-wrap"></div> </div>').insertAfter(this.$block);
					this.$window.click(function(){
					})
				}
				//this.events = args.events || {};
				this._createWindow(message, args.title, args.buttons);
				this.$block.show();
				this.$window.show().attr('instance', args.id);
				$(window)
					.bind('resize.message-box', $.proxy(this.update, this))
					.bind('scroll.message-box', $.proxy(this.update, this));
				this.update(true);
				if( args.autohide ){
					setTimeout(function(){
						LearnPress.MessageBox.hide();
						$.isFunction( args.onHide ) && args.onHide.call(LearnPress.MessageBox, args);
					}, args.autohide)
				}
			}, this)()
		},
		blockUI      : function (message) {

			message = (message !== false ? ( message ? message : 'Wait a moment' ) : '') + '<div class="message-box-animation"></div>';
			this.show(message);
		},
		hide         : function (delay, instance) {
			if( instance ){
				this._removeInstance(instance.id);
			}else {
				this._removeInstance(this.instance.id);
			}
			if(this.instances.length == 0) {
				if (this.$block) {
					this.$block.hide();
				}
				if (this.$window) {
					this.$window.hide();
				}
				$(window)
					.unbind('resize.message-box', this.update)
					.unbind('scroll.message-box', this.update);
			}else{
				this._createWindow( this.instance.message, this.instance.title, this.instance.buttons)
			}

		},
		update       : function (force) {
			var $wrap = this.$window.find('#message-box-wrap'),
				width = $wrap.outerWidth(),
				height = $wrap.outerHeight(),
				top = $wrap.offset().top,
				timer = $wrap.data('timer'),
				_update = function () {
					$wrap.css({
						marginTop: ( $(window).height() - height ) / 2
					});
				};
			if (force) _update();
			timer && clearTimeout(timer);
			timer = setTimeout(_update, 250);
		},
		_removeInstance: function(id){
			for( var i = 0; i < this.instances.length; i++){
				if( this.instances[i].id == id ){

					this.instances.splice(i, 1);

					var len = this.instances.length;
					if(len){
						this.instance = this.instances[len-1];
						this.$window.attr('instance', this.instance.id)
					}else{
						this.instance = false;
						this.$window.removeAttr('instance')
					}
					break;
				}
			}
		},
		_getInstance: function(id){
			for( var i = 0; i < this.instances.length; i++){
				if( this.instances[i].id == id ){
					return this.instances[i];
					break;
				}
			}
		},
		_createWindow: function (message, title, buttons) {
			var $wrap = this.$window.find('#message-box-wrap').html('');
			if (title) {
				$wrap.append('<h3 class="message-box-title">' + title + '</h3>');
			}
			$wrap.append( $( '<div class="message-box-content"></div>').html(message) );
			if (buttons) {
				var $buttons = $('<div class="message-box-buttons"></div>');
				switch (buttons) {
					case 'yesNo':
						$buttons.append(this._createButton('Yes', 'yes'));
						$buttons.append(this._createButton('No', 'no'));
						break;
					case 'okCancel':
						$buttons.append(this._createButton('Ok', 'ok'));
						$buttons.append(this._createButton('Cancel', 'cancel'));
						break;
					default:
						$buttons.append(this._createButton('Ok', 'ok'));
				}
				$wrap.append($buttons);
			}
		},
		_createButton: function (title, type) {
			var $button = $('<button type="button" class="button message-box-button message-box-button-' + type + '">' + title + '</button>'),
				callback = 'on' + ( type.substr(0, 1).toUpperCase() + type.substr(1) );
			$button.data('callback', callback).click(function () {
				var instance = $(this).data('instance'),
					callback = instance.events[$(this).data('callback')];
				if ($.type(callback) == 'function') {
					if (callback.apply(LearnPress.MessageBox, [instance]) === false) {
						return;
					}else{
						LearnPress.MessageBox.hide(null, instance);
					}
				}else {
					LearnPress.MessageBox.hide(null, instance);
				}
			}).data('instance', this.instance);
			return $button;
		}
	}
	LearnPress.Hook = {
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
					return a["priority"] - b["priority"]
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
	LearnPress = $.extend({
		setUrl        : function (url, title) {
			history.pushState({}, title, url);
		},
		reload        : function (url) {
			if (!url) {
				url = window.location.href;
			}
			window.location.href = url;
		},
		parseJSON     : function (data) {
			var m = data.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/);
			try {
				if (m) {
					data = $.parseJSON(m[1]);
				} else {
					data = $.parseJSON(data);
				}
			} catch (e) {
				console.log(e);
				data = {};
			}
			return data;
		},
		funcArgs2Array: function (args) {
			var arr = [];
			for (var i = 0; i < args.length; i++) {
				arr.push(args[i]);
			}
			return arr;
		},
		addFilter     : function (action, callback) {
			var $doc = $(document),
				event = 'LearnPress.' + action;
			$doc.on(event, callback);
			console.log($doc.data('events'))
			return this
		},
		applyFilters  : function () {
			var $doc = $(document),
				action = arguments[0],
				args = this.funcArgs2Array(arguments);
			if ($doc.hasEvent(action)) {
				args[0] = 'LearnPress.' + action;
				return $doc.triggerHandler.apply($doc, args);
			}
			return args[1];
		},
		addAction     : function (action, callback) {
			return this.addFilter(action, callback);
		},
		doAction      : function () {
			var $doc = $(document),
				action = arguments[0],
				args = this.funcArgs2Array(arguments);
			if ($doc.hasEvent(action)) {
				args[0] = 'LearnPress.' + action;
				$doc.trigger.apply($doc, args);
			}
		},
		toElement     : function (element, args) {
			args = $.extend({
				delay   : 300,
				duration: 'slow',
				offset  : 50
			}, args || {});
			$('body, html')
				.fadeIn(10)
				.delay(args.delay)
				.animate({
					scrollTop: $(element).offset().top - args.offset
				}, args.duration);
		},
		uniqueId      : function (prefix, more_entropy) {
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
		parse_json    : function (data) {
			console.log('LearnPress.parse_json has deprecated, use LearnPress.parseJSON instead of')
			return LearnPress.parseJSON(data);
		}
	}, LearnPress);
})(jQuery);