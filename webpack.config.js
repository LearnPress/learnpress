const path = require('path');
const webpack = require('webpack');
// const ExtractTextPlugin = require('extract-text-webpack-plugin');
// // Set different CSS extraction for editor only and common block styles
// const blocksCSSPlugin = new ExtractTextPlugin({
//     filename: './assets/css/main.css',
// });

const tools = require('./tools/webpack');

// Configuration for the ExtractTextPlugin.
// const extractConfig = {
//     use: [
//         {loader: 'raw-loader'},
//         {
//             loader: 'postcss-loader',
//             options: {
//                 plugins: [require('autoprefixer')],
//             },
//         },
//         {
//             loader: 'sass-loader',
//             query: {
//                 outputStyle: 'production' === process.env.NODE_ENV ? 'compressed' : 'nested'
//             },
//         },
//     ],
// };

module.exports = {
    entry: {
        './assets/js/admin/admin': './assets/src/js/admin/admin.js',
        './assets/js/admin/learnpress': './assets/src/js/admin/learnpress.js',
        './assets/js/admin/utils': './assets/src/js/admin/utils/index.js',
        './assets/js/admin/editor/course': './assets/src/js/admin/editor/course.js',
        './assets/js/admin/editor/quiz': './assets/src/js/admin/editor/quiz.js',
        './assets/js/admin/editor/question': './assets/src/js/admin/editor/question.js',
        './assets/js/admin/conditional-logic': './assets/src/js/admin/utils/conditional-logic.js',
        './assets/js/admin/partial/meta-box-order': './assets/src/js/admin/partial/meta-box-order.js',
        './assets/js/admin/pages/statistic': './assets/src/js/admin/pages/statistic.js',
        './assets/js/admin/pages/setup': './assets/src/js/admin/pages/setup.js',
        './assets/js/frontend/learnpress': './assets/src/js/frontend/learnpress.js',
        './assets/js/frontend/utils': './assets/src/js/frontend/utils/index.js',
        './assets/js/global': './assets/src/js/global.js',
        './assets/js/utils': './assets/src/js/utils/index.js',
        './assets/js/frontend/courses': './assets/src/js/frontend/courses.js',
        //'./assets/js/frontend/single-course': './assets/src/js/frontend/single-course.js',
    },
    output: {
        path: path.resolve(__dirname),
        filename: 'production' === process.env.NODE_ENV ? '[name].min.js' : '[name].js',
    },
    watch: false,//'production' !== process.env.NODE_ENV,
    devtool: process.env.NODE_ENV === 'production' ? '' : 'source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        //presets: ['babel-preset-env', 'es2015']
                        presets: [
                            "@babel/preset-env",
                            // "@babel/preset-react",
                            // "@babel/env",
                            // "@babel/react"
                        ]
                    }
                },
            },
            // {
            //     test: /([a-zA-Z0-9\s_\\.\-\(\):])+(.s?css)$/,
            //     use: blocksCSSPlugin.extract(extractConfig),
            // },
        ],
    },
    plugins: [
        //blocksCSSPlugin,
        tools.mergeAndCompressJs
    ]
};