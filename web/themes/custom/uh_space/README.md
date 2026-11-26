# Theme for the University of Helsinki Spaces

UH theme uses Display suite for most content types/layouting.

### Local development

#### Set up University of Helsinki Style Guide

_Note:_ Site uses Style Guide fork from here: git@github.com:wunderio/Styleguide.git

1. Checkout git submodules in repository root (Opetustila-esittely/) by running `git submodule update --recursive --remote`. This should add a specific version of UH’s Styleguide to drupal/web/themes/custom/uh_space/lib/uh_styleguide. The commit ref should be ‘37cee5b0a2a754bbbd54f725793b61d0d25a33b7’.
    1. If you are pulling submodules for the first time for the project, run `git submodule update --init --recursive --remote` instead.

#### Compiling frontend assets
1. Setting up
    1. Go to the theme folder with `cd web/themes/custom/uh_space`.
    2. Use Node.js 22 or newer and npm (`ddev npm` works too). The theme uses npm: `package-lock.json` is the only lockfile.
    3. Run `npm ci`.
2. Compiling frontend assets
    1. Run `npm run watch` to build development-friendly frontend files with auto-rebuild (watch).
    2. Run `npm run build` to build production-ready frontend files without auto-rebuild.

The build is webpack 5 with Dart Sass (`sass`). Glob imports such as `@import "drupal/**/*"` are handled by `sass-glob-importer.js`. `sass` is pinned to `~1.91.0`: from 1.92 on, Dart Sass emits declarations that follow nested rules in source order, which changes the cascade in the styleguide.

#### Compiling frontend assets
Before committing changes, please run `npm run build`.

We deploy pre-built frontend files from this repo, they are NOT built during deployment. They are inside dist/ in the theme folder.

They need to be PRODUCTION-READY. Please remember to do this!
