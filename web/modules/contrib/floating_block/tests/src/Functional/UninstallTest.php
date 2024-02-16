<?php

namespace Drupal\Tests\floating_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group floating_block
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['floating_block'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that floating_block has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('floating_block'));

    // Uninstall floating_block.
    $this->container->get('module_installer')->uninstall(['floating_block']);
    $this->assertFalse($module_handler->moduleExists('floating_block'));
  }

}
