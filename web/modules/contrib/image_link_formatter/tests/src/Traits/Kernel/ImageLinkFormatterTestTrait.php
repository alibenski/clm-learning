<?php

namespace Drupal\Tests\image_link_formatter\Traits\Kernel;

/**
 * @file
 * Contains methods used for Kernel test cases for image link formatters.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;

/**
 * Adds common methods to implement Kernel tests for image link formatters.
 *
 * Provides common functionality for any image formatter using the trait to
 * implement Kernel test cases:
 *   - Handle the logic related with the added link field (setUp).
 *   - Generic test case 'doTestImageLinkFormatterUrlOptions' checks the
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

    // Create and configure link field attached to the test node type.
    $this->createLinkField();
  }

  /**
   * Create a link field attached to the test entity type used for the tests.
   */
  protected function createLinkField() {
    $this->fieldNameLink = mb_strtolower($this->randomMachineName());
    // Create link field storage, config and display for entity test type.
    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldNameLink,
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    // Create link field configuration for test entity's bundle.
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldNameLink,
      'bundle' => $this->bundle,
      'settings' => [
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();

    // Save link field's entity view display settings (visible).
    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle)
      ->setComponent($this->fieldNameLink, [
        'type' => 'link',
        'label' => 'hidden',
      ]);
    $this->display->save();
  }

  /**
   * Test rendering link attributes options wrapped around an image.
   *
   * Provides the basic routine to compare expected results with the rendering
   * output of the image link formatter provided, with additional attributes
   * options, such as 'alt', 'title', 'rel', or any attribute for the test:
   *   - Configure image field's display with image formatter to point to link.
   *   - Create a test entity with the image and link fields set.
   *   - Render the image field values with set attributes options and confirm
   *     generated output contains link's expected HTML, with the correct
   *     attributes.
   *
   * @param string $formatter_name
   *   The name of the formatter to be tested. A module can implement multiple
   *   formatters and could need different test classes or methods.
   * @param array $link_attribute_options
   *   A standard Drupal options array, with the attribute as key associated to
   *   its value.
   *
   * @see \Drupal\Tests\image\Kernel\ImageFormatterTest::testImageFormatterUrlOptions()
   * @see \Drupal\Tests\image_link_formatter\Kernel\ImageLinkFormatterTest::testImageLinkFormatterUrlOptions()
   * @see \Drupal\Tests\responsive_image_link_formatter\Kernel\ResponsiveImageLinkFormatterTest::testImageLinkFormatterUrlOptions()
   */
  public function doTestImageLinkFormatterUrlOptions(string $formatter_name, array $link_attribute_options = []) {
    // Configure the display with which to test the image field formatter
    // pointing to the link field previously created in 'setUp'.
    $this->display->setComponent($this->fieldName, [
      'type' => $formatter_name,
      'label' => 'hidden',
      'settings' => [
        'image_link' => $this->fieldNameLink,
      ],
    ]);

    // Create a test entity with the image and link fields set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    // Number of values to generate for each field.
    $delta_max = 3;
    $entity->{$this->fieldName}->generateSampleItems($delta_max);
    $entity->{$this->fieldNameLink}->generateSampleItems($delta_max);
    $entity->save();

    // Generate the render array to verify URL options are as expected.
    $build = $this->display->build($entity);

    // @todo Uncomment the following line of code when DO-3064751 is fixed:
    // Remove $link_attribute_options as an argument from the function so it
    // can be fixed in method and run the same for all tests using the trait.
    // $link_attribute_options = ['data-attributes-test' => 'test123'];
    // Calculate the expected result of the link with its attributes options,
    // wrapped around the image.
    $expected_attributes = new Attribute($link_attribute_options);

    // Test multiple values for image and link fields, indexed by delta.
    for ($delta = 0; $delta < $delta_max; $delta++) {

      // Passing a 'MarkupInterface' interface object doesn't sanitize the
      // output, so the 'Attribute' object can be passed directly.
      $expected_result = new FormattableMarkup(
        '<a href="@image_file_url"@url_attributes',
        [
          '@image_file_url' => $entity->{$this->fieldNameLink}->get($delta)->getUrl()->toString(),
          '@url_attributes' => $expected_attributes,
        ]);

      $this->assertInstanceOf(Url::class, $build[$this->fieldName][$delta]['#url']);
      // Set the link's URL attributes options.
      $build[$this->fieldName][$delta]['#url']->setOption('attributes', $link_attribute_options);

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = $this->container->get('renderer');

      // Render the field and confirm the output contains link's expected HTML.
      $output = $renderer->renderRoot($build[$this->fieldName][$delta]);
      $this->assertStringContainsString($expected_result, (string) $output);
    }

  }

}
