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

ArgoCD deploys `master` (staging) and `production` from `k8s/`. GitHub Actions (`.github/workflows/build.yml`) validates, analyzes, builds the images (Dockerfiles in `silta/`) and commits their tags. See "ArgoCD deployment".

Staging cron is hourly. Production cron is every five minutes, with autoscaling from 2 to 5 replicas. The production hostname is <https://tilavaraus.helsinki.fi>.

`dev` and `stage` are `ENVIRONMENT_NAME` values in `web/sites/default/settings.php`. They select the `silta` split and an admin-bar color. They are not deployed branches.

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

Secrets are SOPS-encrypted Kubernetes Secrets in `k8s/<overlay>/secrets/` (see "ArgoCD deployment", "Secrets"). Do not commit decrypted copies. Names read from the environment in `web/sites/default/settings.php`:

- `ENVIRONMENT_NAME`
- `WARDEN_TOKEN` (production)
- `OPTIME_URL`
- `OPTIME_API_KEY`

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

This project deploys with ArgoCD; there is no CircleCI deployment.
The platform side lives in the Silta ArgoCD platform.

- `k8s/staging/`: the `main`/`master` branch deploys to the `staging` environment.
- `k8s/feature/`: a pull request from `feature/*`, `hotfix/*` or `epic/*` gets a `pr-<N>`
  environment only while it carries the `preview` label (create the label in the repository if
  it does not exist yet). Removing the label or closing the pull request deletes the environment.
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

In place: CircleCI used to deploy staging and production to the clusters below. ArgoCD took over the same releases (database and volumes included) on 2026-11-26, and the CircleCI configuration and Silta deploy files were removed.

| Branch | ArgoCD environment | Cluster | URL |
| --- | --- | --- | --- |
| `master` | staging | silta-dev-upc | https://master-client-fi-hy-tilavaraus.dev.silta.cloud |
| `production` | production | silta-prod-c | https://production-client-fi-hy-tilavaraus.prod.silta.cloud, https://tilavaraus.helsinki.fi |
| `feature/*` pull requests labelled `preview` | feature | silta-dev-upc | https://pr-<N>-client-fi-hy-tilavaraus.dev.silta.cloud |

Each `k8s/<overlay>/values/drupal.yaml` is the environment's former `silta/` files (in git history) ported to ArgoCD, with the image maps laid out as in the template; the rewrites applied are listed at its top.

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

1. Merge to the environment's branch only once its Secrets are in `k8s/<overlay>/secrets/` (the database
   passwords taken from the running release's Secret with `migrate.py secrets --kube-context`) and the
   `silta-argocd` topic is set.
2. The merge is the takeover: ArgoCD syncs over the release CircleCI deployed, in the same namespace, keeping its
   database and volumes. Do it in a window agreed with ops, who clear what the chart cannot adopt.
3. Remove the CircleCI deploy jobs and `silta/` files that nothing uses any more (done 2026-11-26; the Dockerfiles in `silta/` stay, `build.yml` builds from them).
<!-- silta-migrate:end -->
