const Cookies = {
    get: (name, def, global) => {
        var ret;

        if (global) {
            ret = wpCookies.get(name);
        } else {
            var ck = wpCookies.get('LP');

            if (ck) {
                ck = JSON.parse(ck);
                ret = ck[name];
            }
        }

        if (!ret && ret !== def) {
            ret = def;
        }

        return ret;
    },

    set: function (name, value, expires, domain, path, secure) {

        if (arguments.length > 2) {
            wpCookies.set(name, value, expires, domain, path, secure)
        } else {
            var ck = wpCookies.get('LP');

            if (ck) {
                ck = JSON.parse(ck);
            } else {
                ck = {};
            }

            ck[name] = value;

            wpCookies.set('LP', JSON.stringify(ck))
        }
    }
};

export default Cookies;