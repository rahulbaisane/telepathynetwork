<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\ClipboardSourceTest.
 */

namespace Drupal\filefield_sources\Tests;

/**
 * Tests the clipboard source.
 *
 * @group filefield_sources
 */
class ClipboardSourceTest extends FileFieldSourcesTestBase {

  /**
   * Tests clipboard source enabled.
   */
  public function testClipboardSourceEnabled() {
    $this->enableSources(array(
      'clipboard' => TRUE,
    ));
    $file = $this->createTemporaryFileEntity();

    $this->uploadFileByClipboardSource($file->getFileUri(), $file->getFilename(), 0);

    // We can only upload one file on single value field.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Upload'), t('After uploading a file, "Upload" button is no longer displayed.'));

    $this->removeFile($file->getFilename(), 0);

    // Can upload file again.
    $this->assertFieldByXpath('//input[@type="submit"]', t('Upload'), 'After clicking the "Remove" button, the "Upload" button is displayed.');
  }

}
