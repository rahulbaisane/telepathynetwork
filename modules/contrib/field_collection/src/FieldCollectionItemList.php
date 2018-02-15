<?php

namespace Drupal\field_collection;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a item list class for field collection fields.
 */
class FieldCollectionItemList extends EntityReferenceFieldItemList {

  /**
   * Override submission handler from EntityReferenceFieldItemList.
   *
   * Default values do not apply to field collection items.  This override is
   * needed to stop an error message when the field settings form is submitted.
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    return [];
  }

}
