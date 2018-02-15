<?php

namespace Drupal\field_collection\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:field_collection_item"
 * )
 */
class EntityFieldCollection extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {

    /** @var \Drupal\field_collection\Entity\FieldCollectionItem $field_collection */
    $field_collection = $this->getEntity($row, $old_destination_id_values);

    if ($field_collection->isNew()) {
      // Set host entity based on values passed from source.
      $host_entity = \Drupal::entityTypeManager()
        ->getStorage($row->getDestinationProperty('host_type'))
        ->load($row->getDestinationProperty('host_entity_id'));
      $field_collection->setHostEntity($host_entity);
    }
    $field_collection->save();

    return [$field_collection->id()];
  }
}
