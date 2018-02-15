<?php

namespace Drupal\field_collection\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Entity\ContentEntityInterface;

abstract class FieldCollectionLinksFormatter extends FormatterBase {

  /**
   * Helper function to get Edit and Delete links for an item.
   */
  protected function getEditLinks(FieldItemInterface $item) {
    $links = '';
    if ($item->getEntity()->access('update', \Drupal::currentUser())) {
      $links = '(' . Link::fromTextAndUrl(
        t('Edit'),
        Url::FromRoute(
          'entity.field_collection_item.edit_form',
          ['field_collection_item' => $item->target_id]
        ))
        ->toString();

      $links .= '|' . Link::fromTextAndUrl(
        t('Delete'),
        Url::FromRoute(
          'entity.field_collection_item.delete_form',
          ['field_collection_item' => $item->target_id]
        ))
        ->toString();

      $links .= ')';
    }

    return $links;
  }

  /***
   * Return a link to add a field collection item entity to this field.
   *
   * Returns a blank string if the field is at maximum capacity or the user
   * does not have access to edit it.
   */
  protected function getAddLink(ContentEntityInterface $host) {
    $link = '';

    if ($host->access('update', \Drupal::currentUser())) {
      $link = '<ul class="action-links action-links-field-collection-add"><li>';

      $link .= Link::fromTextAndUrl(t('Add'), Url::FromRoute('field_collection_item.add_page', [
        'field_collection' => $this->fieldDefinition->getName(),
        'host_type' => $host->getEntityTypeId(),
        'host_id' => $host->id(),
      ]))->toString();

      $link .= '</li></ul>';
    }

    return($link);
  }

}
