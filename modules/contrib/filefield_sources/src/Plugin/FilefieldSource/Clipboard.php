<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Plugin\FilefieldSource\Clipboard.
 */

namespace Drupal\filefield_sources\Plugin\FilefieldSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filefield_sources\FilefieldSourceInterface;
use Drupal\Core\Site\Settings;

/**
 * A FileField source plugin to allow transfer of files through the clipboard.
 *
 * @FilefieldSource(
 *   id = "clipboard",
 *   name = @Translation("Paste from clipboard (<a href=""http://drupal.org/node/1775902"">limited browser support</a>)"),
 *   label = @Translation("Clipboard"),
 *   description = @Translation("Allow users to paste a file directly from the clipboard."),
 *   weight = 1
 * )
 */
class Clipboard implements FilefieldSourceInterface {

  /**
   * {@inheritdoc}
   */
  public static function value(array &$element, &$input, FormStateInterface $form_state) {
    if (isset($input['filefield_clipboard']['contents']) && strlen($input['filefield_clipboard']['contents']) > 0) {
      // Check that the destination is writable.
      $temporary_directory = 'temporary://';
      if (!file_prepare_directory($temporary_directory, FILE_MODIFY_PERMISSIONS)) {
        \Drupal::logger('filefield_sources')->log(E_NOTICE, 'The directory %directory is not writable, because it does not have the correct permissions set.', array('%directory' => drupal_realpath($temporary_directory)));
        drupal_set_message(t('The file could not be transferred because the temporary directory is not writable.'), 'error');
        return;
      }
      // Check that the destination is writable.
      $directory = $element['#upload_location'];
      $mode = Settings::get('file_chmod_directory', FILE_CHMOD_DIRECTORY);

      // This first chmod check is for other systems such as S3, which don't
      // work with file_prepare_directory().
      if (!drupal_chmod($directory, $mode) && !file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        $url = $input['filefield_clipboard']['filename'];
        \Drupal::logger('filefield_sources')->log(E_NOTICE, 'File %file could not be copied, because the destination directory %destination is not configured correctly.', array('%file' => $url, '%destination' => drupal_realpath($directory)));
        drupal_set_message(t('The specified file %file could not be copied, because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions. More information is available in the system log.', array('%file' => $url)), 'error');
        return;
      }

      // Split the file information in mimetype and base64 encoded binary.
      $base64_data = $input['filefield_clipboard']['contents'];
      $comma_position = strpos($base64_data, ',');
      $semicolon_position = strpos($base64_data, ';');
      $file_contents = base64_decode(substr($base64_data, $comma_position + 1));
      $mimetype = substr($base64_data, 5, $semicolon_position - 5);

      $extension = \Drupal::service('file.mime_type.guesser.extension')->convertMimeTypeToExtension($mimetype);

      $filename = trim($input['filefield_clipboard']['filename']);
      $filename = preg_replace('/\.[a-z0-9]{3,4}$/', '', $filename);
      $filename = (empty($filename) ? 'paste_' . REQUEST_TIME : $filename) . '.' . $extension;
      $filepath = file_create_filename($filename, $temporary_directory);

      $copy_success = FALSE;
      if ($fp = @fopen($filepath, 'w')) {
        fwrite($fp, $file_contents);
        fclose($fp);
        $copy_success = TRUE;
      }

      if ($copy_success && $file = filefield_sources_save_file($filepath, $element['#upload_validators'], $element['#upload_location'])) {
        if (!in_array($file->id(), $input['fids'])) {
          $input['fids'][] = $file->id();
        }
      }

      // Remove the temporary file generated from paste.
      @unlink($filepath);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['filefield_clipboard'] = array(
      '#weight' => 100.5,
      '#theme' => 'filefield_sources_element',
      '#source_id' => 'clipboard',
      // Required for proper theming.
      '#filefield_source' => TRUE,
      '#filefield_sources_hint_text' => t('Enter filename then paste.'),
      '#description' => filefield_sources_element_validation_help($element['#upload_validators']),
    );

    $element['filefield_clipboard']['capture'] = array(
      '#type' => 'item',
      '#markup' => '<div class="filefield-source-clipboard-capture" contenteditable="true"><span class="hint">example_filename.png</span></div> <span class="hint">' . t('ctrl + v') . '</span>',
      '#description' => t('Enter a file name and paste an image from the clipboard. This feature only works in <a href="http://drupal.org/node/1775902">limited browsers</a>.'),
    );

    $element['filefield_clipboard']['filename'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => array('filefield-source-clipboard-filename')),
    );
    $element['filefield_clipboard']['contents'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => array('filefield-source-clipboard-contents')),
    );

    $class = '\Drupal\file\Element\ManagedFile';
    $ajax_settings = [
      'callback' => [$class, 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $element['upload_button']['#ajax']['wrapper'],
      'effect' => 'fade',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Transfering file...'),
      ],
    ];

    $element['filefield_clipboard']['upload'] = [
      '#name' => implode('_', $element['#parents']) . '_clipboard_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['filefield_sources_field_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
    ];

    return $element;
  }

  /**
   * Theme the output of the clipboard element.
   */
  public static function element($variables) {
    $element = $variables['element'];

    return '<div class="filefield-source filefield-source-clipboard clear-block">' . drupal_render_children($element) . '</div>';
  }

}
