<?php

namespace Drupal\floating_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\floating_block\HelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures floating_block settings.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The Floating Block helper service.
   *
   * @var \Drupal\floating_block\HelperInterface
   */
  protected $helper;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\floating_block\HelperInterface $helper
   *   The Floating Block helper service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HelperInterface $helper) {
    parent::__construct($config_factory);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('floating_block')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'floating_block_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('floating_block.settings');
    $blocks = (array) $config->get('blocks');

    $form['blocks'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Floating block settings'),
      '#default_value' => $this->helper->convertArrayToText($blocks),
      '#description' => $this->t('Floating block configurations, one per line in the formation <code>[css selector]<strong>|</strong>[extra settings]</code>.'),
    ];
    $form['min_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Min width'),
      '#default_value' => $config->get('min_width'),
      '#description' => $this->t('For example, %example. Set to 0 to leave floating blocks enabled for all screen sizes. It should be without with px.', [
        '%example' => '850',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $array = $this->helper->convertTextToArray($values['blocks']);
    $string = $this->helper->convertArrayToText($array);

    // Compare that floating block settings string to array conversion is
    // idempotent. New line characters \n and \r get make comparison difficult.
    if (str_replace(["\n", "\r"], '', $string) != str_replace(["\n", "\r"], '', $values['blocks'])) {
      $form_state->setErrorByName('blocks', $this->t('Each line must of the format: <code>selector|setting_key=setting_value,setting_key=setting_value,...</code>'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory->getEditable('floating_block.settings')
      ->set('blocks', $this->helper->convertTextToArray($values['blocks']))
      ->set('min_width', $values['min_width'])
      ->save();
  }

}
