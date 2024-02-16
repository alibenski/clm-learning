<?php

namespace Drupal\Tests\floating_block\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\floating_block\Form\SettingsForm
 * @group floating_block
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['floating_block'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an user with admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests filling in settings form.
   */
  public function testForm() {
    $edit = [
      'blocks' => ".block-1|container=#main,padding_top=8\n.block-2",
      'min_width' => 850,
    ];
    $this->drupalGet('admin/config/user-interface/floating-block');
    $this->submitForm($edit, 'Save configuration');

    // Check that the config was saved.
    $expected_config = [
      'blocks' => [
        [
          'selector' => '.block-1',
          'container' => '#main',
          'padding_top' => '8',
        ],
        [
          'selector' => '.block-2',
        ],
      ],
      'min_width' => 850,
    ];
    $actual_config = $this->container->get('config.factory')
      ->get('floating_block.settings')
      ->get();

    // From the actual config, remove '_core' as we are not interested in that.
    unset($actual_config['_core']);

    $this->assertEquals($expected_config, $actual_config);

    // And assert that the configuration in the textarea is displayed as
    // expected.
    $this->assertSession()->fieldValueEquals('blocks', ".block-1|container=#main,padding_top=8\n.block-2");
  }

}
