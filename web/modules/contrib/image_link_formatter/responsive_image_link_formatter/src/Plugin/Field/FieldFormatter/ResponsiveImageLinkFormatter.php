<?php

namespace Drupal\responsive_image_link_formatter\Plugin\Field\FieldFormatter;

/**
 * @file
 * Contains implementation of formatter 'responsive_image_link_formatter'.
 */

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\image_link_formatter\Plugin\Field\FieldFormatter\ImageLinkFormatterTrait;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends Core 'responsive_image' formatter to add a link from a custom field.
 *
 * Essentially combines core 'ResponsiveImageFormatter' object with methods of
 * the trait 'ImageLinkFormatterTrait' which extends formatter's methods to
 * allow wrapping a link from a custom field around the image field formatter's
 * output.
 *
 * @FieldFormatter(
 *   id = "responsive_image_link_formatter",
 *   label = @Translation("Responsive image wrapped within link field"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 *
 * @see \Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter
 * @see \Drupal\image_link_formatter\Plugin\Field\FieldFormatter\ImageLinkFormatterTrait
 */
class ResponsiveImageLinkFormatter extends ResponsiveImageFormatter implements ContainerFactoryPluginInterface {

  use ImageLinkFormatterTrait;

  /**
   * Constructs a ResponsiveImageLinkFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $responsive_image_style_storage, $image_style_storage, $link_generator, $current_user);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Adds the 'entity_field.manager' service to parent's container.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('entity_field.manager')
    );
  }

}
