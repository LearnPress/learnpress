const _localStorage = {
    __key: 'LP',
    set: function (name, value) {
        const data = this.get();
        const {set} = lodash;

        set(data, name, value)

        localStorage.setItem(this.__key, JSON.stringify(data));
    },

    get: function (name, def) {
        const data = JSON.parse(localStorage.getItem(this.__key) || "{}");
        const {get} = lodash;

        return !name ? data : ( get(data, name) || def );
    },

    exists: function (name) {
        const data = this.get();

        return data.hasOwnProperty(name);
    },

    remove: function (name) {
        const data = this.get();
        const newData = lodash.omit(data, name);

        this.__set(newData);
    },
    __get: function () {
        return localStorage.getItem(this.__key);
    },
    __set: function (data) {
        localStorage.setItem(this.__key, JSON.stringify(data || "{}"));
    }
};

export default _localStorage;