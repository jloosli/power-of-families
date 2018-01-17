const merge = require('webpack-merge');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');
const common = require('./webpack.common');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const webpack = require('webpack');

const ASSET_PATH = process.env.ASSET_PATH || '';

module.exports = merge(common, {
  output: {
    publicPath: ASSET_PATH
  },
  plugins: [
    new CleanWebpackPlugin(['dist','build']),
    new UglifyJSPlugin({
      sourceMap: true
    }),
    new webpack.DefinePlugin({
      'process.env': {
        'NODE_ENV': JSON.stringify('production')
      }
    })
  ]
});
