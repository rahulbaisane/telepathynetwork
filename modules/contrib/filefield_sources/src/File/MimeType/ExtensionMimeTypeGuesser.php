<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\File\MimeType\ExtensionMimeTypeGuesser.
 */

namespace Drupal\filefield_sources\File\MimeType;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser as CoreExtensionMimeTypeGuesser;

/**
 * Add methods to core guesser.
 */
class ExtensionMimeTypeGuesser extends CoreExtensionMimeTypeGuesser {

  /**
   * Convert mime type to extension.
   *
   * @param string $mimetype
   *   Mime type.
   *
   * @return string|bool
   *   Return extension if found, FALSE otherwise.
   */
  public function convertMimeTypeToExtension($mimetype) {
    $this->checkDefaultMapping();

    $mime_key = array_search($mimetype, $this->mapping['mimetypes']);
    $extension = array_search($mime_key, $this->mapping['extensions']);

    return $extension;
  }

  /**
   * Convert mime type to most common extension.
   *
   * @param string $mimetype
   *   Mime type.
   *
   * @return string|bool
   *   Return extension if found, FALSE otherwise.
   */
  public function convertMimeTypeToMostCommonExtension($mimetype) {
    $this->checkDefaultMapping();

    $extension = FALSE;
    if (isset($mimetype)) {
      // See if this matches a known MIME type.
      $mime_key = array_search($mimetype, $this->mapping['mimetypes']);
      if ($mime_key !== FALSE) {
        // If we have a match, get this list of likely extensions. For some
        // reason Drupal lists the "most common" extension last for most file
        // types including php, jpg, and doc.
        if ($extensions = array_keys($this->mapping['extensions'], $mime_key)) {
          $extension = end($extensions);
        }
      }
    }
    return $extension;
  }

  /**
   * Check for default mapping.
   */
  private function checkDefaultMapping() {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
      $this->mapping = $mapping;
    }
  }

}
