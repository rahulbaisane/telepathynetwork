<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Plugin\FilefieldSource\Attach.
 */

namespace Drupal\filefield_sources\Plugin\FilefieldSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filefield_sources\FilefieldSourceInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use Drupal\Core\Template\Attribute;

/**
 * A FileField source plugin to allow use of files within a server directory.
 *
 * @FilefieldSource(
 *   id = "attach",
 *   name = @Translation("File attach from server directory"),
 *   label = @Translation("File attach"),
 *   description = @Translation("Select a file from a directory on the server."),
 *   weight = 3
 * )
 */
class Attach implements FilefieldSourceInterface {

  /**
   * {@inheritdoc}
   */
  public static function value(array &$element, &$input, FormStateInterface $form_state) {
    if (!empty($input['filefield_attach']['filename'])) {
      $instance = entity_load('field_config', $element['#entity_type'] . '.' . $element['#bundle'] . '.' . $element['#field_name']);
      $filepath = $input['filefield_attach']['filename'];

      // Check that the destination is writable.
      $directory = $element['#upload_location'];
      $mode = Settings::get('file_chmod_directory', FILE_CHMOD_DIRECTORY);

      // This first chmod check is for other systems such as S3, which don't
      // work with file_prepare_directory().
      if (!drupal_chmod($directory, $mode) && !file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        \Drupal::logger('filefield_sources')->log(E_NOTICE, 'File %file could not be copied, because the destination directory %destination is not configured correctly.', array(
          '%file' => $filepath,
          '%destination' => drupal_realpath($directory),
        ));
        drupal_set_message(t('The specified file %file could not be copied, because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions. More information is available in the system log.', array('%file' => $filepath)), 'error');
        return;
      }

      // Clean up the file name extensions and transliterate.
      $original_filepath = $filepath;
      $new_filepath = filefield_sources_clean_filename($filepath, $instance->getSetting('file_extensions'));
      rename($filepath, $new_filepath);
      $filepath = $new_filepath;

      // Run all the normal validations, minus file size restrictions.
      $validators = $element['#upload_validators'];
      if (isset($validators['file_validate_size'])) {
        unset($validators['file_validate_size']);
      }

      // Save the file to the new location.
      if ($file = filefield_sources_save_file($filepath, $validators, $directory)) {
        if (!in_array($file->id(), $input['fids'])) {
          $input['fids'][] = $file->id();
        }

        // Delete the original file if "moving" the file instead of copying.
        if ($element['#filefield_sources_settings']['source_attach']['attach_mode'] !== FILEFIELD_SOURCE_ATTACH_MODE_COPY) {
          @unlink($filepath);
        }
      }

      // Restore the original file name if the file still exists.
      if (file_exists($filepath) && $filepath != $original_filepath) {
        rename($filepath, $original_filepath);
      }

      $input['filefield_attach']['filename'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $settings = $element['#filefield_sources_settings']['source_attach'];
    $field_name = $element['#field_name'];
    $instance = entity_load('field_config', $element['#entity_type'] . '.' . $element['#bundle'] . '.' . $field_name);

    $element['filefield_attach'] = array(
      '#weight' => 100.5,
      '#theme' => 'filefield_sources_element',
      '#source_id' => 'attach',
      // Required for proper theming.
      '#filefield_source' => TRUE,
    );

    $path = static::getDirectory($settings);
    $options = static::getAttachOptions($path, $instance->getSetting('file_extensions'));

    // If we have built this element before, append the list of options that we
    // had previously. This allows files to be deleted after copying them and
    // still be considered a valid option during the validation and submit.
    $triggering_element = $form_state->getTriggeringElement();
    $property = array(
      'filefield_sources',
      $field_name,
      'attach_options',
    );
    if (!isset($triggering_element) && $form_state->has($property)) {
      $attach_options = $form_state->get($property);
      $options = $options + $attach_options;
    }
    // On initial form build and rebuilds after processing input, save the
    // original list of options so they can be restored in the line above.
    else {
      $form_state->set(array('filefield_sources', $field_name, 'attach_options'), $options);
    }

    $description = t('This method may be used to attach files that exceed the file size limit. Files may be attached from the %directory directory on the server, usually uploaded through FTP.', array('%directory' => realpath($path)));

    // Error messages.
    if ($options === FALSE || empty($settings['path'])) {
      $attach_message = t('A file attach directory could not be located.');
      $attach_description = t('Please check your settings for the %field field.', array('%field' => $instance->getLabel()));
    }
    elseif (!count($options)) {
      $attach_message = t('There currently are no files to attach.');
      $attach_description = $description;
    }

    if (isset($attach_message)) {
      $element['filefield_attach']['attach_message'] = array(
        '#markup' => $attach_message,
      );
      $element['filefield_attach']['#description'] = $attach_description;
    }
    else {
      $validators = $element['#upload_validators'];
      if (isset($validators['file_validate_size'])) {
        unset($validators['file_validate_size']);
      }
      $description .= '<br />' . filefield_sources_element_validation_help($validators);
      $element['filefield_attach']['filename'] = array(
        '#type' => 'select',
        '#options' => $options,
      );
      $element['filefield_attach']['#description'] = $description;
    }
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
    ];
    $element['filefield_attach']['attach'] = [
      '#name' => implode('_', $element['#parents']) . '_attach',
      '#type' => 'submit',
      '#value' => t('Attach'),
      '#validate' => [],
      '#submit' => ['filefield_sources_field_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
    ];

    return $element;
  }

  /**
   * Theme the output of the attach element.
   */
  public static function element($variables) {
    $element = $variables['element'];
    if (isset($element['attach_message'])) {
      $output = $element['attach_message']['#markup'];
    }
    elseif (isset($element['filename'])) {
      // Get rendered options.
      $options = form_select_options($element['filename']);
      $option_output = '';
      foreach ($options as $key => $value) {
        $option_output .= '<option value=' . $value["value"] . '>' . $value["label"] . '</option>';
      }
      // Get rendered select.
      $size = !empty($element['filename']['#size']) ? ' size="' . $element['filename']['#size'] . '"' : '';
      $element['filename']['#attributes']['class'][] = 'form-select';
      $multiple = !empty($element['#multiple']);
      $output = '<select name="' . $element['filename']['#name'] . '' . ($multiple ? '[]' : '') . '"' . ($multiple ? ' multiple="multiple" ' : '') . new Attribute($element['filename']['#attributes']) . ' id="' . $element['filename']['#id'] . '" ' . $size . '>' . $option_output . '</select>';
    }

    $output .= drupal_render($element['attach']);
    $element['#children'] = $output;
    $element['#theme_wrappers'] = array('form_element');
    return '<div class="filefield-source filefield-source-attach clear-block">' . drupal_render($element) . '</div>';
  }

  /**
   * Get directory from settings.
   *
   * @param array $settings
   *   Attach source's settings.
   * @param object $account
   *   User to replace token.
   *
   * @return string
   *   Path that contains files to attach.
   */
  protected static function getDirectory(array $settings, $account = NULL) {
    $account = isset($account) ? $account : \Drupal::currentUser();
    $path = $settings['path'];
    $absolute = !empty($settings['absolute']);

    // Replace user level tokens.
    // Node level tokens require a lot of complexity like temporary storage
    // locations when values don't exist. See the filefield_paths module.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token = \Drupal::token();
      $path = $token->replace($path, array('user' => $account));
    }

    return $absolute ? $path : file_default_scheme() . '://' . $path;
  }

  /**
   * Get attach options.
   *
   * @param string $path
   *   Path to scan files.
   * @param string $extensions
   *   Path to scan files.
   *
   * @return array
   *   List of options.
   */
  protected static function getAttachOptions($path, $extensions = FALSE) {
    if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
      drupal_set_message(t('Specified file attach path %path must exist or be writable.', array('%path' => $path)), 'error');
      return FALSE;
    }

    $options = array();
    $pattern = !empty($extensions) ? '/\.(' . strtr($extensions, ' ', '|') . ')$/' : '/.*/';
    $files = file_scan_directory($path, $pattern);

    if (count($files)) {
      $options = array('' => t('-- Select file --'));
      foreach ($files as $file) {
        $options[$file->uri] = str_replace($path . '/', '', $file->uri);
      }
      natcasesort($options);
    }

    return $options;
  }

  /**
   * Implements hook_filefield_source_settings().
   */
  public static function settings(WidgetInterface $plugin) {
    $settings = $plugin->getThirdPartySetting('filefield_sources', 'filefield_sources', array(
      'source_attach' => array(
        'path' => FILEFIELD_SOURCE_ATTACH_DEFAULT_PATH,
        'absolute' => FILEFIELD_SOURCE_ATTACH_RELATIVE,
        'attach_mode' => FILEFIELD_SOURCE_ATTACH_MODE_MOVE,
      ),
    ));

    $return['source_attach'] = array(
      '#title' => t('File attach settings'),
      '#type' => 'details',
      '#description' => t('File attach allows for selecting a file from a directory on the server, commonly used in combination with FTP.') . ' <strong>' . t('This file source will ignore file size checking when used.') . '</strong>',
      '#element_validate' => array(array(get_called_class(), 'filePathValidate')),
      '#weight' => 3,
    );
    $return['source_attach']['path'] = array(
      '#type' => 'textfield',
      '#title' => t('File attach path'),
      '#default_value' => $settings['source_attach']['path'],
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => t('The directory within the <em>File attach location</em> that will contain attachable files.'),
    );
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $return['source_attach']['tokens'] = array(
        '#theme' => 'token_tree',
        '#token_types' => array('user'),
      );
    }
    $return['source_attach']['absolute'] = array(
      '#type' => 'radios',
      '#title' => t('File attach location'),
      '#options' => array(
        FILEFIELD_SOURCE_ATTACH_RELATIVE => t('Within the files directory'),
        FILEFIELD_SOURCE_ATTACH_ABSOLUTE => t('Absolute server path'),
      ),
      '#default_value' => $settings['source_attach']['absolute'],
      '#description' => t('The <em>File attach path</em> may be with the files directory (%file_directory) or from the root of your server. If an absolute path is used and it does not start with a "/" your path will be relative to your site directory: %realpath.', array('%file_directory' => drupal_realpath(file_default_scheme() . '://'), '%realpath' => realpath('./'))),
    );
    $return['source_attach']['attach_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Attach method'),
      '#options' => array(
        FILEFIELD_SOURCE_ATTACH_MODE_MOVE => t('Move the file directly to the final location'),
        FILEFIELD_SOURCE_ATTACH_MODE_COPY => t('Leave a copy of the file in the attach directory'),
      ),
      '#default_value' => isset($settings['source_attach']['attach_mode']) ? $settings['source_attach']['attach_mode'] : 'move',
    );

    return $return;
  }

  /**
   * Validate file path.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   */
  public static function filePathValidate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $parents = $element['#parents'];
    array_pop($parents);
    $input_exists = FALSE;

    // Get input of the whole parent element.
    $input = NestedArray::getValue($form_state->getValues(), $parents, $input_exists);
    if ($input_exists) {
      // Only validate if this source is enabled.
      if (!$input['sources']['attach']) {
        return;
      }

      // Strip slashes from the end of the file path.
      $filepath = rtrim($element['path']['#value'], '\\/');
      $form_state->setValueForElement($element['path'], $filepath);
      $filepath = static::getDirectory($input['source_attach']);

      // Check that the directory exists and is writable.
      if (!file_prepare_directory($filepath, FILE_CREATE_DIRECTORY)) {
        $form_state->setError($element['path'], t('Specified file attach path %path must exist or be writable.', array('%path' => $filepath)));
      }
    }
  }

}
