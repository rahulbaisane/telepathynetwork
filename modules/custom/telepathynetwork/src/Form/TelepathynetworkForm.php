<?php
/**
 * @file
 * Contains \Drupal\telepathynetwork\Form\TelepathynetworkForm.
 */
namespace Drupal\telepathynetwork\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
// To load tid and get its name
class TelepathynetworkForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telepathynetwork_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::database()->query("select name from taxonomy_term_field_data where vid = 'color'");
    $records = $query->fetchAll();
    foreach ($records as $key => $record) {
      $color_list[$record->name] = $record->name;
    }
    $form['color'] = array (
      '#type' => 'radios',
      '#title' => ('Please choose color'),
      '#options' => $color_list,
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      /*if (strlen($form_state->getValue('candidate_number')) < 10) {
        $form_state->setErrorByName('candidate_number', $this->t('Mobile number is too short.'));
      }*/
    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    $path_arg = explode('/', $current_path);
    $node_id = $path_arg[3];  
    if(isset($node_id)) {
      $query = \Drupal::database()->query("select entity_id, field_color_target_id from node__field_color where entity_id = '".$node_id."' ");
      $records = $query->fetchAll();
      foreach ($records as $record) {
        $color_id = $record->field_color_target_id;
      }
      $submited_color_value = $form_state->getValue('color');
      $term = Term::load($color_id);
      $saved_color_name = $term->getName();
      if($submited_color_value == $saved_color_name) {
        drupal_set_message('Your telepathy matched');
      }
      else {
       drupal_set_message('Sorry, Your telepathy not matched, Please try again'); 
      }
    }
  }
}