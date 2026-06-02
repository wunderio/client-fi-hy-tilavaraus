<?php

declare(strict_types=1);

namespace Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drush\Drush;

/**
 * Drush helpers for switching Optime migration source in local development.
 */
class OptimeSourceCommands extends DrushCommands {

  private const MIGRATION_ID = 'migrate_plus.migration.optime_integration';
  private const SOURCE_BACKUP_STATE_KEY = 'migrate_optime_json.source_backup';

  /**
   * Enable offline source mode for Optime migration (local env only).
   *
   * @command optime:source-offline
   * @bootstrap full
   * @option sample Which bundled sample file to use: minimal|full.
   * @usage drush optime:source-offline --sample=minimal
   */
  public function sourceOffline(array $options = ['sample' => 'minimal']): void {
    $this->assertLocalEnvironment();

    $sample = (string) ($options['sample'] ?? 'minimal');
    $sample_files = [
      'minimal' => 'locations_minimal_example.json',
      'full' => 'locations11_example.json',
    ];

    if (!isset($sample_files[$sample])) {
      throw new \RuntimeException('Invalid --sample option. Allowed values: minimal, full.');
    }

    $config = $this->configFactory()->getEditable(self::MIGRATION_ID);
    $source = (array) $config->get('source');

    if (empty($source)) {
      throw new \RuntimeException('Could not read migration source configuration.');
    }

    // Keep a backup so switching back to live restores exact prior values.
    $this->state()->set(self::SOURCE_BACKUP_STATE_KEY, [
      'urls' => $source['urls'] ?? [],
      'headers' => $source['headers'] ?? [],
      'placeholders' => $source['placeholders'] ?? [],
      'data_fetcher_plugin' => $source['data_fetcher_plugin'] ?? 'http',
    ]);

    $source['data_fetcher_plugin'] = 'file';
    $source['urls'] = [DRUPAL_ROOT . '/modules/custom/migrate_optime_json/data/' . $sample_files[$sample]];
    $source['headers'] = [];
    $source['placeholders'] = [];

    $config->set('source', $source)->save();

    $this->logger()->success('Optime migration source set to local sample file: ' . $sample_files[$sample]);
    $this->logger()->notice('This command is intentionally restricted to local environments.');
  }

  /**
   * Restore live Optime source mode for Optime migration (local env only).
   *
   * @command optime:source-live
   * @bootstrap full
   * @usage drush optime:source-live
   */
  public function sourceLive(): void {
    $this->assertLocalEnvironment();

    $config = $this->configFactory()->getEditable(self::MIGRATION_ID);
    $source = (array) $config->get('source');

    if (empty($source)) {
      throw new \RuntimeException('Could not read migration source configuration.');
    }

    $backup = $this->state()->get(self::SOURCE_BACKUP_STATE_KEY);

    if (is_array($backup) && !empty($backup)) {
      $source['urls'] = $backup['urls'] ?? ['https://{optime-url}?fromTimestamp={time-stamp}'];
      $source['headers'] = $backup['headers'] ?? ['apikey' => 'optime-api-key'];
      $source['placeholders'] = $backup['placeholders'] ?? ['optime-api-key', 'optime-url', 'time-stamp'];
      $source['data_fetcher_plugin'] = $backup['data_fetcher_plugin'] ?? 'http';
      $this->state()->delete(self::SOURCE_BACKUP_STATE_KEY);
    }
    else {
      // Fallback to module defaults when no backup is available.
      $source['urls'] = ['https://{optime-url}?fromTimestamp={time-stamp}'];
      $source['headers'] = ['apikey' => 'optime-api-key'];
      $source['placeholders'] = ['optime-api-key', 'optime-url', 'time-stamp'];
      $source['data_fetcher_plugin'] = 'http';
    }

    $config->set('source', $source)->save();

    $this->logger()->success('Optime migration source restored to live URL mode.');
  }

  /**
   * Show current Optime migration source mode.
   *
   * @command optime:source-status
   * @bootstrap full
   * @usage drush optime:source-status
   */
  public function sourceStatus(): void {
    $source = (array) $this->configFactory()->get(self::MIGRATION_ID)->get('source');
    $urls = (array) ($source['urls'] ?? []);
    $fetcher = (string) ($source['data_fetcher_plugin'] ?? 'unknown');
    $mode = $fetcher === 'file' ? 'offline-local' : 'live-url';

    $this->io()->writeln('Mode: ' . $mode);
    $this->io()->writeln('Fetcher: ' . $fetcher);
    $this->io()->writeln('URL(s): ' . implode(', ', $urls));
  }

  /**
   * Restrict source switching commands to local development environments.
   */
  private function assertLocalEnvironment(): void {
    $is_ddev = getenv('IS_DDEV_PROJECT') === 'true' || getenv('DDEV_SITENAME') !== FALSE;
    $uri = (string) Drush::config()->get('options.uri', '');

    $is_local_uri = str_contains($uri, '.ddev.site') || str_contains($uri, '.lndo.site') || str_contains($uri, 'localhost') || str_contains($uri, '127.0.0.1');

    if (!$is_ddev && !$is_local_uri) {
      throw new \RuntimeException('Refusing to change Optime migration source outside local environments.');
    }
  }

  private function configFactory(): ConfigFactoryInterface {
    return \Drupal::configFactory();
  }

  private function state(): StateInterface {
    return \Drupal::state();
  }

}

