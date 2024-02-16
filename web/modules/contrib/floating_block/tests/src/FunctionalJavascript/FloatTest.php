<?php

namespace Drupal\Tests\floating_block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests if a block can float.
 *
 * @group floating_block
 */
class FloatTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'floating_block',
    'floating_block_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests that the floating element moves on scroll.
   */
  public function testFloatOnScroll() {
    $this->setFloatingBlocks([
      [
        'selector' => '#fb-menu',
      ],
    ]);

    $this->drupalGet('/floating_block_test/page');
    $fb_content_xpath = "//section[@id='fb-content']";
    $fb_menu_xpath = "//section[@id='fb-menu']";

    $this->resizeWindow(600, 300);

    // Assert that section's 'fb-content' and 'fb-menu' are both at 50 at first.
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(50, $fb_menu_xpath);

    // Now scroll 100 pixels down.
    $this->scrollTo(0, 100);

    // The floating element's Y position should now be 100.
    $this->assertElementYPosition(100, $fb_menu_xpath);

    // The Y position of the other element should stay 50.
    $this->assertElementYPosition(50, $fb_content_xpath);

    // Now scroll down to below the container's height and ensure that the
    // element is stil floating.
    $this->scrollTo(0, 750);
    $this->assertElementYPosition(750, $fb_menu_xpath);
  }

  /**
   * Tests that the floating element moves and has a certain padding.
   */
  public function testFloatWithPadding() {
    $this->setFloatingBlocks([
      [
        'selector' => '#fb-menu',
        'padding_top' => 30,
      ],
    ]);

    $this->drupalGet('/floating_block_test/page');
    $fb_content_xpath = "//section[@id='fb-content']";
    $fb_menu_xpath = "//section[@id='fb-menu']";

    $this->resizeWindow(600, 300);

    // Assert that section's 'fb-content' and 'fb-menu' are both at 50 at first.
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(50, $fb_menu_xpath);

    // Now scroll 100 pixels down.
    $this->scrollTo(0, 100);

    // The floating element's Y position should now be 130: 100 for scrolling
    // down and 30 was set as offset padding.
    $this->assertElementYPosition(130, $fb_menu_xpath);

    // The Y position of the other element should stay 50.
    $this->assertElementYPosition(50, $fb_content_xpath);
  }

  /**
   * Tests that the floating element only moves within the container.
   */
  public function testFloatWithinContainer() {
    $this->setFloatingBlocks([
      [
        'selector' => '#fb-menu',
        'container' => '#fb-container',
      ],
    ]);

    $this->drupalGet('/floating_block_test/page');
    $fb_content_xpath = "//section[@id='fb-content']";
    $fb_menu_xpath = "//section[@id='fb-menu']";

    $this->resizeWindow(600, 300);

    // Assert that section's 'fb-content' and 'fb-menu' are both at 50 at first.
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(50, $fb_menu_xpath);

    // Now scroll 100 pixels down. The floating element's Y position should be
    // 100, while the other element should not have moved.
    $this->scrollTo(0, 100);
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(100, $fb_menu_xpath);

    // Now scroll down to below the container's height. The floating element
    // should have stopped floating and the position of the other element should
    // still be the same.
    $this->scrollTo(0, 750);
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(550, $fb_menu_xpath);
  }

  /**
   * Tests that the floating element only moves at certain window widths.
   */
  public function testFloatAtMinWidth() {
    $this->setFloatingBlocks([
      [
        'selector' => '#fb-menu',
      ],
    ]);
    // Set a minimum window width where the block should float. When the
    // window's width is lower than this amount, the block should stop floating.
    $this->config('floating_block.settings')
      ->set('min_width', 650)
      ->save();

    $this->drupalGet('/floating_block_test/page');
    $fb_content_xpath = "//section[@id='fb-content']";
    $fb_menu_xpath = "//section[@id='fb-menu']";

    $this->resizeWindow(650, 300);

    // Assert that section's 'fb-content' and 'fb-menu' are both at 50 at first.
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(50, $fb_menu_xpath);

    // Now scroll 100 pixels down. The floating element's Y position should be
    // 100, while the other element should not have moved.
    $this->scrollTo(0, 100);
    $this->assertElementYPosition(50, $fb_content_xpath);
    $this->assertElementYPosition(100, $fb_menu_xpath);

    // Now shrink the window to be lower than 650 wide. The element that was
    // floating should no longer float and be back at its starting position.
    $this->resizeWindow(649, 300);
    // And scroll to trigger recalculating the block's position.
    $this->scrollTo(0, 105);
    $this->assertElementYPosition(50, $fb_menu_xpath);
  }

  /**
   * Asserts that the element is at the expected Y position.
   *
   * @param int $position
   *   The expected y position of the element.
   * @param string $xpath
   *   The xpath that's needed to locate the element on the page.
   */
  protected function assertElementYPosition(int $position, string $xpath): void {
    $location = $this->getElementPosition($xpath);
    $this->assertEquals($position, $location['y']);
  }

  /**
   * Sets floating block configuration.
   */
  protected function setFloatingBlocks(array $blocks): void {
    $this->config('floating_block.settings')
      ->set('blocks', $blocks)
      ->save();
  }

  /**
   * Scroll to a specific location.
   *
   * @param int $x
   *   The X-coordinate to scroll to (left/right).
   * @param int $y
   *   The Y-coordinate to scroll to (up/down).
   */
  protected function scrollTo(int $x, int $y): void {
    $this->getSession()->executeScript("window.scrollTo($x,$y);");
  }

  /**
   * Resizes the window.
   *
   * @param int $width
   *   The width that the window should get.
   * @param int $height
   *   The height that the window should get.
   */
  protected function resizeWindow(int $width, int $height): void {
    $this->getSession()->resizeWindow($width, $height, 'current');
  }

  /**
   * Returns position data of the element.
   *
   * @param string $xpath
   *   The xpath to locate the element.
   *
   * @return array
   *   Data about the element's location, including the X and Y position.
   */
  protected function getElementPosition(string $xpath): array {
    return $this->getSession()->getDriver()->getWebDriverSession()->element('xpath', $xpath)->location();
  }

}
