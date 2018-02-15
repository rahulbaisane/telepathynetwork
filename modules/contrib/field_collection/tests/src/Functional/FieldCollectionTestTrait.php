<?php

namespace Drupal\Tests\field_collection\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_collection\Entity\FieldCollectionItem;

/**
 * Provides helper properties and functions for field collection tests.
 */
trait FieldCollectionTestTrait {

  /**
   * Field storage config for the field collection bundle.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $field_collection_field_storage;

  /**
   * Field config for the field collection bundle.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $field_collection_field;

  /**
   * Name of the field inside the field collection being used for testing.
   *
   * @var string
   */
  protected $inner_field_name;

  /**
   * Definition of the inner field that can be passed to FieldConfig::create().
   *
   * @var array
   */
  protected $inner_field_definition;

  /**
   * Name of the field collection bundle ie. the field in the host entity.
   *
   * @var string
   */
  protected $field_collection_name;

  /**
   * EntityStorageInterface for nodes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Sets up the data structures for the tests.
   */
  private function setUpFieldCollectionTest() {
    $this->nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    // Create Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create a field_collection field to use for the tests.
    $this->field_collection_name = 'field_test_collection';

    $this->field_collection_field_storage = FieldStorageConfig::create([
      'field_name' => $this->field_collection_name,
      'entity_type' => 'node',
      'type' => 'field_collection',
      'cardinality' => 4,
    ]);

    $this->field_collection_field_storage->save();

    $this->field_collection_field = $this->addFieldCollectionFieldToContentType('article');

    // Create an integer field inside the field_collection.
    $this->inner_field_name = 'field_inner';

    $inner_field_storage = FieldStorageConfig::create([
      'field_name' => $this->inner_field_name,
      'entity_type' => 'field_collection_item',
      'type' => 'integer',
    ]);

    $inner_field_storage->save();

    $this->inner_field_definition = [
      'field_name' => $this->inner_field_name,
      'entity_type' => 'field_collection_item',
      'bundle' => $this->field_collection_name,
      'field_storage' => $inner_field_storage,
      'label' => $this->randomMachineName() . '_label',
      'description' => $this->randomMachineName() . '_description',
      'settings' => [],
    ];

    $inner_field = FieldConfig::create($this->inner_field_definition);

    $inner_field->save();

    entity_get_form_display('field_collection_item', $this->field_collection_name, 'default')
      ->setComponent($this->inner_field_name, ['type' => 'number'])
      ->save();

    entity_get_display('field_collection_item', $this->field_collection_name, 'default')
      ->setComponent($this->inner_field_name, ['type' => 'number_decimal'])
      ->save();
  }

  /**
   * Helper function for adding the field collection field to a content type.
   */
  protected function addFieldCollectionFieldToContentType($content_type) {
    $field_collection_definition = [
      'field_name' => $this->field_collection_name,
      'entity_type' => 'node',
      'bundle' => $content_type,
      'field_storage' => $this->field_collection_field_storage,
      'label' => $this->randomMachineName() . '_label',
      'description' => $this->randomMachineName() . '_description',
      'settings' => [],
    ];

    $field_config = FieldConfig::create($field_collection_definition);

    $field_config->save();

    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.$content_type.default")
      ->setComponent($this->field_collection_name, ['type' => 'field_collection_editable'])
      ->save();

    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load("node.$content_type.default")
      ->setComponent($this->field_collection_name, ['type' => 'field_collection_embed'])
      ->save();

    return $field_config;
  }

  /**
   * Helper for creating a new node with a field collection item.
   */
  protected function createNodeWithFieldCollection($content_type) {
    $node = $this->drupalCreateNode(['type' => $content_type]);

    // Manually create a field_collection.
    $entity = FieldCollectionItem::create(['field_name' => $this->field_collection_name]);

    $entity->{$this->inner_field_name}->setValue(1);
    $entity->setHostEntity($node);
    $entity->save();

    return [$node, $entity];
  }

}
