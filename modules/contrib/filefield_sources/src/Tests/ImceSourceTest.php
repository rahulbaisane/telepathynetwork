<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\ClipboardSourceTest.
 */

namespace Drupal\filefield_sources\Tests;

/**
 * Tests the imce source.
 *
 * @group filefield_sources
 */
class ImceSourceTest extends FileFieldSourcesTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('imce');

  /**
   * Sets up for imce source test case.
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpImce();
  }

  /**
   * Tests imce source enabled.
   */
  public function testImceSourceEnabled() {
    $this->enableSources(array(
      'imce' => TRUE,
    ));
    $file = $this->createPermanentFileEntity();

    $this->uploadFileByImceSource($file->getFileUri(), $file->getFilename(), 0);

    // We can only upload one file on single value field.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Select'), t('After uploading a file, "Select" button is no longer displayed.'));

    $this->removeFile($file->getFilename(), 0);

    // Can upload file again.
    $this->assertFieldByXpath('//input[@type="submit"]', t('Select'), 'After clicking the "Remove" button, the "Select" button is displayed.');
  }

}
