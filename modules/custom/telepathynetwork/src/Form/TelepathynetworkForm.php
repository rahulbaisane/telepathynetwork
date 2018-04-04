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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Datetime\Time;
class TelepathynetworkForm extends FormBase {
  /**
   * Drupal\Core\Flood\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Flood\DatabaseBackend
   */
  protected $flood;

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Component\Datetime\Time definition.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $datetimeTime;

  /**
   * Constructs a new MyCustomForm object.
   */
  public function __construct(
  DatabaseBackend $flood, DateFormatter $date_formatter, Time $datetime_time
  ) {
    $this->flood = $flood;
    $this->dateFormatter = $date_formatter;
    $this->datetimeTime = $datetime_time;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('flood'), $container->get('date.formatter'), $container->get('datetime.time')
    );
  }
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
      $interval = 60;
      $limit = 1;
      /*if (!$this->flood->isAllowed('telepathy_flood', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('User cannot submit the form more than %number times in @interval. Please Try again.', [
              '%number' => 5,
              '@interval' => $this->dateFormatter->formatInterval($interval)
        ]));
      }*/
      if (!$this->flood->isAllowed('telepathy_flood', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You enter into gallaxy please choose correct option.'));
      }
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
        $uid = \Drupal::currentUser()->id();
        $username = \Drupal::currentUser()->getUsername();
        $type = 'color';
        $status = 1;
        //current timestamp
        $current_date_time = new \Drupal\Core\Datetime\DrupalDateTime(date("Y-m-d H:i:s"));
        $current_date_time->setTimezone(new \DateTimeZone("asia/kolkata"));
        $current_timestamp = $current_date_time->getTimestamp();
        //insert data in database
        \Drupal::database()->insert('telepathynetwork_tb')->fields(['nid','uid','name','type','status','timestamp'])->values(array($node_id,$uid,$username,$type,$status,$current_timestamp,
        ))->execute();
      }
      else {
      /**
       * Save failure attempt states
       * If continues five times failure then enter into flood.
       */
        $status = array();
        $flag = 0;
        $query = \Drupal::database()->query("select id, status from telepathynetwork_tb Order by id DESC limit 5");
        $records = $query->fetchAll();
        foreach ($records as $key => $record) {
          $status[$key] = $record->status;
        }
        if (in_array("1", $status)) {
          drupal_set_message('Sorry, Your telepathy not matched, Please try again');
          $uid = \Drupal::currentUser()->id();
          $username = \Drupal::currentUser()->getUsername();
          $type = 'color';
          $status = 0;
          //current timestamp
          $current_date_time = new \Drupal\Core\Datetime\DrupalDateTime(date("Y-m-d H:i:s"));
          $current_date_time->setTimezone(new \DateTimeZone("asia/kolkata"));
          $current_timestamp = $current_date_time->getTimestamp();
          //insert data in database
          \Drupal::database()->insert('telepathynetwork_tb')->fields(['nid','uid','name','type','status','timestamp'])->values(array($node_id,$uid,$username,$type,$status,$current_timestamp,
          ))->execute();
        }
        else {
          drupal_set_message('Sorry, Your telepathy not matched, you have to choose correct option or you will enter enter gallaxy');
          // Register for the floodcontrol.
          $this->flood->register('telepathy_flood', 60);
        }
      }
    }
  }
}