<?php

namespace Drupal\counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddForm.
 *
 * @package Drupal\counter\Form\CounterSettingsBasic
 */
class CounterSettingsBasic extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'counter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'counter_basic';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('counter.settings');

    // Generate the form - settings applying to all patterns first.
    $form['counter_settings'] = array(
      '#type' => 'details',
      '#weight' => -30,
      '#title' => t('Basic settings'),
    );

    $form['counter_settings']['counter_show_site_counter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Site Counter'),
      '#default_value' => $config->get('counter_show_site_counter'),
    );

    $form['counter_settings']['counter_show_unique_visitor'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Unique Visitors'),
      '#default_value' => $config->get('counter_show_unique_visitor'),
      '#description' => t('Show Unique Visitors based on their IP'),
    );

    $form['counter_settings']['counter_registered_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Registered Users'),
      '#default_value' => $config->get('counter_registered_user'),
    );

    $form['counter_settings']['counter_unregistered_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Unregistered Users'),
      '#default_value' => $config->get('counter_unregistered_user'),
    );

    $form['counter_settings']['counter_blocked_user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Blocked Users'),
      '#default_value' => $config->get('counter_blocked_user'),
    );

    $form['counter_settings']['counter_published_node'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Published Nodes'),
      '#default_value' => $config->get('counter_published_node'),
    );

    $form['counter_settings']['counter_unpublished_node'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Unpublished Nodes'),
      '#default_value' => $config->get('counter_unpublished_node'),
    );

    $form['counter_settings']['counter_show_server_ip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Web Server IP'),
      '#default_value' => $config->get('counter_show_server_ip'),
    );

    $form['counter_settings']['counter_show_ip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Client IP'),
      '#default_value' => $config->get('counter_show_ip'),
    );

    $form['counter_settings']['counter_show_counter_since'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Site Counter Since'),
      '#default_value' => $config->get('counter_show_counter_since'),
      '#description' => t('Show the first entry date in the Site Counter'),
    );

    $form['counter_statistic'] = array(
      '#type' => 'details',
      '#weight' => -20,
      '#title' => t('Statistic settings'),
    );

    $form['counter_statistic']['counter_statistic_today'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visitors Today'),
      '#default_value' => $config->get('counter_statistic_today'),
    );

    $form['counter_statistic']['counter_statistic_week'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visitors This Week'),
      '#default_value' => $config->get('counter_statistic_week'),
    );

    $form['counter_statistic']['counter_statistic_month'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visitors This Month'),
      '#default_value' => $config->get('counter_statistic_month'),
    );

    $form['counter_statistic']['counter_statistic_year'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visitors This Year'),
      '#default_value' => $config->get('counter_statistic_year'),
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
      ->set('counter_show_site_counter', $form_state->getValue('counter_show_site_counter'))
      ->set('counter_show_unique_visitor', $form_state->getValue('counter_show_unique_visitor'))
      ->set('counter_registered_user', $form_state->getValue('counter_registered_user'))
      ->set('counter_unregistered_user', $form_state->getValue('counter_unregistered_user'))
      ->set('counter_blocked_user', $form_state->getValue('counter_blocked_user'))
      ->set('counter_published_node', $form_state->getValue('counter_published_node'))
      ->set('counter_unpublished_node', $form_state->getValue('counter_unpublished_node'))
      ->set('counter_show_server_ip', $form_state->getValue('counter_show_server_ip'))
      ->set('counter_show_ip', $form_state->getValue('counter_show_ip'))
      ->set('counter_show_counter_since', $form_state->getValue('counter_show_counter_since'))
      ->set('counter_statistic_today', $form_state->getValue('counter_statistic_today'))
      ->set('counter_statistic_week', $form_state->getValue('counter_statistic_week'))
      ->set('counter_statistic_month', $form_state->getValue('counter_statistic_month'))
      ->set('counter_statistic_year', $form_state->getValue('counter_statistic_year'))
      ->save();
  }

}
