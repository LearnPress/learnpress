const path = require( 'path' );
const webpack = require( 'webpack' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const CustomTemplatedPathPlugin = require( '@wordpress/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const LearnPressCustomTemplatedPathPlugin = require( '@learnpress/custom-templated-path-webpack-plugin' );
const LearnPressDependencyExtractionWebpackPlugin = require( '@learnpress/dependency-extraction-webpack-plugin' );
const MergeIntoSingleFilePlugin = require( 'webpack-merge-and-include-globally' );

const { get, escapeRegExp, compact } = require( 'lodash' );
const { basename, sep } = require( 'path' );
const args = require( 'yargs' ).argv;

const baseDir = path.join( __dirname, '../' );
const buildPath = __dirname + '/build';
const { camelCaseDash } = require( '@wordpress/dependency-extraction-webpack-plugin/lib/util' );
const packageDir = baseDir + '/assets/src/apps';
const buildPackages = [

	// Global
	'data-controls',

	// Admin
	//'admin/react/data-controls',
	//'admin/react/question-editor',

	// Frontend
	'frontend/modal',
	'frontend/courses',
	'frontend/single-course',
	'frontend/single-curriculum',
	'frontend/question-types',
	'frontend/lesson',
	'frontend/quiz',
	'frontend/lp-configs',
	//'frontend/data-controls',
	'frontend/custom',
	'frontend/profile',
	'frontend/widgets',
	'blocks/index',
];

const {
	NODE_ENV: mode = 'development',
	WP_DEVTOOL: devtool = ( mode === 'production' ? false : 'source-map' ),
} = process.env;
const isDev = mode !== 'production';
const isWatch = !! args.watch;

module.exports = function( env = { environment: 'production', watch: false, buildTarget: false } ) {
	const config = {
		mode,
		entry: buildPackages.reduce( ( memo, slug ) => {
			const basename = path.basename( slug );
			let name = camelCaseDash( basename );
			if ( 'lpConfigs' === name ) {
				name = 'config';
			}
			memo[ name ] = path.resolve( packageDir, `js/${ slug }.js` );
			return memo;
		}, {} ),
		output: {
			path: path.resolve( baseDir, 'assets/js/dist' ),
			filename: '[LP_BASEPATH]/[LP_BASENAME]' + ( isDev ? '' : '.min' ) + '.js',
			library: [ 'LP', '[name]' ],
			libraryTarget: 'this',
		},
		watch: isWatch, //'production' !== process.env.NODE_ENV,
		module: {
			rules: [
				{
					test: /\.js$/,
					exclude: /(node_modules|bower_components)/,
					use: {
						loader: 'babel-loader',
						options: {
							//presets: ['babel-preset-env']
							presets: [ '@babel/preset-env' ],
						},
					},
				},
			],
		},
		plugins: [
			new CustomTemplatedPathPlugin( {
				// basename(path, data) {
				//     let rawRequest;
				//
				//     const entryModule = get(data, ['chunk', 'entryModule'], {});
				//     switch (entryModule.type) {
				//         case 'javascript/auto':
				//             rawRequest = entryModule.rawRequest;
				//             break;
				//
				//         case 'javascript/esm':
				//             rawRequest = entryModule.rootModule.rawRequest;
				//             break;
				//     }
				//
				//     if (rawRequest) {
				//         return basename(rawRequest);
				//     }
				//
				//     return path;
				// }
			} ),
			new DependencyExtractionWebpackPlugin(),
			new LearnPressCustomTemplatedPathPlugin( {
				LP_BASENAME( p, data ) {
					let rawRequest;

					const entryModule = get( data, [ 'chunk', 'entryModule' ], {} );
					switch ( entryModule.type ) {
					case 'javascript/auto':
						rawRequest = entryModule.rawRequest;
						break;

					case 'javascript/esm':
						rawRequest = entryModule.rootModule.rawRequest;
						break;
					}

					if ( rawRequest ) {
						return path.basename( rawRequest ).replace( /\.js$/, '' );
					}

					return p;
				},
				LP_BASEPATH( p, data ) {
					let rawRequest;

					const entryModule = get( data, [ 'chunk', 'entryModule' ], {} );
					switch ( entryModule.type ) {
					case 'javascript/auto':
						rawRequest = entryModule.rawRequest;
						break;

					case 'javascript/esm':
						rawRequest = entryModule.rootModule.rawRequest;
						break;
					}

					if ( rawRequest ) {
						return path.basename( path.dirname( rawRequest ) );
					}

					return p;
				},
			} ),

			new LearnPressDependencyExtractionWebpackPlugin( {
				namespace: '@learnpress',
				library: 'LP',
			} ),

			// new CopyPlugin(editorPackages.map((name) => {
			//     return {
			//         from: packageDir + `/packages/${name}/package.json`,
			//         to: path.resolve(__dirname, 'build') + `/${name}/`
			//     }
			// }).concat(editorPackages.map((name) => {
			//     return {
			//         from: path.resolve(__dirname, 'build') + `/${name}/index.js.map`,
			//         to: packageDir + `/assets/js/${name}.js.map`
			//     }
			// }))),
			//
			// new MergeIntoSingleFilePlugin({
			//     files: editorPackages.reduce((memo, packageName) => {
			//         const name = `../src/assets/js/${ packageName }.js`;
			//         memo[name] = [__dirname + `/build/${packageName}/index.js`];
			//         return memo;
			//     }, {}),
			//     transform: editorPackages.reduce((memo, packageName) => {
			//         const name = `../src/assets/js/${ packageName }.js`;
			//         memo[name] = function (code) {
			//             return code.replace(/index.js.map/, `${packageName}.js.map`);
			//         }
			//         return memo;
			//     }, {})
			// })
			//blocksCSSPlugin,
		],
		devtool,
		//...buildConfig(env)
	};

	return config;
};
