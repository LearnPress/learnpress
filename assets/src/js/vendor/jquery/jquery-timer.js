(function (e) {
	e.backward_timer = function (t) {
		var n = {seconds: 5, step: 1, format: "h%:m%:s%", value_setter: undefined, on_exhausted: function (e) {
		}, on_tick      : function (e) {
		}}, r = this;
		r.seconds_left = 0;
		r.target = e(t);
		r.timeout = undefined;
		r.settings = {};
		r.methods = {init     : function (t) {
			r.settings = e.extend({}, n, t);
			if (r.settings.value_setter == undefined) {
				if (r.target.is("input")) {
					r.settings.value_setter = "val"
				} else {
					r.settings.value_setter = "text"
				}
			}
			r.methods.reset()
		}, start              : function () {
			if (r.timeout == undefined) {
				var e = r.seconds_left == r.settings.seconds ? 0 : r.settings.step * 1e3;
				setTimeout(r.methods._on_tick, e, e)
			}
		}, cancel             : function () {
			if (r.timeout != undefined) {
				clearTimeout(r.timeout);
				r.timeout = undefined
			}
		}, reset              : function () {
			r.seconds_left = r.settings.seconds;
			r.methods._render_seconds()
		}, _on_tick           : function (e) {
			if (e != 0) {
				r.settings.on_tick(r)
			}
			r.methods._render_seconds();
			if (r.seconds_left > 0) {
				if (r.seconds_left < r.settings.step) {
					var t = r.seconds_left
				} else {
					var t = r.settings.step
				}
				r.seconds_left -= t;
				var n = t * 1e3;
				r.timeout = setTimeout(r.methods._on_tick, n, n)
			} else {
				r.timeout = undefined;
				r.settings.on_exhausted(r)
			}
		}, _render_seconds    : function () {
			var e = r.methods._seconds_to_dhms(r.seconds_left), t = r.settings.format;
			if (t.indexOf("d%") !== -1) {
				t = t.replace("d%", e.d).replace("h%", r.methods._check_leading_zero(e.h))
			} else {
				t = t.replace("h%", e.d * 24 + e.h)
			}
			t = t.replace("m%", r.methods._check_leading_zero(e.m)).replace("s%", r.methods._check_leading_zero(e.s));
			r.target[r.settings.value_setter](t)
		}, _seconds_to_dhms   : function (e) {
			var t = Math.floor(e / (24 * 3600)), e = e - t * 24 * 3600, n = Math.floor(e / 3600), e = e - n * 3600, r = Math.floor(e / 60), i = Math.floor(e - r * 60);
			return{d: t, h: n, m: r, s: i}
		}, _check_leading_zero: function (e) {
			return e < 10 ? "0" + e : "" + e
		}}
	};
	e.fn.backward_timer = function (t) {
		var n = arguments;
		return this.each(function () {
			var r = e(this).data("backward_timer");
			if (r == undefined) {
				r = new e.backward_timer(this);
				e(this).data("backward_timer", r)
			}
			if (r.methods[t]) {
				return r.methods[t].apply(this, Array.prototype.slice.call(n, 1))
			} else if (typeof t === "object" || !t) {
				return r.methods.init.apply(this, n)
			} else {
				e.error("Method " + t + " does not exist on jQuery.backward_timer")
			}
		})
	}
})(jQuery)
