<?php

namespace Drupal\Tests\responsive_image_link_formatter\Kernel;

/**
 * @file
 * Contains Kernel test cases for the 'responsive_image_link_formatter'.
 */

use Drupal\Tests\image\Kernel\ImageFormatterTest;
use Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait;

/**
 * Tests the rendering of a responsive image wrapped within a custom link.
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
 * @see \Drupal\Tests\image_link_formatter\Kernel\ImageLinkFormatterTest
 * @see \Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait
 */
class ResponsiveImageLinkFormatterTest extends ImageFormatterTest {

  use ImageLinkFormatterTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'link',
    'responsive_image',
    'responsive_image_link_formatter',
  ];

  /**
   * Tests rendering link attributes options wrapped around a responsive image.
   *
   * Call standard test method from the trait which renders the values of the
   * image field of a node, with the responsive image link formatter with a
   * link and checks whether the expected HTML is found with the correct
   * attributes options.
   *
   * @see \Drupal\Tests\image_link_formatter\Kernel\ImageLinkFormatterTest::testImageLinkFormatterUrlOptions()
   * @see \Drupal\Tests\image_link_formatter\Traits\Kernel\ImageLinkFormatterTestTrait::doTestImageLinkFormatterUrlOptions()
   */
  public function testImageLinkFormatterUrlOptions() {
    // Call the test from the trait for the 'responsive_image_link_formatter'.
    $this->doTestImageLinkFormatterUrlOptions('responsive_image_link_formatter');
  }

}
