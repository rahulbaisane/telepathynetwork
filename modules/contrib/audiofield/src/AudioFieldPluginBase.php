<?php

namespace Drupal\audiofield;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Random;
use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for audiofield plugins. Includes global functions.
 */
abstract class AudioFieldPluginBase extends PluginBase {

  /**
   * Renders the player.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The uploaded item list.
   * @param string $langcode
   *   The language code.
   * @param array $settings
   *   An array of additional render settings.
   *
   * @return array
   *   Returns the rendered array.
   */
  abstract public function renderPlayer(FieldItemListInterface $items, $langcode, array $settings);

  /**
   * Gets the plugin_id of the plugin instance.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   */
  public function getPluginId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Gets the title of the plugin instance.
   *
   * @return string
   *   The title of the plugin instance.
   */
  public function getPluginTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * Gets the name of the main library of the plugin instance.
   *
   * @return string
   *   The name of the main library of the plugin instance.
   */
  public function getPluginLibraryName() {
    return $this->pluginDefinition['libraryName'];
  }

  /**
   * Gets the main library instance of this plugin.
   *
   * @return array
   *   The definition of the main library for this plugin.
   */
  public function getPluginLibrary() {
    // Get the main library for this plugin.
    return \Drupal::service('library.discovery')->getLibraryByName('audiofield', 'audiofield.' . $this->getPluginLibraryName());
  }

  /**
   * Parses library to get version number of installed library.
   *
   * @return string
   *   The version number of the currently installed library.
   */
  public function getPluginLibraryVersion() {
    // Default to 1. This is implemented in the plugins.
    return '1';
  }

  /**
   * Gets the location of this plugin's library installation.
   *
   * @return string
   *   The library path of the plugin instance.
   */
  public function getPluginLibraryPath() {
    // Get the main library for this plugin.
    $library = $this->getPluginLibrary();

    return preg_filter('%(^libraries/[^//]+).*%', '/$1', $library['js'][0]['data']);
  }

  /**
   * Gets the remote download source from the plugin's main library.
   *
   * @return string
   *   The remote download source of the plugin instance.
   */
  public function getPluginRemoteSource() {
    // Get the main library for this plugin.
    $library = $this->getPluginLibrary();

    return $library['remote'];
  }

  /**
   * Gets the definition of the plugin implementation.
   *
   * @return array
   *   The plugin definition, as returned by the discovery object used by the
   *   plugin manager.
   */
  public function getPluginDefinition() {
    return t('@title: @description. Plugin library can be found at %librarySource.', [
      '@title' => $this->getPluginTitle(),
      '@description' => $this->pluginDefinition['description'],
      '%librarySource' => $this->getPluginRemoteSource(),
    ]);
  }

  /**
   * Checks to see if this audio plugin has been properly installed.
   *
   * @param bool $log_error
   *   Flag to indicate whether or not alert should be logged/shown.
   *
   * @return bool
   *   Returns a boolean indicating install state.
   */
  public function checkInstalled($log_error = TRUE) {
    // Get the main library for this plugin.
    $library = $this->getPluginLibrary();

    // Check if the library is installed.
    if (file_exists(DRUPAL_ROOT . '/' . $library['js'][0]['data'])) {
      // Check to make sure the installed version is up to date.
      $this->checkVersion($log_error);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks to see if this audio plugin version is up to date.
   *
   * @param bool $log_error
   *   Flag to indicate whether or not alert should be logged/shown.
   *
   * @return bool
   *   Returns a boolean indicating if version is up to date.
   */
  public function checkVersion($log_error = TRUE) {
    // Get the main library for this plugin.
    $library = $this->getPluginLibrary();

    // Check to make sure the installed version is up to date.
    if (version_compare($this->getPluginLibraryVersion(), $library['version'], '<')) {
      // Log the warning if necessary.
      if ($log_error) {
        $message_data = [
          '@plugin' => $this->getPluginTitle(),
          '@version' => $this->getPluginLibraryVersion(),
          '@newversion' => $library['version'],
          '@download-link' => Link::fromTextAndUrl($this->getPluginRemoteSource(), Url::fromUri($this->getPluginRemoteSource()))->toString(),
          '%command' => 'drush audiofield-update',
          '@status_report' => Link::createFromRoute('status report', 'system.status')->toString(),
        ];
        \Drupal::logger('audiofield')->warning('Warning: @plugin library is out of date. You should upgrade from version @version to version @newversion. You can manually download the required version here: @download-link or you can install automatically by running the command %command. See the @status_report for more information', $message_data);
        drupal_set_message(t('Warning: @plugin library is out of date. You should upgrade from version @version to version @newversion. You can manually download the required version here: @download-link or you can install automatically by running the command %command. See the @status_report for more information', $message_data), 'warning');
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Shows library installation errors for in-use libraries.
   */
  public function showInstallError() {
    $message_data = [
      '@library_name' => $this->getPluginLibraryName(),
      '@status_report' => Link::createFromRoute('status report', 'system.status')->toString(),
    ];
    \Drupal::logger('audiofield')->error('Error: @library_name library is not currently installed! See the @status_report for more information.', $message_data);
    drupal_set_message(t('Error: @library_name library is not currently installed! See the @status_report for more information.', $message_data), 'error');
  }

  /**
   * Validate that a file entity will work with this player.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return bool
   *   Indicates if the file is valid for this player or not.
   */
  private function validateFileAgainstPlayer(FileInterface $file) {
    // Validate the file extension agains the list of valid extensions.
    $errors = file_validate_extensions($file, implode(' ', $this->pluginDefinition["fileTypes"]));
    if (count($errors) > 0) {
      $message_data = [
        '%filename' => $file->getFilename(),
        '@player' => $this->getPluginLibraryName(),
        '%extensions' => implode(', ', $this->pluginDefinition["fileTypes"]),
      ];
      \Drupal::logger('audiofield')->error('Error playing file %filename: currently selected audio player only supports the following extensions: %extensions', $message_data);
      drupal_set_message(t('Error playing file %filename: currently selected audio player only supports the following extensions: %extensions', $message_data), 'error');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validate that a link entity will work with this player.
   *
   * @param \Drupal\Core\Url $link
   *   Url to the link.
   *
   * @return bool
   *   Indicates if the link is valid for this player or not.
   */
  private function validateLinkAgainstPlayer(Url $link) {
    // Check for a valid link and a valid extension.
    $extension = pathinfo($link->toString(), PATHINFO_EXTENSION);
    if (!UrlHelper::isValid($link->toString(), FALSE) ||empty($extension)) {
      // We are currently not validating file types for links.
      $message_data = [
        '%link' => $link->toString(),
      ];
      \Drupal::logger('audiofield')->error('Error playing file: invalid link: %link', $message_data);
      drupal_set_message(t('Error playing file: invalid link: %link', $message_data), 'error');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the class type for an entity.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are determining class type.
   *
   * @return string
   *   The class type for the item.
   */
  protected function getClassType($item) {
    // Handle File entity.
    if (get_class($item) == 'Drupal\file\Plugin\Field\FieldType\FileItem') {
      return 'FileItem';
    }
    // Handle Link entity.
    elseif (get_class($item) == 'Drupal\link\Plugin\Field\FieldType\LinkItem') {
      return 'LinkItem';
    }
    return NULL;
  }

  /**
   * Validate that this entity will work with this player.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item which we are validating works with the player.
   *
   * @return bool
   *   Indicates if the entity is valid for this player or not.
   */
  protected function validateEntityAgainstPlayer($item) {
    // Handle File entity.
    if ($this->getClassType($item) == 'FileItem') {
      // Load the associated file.
      $file = $this->loadFileFromItem($item);

      // Validate that this file will work with this player.
      return $this->validateFileAgainstPlayer($file);
    }
    // Handle Link entity.
    elseif ($this->getClassType($item) == 'LinkItem') {
      // Get the source URL for this item.
      $source_url = $this->getAudioSource($item);

      // Validate that this link will work with this player.
      return $this->validateLinkAgainstPlayer($source_url);
    }

    return FALSE;
  }

  /**
   * Load a file from an audio file entity.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are loading the file entity.
   *
   * @return \Drupal\file\FileInterface
   *   FileInterface from the item.
   */
  private function loadFileFromItem($item) {
    // Load the associated file.
    return file_load($item->get('target_id')->getCastedValue());
  }

  /**
   * Get a unique ID for an item.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are generating a unique ID.
   *
   * @return string
   *   Unique ID for the item.
   */
  private function getUniqueId($item) {
    // Used to generate unique container.
    $random_generator = new Random();
    // Handle File entity.
    if ($this->getClassType($item) == 'FileItem') {
      // Load the associated file.
      $file = $this->loadFileFromItem($item);

      // Craft a unique ID.
      return 'file_' . $file->get('fid')->getValue()[0]['value'] . '_' . $random_generator->name(16, TRUE);
    }
    // Handle Link entity.
    elseif ($this->getClassType($item) == 'LinkItem') {
      // Craft a unique ID.
      return 'item_' . $random_generator->name(16, TRUE);
    }
    return $random_generator->name(16, TRUE);
  }

  /**
   * Get the filetype for an item.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are determining filetype.
   *
   * @return string
   *   Filetype for the item.
   */
  private function getFiletype($item) {
    // Handle File entity.
    if ($this->getClassType($item) == 'FileItem') {
      // Load the associated file.
      $file = $this->loadFileFromItem($item);

      return pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    }
    // Handle Link entity.
    elseif ($this->getClassType($item) == 'LinkItem') {
      return pathinfo($this->getAudioSource($item)->toString(), PATHINFO_EXTENSION);
    }
    return '';
  }

  /**
   * Get source URL from an audiofield entity.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are determining source.
   *
   * @return string
   *   The source URL of an entity.
   */
  private function getAudioSource($item) {
    $source_url = '';
    if ($this->getClassType($item) == 'FileItem') {
      // Load the associated file.
      $file = $this->loadFileFromItem($item);
      // Get the file URL.
      $source_url = Url::fromUri(file_create_url($file->getFileUri()));
    }
    // Handle Link entity.
    elseif ($this->getClassType($item) == 'LinkItem') {
      // Get the file URL.
      $source_url = $item->getUrl();
    }

    return $source_url;
  }

  /**
   * Get a title description from an audiofield entity.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which a title is being generated.
   *
   * @return string
   *   The description of an entity.
   */
  private function getAudioDescription($item) {
    $entity_description = '';
    if ($this->getClassType($item) == 'FileItem') {
      // Get the file description - use the filename if it doesn't exist.
      $entity_description = $item->get('description')->getString();
      if (empty($entity_description)) {
        // Load the associated file.
        $file = $this->loadFileFromItem($item);

        $entity_description = $file->getFilename();
      }
    }
    // Handle Link entity.
    elseif ($this->getClassType($item) == 'LinkItem') {
      // Get the file description - use the filename if it doesn't exist.
      $entity_description = $item->get('title')->getString();
      if (empty($entity_description)) {
        $entity_description = $item->getUrl()->toString();
      }
    }

    return $entity_description;
  }

  /**
   * Get required rendering information from an entity.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem|\Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   Item for which we are getting render information.
   *
   * @return object
   *   Render information for an item.
   */
  public function getAudioRenderInfo($item) {
    return (object) [
      'description' => $this->getAudioDescription($item),
      'url' => $this->getAudioSource($item),
      'id' => $this->getUniqueId($item),
      'filetype' => $this->getFiletype($item),
    ];
  }

  /**
   * Used to format file entities for use in the twig themes.
   *
   * @param object $items
   *   A list of items for which we need to generate render information.
   * @param int $limit
   *   An upper limit for the number of files to return. 0 indicates unlimited.
   *
   * @return array
   *   A render array containing files in the proper format for rendering.
   */
  public function getItemRenderList($items, $limit = 0) {
    $template_files = [];
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Add the file to the render array.
        $template_files[] = $renderInfo;

        // Return list if we have hit the limit.
        if ($limit > 0 && count($template_files) == $limit) {
          return $template_files;
        }
      }
    }
    return $template_files;
  }

  /**
   * Used to render list of downloads as an item list.
   *
   * @param object $items
   *   A list of items for which we need to generate download links..
   * @param array $settings
   *   An array of additional render settings.
   *
   * @return array
   *   A render array containing download links.
   */
  public function createDownloadList($items, array $settings) {
    $download_links = [];

    // Check if download links are turned on.
    if ($settings['download_link']) {
      // Loop over each item.
      foreach ($items as $item) {
        // Get the source URL for this item.
        $source_url = $this->getAudioSource($item);

        // Get the entity description for this item.
        $entity_description = $this->getAudioDescription($item);

        // Add the link.
        $download_links[] = [
          '#markup' => Link::fromTextAndUrl($entity_description, $source_url)->toString(),
          '#wrapper_attributes' => [
            'class' => [
              'audiofield-download-link',
            ],
          ],
        ];
      }
    }

    // Render links if we have them.
    $download_render_array = [];
    if (count($download_links) > 0) {
      $download_render_array = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => t('Download files:'),
        '#wrapper_attributes' => [
          'class' => [
            'audiofield-downloads',
          ],
        ],
        '#attributes' => [],
        '#empty' => '',
        '#items' => $download_links,
      ];
    }

    return [
      '#theme' => 'audiofield_download_links',
      '#links' => $download_render_array,
    ];
  }

}
