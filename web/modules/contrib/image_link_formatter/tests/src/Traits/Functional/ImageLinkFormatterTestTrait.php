<?php

namespace Drupal\Tests\image_link_formatter\Traits\Functional;

/**
 * @file
 * Contains methods used for Functional test cases for image link formatters.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\link\LinkItemInterface;

/**
 * Adds common methods to implement Functional tests for image link formatters.
 *
 * Provides common functionality for any image formatter using the trait to
 * implement Functional test cases:
 *   - Handle the logic related with the added link field (setUp).
 *   - Generic test case 'doTestImageLinkFormatterWrappedImage' checks the
 *     display of the image link formatter on a node and compares its output
 *     with the expected HTML result.
 *
 * @see \Drupal\Tests\image_link_formatter\Functional\ImageLinkFormatterTest
 * @see \Drupal\Tests\image_link_formatter\Traits\Functional\ImageLinkFormatterTestTrait::doTestImageLinkFormatterWrappedImage()
 * @see \Drupal\Tests\responsive_image_link_formatter\Functional\ResponsiveImageLinkFormatterTest
 */
trait ImageLinkFormatterTestTrait {


  /**
   * The name of the link field used for testing the formatter.
   *
   * @var string
   */
  protected $fieldNameLink;

  /**
   * {@inheritdoc}
   *
   * Extend parent's 'setUp' method by adding a link field to the entity type.
   * Provides a standard setup with an added link field for any test class using
   * the trait.
   */
  protected function setUp(): void {
    // Call parent's 'setUp'.
    parent::setUp();

    // Create and configure link field attached to the 'article' node type.
    $this->createLinkField();
  }

  /**
   * Render a field on a given entity with the given settings.
   *
   * @param Object $entity
   *   The entity to render the field from.
   * @param string $field_name
   *   The field to render.
   * @param array $settings
   *   An array of field settings or a view mode.
   *
   * @return string
   *   Rendered field HTML.
   */
  public function renderField(Object $entity, string $field_name, array $settings) {
    $build = $entity->get($field_name)->view($settings);
    \Drupal::service('renderer')->renderRoot($build[0]);
    $field_output = trim($build[0]['#markup']);
    $field_output = str_replace("\n", '', $field_output);
    return $field_output;
  }

  /**
   * Create and configure link field attached to the 'article' node type.
   */
  protected function createLinkField() {
    // Functional tests with node type 'article' created in parent's 'setUp'.
    $test_entity_type = 'node';
    $test_bundle = 'article';
    $this->fieldNameLink = strtolower($this->randomMachineName());
    // Create link field storage, config and display for 'article' node type.
    FieldStorageConfig::create([
      'entity_type' => $test_entity_type,
      'field_name' => $this->fieldNameLink,
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    // Create link field configuration for 'article' node type.
    FieldConfig::create([
      'entity_type' => $test_entity_type,
      'field_name' => $this->fieldNameLink,
      'bundle' => $test_bundle,
      'settings' => [
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();

    // Save link field's entity view display settings (visible).
    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($test_entity_type, $test_bundle)
      ->setComponent($this->fieldNameLink, [
        'type' => 'link',
        'label' => 'hidden',
      ]);
    $this->display->save();
  }

  /**
   * Test rendering provided image link formatter against expected HTML results.
   *
   * Provides the basic routine to compare expected results with the rendering
   * output of the image link formatter provided:
   *   - Create and configure image field attached to the 'article' node type.
   *   - Upload an image through the test 'image' field.
   *   - Render and test formatter when the link field is empty ('no_link').
   *   - Add a value to the link field in the node and save.
   *   - Test the output has been updated: field is showing the expected HTML.
   *
   * @param string $formatter_name
   *   The name of the formatter to be tested. A module can implement multiple
   *   formatters and could need different test classes or methods.
   * @param array $expected_result
   *   An associative array for tests with ('link') or without ('no_link') a
   *   link, containing the HTML result the formatter is expected to output.
   *
   * @see \Drupal\Tests\image_link_formatter\Functional\ImageLinkFormatterTest::testImageLinkFormatterWrappedImage()
   * @see \Drupal\Tests\responsive_image_link_formatter\Functional\ResponsiveImageLinkFormatterTest::testResponsiveImageLinkFormatterWrappedImage()
   */
  public function doTestImageLinkFormatterWrappedImage(string $formatter_name, array $expected_result) {
    // Since Functional tests parent class method 'ImageFormatterTest::setUp'
    // doesn't create an image field, it needs to be created in the test method.
    $image_field_name = strtolower($this->randomMachineName());
    $this->createImageField(
      $image_field_name,
      'node',
      'article',
      ['uri_scheme' => 'public'],
      ['alt_field_required' => 0]
    );

    // Upload a test image to a new node of type 'article' without a link value.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $image_field_name, 'article');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node = $node_storage->load($nid);
    $file = File::load($node->$image_field_name->get(0)->getValue()['target_id']);

    // Configure image field formatter's settings to point to the link field
    // previously created in 'setUp'.
    $image_field_settings = [
      'type' => $formatter_name,
      'settings' => ['image_link' => $this->fieldNameLink],
    ];

    // Test the formatter when the link field is empty ('no_link').
    $output = $this->renderField($node, $image_field_name, $image_field_settings);
    $this->assertImageLinkFormatter($output, $expected_result, $file->createFileUrl());

    // Add a link to the node and test the output has been updated.
    $node->{$this->fieldNameLink} = [
      'uri' => 'http://example.com',
      'options' => [],
      'title' => 'Custom Link',
    ];
    $node->save();

    // Test the formatter when a link is present ('link').
    $output = $this->renderField($node, $image_field_name, $image_field_settings);
    $this->assertImageLinkFormatter($output, $expected_result, $file->createFileUrl(), TRUE);
  }

  /**
   * Helper function to assert the correct display of the image link formatter.
   *
   * The rendered output is compared to the expected result, with or without a
   * link.
   *
   * @param string $rendered_output
   *   The output of the field formatter's rendering process.
   * @param array $expected_result
   *   An associative array keyed for tests with ('link' key) or without
   *   ('no_link' key) a link, containing the HTML result the formatter is
   *   expected to generate.
   * @param string $file_url
   *   The URL of the image file.
   * @param bool $show_link
   *   Boolean flag to determine whether to expect a link in the output or not.
   *   Set to 'TRUE' to show a link in the expected result.
   * @param string $alt
   *   Additional HTML attributes options to be added to the image or link, such
   *   as 'alt', 'title', 'rel' (@TODO: currently unused - not yet implemented).
   *
   * @see \Drupal\Tests\image_link_formatter\Functional\ImageLinkFormatterTest::testImageLinkFormatterWrappedImage()
   * @see \Drupal\Tests\image_link_formatter\Functional\ResponsiveImageLinkFormatterTest::testResponsiveImageLinkFormatterWrappedImage()
   */
  public function assertImageLinkFormatter($rendered_output, array $expected_result, string $file_url, bool $show_link = FALSE, string $alt = "") {
    // Determine whether we're testing the output with a 'link' or 'no_link'.
    $show_link = ($show_link) ? 'link' : 'no_link';

    // Compare the rendered output with the expected result for this case.
    $this->assertSame($rendered_output, (string) new FormattableMarkup($expected_result[$show_link], ["@file_url" => $file_url]));
  }

}
