<?php

namespace Drupal\next_path_alias\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Next.js Site Path Alias settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'next_path_alias_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['next_path_alias.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sites_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sites field name'),
      '#description' => $this->t('The name of the field that contains the sites a content is published into. This field must be an entity reference to <em>Next.js site</em> entity type.'),
      '#default_value' => $this->config('next_path_alias.settings')->get('sites_field_name'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('next_path_alias.settings')
      ->set('sites_field_name', $form_state->getValue('sites_field_name'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
