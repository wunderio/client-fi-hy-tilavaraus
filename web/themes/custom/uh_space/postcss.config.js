module.exports = {
  plugins: {
    autoprefixer: {},
    // cssnano's default preset also drops comments, except /*! ... */ ones.
    cssnano: {}
  }
}
