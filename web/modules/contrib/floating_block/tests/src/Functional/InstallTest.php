<?php

namespace Drupal\Tests\floating_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group floating_block
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Tests module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('floating_block'));
    $this->assertTrue($this->moduleInstaller->install(['floating_block']));

    // Reload module handler.
    unset($this->moduleHandler);
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');

    $this->assertTrue($this->moduleHandler->moduleExists('floating_block'));

    // Make sure that by default the config page is not accessible.
    $this->drupalGet('admin/config/user-interface/floating-block');
    $this->assertSession()->statusCodeEquals(403, 'No access to the Floating Block settings form.');
  }

}
