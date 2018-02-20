<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\UploadSourceTest.
 */

namespace Drupal\filefield_sources\Tests;

/**
 * Tests the upload source.
 *
 * @group filefield_sources
 */
class UploadSourceTest extends FileFieldSourcesTestBase {

  /**
   * Tests upload source enabled.
   */
  public function testUploadSourceEnabled() {
    $this->enableSources(array(
      'upload' => TRUE,
    ));

    $this->assertUploadSourceWorkProperly();
  }

  /**
   * Tests all sources enabled.
   */
  public function testAllSourcesEnabled() {
    $this->enableSources(array(
      'upload' => TRUE,
      'remote' => TRUE,
      'clipboard' => TRUE,
      'reference' => TRUE,
      'attach' => TRUE,
    ));

    $this->assertUploadSourceWorkProperly();
  }

  /**
   * Tests upload source still working properly.
   */
  protected function assertUploadSourceWorkProperly() {
    $file = $this->createTemporaryFileEntity();

    // Upload a file by 'Upload' source.
    $this->uploadFileByUploadSource($file->getFileUri(), $file->getFilename(), 0, FALSE);

    // We can only upload one file on single value field.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Upload'), t('After uploading a file, "Upload" button is no longer displayed.'));

    // Remove uploaded file.
    $this->removeFile($file->getFilename(), 0);

    // Can upload file again.
    $this->assertFieldByXpath('//input[@type="submit"]', t('Upload'), 'After clicking the "Remove" button, the "Upload" button is displayed.');
  }

}
