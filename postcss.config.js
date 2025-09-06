/** @type {import('postcss-load-config').Config} */
const config = {
  plugins: [
    require('autoprefixer'),
    require('postcss-nested'),
    require('@csstools/postcss-global-data')({
		files: [
			'power-of-families/assets/css/components/variables.css'
		]
	}),
    require('postcss-custom-media'),
  ]
}

module.exports = config