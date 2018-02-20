<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Access\FieldAccessCheck.
 */

namespace Drupal\filefield_sources\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for file field source routes.
 */
class FieldAccessCheck implements RoutingAccessInterface {

  /**
   * Checks access.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle_name
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access($entity_type, $bundle_name, $field_name, AccountInterface $account) {
    $field = entity_load('field_config', $entity_type . '.' . $bundle_name . '.' . $field_name);
    return $field->access('edit', $account, TRUE);
  }

}
