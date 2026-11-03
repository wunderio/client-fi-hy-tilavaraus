# Opetustilat

University of Helsinki teaching-space catalog (`uh/tilat`). The public site lists spaces, campuses, and buildings in Finnish, English, and Swedish. A space page links out to Optime for booking. There is no booking form on this site.

Drupal 11 (install profile `minimal`), PHP 8.3, docroot `web/`. A longer map of the theme, modules, and config is in [docs/project.md](docs/project.md).

## Local development

This project uses [DDEV](https://ddev.com/get-started/). The project name is `client-fi-hy-tilavaraus`.

```bash
ddev start
ddev auth ssh
ddev syncdb
ddev drush deploy
ddev drush uli
```

The site is at <https://client-fi-hy-tilavaraus.ddev.site>. `ddev describe` lists Mailpit, Adminer, and the other local URLs. Search uses the database (`search_api_db`). This checkout does not add Elasticsearch, Kibana, Redis, or a project Varnish service.

Theme CSS and JS are committed under `web/themes/custom/uh_space/dist`. Rebuild them from the theme directory with `npm run build` in `web/themes/custom/uh_space`. The root `npm run build` script does not compile the theme.

`ENVIRONMENT_NAME` `ddev` or `local` enables the `local` config split (devel and stage file proxy) and points the Optime migration at the example JSON in `web/modules/custom/migrate_optime_json/data/`.

## Environments

Config lives in `config/sync`. Splits:

| Split | Folder | When it is on |
| --- | --- | --- |
| `silta` | `config/silta` | Feature branches, and `ENVIRONMENT_NAME` `dev` or `stage` |
| `master` | `config/master` | `ENVIRONMENT_NAME` `master` |
| `local` | `config/local` | `ENVIRONMENT_NAME` `ddev` or `local` |

`production` turns every split off. Stage file proxy on non-production environments fetches files from <https://tilavaraus.helsinki.fi>.

CircleCI (`.circleci/config.yml`) validates, analyzes, builds, and deploys with the Silta orb.

| Environment | Git branch | Silta files | CircleCI context |
| --- | --- | --- | --- |
| Feature | any branch except `master` and `production` | `silta/silta.yml`, `silta/secrets-dev.yml` | `silta_dev_upc_hy_tilavaraus` |
| Master | `master` | `silta/silta.yml`, `silta/silta-master.yml`, `silta/secrets-dev.yml` | `silta_dev_upc_hy_tilavaraus` |
| Production | `production` | `silta/silta.yml`, `silta/silta-prod.yml`, `silta/secrets-prod.yml` | `silta_prod_c_hy_tilavaraus` |

Feature deploys wait for the `approve-deployment` job. Master cron is hourly (`silta/silta-master.yml`). Production cron is every five minutes, with autoscaling from 2 to 5 replicas. The production hostname is <https://tilavaraus.helsinki.fi>.

`dev` and `stage` are `ENVIRONMENT_NAME` values in `web/sites/default/settings.php`. They select the `silta` split and an admin-bar color. They are not CircleCI branches.

Drush aliases are in `drush/sites/self.site.yml`:

- Local: `ddev drush`
- Current feature environment: `drush @current st`
- Master: `drush @master st`
- Production: `drush @prod st`

The master alias host is `master-shell.client-fi-hy-tilavaraus` via `www-admin@ssh.dev.wdr.io`. Production uses `production-shell.client-fi-hy-tilavaraus` via `www-admin@ssh.finland.wdr.io`. The public URL of the master environment is not set in `silta/silta-master.yml`.

The admin bar colors are set in `settings.php`:

| `ENVIRONMENT_NAME` | Color | Label |
| --- | --- | --- |
| unset, or any other value | `#5c6b73` | Feature |
| `master` | `#004984` | Master |
| `stage` | `#e56716` | Stage |
| `dev` | `#6f42c1` | Dev |
| `ddev`, `local` | `#88b700` | Local |
| `production` | `#d4000f` | Production |

## Secrets

`silta/secrets-dev.yml` and `silta/secrets-prod.yml` are encrypted. Do not commit decrypted copies. Names read from the environment in `web/sites/default/settings.php`:

- `ENVIRONMENT_NAME`
- `WARDEN_TOKEN` (production)
- `OPTIME_URL`
- `OPTIME_API_KEY`

Decrypt and encrypt with [Silta CLI](https://github.com/wunderio/silta-cli). The CircleCI contexts above hold the keys. See [Silta's secret documentation](https://wunderio.github.io/silta/docs/encrypting-sensitive-configuration/#using-a-custom-encryption-key).

## Tests and code quality

PHPUnit is configured in `phpunit.xml`. Custom modules have no test classes. GrumPHP runs the empty `unit` suite and notes that PHPUnit fails with 0 tests. Playwright is not installed. The adoption plan is [docs/playwright-adoption.md](docs/playwright-adoption.md). Findings and follow-on work are in
[docs/audit-upsell.md](docs/audit-upsell.md).

```bash
ddev npm run lint
ddev grumphp
```

Lint scripts cover ESLint, Stylelint, and markdownlint. GrumPHP also runs PHPCS, PHPStan, and PHP 8.3 compatibility on `web/modules/custom` and `web/themes/custom`.

## Contributing

Maintained by [Wunder](https://wunder.io/). GrumPHP requires a commit subject that starts with a ticket id:

```text
HYTV-123: (feat) Short description

- What changed and why

Refs: path/to/file.php
```

GrumPHP checks that the subject starts with a ticket id, a colon, and a space. Types used in this project: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`.
