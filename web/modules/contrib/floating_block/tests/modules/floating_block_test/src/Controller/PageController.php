<?php

namespace Drupal\floating_block_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Generates pages with elements that can be configured to float.
 */
class PageController extends ControllerBase {

  /**
   * Returns a page.
   */
  public function page(): array {
    return [
      '#theme' => 'floating_block_test_page',
    ];
  }

}
