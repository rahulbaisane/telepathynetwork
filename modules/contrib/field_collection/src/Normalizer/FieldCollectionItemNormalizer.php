<?php

namespace Drupal\field_collection\Normalizer;

use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\field_collection\Plugin\Field\FieldType\FieldCollection;

/**
 * Adds field collection items to field collections.
 */
class FieldCollectionItemNormalizer extends ComplexDataNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldCollection::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    // Set the normalized field output to the field collection item.
    $values = $this->serializer->normalize($field_item->getFieldCollectionItem(), $format, $context);

    return $values;
  }

}
