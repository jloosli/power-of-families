var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var WebpackNotifierPlugin = require('webpack-notifier');

const extractSass = new ExtractTextPlugin({
  filename: "[name].css",
  disable: process.env.NODE_ENV === "development"
});

module.exports = {
  entry: {
    main: [
      './power-of-families/src/main.ts'
    ],
    admin: [
      './power-of-families/src/admin.ts'
    ]
  },
  output: {
    filename: '[name].js',
    path: __dirname + '/dist/',
    publicPath: '/assets/'
  },
  resolve: {
    extensions: ['.ts', '.js', '.tsx', '.scss']
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
            'css-loader',
            'sass-loader'
          ],
          fallback: 'style-loader'
        })
      },
      {
        test: /\.(png|gif|jpg|ttf|eot|svg|woff(2)?)(\?[a-z0-9=&.]+)?$/,
        loader: 'file-loader'
      }
    ]
  },
  plugins: [
    new ExtractTextPlugin('main.css'),
    new webpack.WatchIgnorePlugin([
      /\.js$/,
      /\.d\.ts$/
    ]),
    new WebpackNotifierPlugin(),
    extractSass
  ],
};
