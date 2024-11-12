<?php

namespace Drupal\Tests\image_link_formatter\Kernel;

/**
 * @file
 * Contains Kernel test cases for the 'image_link_formatter'.
 */

use Drupal\Tests\image\Kernel\ImageFormatterTest;
use Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait;

/**
 * Tests the rendering of an image wrapped within a link through the formatter.
 *
 * Extends the core test case 'ImageFormatterTest' for the creation of the base
 * configuration objects needed for the tests ('setUp'), in particular the
 * 'image' field. On top, the logic related with the 'link' field is added by
 * the trait 'ImageLinkFormatterTestTrait' which provides common methods, such
 * as the effective tests ('doTestImageLinkFormatterUrlOptions'), or the
 * creation and configuration of the link field.
 *
 * @group image_link_formatter
 * @see \Drupal\Tests\image\Kernel\ImageFormatterTest
 * @see \Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait
 * @see \Drupal\Tests\responsive_image_link_formatter\Kernel\ResponsiveImageLinkFormatterTest
 */
class ImageLinkFormatterTest extends ImageFormatterTest {

  use ImageLinkFormatterTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'image', 'link', 'image_link_formatter'];

  /**
   * Tests rendering link field attributes options wrapped around an image.
   *
   * Define the attributes options to be expected in the link displayed, such as
   * 'alt', 'title', 'rel', or any attribute for the test. Then call standard
   * test method from the trait which renders the values of the image field of a
   * node, with the image link formatter with a link and checks whether the
   * expected HTML is found with the correct attributes options.
   *
   * @see \Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait::doTestImageLinkFormatterUrlOptions()
   * @see \Drupal\Tests\responsive_image_link_formatter\Kernel\ResponsiveImageLinkFormatterTest::testImageLinkFormatterUrlOptions()
   */
  public function testImageLinkFormatterUrlOptions() {
    // @todo Remove the following line of code when DO-3064751 is fixed:
    // The $link_attribute_options parameter should be moved to trait's method
    // 'doTestImageLinkFormatterUrlOptions' to stay fixed for any test case
    // using the trait. But since it currently breaks the Kernel tests for
    // 'responsive_image_link_formatter', due to core bug #3064751, with module
    // 'responsive_image' not passing attributes in theme, the
    // $link_attribute_options is made a parameter so it can be skipped (empty)
    // for 'ResponsiveImageLinkFormatterTest'.
    $link_attribute_options = ['data-attributes-test' => 'test123'];

    // Call the test from the trait for the 'image_link_formatter'.
    $this->doTestImageLinkFormatterUrlOptions('image_link_formatter', $link_attribute_options);
  }

}
