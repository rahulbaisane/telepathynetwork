<?php

namespace Drupal\field_collection\Tests;

use Drupal\rest\Tests\RESTTestBase;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\field_collection\Functional\FieldCollectionTestTrait;

/**
 * Test REST features.
 *
 * @group field_collection
 */
class FieldCollectionRESTTest extends RESTTestBase {

  use FieldCollectionTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'rest',
    'node',
    'field_collection',
  ];

  /**
   * Sets up the data structures for the tests.
   */
  public function setUp() {
    parent::setUp();
    $this->setUpFieldCollectionTest();
  }

  /**
   * Tests read requests on host entities.
   */
  public function testRead() {
    $this->enableService('entity:node', 'GET', 'json');

    // Create a user account that has the required permissions to read
    // resources via the REST API.
    $permissions = $this->entityPermissions('node', 'view');
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Create a node programmatically.
    list ($node, $field_collection_item) = $this->createNodeWithFieldCollection('article');

    // Read the test node over the REST API.
    $url = $node->toUrl('canonical');
    $url->setRouteParameter('node', $node->id());
    $url->setRouteParameter('_format', 'json');
    $response = $this->httpRequest($url, 'GET', NULL, 'application/json');

    // Check the received data.
    $data = Json::decode($response);
    $field_collection_data = $data['field_test_collection'][0];
    $this->assertEqual($field_collection_data['uuid'][0]['value'], $field_collection_item->uuid(), 'Field collection item UUID is correct');
    $this->assertEqual($field_collection_data['item_id'][0]['value'], $field_collection_item->id(), 'Field collection item ID is correct');
    $this->assertEqual($field_collection_data['host_type'][0]['value'], 'node', 'Field collection item host type is correct');
    $this->assertEqual($field_collection_data['revision_id'][0]['value'], $field_collection_item->getRevisionId(), 'Field collection item revision id is correct');
    $this->assertEqual($field_collection_data['field_name'][0]['target_id'], 'field_test_collection', 'Field collection item field name is correct');
    $this->assertEqual($field_collection_data['field_inner'][0]['value'], '1', 'Field collection item inner field value is correct');
  }

}
