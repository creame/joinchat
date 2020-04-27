module.exports = {
  parser: 'postcss-scss',
  plugins: [
    require('precss'),
    require('postcss-strip-inline-comments'),
    require('postcss-hexrgba'),
    require('postcss-calc'),
    require('postcss-inline-svg'),
    require('postcss-svgo'),
    require('autoprefixer'),
    require('stylelint')({ fix: true }),
  ]
}