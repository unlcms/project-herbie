const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: {
    mediaImageClass:
      './js/ckeditor5_plugins/media_image_class/src/index.js',
  },

  output: {
    path: path.resolve(__dirname, 'js/build'),
    filename: '[name].js',
    library: ['CKEditor5', '[name]'],
    libraryTarget: 'umd',
    libraryExport: 'default',
  },

  plugins: [
    new webpack.DllReferencePlugin({
      manifest: require('ckeditor5/build/ckeditor5-dll.manifest.json'),
      scope: 'ckeditor5/src',
      name: 'CKEditor5.dll',
    }),
  ],

  module: {
    rules: [],
  },
};
