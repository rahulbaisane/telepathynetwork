<?php
/**
 * @file
 * This file documents hooks provided by the FileField Sources module.
 *
 * Note that none of this code is executed by using FileField Sources module,
 * it is provided here for reference as an example how to implement these hooks
 * in your own module.
 */

/**
 * Returns a list of widgets that are compatible with FileField Sources.
 *
 * FileField Sources works with the most common widgets used with Drupal (the
 * standard Image and File widgets). Any module that provides another widget
 * for uploading files may add compatibility with FileField Sources by
 * implementing this hook and returning the widgets that their module supports.
 */
function hook_filefield_sources_widgets() {
  // Add any widgets that your module supports here.
  return array('mymodule_file_widgetname');
}

/**
 * Allows altering the sources available on a field.
 *
 * This hook allows other modules to modify the sources available to a user.
 *
 * @param array $sources
 *   List of filefiled sources plugins.
 *
 * @param mixed $context
 *   Contains 'enabled_sources', 'element', 'form_state'.
 */
function hook_filefield_sources_sources_alter(&$sources, $context) {
  // This example will exclude sources the user doesn't have access to.
  foreach (array_keys($sources) as $type) {
    if (!user_access("use $type filefield source")) {
      unset($sources[$type]);
    }
  }
}
