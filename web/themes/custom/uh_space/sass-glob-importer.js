/**
 * Sass importer (modern API) for glob imports such as @import "drupal/**\/*".
 *
 * Replaces node-sass-glob-importer, which only works with the legacy node-sass
 * importer API. Resolution matches it: the pattern is matched in the importing
 * file's directory first, then in each load path; matches are sorted per
 * directory and de-duplicated, then imported in that order.
 */
const fs = require('fs')
const path = require('path')
const { fileURLToPath, pathToFileURL } = require('url')

const SCHEME = 'uh-glob:'
const hasMagic = (url) => /[*?[\]{}]/.test(url)

module.exports = function sassGlobImporter(loadPaths) {
  const resolve = (pattern, dirs) => {
    const files = new Set()
    for (const dir of dirs) {
      fs.globSync(pattern, { cwd: dir })
        .sort((a, b) => a.localeCompare(b, 'en'))
        .map((rel) => path.resolve(dir, rel))
        .filter((abs) => fs.statSync(abs).isFile())
        .forEach((abs) => files.add(abs))
    }
    return [...files]
  }

  return {
    canonicalize(url, context) {
      if (url.startsWith(SCHEME)) {
        return new URL(url)
      }
      if (!hasMagic(url)) {
        return null
      }
      const dirs = []
      if (context.containingUrl && context.containingUrl.protocol === 'file:') {
        dirs.push(path.dirname(fileURLToPath(context.containingUrl)))
      }
      dirs.push(...loadPaths)
      const params = new URLSearchParams({ pattern: url, dirs: JSON.stringify(dirs) })
      return new URL(`${SCHEME}?${params}`)
    },
    load(canonicalUrl) {
      const pattern = canonicalUrl.searchParams.get('pattern')
      const dirs = JSON.parse(canonicalUrl.searchParams.get('dirs'))
      const contents = resolve(pattern, dirs)
        .map((file) => `@import "${pathToFileURL(file).href}";`)
        .join('\n')
      return { contents, syntax: 'scss' }
    },
  }
}
