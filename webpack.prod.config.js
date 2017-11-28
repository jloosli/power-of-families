var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = {
  entry: {
    main: [
      'babel-polyfill',
      './theme_vivial2016/assets/js/main',
    ]
  },
  output: {
    filename: 'scripts.js',
    path: __dirname + '/build/vivial2016/assets/',
    publicPath: './'
  },
  resolve: {
    modulesDirectories: ['node_modules'],
    root: path.resolve('./theme_vivial2016/assets/js'),
    extensions:         ['', '.js', '.jsx', '.scss']
  },
  loader: {
    appSettings: {
      env: 'production' // string, default to 'development'
    }
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        exclude: /(node_modules|build)/,
        loader: 'babel',
        babelrc: false,
        query: {
          presets: ['es2015', 'react', 'stage-1'],
          plugins: []
        }
      },
      {
        test: /\.jsx?$/,
        loader: "eslint-loader",
        exclude: /(node_modules|build)/
      },
      {
        "test": /\.json$/,
        "loader": "json"
      },
      {
        test: /\.scss$/,
        loader: ExtractTextPlugin.extract(
          'css?sourceMap!sass?outputStyle=compressed' // loaders to preprocess CSS
        )
      },
      {
        test: /\.(png|gif|jpg|ttf|eot|svg|woff(2)?)(\?[a-z0-9=&.]+)?$/,
        loader: 'file-loader'
      }
    ],
    eslint: {
      configFile: '.eslintrc.js'
    }
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env': {
        // This has effect on the react lib size
        'NODE_ENV': JSON.stringify('production'),
      }
    }),
    new ExtractTextPlugin('main.css'),
    new webpack.optimize.DedupePlugin(),
    new webpack.optimize.OccurrenceOrderPlugin(true),
    new webpack.optimize.UglifyJsPlugin({
      minimize: true,
      compress: {
        screw_ie8: true,
        warnings: false
      },
      comments: false
    }),
    new webpack.IgnorePlugin(/nodent|js\-beautify/),
  ],
};
