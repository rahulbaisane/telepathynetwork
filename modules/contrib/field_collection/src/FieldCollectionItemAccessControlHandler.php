<?php

namespace Drupal\field_collection;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class FieldCollectionItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\field_collection\Entity\FieldCollectionItem $entity */
    $result = parent::checkAccess($entity, $operation, $account);

    if (!$result->isForbidden()) {
      $host = $entity->getHost();

      if (NULL !== $host) {
        return $host->access($operation, $account, TRUE);
      }
      // Here we will be if host entity was not set and entity is not new.
      elseif (!$entity->isNew()) {
        throw new \RuntimeException($this->t('Host entity for field collection item (@id) was not set.', [
          '@id' => $entity->id(),
        ]));
      }
    }

    return $result;
  }

}
