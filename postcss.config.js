module.exports = {
  parser: 'postcss-scss',
  plugins: [
    require('precss')({ stage: 4 }),
    require('postcss-strip-inline-comments'),
    require('postcss-hexrgba'),
    require('postcss-calc'),
    require('postcss-sort-media-queries'),
    require('autoprefixer'),
    require('stylelint')({ fix: true }),
    require('postcss-discard-duplicates'),
    require('postcss-inline-svg'),
    require('postcss-svgo'),
  ]
}
