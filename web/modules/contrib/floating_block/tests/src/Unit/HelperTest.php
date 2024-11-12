<?php

namespace Drupal\Tests\floating_block\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\floating_block\Helper;

/**
 * @coversDefaultClass \Drupal\floating_block\Helper
 * @group floating_block
 */
class HelperTest extends UnitTestCase {

  /**
   * The class to test.
   *
   * @var \Drupal\floating_block\Helper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->helper = new Helper();
  }

  /**
   * Tests converting settings from string to array.
   *
   * @param array $expected
   *   The expected output.
   * @param string $floating_block_data
   *   The floating block settings to convert to an array.
   *
   * @covers ::convertTextToArray
   * @dataProvider textDataProvider
   */
  public function testConvertTextToArray(array $expected, string $floating_block_data) {
    $this->assertEquals($expected, $this->helper->convertTextToArray($floating_block_data));
  }

  /**
   * Data provider for testConvertTextToArray().
   */
  public function textDataProvider() {
    return [
      // An empty string should result into an empty array.
      'empty' => [
        'expected' => [],
        'floating_block_data' => '',
      ],
      // A single line containing a class.
      'single-line-with-class' => [
        'expected' => [
          ['selector' => '.block-1'],
        ],
        'floating_block_data' => '.block-1',
      ],
      // A single line containing a class and one extra setting.
      'single-line-with-class-and-setting' => [
        'expected' => [
          [
            'selector' => '.block-1',
            'container' => '#main',
          ],
        ],
        'floating_block_data' => '.block-1|container=#main',
      ],
      // A single line containing a class and multiple extra settings.
      'single-line-with-class-and-multiple-settings' => [
        'expected' => [
          [
            'selector' => '.block-1',
            'container' => '#main',
            'padding_top' => '8',
            'padding_bottom' => '4',
          ],
        ],
        'floating_block_data' => '.block-1|container=#main,padding_top=8,padding_bottom=4',
      ],
      // Multiple line configuration.
      'multiple-lines' => [
        'expected' => [
          [
            'selector' => '.block-1',
            'container' => '#main',
            'padding_top' => '8',
          ],
          [
            'selector' => '.block-2',
          ],
        ],
        'floating_block_data' => ".block-1|container=#main,padding_top=8\n.block-2",
      ],
    ];
  }

  /**
   * Tests converting settings from array to string.
   *
   * @param string $expected
   *   The expected output.
   * @param array $floating_blocks
   *   The floating block settings to convert to a string.
   *
   * @covers ::convertArrayToText
   * @dataProvider arrayDataProvider
   */
  public function testConvertArrayToText(string $expected, array $floating_blocks) {
    $this->assertEquals($expected, $this->helper->convertArrayToText($floating_blocks));
  }

  /**
   * Data provider for testConvertArrayToText().
   */
  public function arrayDataProvider() {
    $cases = [];

    // Pick the same cases from the text data provider, but reverse the
    // parameters.
    foreach ($this->textDataProvider() as $key => $text_case) {
      $cases[$key] = [
        'expected' => $text_case['floating_block_data'],
        'floating_blocks' => $text_case['expected'],
      ];
    }

    return $cases;
  }

}
