var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var WebpackNotifierPlugin = require('webpack-notifier');

const extractSass = new ExtractTextPlugin({
  filename: "[name].css",
  // filename: "[name].[contenthash].css",
  disable: process.env.NODE_ENV === "development"
});

module.exports = {
  devtool: 'inline-source-map',
  entry: {
    main: [
      // 'babel-polyfill',
      'webpack-dev-server/client?http://localhost:8010',
      './power-of-families/src/main.ts',
      './power-of-families/assets/scss/main.scss'
    ]
  },
  output: {
    filename: 'scripts.js',
    path: __dirname + '/.tmp/assets/',
    publicPath: '/assets/'
  },
  resolve: {
    extensions: ['.ts', '.js', '.tsx', '.scss']
  },
  loader: {
    appSettings: {
      env: 'development' // string, default to 'development'
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
        // loader: ExtractTextPlugin.extract(
        //   'css?sourceMap!sass?outputStyle=expanded&sourceMap=true&sourceMapContents=true' // loaders to preprocess CSS
        // )
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
        // This could have an effect on some dependency bundled sizes
        'NODE_ENV': JSON.stringify('development'),
      }
    }),
    new ExtractTextPlugin('main.css'),
    new webpack.WatchIgnorePlugin([
      /\.js$/,
      /\.d\.ts$/
    ]),
    new WebpackNotifierPlugin(),
    extractSass
  ],
};
