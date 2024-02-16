<?php

namespace Drupal\hacked\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure locale settings for this site.
 */
class HackedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hacked.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hacked_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hacked.settings');

    $hashers = hacked_get_file_hashers();

    $form['selected_file_hasher'] = [
      '#type' => 'details',
      '#title' => $this->t('File hasher'),
      '#open' => TRUE,
    ];

    $parents = ['selected_file_hasher'];

    foreach ($hashers as $name => $hasher_info) {
      // Generate the parents as the autogenerator does, so we will have a
      // unique id for each radio button.
      $parents_for_id = array_merge($parents, [$name]);
      $form['selected_file_hasher'][$name] = [
        '#type' => 'radio',
        '#title' => $hasher_info['name'],
        '#default_value' => $config->get('selected_file_hasher'),
        '#return_value' => $name,
        '#parents' => $parents,
        '#description' => !empty($hasher_info['description']) ? $hasher_info['description'] : '',
        '#id' => Html::getId('edit-' . implode('-', $parents_for_id)),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('hacked.settings');
    $config->set('selected_file_hasher', $values['selected_file_hasher'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
