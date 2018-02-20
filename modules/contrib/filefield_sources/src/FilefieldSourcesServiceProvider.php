<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\FilefieldSourcesServiceProvider.
 */

namespace Drupal\filefield_sources;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Alter file.mime_type.guesser.extension service.
 */
class FilefieldSourcesServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('file.mime_type.guesser.extension');
    $definition->setClass('Drupal\filefield_sources\File\MimeType\ExtensionMimeTypeGuesser');
  }

}
