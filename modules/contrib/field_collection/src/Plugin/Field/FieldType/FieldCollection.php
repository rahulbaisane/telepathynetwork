<?php

namespace Drupal\field_collection\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;

/**
 * Plugin implementation of the 'field_collection' field type.
 *
 * @FieldType(
 *   id = "field_collection",
 *   label = @Translation("Field collection"),
 *   description = @Translation(
 *     "This field stores references to embedded entities, which itself may
 *     contain any number of fields."
 *   ),
 *   settings = {
 *     "path" = "",
 *     "hide_blank_items" = TRUE,
 *   },
 *   instance_settings = {
 *   },
 *   default_widget = "field_collection_embed",
 *   default_formatter = "field_collection_list",
 *   list_class = "\Drupal\field_collection\FieldCollectionItemList",
 * )
 */
class FieldCollection extends EntityReferenceItem {

  /**
   * Cache for whether the host is a new revision.
   *
   * Set in preSave and used in update().  By the time update() is called
   * isNewRevision() for the host is always FALSE.
   *
   * @var bool
   */
  protected $newHostRevision;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => 'field_collection_item',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'target_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'description' => 'The ID of the field collection item.',
          'not null' => TRUE,
        ],
        'revision_id' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(t('The ID of the field collection item.'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $properties['revision_id'] = DataDefinition::create('integer')
      ->setLabel(t('Field collection item revision'))
      ->setReadOnly(TRUE);

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Field collection item'))
      ->setDescription(t('The referenced field collection item'))
      // The field collection item object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setTargetDefinition(EntityDataDefinition::create('field_collection_item'))
      ->addConstraint('EntityType', 'field_collection_item');

    return $properties;
  }

  /**
   * Override EntityReferenceItem storage settings form.
   *
   * The target type setting from EntityReferenceItem does not apply to field
   * collections so override the settings form with a blank one.
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return array();
  }

  /**
   * Override EntityReferenceItem field settings form.
   *
   * These options do not apply to field collections.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    $form['handler']['#access'] = FALSE;
    $form['handler']['handler_settings']['target_bundles']['#access'] = FALSE;
    $form['handler']['handler_settings']['target_bundles']['#default_value'] = array($this->getFieldDefinition()->getName() => $this->getFieldDefinition()->getName());
    $form['handler']['handler_settings']['auto_create']['#access'] = FALSE;
    $form['handler']['handler']['#access'] = FALSE;
    $form['handler']['handler_settings']['sort']['field']['#access'] = FALSE;

    return $form;
  }

  /**
   * @TODO
   */
  public function getFieldCollectionItem($create = FALSE, $use_reference = TRUE) {
    if ($use_reference && isset($this->entity)) {
      return $this->entity;
    }
    elseif (isset($this->target_id)) {
      // By default always load the default revision, so caches get used.
      $field_collection_item = FieldCollectionItem::load($this->target_id);
      if ($field_collection_item !== NULL && $field_collection_item->getRevisionId() != $this->revision_id) {
        // A non-default revision is a referenced, so load this one.
        $field_collection_item = \Drupal::entityTypeManager()->getStorage('field_collection_item')->loadRevision($this->revision_id);
      }
      return $field_collection_item;
    }
    elseif ($create) {
      $field_collection_item = FieldCollectionItem::create(['field_name' => $this->getFieldDefinition()->getName()]);

      // TODO: Uncomment or delete
      /*
      $field_collection_item->setHostEntity($this->getEntity(), FALSE);
      */

      return $field_collection_item;
    }
    return FALSE;
  }

  public function delete() {
    $field_collection_item = $this->getFieldCollectionItem();
    if ($field_collection_item !== NULL) {
      // Set a flag so the field collection item entity knows that the field in
      // its host is already being taken care of.
      // See \Drupal\field_collection\Entity\FieldCollectionItem::delete().
      $field_collection_item->field_collection_deleting = TRUE;
      $field_collection_item->delete();
    }
    parent::delete();
  }

  // TODO: Format comment
  /**
   * Care about removed field collection items.
   *
   * Support saving field collection items in @code $item['entity'] @endcode. This
   * may be used to seamlessly create field collection items during host-entity
   * creation or to save changes to the host entity and its collections at once.
   */
  public function preSave() {

    if ($field_collection_item = $this->getFieldCollectionItem()) {
      $host = $this->getEntity();

      // Handle node cloning
      if($host->isNew() && !$field_collection_item->isNew()) {
        // If the host entity is new but we have a field_collection that is not
        // new, it means that its host is being cloned. Thus we need to clone
        // the field collection entity as well.
        $field_collection_item = $field_collection_item->createDuplicate();
      }

      // TODO: Handle deleted items
      /*
      $field_name = $this->getFieldDefinition()->field_name;
      $host_original = $host->original;
      $items_original = !empty($host_original->$field_name) ? $host_original->$field_name : [];
      $original_by_id = array_flip(field_collection_field_item_to_ids($items_original));
      foreach ($items as &$item) {
      */

      // TODO: Handle deleted items
      /*
        unset($original_by_id[$item['target_id']]);
      }
      // If there are removed items, care about deleting the item entities.
      if ($original_by_id) {
        $ids = array_flip($original_by_id);
        // If we are creating a new revision, the old-items should be kept but get
        // marked as archived now.
        if (!empty($host_entity->revision)) {
          db_update('field_collection_item')
            ->fields(['archived' => 1])
            ->condition('target_id', $ids, 'IN')
            ->execute();
        }
        else {
          // Delete unused field collection items now.
          foreach (FieldCollectionItem::loadMultiple($ids) as $un_item) {
            $un_item->updateHostEntity($host_entity);
            $un_item->deleteRevision(TRUE);
          }
        }
      }
      */

      $this->newHostRevision = $host->isNewRevision();

      // If the host entity is saved as new revision, do the same for the item.
      if ($this->newHostRevision) {

        $field_collection_item->setNewRevision();

        // TODO: Verify for D8, may not be necessary
        /*
        // Without this cache clear entity_revision_is_default will
        // incorrectly return false here when creating a new published revision
        if (!isset($cleared_host_entity_cache)) {
          list($entity_id) = entity_extract_ids($host_entity_type, $host_entity);
          entity_get_controller($host_entity_type)->resetCache([$entity_id]);
          $cleared_host_entity_cache = true;
        }
        */

        if ($host->isDefaultRevision()) {
          $field_collection_item->isDefaultRevision(TRUE);
          //$entity->archived = FALSE;
        }
      }

      if ($field_collection_item->isNew()) {
        $field_collection_item->setHostEntity($this->getEntity(), FALSE);
      }

      $field_collection_item->save(TRUE);
      $this->target_id = $field_collection_item->id();
      $this->revision_id = $field_collection_item->getRevisionId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->target_id) {
      return FALSE;
    }
    else if ($this->getFieldCollectionItem()) {
      return $this->getFieldCollectionItem()->isEmpty();
    }
    return TRUE;
  }

  /**
   * No preconfigured options.
   *
   * This overrides the EntityReferenceItem version because that would allow
   * FieldCollectionItem fields to be created that could point to entities
   * other than FieldCollectionItems.
   */
  public static function getPreconfiguredOptions() {
    return array();
  }

}

