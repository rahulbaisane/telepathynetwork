<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\RemoteSourceTest.
 */

namespace Drupal\filefield_sources\Tests;

/**
 * Tests the remote source.
 *
 * @group filefield_sources
 */
class RemoteSourceTest extends FileFieldSourcesTestBase {

  /**
   * Tests remote source enabled.
   */
  public function testRemoteSourceEnabled() {
    $this->enableSources(array(
      'remote' => TRUE,
    ));

    // Upload a file by 'Remote' source.
    $this->uploadFileByRemoteSource($GLOBALS['base_url'] . '/README.txt', 'README.txt', 0);

    // We can only transfer one file on single value field.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Transfer'), t('After uploading a file, "Transfer" button is no longer displayed.'));

    // Remove uploaded file.
    $this->removeFile('README.txt', 0);

    // Can transfer file again.
    $this->assertFieldByXpath('//input[@type="submit"]', t('Transfer'), 'After clicking the "Remove" button, the "Transfer" button is displayed.');
  }

}
