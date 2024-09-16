<?php

namespace Drupal\recorder_feedback_indicia_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class RecorderFeedbackIndiciaConnectorSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'recorder_feedback_indicia_connector.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recorder_feedback_indicia_connector_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['system_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix for user external keys'),
      '#description' => $this->t('A unique name for this website - will be used as a prefix added to Indicia user IDs in order to generate a globally unique key for each user, e.g. the value "irecord" will generate keys such as "irecord:123". Lowercase alphanumeric characters and underscores only.'),
      '#default_value' => $config->get('system_prefix'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('API key for the Recorder Feedback API.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#description' => $this->t('URL for the Recorder Feedback API.'),
      '#default_value' => $config->get('api_url'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[a-z0-9_]+$/', $form_state->getValue('system_prefix'))) {
      $form_state->setErrorByName(
        'system_prefix',
        $this->t('The prefix for user external keys must contain lowercase alphanumeric characters and underscores only.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::SETTINGS)
      // Set the submitted configuration settings.
      ->set('system_prefix', trim($form_state->getValue('system_prefix')))
      ->set('api_key', trim($form_state->getValue('api_key')))
      ->set('api_url', trim($form_state->getValue('api_url')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
