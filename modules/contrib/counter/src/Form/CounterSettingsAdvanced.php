<?php

namespace Drupal\counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddForm.
 *
 * @package Drupal\counter\Form\CounterSettingsAdvanced.
 */
class CounterSettingsAdvanced extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'counter.advanced',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'counter_advanced';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('counter.settings');

    // Generate the form - settings applying to all patterns first.
    $form['counter_advanced'] = array(
      '#type' => 'details',
      '#weight' => -20,
      '#title' => t('Advanced settings'),
    );

    $form['counter_advanced']['counter_skip_admin'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip admin'),
      '#default_value' => $config->get('counter_skip_admin'),
      '#description' => t("Do not count when visitor is admin (uid=1)."),
    );

    $form['counter_advanced']['counter_refresh_delay'] = array(
      '#type' => 'textfield',
      '#title' => t('Delay before refresh counter data (in second)'),
      '#default_value' => $config->get('counter_refresh_delay'),
      '#description' => t("Delay before re-calculate counter data, otherwise read from previous value."),
    );

    $form['counter_advanced']['counter_insert_delay'] = array(
      '#type' => 'textfield',
      '#title' => t('Delay before next insert (in second)'),
      '#default_value' => $config->get('counter_insert_delay'),
      '#description' => t("Wait for certain second before next insert. Increase this value if your server can not handle too much data recording. Set to 0 for no delay."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('counter.settings')
      ->set('counter_skip_admin', $form_state->getValue('counter_skip_admin'))
      ->set('counter_refresh_delay', $form_state->getValue('counter_refresh_delay'))
      ->set('counter_insert_delay', $form_state->getValue('counter_insert_delay'))
      ->save();
  }

}
