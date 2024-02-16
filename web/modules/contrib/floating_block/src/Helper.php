<?php

namespace Drupal\floating_block;

/**
 * Floating Block helper service.
 */
class Helper implements HelperInterface {

  /**
   * {@inheritdoc}
   */
  public function convertTextToArray(string $floating_block_data): array {
    // Bail out early if an empty string is provided.
    if (empty($floating_block_data)) {
      return [];
    }

    $floating_blocks = preg_split("/(\r\n|\n)/", $floating_block_data, -1, PREG_SPLIT_NO_EMPTY);
    $output = [];

    foreach ($floating_blocks as $floating_block) {
      $settings = explode('|', $floating_block);
      $instance = [
        'selector' => $settings[0],
      ];
      if (isset($settings[1])) {
        preg_match_all("/([^=|,]*)=([^=|,]*),?/", $settings[1], $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
          $instance[$match[1]] = $match[2];
        }
      }
      $output[] = $instance;
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function convertArrayToText(array $floating_blocks): string {
    // Bail out early if an empty array is provided.
    if (empty($floating_blocks)) {
      return '';
    }

    $output = [];
    foreach ($floating_blocks as $settings) {
      if (count($settings) && isset($settings['selector'])) {
        $output_line = $settings['selector'];
        $settings_line = [];
        foreach ($settings as $key => $value) {
          if ($key != 'selector') {
            $settings_line[] = $key . '=' . $value;
          }
        }
        if (!empty($settings_line)) {
          $output_line .= '|' . implode(',', $settings_line);
        }
        $output[] = $output_line;
      }
    }

    return implode("\n", $output);
  }

}
