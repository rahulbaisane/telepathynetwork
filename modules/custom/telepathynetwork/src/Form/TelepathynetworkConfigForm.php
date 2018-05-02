<?php

/**
 * @file
 * Contains \Drupal\telepathynetwork\Form\TelepathynetworkConfigForm.
 */

namespace Drupal\telepathynetwork\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
/**
 * Configure where you want to append "Telepath Network Flood".
 */
class TelepathynetworkConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('telepathynetwork');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'telepathynetwork_flood_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('telepathynetwork.settings');
    $form['telepathynetwork'] = array(
      '#type' => 'details',
      '#title' => $this->t('Telepathy Network Flood'),
      '#open' => TRUE,
    );
    // Number.
    $form['telepathynetwork']['telepathynetwork_flood_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number'),
      '#description' => $this->t('telepathynetwork flood number'),
      '#default_value' => 1 /*$config->get('telepathynetwork_flood_number')*/,
    );
    // Interval
    $form['telepathynetwork']['telepathynetwork_flood_interval'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Interval'),
      '#description' => $this->t('telepathynetwork flood interval'),
      '#default_value' => 60/*$config->get('telepathynetwork_flood_interval')*/,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*// Validation for US molecule.
    $aer_register_us_mol = $form_state->getValue('aer_register_us_mol');
    if (!preg_match("/^[0-9,]*$/", $aer_register_us_mol)) {
      $form_state->setErrorByName('aer_register_us_mol', $this->t('invalide days'));
    }
    // Validation for EU molecule.
    $aer_register_eu_mol = $form_state->getValue('aer_register_eu_mol');
    if (!preg_match("/^[0-9,]*$/", $aer_register_eu_mol)) {
      $form_state->setErrorByName('aer_register_eu_mol', $this->t('invalide days'));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('telepathynetwork.settings');
    // US Molecule.
    $config->set('telepathynetwork_flood_number', $form_state->getValue('telepathynetwork_flood_number'))->save();
    // EU Molecule.
    $config->set('telepathynetwork_flood_interval', $form_state->getValue('telepathynetwork_flood_interval'))->save();
    parent::submitForm($form, $form_state);
  }
}
