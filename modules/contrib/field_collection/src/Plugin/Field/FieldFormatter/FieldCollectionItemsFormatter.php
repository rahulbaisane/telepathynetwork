<?php

namespace Drupal\field_collection\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_collection_items' formatter.
 *
 * @FieldFormatter(
 *   id = "field_collection_items",
 *   label = @Translation("Field Collection Items"),
 *   field_types = {
 *     "field_collection"
 *   },
 * )
 */
class FieldCollectionItemsFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $render_items = parent::viewElements($items, $langcode);

    // Make subfields accessible via twig.
    // For example, in field--field-collection.html.twig:
    // {{ items[0].content.field_aaa }}
    foreach($render_items as $delta => $item) {
      $builder = $item['#pre_render'][0][0];
      unset($item['#pre_render']);
      $render_items[$delta] = $builder->build($item);
    }

    return $render_items;
  }
}
