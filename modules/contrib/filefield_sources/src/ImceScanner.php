<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\ImceScanner.
 */

namespace Drupal\filefield_sources;

use Drupal\imce\Imce;

/**
 * Imce scanner service.
 */
class ImceScanner {

  /**
   * Scanner context.
   *
   * @var mixed
   */
  private $context = NULL;

  /**
   * Sets scanner context.
   */
  public function setContext($context) {
    $this->context = $context;
  }

  /**
   * Scan and return files, subdirectories.
   */
  public function customScanFull($dirname, $options) {
    // Get a list of files in the database for this directory.
    $scheme = $this->context['scheme'];
    $sql_uri_name = $dirname == '.' ? $scheme . '://' : $dirname . '/';

    $result = db_select('file_managed', 'f')
      ->fields('f', array('uri'))
      ->condition('f.uri', $sql_uri_name . '%', 'LIKE')
      ->condition('f.uri', $sql_uri_name . '_%/%', 'NOT LIKE')
      ->execute();

    $db_files = array();
    foreach ($result as $row) {
      $db_files[basename($row->uri)] = 1;
    }

    // Get the default IMCE directory scan, then filter down to database files.
    $content = Imce::scanDir($dirname, $options);
    foreach ($content['files'] as $filename => $file) {
      if (!isset($db_files[$filename])) {
        unset($content['files'][$filename]);
      }
    }

    return $content;
  }

  /**
   * Scan directory and return file list.
   *
   * This only work on Restricted Mode.
   */
  public function customScanRestricted($dirname, $options) {
    $content = array('files' => array(), 'subfolders' => array());
    $field_uri = $this->context['uri'];
    $is_root = $this->context['is_root'];

    if ($dirname !== $field_uri) {
      return $content;
    }

    $entity_type = $this->context['entity_type'];
    $field_name = $this->context['field_name'];
    $field_storage = entity_load('field_storage_config', $entity_type . '.' . $field_name);
    $entity_manager = \Drupal::entityManager();
    if ($entity_manager->hasDefinition($entity_type)) {
      $storage = $entity_manager->getStorage($entity_type);
      $table_mapping = $storage->getTableMapping();
      $field_table = $table_mapping->getDedicatedDataTableName($field_storage);
      $field_column_name = $table_mapping->getFieldColumnName($field_storage, 'target_id');

      $sql_uri = $field_uri . ($is_root ? '' : '/');
      $query = db_select($field_table, 'cf');
      $query->innerJoin('file_managed', 'f', 'f.fid = cf.' . $field_column_name);
      $result = $query->fields('f')
        ->condition('f.status', 1)
        ->condition('f.uri', $sql_uri . '%', 'LIKE')
        ->condition('f.uri', $sql_uri . '%/%', 'NOT LIKE')
        ->execute();
      foreach ($result as $file) {
        // Get real name.
        $name = basename($file->uri);
        $content['files'][$name] = $file->uri;
      }
    }

    return $content;
  }
}
