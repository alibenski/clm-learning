<?php

namespace Drupal\hacked;

/**
 * Base class for the different ways that files can be hashed.
 */
abstract class HackedFileHasher {

  /**
   * Returns a hash of the given filename.
   *
   * Ignores file line endings.
   */
  public function hash($filename) {
    if (file_exists($filename)) {
      if ($hash = $this->cacheGet($filename)) {
        return $hash;
      }
      else {
        $hash = $this->performHash($filename);
        $this->cacheSet($filename, $hash);
        return $hash;
      }
    }
  }

  /**
   * Set cache.
   */
  public function cacheSet($filename, $hash) {
    \Drupal::cache()->set($this->cacheKey($filename), $hash, strtotime('+7 days'));
  }

  /**
   * Get cache.
   */
  public function cacheGet($filename) {
    $cache = \Drupal::cache()->get($this->cacheKey($filename));
    if (!empty($cache->data)) {
      return $cache->data;
    }
  }

  /**
   * Cache key.
   */
  public function cacheKey($filename) {
    $key = [
      'filename' => $filename,
      'mtime' => filemtime($filename),
      'class_name' => get_class($this),
    ];
    return sha1(serialize($key));
  }

  /**
   * Compute and return the hash of the given file.
   *
   * @param string $filename
   *   A fully-qualified filename to hash.
   *
   * @return string
   *   The computed hash of the given file.
   */
  abstract public function performHash(string $filename);

  /**
   * Compute and return the lines of the given file.
   *
   * @param string $filename
   *   A fully-qualified filename to return.
   *
   * @return array|bool
   *   The lines of the given filename or FALSE on failure.
   */
  abstract public function fetchLines(string $filename);

}
