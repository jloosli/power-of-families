const merge = require('webpack-merge');
const common = require('./webpack.common');
var webpack = require('webpack');

module.exports = merge(common, {
  devtool: 'inline-source-map',
  devServer: {
    contentBase: './dist',
    progress: true,
    port: 8010
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env': {
        'NODE_ENV': JSON.stringify('development')
      }
    })
  ]
});
