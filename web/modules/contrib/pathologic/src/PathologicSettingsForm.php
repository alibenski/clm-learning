<?php

declare(strict_types=1);

namespace Drupal\pathologic;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PathologicSettingsForm extends ConfigFormBase {

  use PathologicCommonSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathologic_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pathologic.settings');

    $form['reminder'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Reminder: The settings on this form only affect text formats for which Pathologic is configured to use the global Pathologic settings; if it&rsquo;s configured to use per-format settings, these settings will have no effect.') . '</p>',
      '#weight' => 0,
    ];

    $defaults = [
      'protocol_style' => $config->get('protocol_style'),
      'local_paths' => $config->get('local_paths'),
      'keep_language_prefix' => $config->get('keep_language_prefix'),
    ];
    $form += $this->commonPathologicSettingsForm($defaults);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pathologic.settings')
      ->set('protocol_style', $form_state->getValue('protocol_style'))
      ->set('local_paths', $form_state->getValue('local_paths'))
      ->set('keep_language_prefix', $form_state->getValue('keep_language_prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return [
      'pathologic.settings',
    ];
  }

}
