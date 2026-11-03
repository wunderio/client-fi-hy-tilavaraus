# Opetustilat project map

Facts below come from this repository. Anything not in the repo is marked unknown.

## 1. Project snapshot

Opetustilat is the University of Helsinki teaching-space catalog. Composer package `uh/tilat` (`composer.json`, description `HY-Tilat Composer file`). The exported site name is Opetustilat (`config/sync/system.site.yml`).

Visitors search spaces, open a campus or building, and follow a booking link to Optime. The site does not take the booking itself. Languages are Finnish (default), English, and Swedish, each on a path prefix (`/fi`, `/en`, `/sv`).

Drupal core is `drupal/core-recommended: ^11`, locked at 11.4.11 in `composer.lock`. PHP is 8.3. The install profile is `minimal` (`config/sync/core.extension.yml`). The docroot is `web/`.

## 2. Platform and environments

### Config

Active sync directory: `config/sync`, set in `web/sites/default/settings.php` as `../config/sync`.

| Split | Config entity | Folder | What it adds |
| --- | --- | --- | --- |
| `silta` | `config/sync/config_split.config_split.silta.yml` | `config/silta` | `stage_file_proxy` |
| `master` | `config/sync/config_split.config_split.master.yml` | `config/master` | `stage_file_proxy` |
| `local` | `config/sync/config_split.config_split.local.yml` | `config/local` | `devel`, `devel_php`, `stage_file_proxy`, plus a few partial ignores |

`settings.php` turns splits on from `ENVIRONMENT_NAME`. Feature branches get `silta`. `master` gets the master split and turns `silta` off. `dev` and `stage` get `silta` and do not get the local split. `ddev` and `local` get the local split. `production` turns all three off.

`config/production/` contains only `.htaccess`. There is no production config split.

`system.site` is listed in `config/sync/config_ignore.settings.yml`, so the exported front page and site name can differ from what is in the database.

Non-production environments set stage file proxy origin to `https://tilavaraus.helsinki.fi`.

### Local

DDEV project name `client-fi-hy-tilavaraus` (`.ddev/config.yaml`). URL `https://client-fi-hy-tilavaraus.ddev.site`. PHP 8.3, docroot `web`, nginx-fpm, MariaDB 11.8. `type` is `drupal10` while core is 11.

Tracked DDEV files: `.ddev/config.yaml`, `.ddev/config.wunderio.yaml`, `.ddev/docker-compose.wunderio.yaml`. No project compose file for Elasticsearch, Redis, or Varnish. Mailpit is DDEV's mail catcher.

The local `settings.php` case points the Optime migration at `web/modules/custom/migrate_optime_json/data/locations11_example.json`.

### Remote

Deploy is CircleCI, orb `silta/silta@1`, image `wunderio/silta-cicd:circleci-php8.3-node24-composer2-v1` (`.circleci/config.yml`). Jobs: `validate`, `analyze`, feature `build` / `deploy` (manual approval), `build-master` / `deploy-master`, `build-prod` / `deploy-prod`. The build runs Composer install
and `silta/npm-install-build`. There is no PHPUnit job and no GitHub Actions workflow besides `.github/dependabot.yml`.

| Environment | Branch | Config files | Context | Role |
| --- | --- | --- | --- | --- |
| Feature | not `master` or `production` | `silta/silta.yml`, `silta/secrets-dev.yml` | `silta_dev_upc_hy_tilavaraus` | Review deploy. Cron is disabled with a non-date (`silta/silta.yml`). |
| Master | `master` | those two plus `silta/silta-master.yml` | same dev context | Long-lived non-production. Cron about hourly. |
| Production | `production` | `silta/silta.yml`, `silta/silta-prod.yml`, `silta/secrets-prod.yml` | `silta_prod_c_hy_tilavaraus` | Public site. Hostname `tilavaraus.helsinki.fi`. Autoscaling 2–5. Cron every 5 minutes. Mailpit off. Varnish on. |

`silta/silta.yml` sets `projectName: Tilavaraus`, `php.drupalCoreVersion: "11"`, and `referenceData.referenceEnvironment: master`.

Drush aliases (`drush/sites/self.site.yml`): `local`, `current`, `master`, `prod`. `drush/Commands/SiltaAliasAlterCommands.php` fills `@current`, `@master`, and `@prod` from the git remote name `client-fi-hy-tilavaraus` and the Silta project name. `@master` uses `master-shell.client-fi-hy-tilavaraus`
through `ssh.dev.wdr.io`. `@prod` uses `production-shell.client-fi-hy-tilavaraus` through `ssh.finland.wdr.io`.

Secret files `silta/secrets-dev.yml` and `silta/secrets-prod.yml` are OpenSSL-encrypted. Key names inside them are not readable. `settings.php` reads `ENVIRONMENT_NAME`, `WARDEN_TOKEN`, `OPTIME_URL`, and `OPTIME_API_KEY`.

The admin environment bar is `$settings['simple_environment_indicator']` in `settings.php`. Production is `#d4000f Production`. Other values: Feature `#5c6b73`, Master `#004984`, Stage `#e56716`, Dev `#6f42c1`, Local `#88b700`.

## 3. Site characteristics

Notable contributed areas, from `composer.json` and `config/sync/core.extension.yml`:

- Search: `search_api`, `search_api_db`, `search_api_autocomplete`. Server `opetustilat_drupal` uses the database backend (`config/sync/search_api.server.opetustilat_drupal.yml`). The public view is `space_search` (block on the front page). A second index, `name_and_location`, is named deprecated.
- Media and maps: image styles, `geofield`, `leaflet` on building terms.
- Layout: Display Suite (`ds`, `ds_chains`), Field Group.
- Paths: `pathauto`, `redirect`, `easy_breadcrumb`.
- Multilingual: core language plus `menu_multilingual`. Interface negotiation is URL, then user, then selected language (`config/sync/language.negotiation.yml`).
- Workflow-adjacent: revisions on `page`. No content-moderation module is called out here as the editorial workflow.
- Cache: Varnish is enabled in `silta/silta.yml`. `core.extension.yml` does not enable `purge` or `varnish_purger`.
- Auth: core login at `/user/login`. No SimpleSAMLphp, OpenID, or CAS in `composer.json`. `drupal/simplei` is the lightweight environment indicator, which matches the admin bar in `settings.php`.
- Other: `cookieconsent` (Drupal 10 constraint, allowed through `drupal-lenient` in `composer.json`), `google_tag`, `config_split`, `config_ignore`, `config_readonly`, `stage_file_proxy`, `disable_field`.

Content types (`config/sync/node.type.*.yml`):

- `page` — general page. Body, lead text, featured image. The front-page hero is for `page` nodes (`config/sync/block.block.heroblock.yml`).
- `space` — a room. Campus, building, address, floor, capacity, room type, accessibility, equipment, images, price category, Optime id, and `field_reservation_link` (booking link). Editors cannot edit that link (`disable_field`).

Vocabularies include `campus`, `building`, `room_type`, `equipment`, `accessibility`, `price_categories`, and `contacts`.

The public search block (`views.view.space_search`, display `main`) filters campus, minimum capacity, accessibility, and equipment. Fulltext search exists on the default display and is not exposed. Results are published `space` nodes, 12 per page, with a mini pager.

There is no Webform or Contact module in `core.extension.yml`. Pathauto still has a `form_webformtitle` pattern, and `system.mail` still names `webform_php_mail`.

Roles in config: `anonymous`, `authenticated`, `editor`, `admin`. The editor role can administer nodes, menus, and taxonomy, and has `bypass node access`.

Booking URI pattern used by the migration: `https://optime.helsinki.fi/Timetables/Room/` plus an Optime index. The theme forces the link text to "Check availability".

## 4. Custom themes

### `uh_space`

| | |
| --- | --- |
| Human name | UH Space |
| Base theme | `stable` (contrib `drupal/stable`, because Drupal 11 does not ship Stable) |
| Default theme | Yes (`config/sync/system.theme.yml`). Admin theme is `claro`. |
| Machine name | `uh_space` |

It owns the public layout: header, hero, search, space and building pages, and the UH footer. Regions are declared in `web/themes/custom/uh_space/uh_space.info.yml`: `header`, `header_2`, `primary_menu`, `hero`, `content_top`, `content`, `content_bottom`, `footer_top`, `footer_site`, `footer`,
`hidden`.

Libraries (`uh_space.libraries.yml`), both attached globally from the info file:

- `uh_space/global` — `dist/app.css` and `dist/app.js`
- `uh_space/select2` — `lib/selectWoo/dist/js/selectWoo.min.js`, `lib/selectWoo/dist/css/selectWoo.min.css`, and `core/jquery.once`

`views-exposed-filters` (`js/views-exposed-filters.js`) is defined and is not in the global library list.

Source SCSS and JS live under `web/themes/custom/uh_space/src`. Webpack (`web/themes/custom/uh_space/package.json`) writes `dist/app.css` and `dist/app.js`. The committed `dist/` directory has `app.css` and does not have `app.js`. Select2's `.min` files are also absent. `lib/selectWoo/dist` has
`selectWoo.js` and `selectWoo.css`.

Twig overrides are grouped by concern under `web/themes/custom/uh_space/templates` (page, region, views, navigation, form elements). Icons are inline SVG loaded by `_uh_space_load_icon()` in `uh_space.theme`, from theme `src/icons` and `lib/uh_styleguide/icons/src`. `lib/uh_styleguide` is the older
UH living styleguide (its own Gulp package). The theme libraries do not attach the styleguide CSS or JS. `.gitmodules` no longer points at that directory. The files are normal tracked files.

Preprocess worth knowing (`uh_space.theme`):

- `uh_space_preprocess_links__language_block` hides the active language and shows the langcode.
- `uh_space_preprocess_region` copies copyright and contact HTML from `system.site` into footer regions.
- `uh_space_preprocess_menu` adds footer link classes. The menu template does not print child items.
- Views exposed form, mini pager, and search autocomplete suggestion preprocessors adjust the space search UI.

The theme depends on the `uh_space_site` hero template and on Display Suite layouts for space and building. CKEditor is not customized in the theme info file.

Copy the preprocess-plus-Twig pattern used for the language switcher and the space search form. Do not copy the global Select2 attachment or the unused styleguide app as the way to add a new component.

## 5. Custom modules

| Module | Package | Purpose | Depends on |
| --- | --- | --- | --- |
| `migrate_optime_json` | Opetustilat | One-way import of Optime rooms into `space` nodes | `migrate`, `migrate_plus` |
| `uh_space_site` | Custom | Hero block, search query tweak, breadcrumb and language-switch alters | none declared |
| `uh_space_site_information` | Student Guide | Extra fields on the site information form | none declared |

### `migrate_optime_json`

Route `/admin/config/system/migrate-optime-json` (`migrate_optime_json.routing.yml`), permission `administer migrate_optime_json configuration`, form `SettingsForm`. A controller route in that file is commented out.

Migration id `optime_integration` (`config/sync/migrate_plus.migration.optime_integration.yml` and the copy under `config/install`). Source plugin `optime_url` (`src/Plugin/migrate/source/OptimeUrl.php`) reads `OPTIME_URL` and `OPTIME_API_KEY` from settings and always requests changes from the last
three days.

`migrate_optime_json.module` hooks:

- `hook_entity_presave` on spaces copies campus, building id, and address onto the building term when those term fields are empty, then saves the term. It also unpublishes the space when an Optime or Drupal inactivate field is set.
- `hook_entity_insert` creates legacy redirects.
- `migrate_optime_json_legacy_redirect()` strips the prefix `http://www.helsinki.fi/teknos/opetustilat/` and stores a redirect for Finnish and English only.

`MigrateOptimeJsonSubscriber` runs after the `optime_integration` import, loads nodes for each equipment term, and deletes terms that no space references.

There is no custom table. Uninstall deletes the migration config and leaves nodes, terms, and redirects (`migrate_optime_json.install`).

Who it is for: editors and the cron import. Visitors only see the resulting nodes and the booking link.

Pattern: migrate source plugin, plus procedural hooks for the building term and redirects.

### `uh_space_site`

`HeroBlock` (`src/Plugin/Block/HeroBlock.php`) and `templates/hero-block.html.twig`. The block is placed in `content_top` for the front page when the node bundle is `page`.

`hook_entity_extra_field_info` declares `field_smart_equipment_information` on `space`. No extra-field view builder is implemented. `hook_ds_pre_render_alter` hides the featured image on the front page and handles the equipment fallback by unsetting fields.

`hook_views_query_alter` skips the space search query when the only exposed input is an empty keyword.

`hook_system_breadcrumb_alter` hides breadcrumbs on the front page and on user routes.

`hook_language_switch_links_alter` drops Swedish unless the interface language is already Swedish. The function has an `@todo`.

`assets/js/leaflet_map.js` is the building map behavior.

Who it is for: anonymous visitors (search, hero, breadcrumbs, language links) and the theme.

Pattern: hooks plus one block plugin.

### `uh_space_site_information`

Replaces the site information form (`src/Form/SpaceSiteInformationForm.php`) via `src/Routing/SpaceSiteInformationRouteSubscriber.php`. Fields stored on `system.site`: logo text, logo path, copyright, contact HTML, spaces contact HTML. Schema is `config/schema/uh_space_site_information.schema.yml`,
which redeclares `system.site`.

Who it is for: editors of the site information form. The theme prints the values in the footer.

Pattern: route subscriber and a form alter of core's site information form.

## 6. Unknowns and where to look next

- The live front-page node. `system.site` exports a front page, and that config is ignored on import, so the database can disagree with git.
- Footer menu link targets. Menus `footer` and `site-footer` are placed. No `menu_link_content` config is exported.
- The public URL of the master Silta environment. `silta/silta-master.yml` only sets cron. The Drush alias is the clue, not a confirmed browser URL.
- Whether the Silta chart's `settings.silta.php` overrides `hash_salt`, `trusted_host_patterns`, or the database credentials. The committed `hash_salt` in `settings.php` is a placeholder. The included file is not in this repo.
- Whether `referenceEnvironment: master` still names a Silta reference-data environment that ops expect to keep.
- Production traffic, Optime error rates, and which equipment terms were created by editors rather than the migration. None of that is in git.
