module.exports = function (api) {
    api.cache(true);
    // const presets = [
    //     "@babel/preset-env",
    //     "@babel/preset-react",
    //     "@babel/env",
    //     "@babel/react"
    // ];

    const presets = [
        "es2015",
        "env",
        "react",
        "stage-0"
    ];



    const plugins = [
        "@babel/plugin-proposal-class-properties"
    ];

    return {
        presets,
        plugins
    };
}