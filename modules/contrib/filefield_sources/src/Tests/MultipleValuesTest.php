<?php

/**
 * @file
 * Definition of Drupal\filefield_sources\Tests\MultipleValuesTest.
 */

namespace Drupal\filefield_sources\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests multiple sources on multiple values field.
 *
 * @group filefield_sources
 */
class MultipleValuesTest extends FileFieldSourcesTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('imce');

  /**
   * Sets up for multiple values test case.
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpImce();

    // Create test files.
    $this->permanent_file_entity_1 = $this->createPermanentFileEntity();
    $this->permanent_file_entity_2 = $this->createPermanentFileEntity();
    $this->temporary_file_entity_1 = $this->createTemporaryFileEntity();
    $this->temporary_file_entity_2 = $this->createTemporaryFileEntity();

    $path = file_default_scheme() . '://' . FILEFIELD_SOURCE_ATTACH_DEFAULT_PATH . '/';
    $this->temporary_file = $this->createTemporaryFile($path);

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
  }

  /**
   * Tests uploading then removing files.
   */
  public function testUploadThenRemoveFiles() {
    $this->uploadFiles();

    // Remove all uploaded files.
    $this->removeFile($this->temporary_file_entity_2->getFilename(), 4);
    $this->removeFile('INSTALL.txt', 0);
    $this->removeFile($this->temporary_file_entity_1->getFilename(), 1);
    $this->removeFile($this->temporary_file->filename, 1);
    $this->removeFile($this->permanent_file_entity_1->getFilename(), 0);
    $this->removeFile($this->permanent_file_entity_2->getFilename(), 0);

    // Ensure all files have been removed.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), 'All files have been removed.');
  }

  /**
   * Tests uploading files and saving node.
   */
  public function testUploadFilesThenSaveNode() {
    $this->uploadFiles();

    $this->drupalPostForm(NULL, array('title[0][value]' => $this->randomMachineName()), t('Save and publish'));

    // Ensure all files are saved to node.
    $this->assertLink('INSTALL.txt');
    $this->assertLink($this->permanent_file_entity_1->getFilename());
    $this->assertLink($this->permanent_file_entity_2->getFilename());
    $this->assertLink($this->temporary_file_entity_1->getFilename());
    $this->assertLink($this->temporary_file_entity_2->getFilename());
    $this->assertLink($this->temporary_file->filename);
  }

  /**
   * Upload files.
   *
   * @return int
   *   Number of files uploaded.
   */
  protected function uploadFiles() {
    $uploaded_files = 0;

    // Ensure no files has been uploaded.
    $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), 'There are no file have been uploaded.');

    // Upload a file by 'Remote' source.
    $this->uploadFileByRemoteSource($GLOBALS['base_url'] . '/core/INSTALL.txt', 'INSTALL.txt', $uploaded_files);
    $uploaded_files++;

    // Upload a file by 'Reference' source.
    $this->uploadFileByReferenceSource($this->permanent_file_entity_1->id(), $this->permanent_file_entity_1->getFilename(), $uploaded_files);
    $uploaded_files++;

    // Upload a file by 'Clipboard' source.
    $this->uploadFileByClipboardSource($this->temporary_file_entity_1->getFileUri(), $this->temporary_file_entity_1->getFileName(), $uploaded_files);
    $uploaded_files++;

    // Upload a file by 'Attach' source.
    $this->uploadFileByAttachSource($this->temporary_file->uri, $this->temporary_file->filename, $uploaded_files);
    $uploaded_files++;

    // Upload a file by 'Upload' source.
    $this->uploadFileByUploadSource($this->temporary_file_entity_2->getFileUri(), $this->temporary_file_entity_2->getFilename(), $uploaded_files, TRUE);
    $uploaded_files++;

    // Upload a file by 'Imce' source.
    $this->uploadFileByImceSource($this->permanent_file_entity_2->getFileUri(), $this->permanent_file_entity_2->getFileName(), $uploaded_files);
    $uploaded_files++;

    // Ensure files have been uploaded.
    $remove_buttons = $this->xpath('//input[@type="submit" and @value="' . t('Remove') . '"]');
    $this->assertEqual(count($remove_buttons), $uploaded_files, "There are $uploaded_files files have been uploaded.");

    return $uploaded_files;
  }

}
