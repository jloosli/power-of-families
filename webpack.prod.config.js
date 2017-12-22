var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
const extractSass = new ExtractTextPlugin({
  filename: "[name].css",
  // filename: "[name].[contenthash].css",
  disable: process.env.NODE_ENV === "development"
});
module.exports = {
  entry: {
    main: [
      'babel-polyfill',
      './power-of-families/src/main.ts',
      './power-of-families/assets/scss/main.scss'
    ]
  },
  output: {
    filename: 'scripts.js',
    path: __dirname + '/build/power-of-families/assets/',
    publicPath: './'
  },
  resolve: {
    extensions: ['.ts', '.js', '.tsx', '.scss']
  },
  loader: {
    appSettings: {
      env: 'production' // string, default to 'development'
    }
  },
  module: {
    loaders: [
      {
        test: /\.tsx?$/,
        exclude: /(node_modules|build)/,
        loader: 'ts-loader'
      },
      {
        "test": /\.json$/,
        "loader": "json"
      },
      {
        test: /\.scss$/,
        use: extractSass.extract({
          use: [
            // {
            // loader: "style-loader" // creates style nodes from JS strings
            // },
            {
              loader: "css-loader" // translates CSS into CommonJS
            },
            {
              loader: "sass-loader" // compiles Sass to CSS
            }
          ],
          fallback: "style-loader"
        })
      },
      {
        test: /\.(png|gif|jpg|ttf|eot|svg|woff(2)?)(\?[a-z0-9=&.]+)?$/,
        loader: 'file-loader'
      }
    ]
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
