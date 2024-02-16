<?php

namespace Drupal\hacked;

/**
 * This is a much faster, but potentially less useful file hasher.
 */
class HackedFileIncludeEndingsHasher extends HackedFileHasher {

  /**
   * {@inheritdoc}
   */
  public function performHash($filename) {
    return sha1_file($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLines($filename) {
    return file($filename);
  }

}
