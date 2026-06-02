<?php

declare(strict_types=1);

namespace Drupal\migrate_optime_json\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for retrieving data via URLs.
 */
#[MigrateSource(id: 'optime_url')]
class OptimeUrl extends Url {

  /**
   * The state service.
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    DataParserPluginManager $parserPluginManager,
    StateInterface $state,
  ) {
    $this->state = $state;

    // Track last imported time.
    if (isset($configuration['track_last_imported']) && $configuration['track_last_imported']) {
      $configuration['track_last_imported'] = TRUE;
    }

    // Replace all placeholders from settings.
    if (isset($configuration['placeholders'])) {
      $this->handlePlaceholders($configuration);
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parserPluginManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    /** @var \Drupal\migrate_plus\DataParserPluginManager $parser_plugin_manager */
    $parser_plugin_manager = $container->get('plugin.manager.migrate_plus.data_parser');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $container->get('state');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $parser_plugin_manager,
      $state,
    );
  }

  /**
  * Sets placeholders.
  *
  * @param array $configuration
  *   Plugin configuration.
  */
  private function handlePlaceholders(array &$configuration): void {
    // Handle placeholders for URL and API key values.
    foreach ($configuration['placeholders'] as $placeholder) {
      if ($placeholder === 'time-stamp') {
        // Import the last 3 days of changes.
        $import_period = (string) (time() - (3 * 24 * 3600));
        $configuration['urls'] = str_replace('{' . $placeholder . '}', $import_period, $configuration['urls']);
        continue;
      }

      // This needs state vars to be set per environment/release.
      $value = (string) $this->state->get($placeholder, '');

      if ($placeholder === 'optime-url') {
        $configuration['urls'] = str_replace('{' . $placeholder . '}', $value, $configuration['urls']);
      }

      if ($placeholder === 'optime-api-key' && !empty($configuration['headers'])) {
        foreach ($configuration['headers'] as $key => $header) {
          $configuration['headers'][$key] = str_replace($placeholder, $value, (string) $header);
        }
      }
    }
  }

}
