<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Annotation\FilefieldSource.
 */

namespace Drupal\filefield_sources\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API processor annotation object.
 *
 * @Annotation
 */
class FilefieldSource extends Plugin {

  /**
   * The file field source plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the file field source plugin.
   *
   * It will be displayed in a select list.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * The human-readable name of the file field source plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the file field source plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The weight of file field source plugin.
   *
   * @var integer
   */
  public $weight;

}
