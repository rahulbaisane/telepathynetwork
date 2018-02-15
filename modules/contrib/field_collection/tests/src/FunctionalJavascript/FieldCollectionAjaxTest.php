<?php

namespace Drupal\Tests\field_collection\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\field_collection\Functional\FieldCollectionTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests that AJAX functionality works.
 *
 * @group Ajax
 */
class FieldCollectionAjaxTest extends JavascriptTestBase {
  use FieldCollectionTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_collection', 'node', 'field', 'field_ui'];

  /**
   * Sets up the data structures for the tests.
   */
  public function setUp() {
    parent::setUp();
    $this->setUpFieldCollectionTest();
  }

  /**
   * Tests how Field Collections manage empty fields.
   *
   * @see \Drupal\field_collection\Plugin\Field\FieldWidget\FieldCollectionEmbedWidget::formMultipleElements()
   */
  public function testEmptyFields() {
    $user_privileged = $this->drupalCreateUser([
      'access content',
      'edit any article content',
      'create article content',
    ]);
    $this->drupalLogin($user_privileged);

    // First, set the field collection cardinality to unlimited.
    $field_config = FieldStorageConfig::loadByName('node', $this->field_collection_name);
    $field_config->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_config->save();

    // Check that we can see field collection fields when creating content.
    $this->drupalGet('node/add/article');
    $this->assertFieldById('edit-field-test-collection-0-field-inner-0-value');

    // Check that the "Add another item" button works as expected.
    //$this->drupalPostAjaxForm('node/add/article', [], ['field_test_collection_add_more' => t('Add another item')]);
    $this->click('.field-add-more-submit');
    // The AJAX request changes field identifiers, so we need to find them by name.
    $this->assertJsCondition('jQuery("[name=\'field_test_collection[0][field_inner][0][value]\']").length', 10000);
    $this->assertJsCondition('jQuery("[name=\'field_test_collection[1][field_inner][0][value]\']").length', 10000);

    // Check that we can see an empty field collection when editing content
    // that did not have values for it.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldById('edit-field-test-collection-0-field-inner-0-value');
  }

}

