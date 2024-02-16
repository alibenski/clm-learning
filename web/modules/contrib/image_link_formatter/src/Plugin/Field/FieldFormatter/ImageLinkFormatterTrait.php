<?php

namespace Drupal\image_link_formatter\Plugin\Field\FieldFormatter;

/**
 * @file
 * Trait implementation of common methods for formatter 'image_link_formatter'.
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Wrapper trait for image formatter plugins to add a link from a custom field.
 *
 * Provides common functionality for any image formatter with the setting
 * 'image_link': It adds entity type's custom link fields to the image field
 * formatter's list of options in the 'image_link' select dropdown. Then, upon
 * display of the field rendered through the image formatter, its corresponding
 * link value is wrapped around the output of the extended formatter.
 *
 * @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter
 * @see \Drupal\image_link_formatter\Plugin\Field\FieldFormatter\ImageLinkFormatter
 * @see \Drupal\responsive_image_link_formatter\Plugin\Field\FieldFormatter\ResponsiveImageLinkFormatter
 */
trait ImageLinkFormatterTrait {

  /**
   * The entity field manager service, to get field definitions, bundles, etc...
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * {@inheritdoc}
   *
   * Adds entity type's custom link fields to the image field formatter's list
   * of options in the 'image_link' select dropdown.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    // Add the link fields options to the 'image_link' select dropdown.
    $element['image_link']['#options'] += $this->getLinkFieldsOptions();
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Adds selected custom link field name to image formatter's summary on the
   * display management page.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $image_link_setting = $this->getSetting('image_link');
    $link_fields = $this->getLinkFieldsOptions();
    // Add link field label to summary, if any is selected.
    if (isset($link_fields[$image_link_setting])) {
      $summary[] = $this->t('Linked to :link_field_label', [':link_field_label' => $link_fields[$image_link_setting]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * Upon display of each value of the image field, corresponding custom link
   * field value is added to the render array as a URL object ('#url' key of the
   * image's render array).
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Extend core image formatter with a URL provided by a custom link field.
    $elements = parent::viewElements($items, $langcode);

    $image_link_setting = $this->getSetting('image_link');
    $link_fields = $this->getLinkFieldsOptions();
    if (isset($link_fields[$image_link_setting])) {
      /** @var \Drupal\link\Plugin\Field\FieldType\LinkItemList $link_items */
      $link_items = $items->getEntity()->get($image_link_setting);
      // If a link field is selected in formatter's settings, iterate through
      // each rendered image and set its '#url' value to matching link URL.
      foreach ($elements as $delta => $element) {
        /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $link_item_value */
        $link_item_value = $link_items->get($delta);
        if (!empty($link_item_value)) {
          $elements[$delta]['#url'] = $link_item_value->getUrl();
        }
      }
    }

    return $elements;
  }

  /**
   * Helper function to get a list of link fields attached to entity and bundle.
   *
   * @return array
   *   An options array of all link fields attached to this entity and bundle.
   */
  private function getLinkFieldsOptions() {
    $options = [];
    // Filter all link fields attached to this entity with this bundle.
    $link_fields = array_filter($this->entityFieldManager->getFieldDefinitions(
        $this->fieldDefinition->getTargetEntityTypeId(),
        $this->fieldDefinition->getTargetBundle()
      ),
      function (FieldDefinitionInterface $element) {
        return $element->getType() === 'link';
      });

    // Populate options array keyed by field name, for each link field found.
    foreach ($link_fields as $link_field) {
      $options[$link_field->getName()] = $link_field->getLabel() . ' (' . $link_field->getName() . ')';
    }
    return $options;
  }

}
