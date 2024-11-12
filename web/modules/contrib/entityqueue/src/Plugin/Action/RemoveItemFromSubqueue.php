<?php

namespace Drupal\entityqueue\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Provides a Remove Item from Subqueue action.
 *
 * @Action(
 *   id = "entityqueue_remove_item",
 *   label = @Translation("Remove Item from a Subqueue"),
 *   type = "entity",
 *   category = @Translation("Entityqueue")
 * )
 */
class RemoveItemFromSubqueue extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'subqueue' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['subqueue'] = [
      '#title' => $this->t('Subqueue ID to add entity'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['subqueue'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['subqueue'] = $form_state->getValue('subqueue');
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = EntitySubqueue::load($this->configuration['subqueue']);
    $access = $subqueue->access('update');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = EntitySubqueue::load($this->configuration['subqueue']);
    $subqueue->removeItem($entity)->save();
  }

}
