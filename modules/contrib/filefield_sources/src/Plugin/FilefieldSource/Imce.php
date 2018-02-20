<?php

/**
 * @file
 * Contains \Drupal\filefield_sources\Plugin\FilefieldSource\Imce.
 */

namespace Drupal\filefield_sources\Plugin\FilefieldSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filefield_sources\FilefieldSourceInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Field\WidgetInterface;

/**
 * A FileField source plugin to allow referencing of files from IMCE.
 *
 * @FilefieldSource(
 *   id = "imce",
 *   name = @Translation("IMCE file browser"),
 *   label = @Translation("File browser"),
 *   description = @Translation("Select a file to use from a file browser."),
 *   weight = -1
 * )
 */
class Imce implements FilefieldSourceInterface {

  /**
   * {@inheritdoc}
   */
  public static function value(array &$element, &$input, FormStateInterface $form_state) {
    if (isset($input['filefield_imce']['imce_paths']) && $input['filefield_imce']['imce_paths'] != '') {
      $instance = entity_load('field_config', $element['#entity_type'] . '.' . $element['#bundle'] . '.' . $element['#field_name']);
      $field_settings = $instance->getSettings();
      $scheme = $field_settings['uri_scheme'];
      $imce_paths = explode(':', $input['filefield_imce']['imce_paths']);
      $uris = [];

      foreach ($imce_paths as $imce_path) {
        //$wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
        //$file_directory_prefix = $scheme == 'private' ? 'system/files' : $wrapper->getDirectoryPath();
        //$uri = preg_replace('/^' . preg_quote(base_path() . $file_directory_prefix . '/', '/') . '/', $scheme . '://', $imce_path);
        $uri = rawurldecode($scheme . '://' . $imce_path);
        $uris[] = $uri;
      }

      // Resolve the file path to an FID.
      $fids = db_select('file_managed', 'f')
        ->condition('uri', $uris, 'IN')
        ->fields('f', array('fid'))
        ->execute()
        ->fetchCol();
      if ($fids) {
        $files = file_load_multiple($fids);
        foreach ($files as $file) {
          if (filefield_sources_element_validate($element, $file, $form_state)) {
            if (!in_array($file->id(), $input['fids'])) {
              $input['fids'][] = $file->id();
            }
          }
        }
      }
      else {
        $form_state->setError($element, t('The selected file could not be used because the file does not exist in the database.'));
      }
      // No matter what happens, clear the value from the file path field.
      $input['filefield_imce']['imce_paths'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $instance = entity_load('field_config', $element['#entity_type'] . '.' . $element['#bundle'] . '.' . $element['#field_name']);

    $element['filefield_imce'] = array(
      '#weight' => 100.5,
      '#theme' => 'filefield_sources_element',
      '#source_id' => 'imce',
      // Required for proper theming.
      '#filefield_source' => TRUE,
      '#description' => filefield_sources_element_validation_help($element['#upload_validators']),
    );

    $imce_url = \Drupal::url('filefield_sources.imce', array(
      'entity_type' => $element['#entity_type'],
      'bundle_name' => $element['#bundle'],
      'field_name' => $element['#field_name'],
    ),
    array(
      'query' => array(
        'sendto' => 'imceFileField.sendto',
        'fieldId' => $element['#attributes']['data-drupal-selector'] . '-filefield-imce',
      ),
    ));
    $element['filefield_imce']['browse'] = array(
      '#type' => 'markup',
      '#markup' => '<span>' . t('No file selected') . '</span> (<a class="filefield-sources-imce-browse" href="' . $imce_url . '">' . t('browse') . '</a>)',
    );

    $element['#attached']['library'][] = 'imce/drupal.imce.filefield';
    // Set the pre-renderer to conditionally disable the elements.
    $element['#pre_render'][] = array(get_called_class(), 'preRenderWidget');

    // Path input
    $element['filefield_imce']['imce_paths'] = array(
      '#type' => 'hidden',
      // Reset value to prevent consistent errors
      '#value' => '',
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
    ];

    $element['filefield_imce']['imce_button'] = array(
      '#name' => implode('_', $element['#parents']) . '_imce_select',
      '#type' => 'submit',
      '#value' => t('Select'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['filefield_sources_field_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
    );

    return $element;
  }

  /**
   * Theme the output of the imce element.
   */
  public static function element($variables) {
    $element = $variables['element'];

    $output = drupal_render_children($element);
    return '<div class="filefield-source filefield-source-imce clear-block">' . $output . '</div>';
  }

  /**
   * Define routes for Imce source.
   *
   * @return array
   *   Array of routes.
   */
  public static function routes() {
    $routes = array();

    $routes['filefield_sources.imce'] = new Route(
      '/file/imce/{entity_type}/{bundle_name}/{field_name}',
      array(
        '_controller' => '\Drupal\filefield_sources\Controller\ImceController::page',
        '_title' => 'File Manager',
      ),
      array(
        '_access_filefield_sources_field' => 'TRUE',
      )
    );

    return $routes;
  }

  /**
   * Implements hook_filefield_source_settings().
   */
  public static function settings(WidgetInterface $plugin) {
    $settings = $plugin->getThirdPartySetting('filefield_sources', 'filefield_sources', array(
      'source_imce' => array(
        'imce_mode' => 0,
      ),
    ));

    $return['source_imce'] = array(
      '#title' => t('IMCE file browser settings'),
      '#type' => 'details',
      '#access' => \Drupal::moduleHandler()->moduleExists('imce'),
    );

    // $imce_admin_url = \Drupal::url('imce.admin');
    $imce_admin_url = 'admin/config/media/imce';
    $return['source_imce']['imce_mode'] = array(
      '#type' => 'radios',
      '#title' => t('File browser mode'),
      '#options' => array(
        0 => t('Restricted: Users can only browse the field directory. No file operations are allowed.'),
        1 => t('Full: Browsable directories are defined by <a href=":imce-admin-url">IMCE configuration profiles</a>. File operations are allowed.', array(':imce-admin-url' => $imce_admin_url)),
      ),
      '#default_value' => isset($settings['source_imce']['imce_mode']) ? $settings['source_imce']['imce_mode'] : 0,
    );

    return $return;

  }

  /**
   * Pre-renders widget form.
   */
  public static function preRenderWidget($element) {
    // Hide elements if there is already an uploaded file.
    if (!empty($element['#value']['fids'])) {
      $element['filefield_imce']['imce_paths']['#access'] = FALSE;
      $element['filefield_imce']['imce_button']['#access'] = FALSE;
    }
    return $element;
  }

}
