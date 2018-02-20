<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\FilefieldSourceManager.
 */

namespace Drupal\filefield_sources;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for file field source.
 *
 * @see \Drupal\filefield_sources\Annotation\FilefieldSource
 * @see \Drupal\filefield_sources\FilefieldSourceInterface
 * @see plugin_api
 */
class FilefieldSourceManager extends DefaultPluginManager {

  /**
   * Constructs a FilefieldSourceManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->setCacheBackend($cache_backend, 'filefield_sources');

    parent::__construct('Plugin/FilefieldSource', $namespaces, $module_handler, 'Drupal\filefield_sources\FilefieldSourceInterface', 'Drupal\filefield_sources\Annotation\FilefieldSource');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    if (!\Drupal::moduleHandler()->moduleExists('imce')) {
      unset($definitions['imce']);
    }
    if (!filefield_sources_curl_enabled()) {
      unset($definitions['remote']);
    }
    return $definitions;
  }

}
