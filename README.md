# Opetustilat

University of Helsinki teaching-space catalog (`uh/tilat`). The public site lists spaces, campuses, and buildings in Finnish, English, and Swedish. A space page links out to Optime for booking. There is no booking form on this site.

Drupal 11 (install profile `minimal`), PHP 8.4, docroot `web/`. A longer map of the theme, modules, and config is in [docs/project.md](docs/project.md).

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
- Master: `drush @master st`
- Production: `drush @prod st`

The master alias host is `master-shell.client-fi-hy-tilavaraus` via `www-admin@ssh.dev.silta.cloud`. Production uses `production-shell.client-fi-hy-tilavaraus` via `www-admin@ssh.prod.silta.cloud`. These are the ArgoCD environments (see "ArgoCD deployment").

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

PHPUnit 11 is configured in `phpunit.xml`. Custom modules have no PHPUnit classes. Playwright covers the anonymous login pages and creating a `page` node.

```bash
ddev npm run lint
ddev grumphp
ddev npx playwright test
```

`ddev start` installs Playwright browsers. `npx playwright show-report` opens the last HTML report.

Lint scripts cover ESLint, Stylelint, and markdownlint. GrumPHP also runs PHPCS, PHPStan, and PHP 8.3 compatibility on `web/modules/custom` and `web/themes/custom`.

## Contributing

Maintained by [Wunder](https://wunder.io/). GrumPHP requires a commit subject that starts with a ticket id:

```text
HYTV-123: (feat) Short description

- What changed and why

Refs: path/to/file.php
```

GrumPHP checks that the subject starts with a ticket id, a colon, and a space. Types used in this project: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`.

<!-- silta-migrate:begin -->
## ArgoCD deployment

Projects created from this template deploy with ArgoCD; there is no CircleCI deployment.
The platform side lives in the Silta ArgoCD platform.

- `k8s/staging/`: the `main`/`master` branch deploys to the `staging` environment.
- `.github/workflows/build.yml` builds the images on each push to a branch with an overlay
  here, pushes them to `registry.wdr.io/<repository>/` and commits the new tags to
  `k8s/<overlay>/values/drupal.yaml`. ArgoCD deploys that commit. Other branches and pull
  requests are validated only.

Add `k8s/develop/` or `k8s/production/` (copies of `k8s/staging/`) to deploy the `develop` or
`production` branch too. CI updates the tags of `main`/`master`, `develop`, `production` and pull
request branches, so a release is a merge to `production`: CI builds the `production` images,
commits their tags to `k8s/production/values/` and ArgoCD deploys that commit. Until
`k8s/production/` exists, `production` builds no images. To set production tags
by hand instead, remove `production: "true"` from `build.yml`. Every project needs
`k8s/staging/apps.yaml` on `main`/`master`: the staging environment creates the project's ArgoCD
project.

_From the ArgoCD section of the [wunderio/drupal-project](https://github.com/wunderio/drupal-project) template, commit `6e220badb0a3`; the environments, Secrets and cutover below are this site's (silta-migrate)._

### Environments

CircleCI keeps deploying to GKE until the cutover.

| Branch | ArgoCD environment | Cluster | URL |
| --- | --- | --- | --- |
| `master` | staging | silta-dev-upc | https://master-client-fi-hy-tilavaraus.dev.silta.cloud |
| `production` | production | silta-prod-c | https://production-client-fi-hy-tilavaraus.prod.silta.cloud, https://tilavaraus.helsinki.fi |

Each `k8s/<overlay>/values/drupal.yaml` is the environment's `silta/` files ported to ArgoCD, with the image maps laid out as in the template; the rewrites applied are listed at its top.

### Drush and SSH

`drush/sites/self.site.yml` has aliases for the ArgoCD environments: `drush @main st` (staging, on
the dev cluster) and `drush @production st` (once `k8s/production/` exists). Change `main` to
`master` there if that is the staging branch. Shell access works for the repository's
collaborators with push access:

```bash
ssh www-admin@main-shell.<namespace> -J www-admin@ssh.dev.silta.cloud
ssh www-admin@pr-<N>-shell.<namespace> -J www-admin@ssh.dev.silta.cloud
ssh www-admin@production-shell.<namespace> -J www-admin@ssh.prod.silta.cloud
```

`<namespace>` is the repository name in lowercase. Staging is at `https://main-<project>.dev.silta.cloud`
and a pull request preview at `https://pr-<N>-<project>.dev.silta.cloud`; the pull request gets a
comment with its URL.

### Secrets

Put Kubernetes Secrets in `k8s/<overlay>/secrets/<name>.sops.yaml`, encrypt them, and set
`secrets.enabled: true` in that overlay's `apps.yaml`. `.sops.yaml` encrypts them to the shared
tenant key, which only ArgoCD can decrypt:

```bash
sops --encrypt --in-place k8s/staging/secrets/app.sops.yaml
```

A file may hold only `apiVersion: v1`, `kind: Secret` objects in the project's namespace;
anything else fails the deployment. Never commit a decrypted file.

This site's Secrets, by key name (`migrate.py secrets` writes them; values never go in git):

```yaml
# k8s/staging/secrets/secrets.sops.yaml
apiVersion: v1
kind: Secret
metadata:
  name: client-fi-hy-tilavaraus-secrets
  namespace: client-fi-hy-tilavaraus
stringData:
  OPTIME_API_KEY: "<value>"
  OPTIME_URL: "<value>"
  WARDEN_TOKEN: "<value>"
---
# k8s/staging/secrets/mariadb.sops.yaml
apiVersion: v1
kind: Secret
metadata:
  name: master-mariadb
  namespace: client-fi-hy-tilavaraus
stringData:
  mariadb-root-password: "<value>"
  mariadb-password: "<value>"
---
# k8s/staging/secrets/basicauth.sops.yaml
apiVersion: v1
kind: Secret
metadata:
  name: master-basicauth
  namespace: client-fi-hy-tilavaraus
stringData:
  .htaccess: "<value>"
---
# k8s/production/secrets/secrets.sops.yaml
apiVersion: v1
kind: Secret
metadata:
  name: client-fi-hy-tilavaraus-secrets
  namespace: client-fi-hy-tilavaraus
stringData:
  OPTIME_API_KEY: "<value>"
  OPTIME_URL: "<value>"
  WARDEN_TOKEN: "<value>"
---
# k8s/production/secrets/mariadb.sops.yaml
apiVersion: v1
kind: Secret
metadata:
  name: production-mariadb
  namespace: client-fi-hy-tilavaraus
stringData:
  mariadb-root-password: "<value>"
  mariadb-password: "<value>"
```

### Cutover

1. Merge to the environment's branch only once its Secrets are in `k8s/<overlay>/secrets/`.
2. Copy the database and files to the new environment, then check the site on its temporary URL.
3. Point DNS (or the CDN backend) for the site's domains at the UpCloud cluster.
4. Remove the CircleCI deploy jobs and `silta/` files that nothing uses any more.
<!-- silta-migrate:end -->
