<?php

namespace Drupal\counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddForm.
 *
 * @package Drupal\counter\Form\CounterSettingsInitial.
 */
class CounterSettingsInitial extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'counter.initial',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'counter_initial';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('counter.settings');

    // Generate the form - settings applying to all patterns first.
    $form['counter_initial'] = array(
      '#type' => 'details',
      '#weight' => -30,
      '#title' => t('Basic settings'),
    );

    $form['counter_initial'] = array(
      '#type' => 'details',
      '#weight' => -10,
      '#title' => t('Initial Values'),
      '#description' => t("Set initial values for Site Counter."),
    );

    $form['counter_initial']['counter_initial_counter'] = array(
      '#type' => 'textfield',
      '#title' => t('Initial value of Site Counter'),
      '#default_value' => $config->get('counter_initial_counter'),
      '#description' => t('Initial value of Site Counter'),
    );

    $form['counter_initial']['counter_initial_unique_visitor'] = array(
      '#type' => 'textfield',
      '#title' => t('Initial value of Unique Visitor'),
      '#default_value' => $config->get('counter_initial_unique_visitor'),
      '#description' => t('Initial value of Unique Visitor'),
    );

    $form['counter_initial']['counter_initial_since'] = array(
      '#type' => 'textfield',
      '#title' => t("Replace 'Since' value with this Unix timestamp"),
      '#default_value' => $config->get('counter_initial_since'),
      '#description' => t("This field type is Unix timestamp, so you must enter like: 1404671462."),
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
      ->set('counter_initial_counter', $form_state->getValue('counter_initial_counter'))
      ->set('counter_initial_unique_visitor', $form_state->getValue('counter_initial_unique_visitor'))
      ->set('counter_initial_since', $form_state->getValue('counter_initial_since'))
      ->save();
  }

}
