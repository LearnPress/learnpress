const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

// Set different CSS extraction for editor only and common block styles
const blocksCSSPlugin = new ExtractTextPlugin({
    filename: './assets/css/main.css',
});

// Configuration for the ExtractTextPlugin.
const extractConfig = {
    use: [
        {loader: 'raw-loader'},
        {
            loader: 'postcss-loader',
            options: {
                plugins: [require('autoprefixer')],
            },
        },
        {
            loader: 'sass-loader',
            query: {
                outputStyle: 'production' === process.env.NODE_ENV ? 'compressed' : 'nested'
            },
        },
    ],
};


module.exports = {
    entry: {
        './assets/js/admin/admin.min': './assets/src/js/admin/admin.js',
        './assets/js/admin/learnpress.min': './assets/src/js/admin/learnpress.js',
        './assets/js/admin/utils.min': './assets/src/js/admin/utils/index.js',
        './assets/js/utils.min': './assets/src/js/utils/index.js',
        //'./assets/js/admin/integration': './src/admin/integration.dev.js',
    },
    output: {
        path: path.resolve(__dirname),
        filename: '[name].js',
    },
    watch: 'production' !== process.env.NODE_ENV,
    devtool: process.env.NODE_ENV === 'production' ? '' : 'source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['babel-preset-env']
                    }
                },
            },
            {
                test: /([a-zA-Z0-9\s_\\.\-\(\):])+(.s?css)$/,
                use: blocksCSSPlugin.extract(extractConfig),
            },
        ],
    },
    plugins: [
        blocksCSSPlugin,
    ]
};