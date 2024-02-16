<?php

namespace Drupal\image_link_formatter\Plugin\Field\FieldFormatter;

/**
 * @file
 * Contains implementation of formatter plugin 'image_link_formatter'.
 */

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends Core 'image' formatter plugin to add a link from a custom field.
 *
 * Essentially combines core 'ImageFormatter' object with methods of the trait
 * 'ImageLinkFormatterTrait' which extends formatter's methods to allow wrapping
 * a link from a custom field around the image field formatter's output.
 *
 * @FieldFormatter(
 *   id = "image_link_formatter",
 *   label = @Translation("Image wrapped within link field"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 *
 * @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter
 * @see \Drupal\image_link_formatter\Plugin\Field\FieldFormatter\ImageLinkFormatterTrait
 */
class ImageLinkFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  use ImageLinkFormatterTrait;

  /**
   * Constructs an ImageLinkFormatter object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator'),
      $container->get('entity_field.manager')
    );
  }

}
