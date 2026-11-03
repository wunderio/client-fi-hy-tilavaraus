<?php

/**
 * General settings.php for all environments.
 * You could use this to add general settings to be used for all environments.
 */

/**
 * Database settings (overridden per environment)
 */
$databases = [];
$databases['default']['default'] = [
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASS'),
  'prefix' => '',
  'host' => getenv('DB_HOST'),
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

// CHANGE THIS.
$settings['hash_salt'] = 'some-hash-salt-please-change-this';

if ((isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on")
  || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")
  || (isset($_SERVER["HTTP_HTTPS"]) && $_SERVER["HTTP_HTTPS"] == "on")
) {
  $_SERVER["HTTPS"] = "on";

  // Tell Drupal we're using HTTPS (url() for one depends on this).
  $settings['https'] = TRUE;
}

// @codingStandardsIgnoreStart
if (isset($_SERVER['REMOTE_ADDR'])) {
  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR']];
}
// @codingStandardsIgnoreEnd

if (!empty($_SERVER['SERVER_ADDR'])) {
  // This should return last section of IP, such as "198". (dont want/need to expose more info).
  //drupal_add_http_header('X-Webserver', end(explode('.', $_SERVER['SERVER_ADDR'])));
  $pcs = explode('.', $_SERVER['SERVER_ADDR']);
  header('X-Webserver: ' . end($pcs));
}

// Disallow configuration changes via UI by default.
$settings['config_readonly'] = FALSE;

// Some configurations changes we want to allow like menu items and
// configurations that are intentionally ignored with config_ignore.
$settings['config_readonly_whitelist_patterns'] = [
  'system.performance',
  'system.site',
  'system.menu.*',
  'core.menu.static_menu_link_overrides',
  'cookieconsent.settings',
];

// Allow submitting the taxonomy "Reset to alphabetical" confirmation form.
// There is no configuration match so we cannot use the whitelist above.
if (preg_match("/\/(.*)\/admin\/structure\/taxonomy\/manage(.*)reset/i", $_SERVER['REQUEST_URI'])) {
  $settings['config_readonly'] = FALSE;
}

// Command line (like drush) should allow configuration changes.
if (PHP_SAPI === 'cli') {
  $settings['config_readonly'] = FALSE;
}

// Default config_split settings cover feature environments in Silta.
// Production, master, dev, stage, and local overrides are set in the switch below.
$config['config_split.config_split.silta']['status'] = TRUE;
$config['config_split.config_split.master']['status'] = FALSE;
$config['config_split.config_split.local']['status'] = FALSE;

// Feature branches and any environment without its own case.
$settings['simple_environment_indicator'] = '#5c6b73 Feature';

// Fetch missing files from production on non-production environments.
$config['stage_file_proxy.settings']['origin'] = 'https://tilavaraus.helsinki.fi';

// Environment-specific settings.
$env = getenv('ENVIRONMENT_NAME');
global $base_url;

switch ($env) {
  case 'production':
    // Warden settings.
    // Shared secret between the site and Warden server.
    $config['warden.settings']['warden_token'] = getenv('WARDEN_TOKEN');
    // Location of your Warden server. No trailing slash.
    $config['warden.settings']['warden_server_host_path'] = 'https://warden.wunder.io';
    // Allow external callbacks to the site. When set to FALSE pressing refresh site
    // data in Warden will not work.
    $config['warden.settings']['warden_allow_requests'] = TRUE;
    // Basic HTTP authorization credentials.
    $config['warden.settings']['warden_http_username'] = 'warden';
    $config['warden.settings']['warden_http_password'] = 'wunder';
    // IP address of the Warden server. Only these IP addresses will be allowed to
    // make callback # requests.
    $config['warden.settings']['warden_public_allow_ips'] = '35.228.188.78,35.228.81.50';
    // Define module locations.
    $config['warden.settings']['warden_preg_match_custom'] = '{^modules\/custom\/*}';
    $config['warden.settings']['warden_preg_match_contrib'] = '{^modules\/contrib\/*}';
    $config['warden.settings']['warden_match_contrib'] = TRUE;

    $settings['simple_environment_indicator'] = '#d4000f Production';
    $base_url = "https://tilavaraus.helsinki.fi";
    // Sitemap settings override.
    $config['simple_sitemap.settings']['base_url'] = 'https://tilavaraus.helsinki.fi';
    // Do not enable stage_file_proxy on production.
    $config['config_split.config_split.silta']['status'] = FALSE;
    $config['config_split.config_split.master']['status'] = FALSE;
    $config['config_split.config_split.local']['status'] = FALSE;
    break;

  case 'master':
    $settings['simple_environment_indicator'] = '#004984 Master';
    $config['config_split.config_split.master']['status'] = TRUE;
    $config['config_split.config_split.silta']['status'] = FALSE;
    $config['config_split.config_split.local']['status'] = FALSE;
    break;

  case 'dev':
    $settings['simple_environment_indicator'] = '#6f42c1 Dev';
    // Disable config_readonly on dev.
    $settings['config_readonly'] = FALSE;
    // Remote dev uses the Silta split, not the DDEV local split.
    $config['config_split.config_split.silta']['status'] = TRUE;
    $config['config_split.config_split.master']['status'] = FALSE;
    $config['config_split.config_split.local']['status'] = FALSE;
    break;

  case 'stage':
    $settings['simple_environment_indicator'] = '#e56716 Stage';
    $config['config_split.config_split.silta']['status'] = TRUE;
    $config['config_split.config_split.master']['status'] = FALSE;
    $config['config_split.config_split.local']['status'] = FALSE;
    break;

  case 'ddev':
  case 'local':
    $settings['simple_environment_indicator'] = '#88b700 Local';
    $base_url = "https://client-fi-hy-tilavaraus.ddev.site";
    // Disable config_readonly on local.
    $settings['config_readonly'] = FALSE;
    // Enable local split on local.
    $config['config_split.config_split.local']['status'] = TRUE;
    $config['config_split.config_split.silta']['status'] = FALSE;
    $config['config_split.config_split.master']['status'] = FALSE;
    // Sitemap settings override.
    $config['simple_sitemap.settings']['base_url'] = 'https://client-fi-hy-tilavaraus.ddev.site';
    $config['migrate_plus.migration.optime_integration']['source']['urls'] = 'https://client-fi-hy-tilavaraus.ddev.site/modules/custom/migrate_optime_json/data/locations11_example.json';
    break;
}

/**
 * Location of the site configuration files.
 */
$config_directories = [];

// Location of the site configuration files.
$settings['config_sync_directory'] = '../config/sync';

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = FALSE;

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

// Fix warning on Drupal status page.
$settings['trusted_host_patterns'] = [
  '^tilat\.ddev\.site$',
  '^.*\.helsinki\.fi$',
  '^127\.0\.0\.1$',
];

/**
 * Optime migration source. Read by the optime_url migrate source plugin.
 * Set OPTIME_URL and OPTIME_API_KEY in the environment (Silta php.env).
 */
$settings['optime-url'] = getenv('OPTIME_URL') ?: '';
$settings['optime-api-key'] = getenv('OPTIME_API_KEY') ?: '';
/**
 * Environment specific override configuration, if available.
 */
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}

// Automatically generated include for settings managed by ddev.
$ddev_settings = __DIR__ . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

// Silta cluster configuration overrides.
// @see: https://github.com/wunderio/charts/blob/master/drupal/files/settings.silta.php
if (getenv('SILTA_CLUSTER') && file_exists(DRUPAL_ROOT . '/sites/default/settings.silta.php')) {
  include DRUPAL_ROOT . '/sites/default/settings.silta.php';
}
