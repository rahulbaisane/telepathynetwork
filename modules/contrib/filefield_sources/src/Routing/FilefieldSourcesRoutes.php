<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Routing\FilefieldSourcesRoutes.
 */

namespace Drupal\filefield_sources\Routing;

/**
 * Defines a route subscriber to register a url for serving filefield sources.
 */
class FilefieldSourcesRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();

    foreach (\Drupal::service('filefield_sources')->getDefinitions() as $definition) {
      // Get routes defined by each plugin.
      $callback = array($definition['class'], 'routes');
      if (is_callable($callback)) {
        $routes = array_merge($routes, call_user_func($callback));
      }
    }

    return $routes;
  }

}
