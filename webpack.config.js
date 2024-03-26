const path = require('path');
const defaults = require('@wordpress/scripts/config/webpack.config.js');

module.exports = {
  ...defaults,
  entry: {
    main: path.resolve( process.cwd(),'power-of-families-theme', 'src', 'main.ts' )
  },
  output: {
    filename: '[name].js',
    path: path.resolve( process.cwd(), 'power-of-families-theme', 'build' ),
  },  
  module: {
    ...defaults.module,
    rules: [
      ...defaults.module.rules,
      {
        test: /\.tsx?$/,
        use: [
          {
            loader: 'ts-loader',
            options: {
              configFile: 'tsconfig.json',
              transpileOnly: true,
            }
          }
        ]        
      }
    ]
  },
  resolve: {
    extensions: [ '.ts', '.tsx', ...(defaults.resolve ? defaults.resolve.extensions || ['.js', '.jsx'] : [])]
  }
};
