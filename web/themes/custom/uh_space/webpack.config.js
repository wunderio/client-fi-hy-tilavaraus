const path = require('path')

const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const sassGlobImporter = require('./sass-glob-importer')

// Sass load paths, relative to the theme root.
const loadPaths = [
  'lib/uh_styleguide/sass',
  'node_modules/breakpoint-sass/stylesheets',
  'node_modules/normalize.css'
].map((p) => path.resolve(__dirname, p))

const srcPath = path.resolve(__dirname, 'src')

module.exports = {
  context: srcPath,
  entry: {
    app: './index.js',
  },
  module: {
    rules: [
      {
        test: /\.(css|scss)$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          {
            loader: 'sass-loader',
            options: {
              sassOptions: {
                loadPaths,
                importers: [sassGlobImporter(loadPaths)],
                // The UH styleguide and theme still use @import, global
                // functions, '/' division and declarations after nested rules
                // (mixed-decls). Silence those deprecations to keep the build
                // output readable. sass is pinned to ~1.91.0 in package.json:
                // from 1.92 on, declarations after nested rules are emitted in
                // source order, which changes the cascade in ~550 places.
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'slash-div', 'color-functions', 'mixed-decls'],
              },
            },
          },
        ],
      },
      {
        test: /\.(gif|png|jpe?g|svg|woff|woff2|eot|ttf)$/i,
        type: 'asset/resource',
        generator: {
          // Same layout as the old file-loader '[path][name].[ext]': paths
          // relative to src/, with '../' written as '_/' to stay inside dist/.
          // pathData.filename is already relative to the context (src/).
          filename: (pathData) => pathData.filename
            .split('/')
            .map((part) => (part === '..' ? '_' : part))
            .join('/'),
        },
      },
    ],
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'dist'),
    publicPath: '',
    // Clean dist folder before building.
    clean: true,
  },
  plugins: [
    // Extract CSS to its own file.
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  ],
}
