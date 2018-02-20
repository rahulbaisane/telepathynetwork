<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\EmptyValuesTest.
 */

namespace Drupal\filefield_sources\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests empty values.
 *
 * @group filefield_sources
 */
class EmptyValuesTest extends FileFieldSourcesTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('imce');

  /**
   * Sets up for empty values test case.
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpImce();
  }

  /**
   * Tests all sources enabled.
   */
  public function testAllSourcesEnabled() {
    // Change allowed number of values.
    $this->drupalPostForm('admin/structure/types/manage/' . $this->typeName . '/fields/node.' . $this->typeName . '.' . $this->fieldName . '/storage', array('cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED), t('Save field settings'));

    $this->enableSources(array(
      'upload' => TRUE,
      'remote' => TRUE,
      'clipboard' => TRUE,
      'reference' => TRUE,
      'attach' => TRUE,
      'imce' => TRUE,
    ));

    // Upload a file by 'Remote' source.
    $this->uploadFileByRemoteSource();

    // Upload a file by 'Reference' source.
    $this->uploadFileByReferenceSource();

    // Upload a file by 'Clipboard' source.
    $this->uploadFileByClipboardSource();

    // Upload a file by 'Attach' source.
    $this->uploadFileByAttachSource();

    // Upload a file by 'Upload' source.
    $this->uploadFileByUploadSource('', '', 0, TRUE);

    // Upload a file by 'Imce' source.
    $this->uploadFileByImceSource();

    $this->assertUniqueSubmitButtons();
  }

  /**
   * Check that there is only one submit button of a source.
   */
  protected function assertUniqueSubmitButtons() {
    $buttons = array(
      $this->fieldName . '_0_attach' => t('Attach'),
      $this->fieldName . '_0_clipboard_upload_button' => t('Upload'),
      $this->fieldName . '_0_autocomplete_select' => t('Select'),
      $this->fieldName . '_0_transfer' => t('Transfer'),
      $this->fieldName . '_0_upload_button' => t('Upload'),
      $this->fieldName . '_0_imce_select' => t('Select'),
    );
    foreach ($buttons as $button_name => $button_label) {
      // Ensure that there is only one button with name.
      $buttons = $this->xpath('//input[@name="' . $button_name . '" and @value="' . $button_label . '"]');
      $this->assertEqual(count($buttons), 1, format_string('There is only one button with name %name and label %label', array('%name' => $button_name, '%label' => $button_label)));
    }
  }

}
