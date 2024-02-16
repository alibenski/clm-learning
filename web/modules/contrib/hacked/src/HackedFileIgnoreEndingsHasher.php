<?php

namespace Drupal\hacked;

/**
 * Hacked file ignore endings hasher.
 */
class HackedFileIgnoreEndingsHasher extends HackedFileHasher {

  /**
   * {@inheritdoc}
   */
  public function performHash($filename) {
    if (!hacked_file_is_binary($filename)) {
      $file = file($filename, FILE_IGNORE_NEW_LINES);
      return sha1(serialize($file));
    }
    else {
      return sha1_file($filename);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLines($filename) {
    return file($filename, FILE_IGNORE_NEW_LINES);
  }

}
