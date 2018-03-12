if (!Object.prototype.watchChange) {
    var isFunction = function (fn) {
        return fn && {}.toString.call(fn) === '[object Function]';
    };
    Object.defineProperty(
        Object.prototype,
        'watchChange',
        {
            enumerable: false,
            configurable: true,
            writable: false,
            value: function (prop, handler) {
                var obj = this;

                function x(prop, handler) {
                    var oldval = obj[prop],
                        newval = oldval,
                        getter = function () {
                            return newval;
                        },
                        setter = function (val) {
                            return newval = handler.call(obj, prop, oldval, val);
                        };

                    if (delete obj[prop]) {
                        Object.defineProperty(
                            obj,
                            prop,
                            {
                                get: getter,
                                set: setter,
                                enumerable: true,
                                configurable: true
                            }
                        );
                    }
                }

                if (isFunction(prop)) {
                    for (var k in this) {
                        new x(k, prop);
                    }
                } else {
                    new x(prop, handler)
                }
            }
        });
}

if (!Object.prototype.unwatchChange) {
    Object.defineProperty(
        Object.prototype,
        'unwatchChange',
        {
            enumerable: false,
            configurable: true,
            writable: false,
            value: function (prop) {
                var val = this[prop];
                delete this[prop];
                this[prop] = val;
            }
        }
    );
}