<?php

namespace Drupal\field_collection\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Provides tests for converting field collections to entity references.
 *
 * @group field_collection
 */
class Update8001Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/test_update_8001.php.gz',
    ];
  }

  /**
   * Tests that the data still exists after the update.
   */
  public function testUpdateHook8001() {
    $this->runUpdates();

    $node = node_load(1);
    $this->assertIdentical($node->field_test_field_collection_1[0]->target_id, '1', 'field_collection_item value converted to target_id.');
    $this->assertIdentical($node->field_test_field_collection_1[2]->target_id, '3', 'field_collection_item value converted to target_id.');
    $this->assertIdentical($node->field_test_field_collection_2[0]->target_id, '4', 'field_collection_item value converted to target_id.');
    $this->assertIdentical($node->field_test_field_collection_2[2]->target_id, '6', 'field_collection_item value converted to target_id.');
  }

}
