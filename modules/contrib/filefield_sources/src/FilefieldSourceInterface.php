<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\FilefieldSourceInterface.
 */

namespace Drupal\filefield_sources;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for file field source plugins.
 *
 * @see \Drupal\filefield_sources\FilefieldSourceManager
 * @see \Drupal\filefield_sources\Annotation\FilefieldSource
 * @see plugin_api
 *
 * @ingroup filefield_sources
 */
interface FilefieldSourceInterface {

  /**
   * Value callback for file field source plugin.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param mixed $input
   *   The incoming input to populate the form element. If this is FALSE,
   *   the element's default value should be returned.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed
   *   The value to assign to the element.
   */
  public static function value(array &$element, &$input, FormStateInterface $form_state);

  /**
   * Process callback for file field source plugin.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form);

}
