<?php

namespace Drupal\floating_block;

/**
 * Interface for Floating Block helper service.
 */
interface HelperInterface {

  /**
   * Converts a string representation of floating block settings to an array.
   *
   * @param string $floating_block_data
   *   A string representation of floating block settings.
   *
   * @return array
   *   An array representation of floating block settings.
   */
  public function convertTextToArray(string $floating_block_data): array;

  /**
   * Converts an array representation of floating block settings to a string.
   *
   * @param array $floating_blocks
   *   An array representation of floating block settings.
   *
   * @return string
   *   A string representation of floating block settings.
   */
  public function convertArrayToText(array $floating_blocks): string;

}
