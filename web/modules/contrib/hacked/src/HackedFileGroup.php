<?php

namespace Drupal\hacked;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Represents a group of files on the local filesystem.
 */
class HackedFileGroup {

  use StringTranslationTrait;

  /**
   * Base path.
   *
   * @var string
   */
  public $basePath = '';

  /**
   * Files.
   *
   * @var array
   */
  public $files = [];

  /**
   * Files hashes.
   *
   * @var array
   */
  public $filesHashes = [];

  /**
   * File mtimes.
   *
   * @var array
   */
  public $fileMtimes = [];

  /**
   * Hasher.
   *
   * @var HackedFileIgnoreEndingsHasher|HackedFileIncludeEndingsHasher
   */
  public $hasher;

  /**
   * Constructor.
   */
  public function __construct($basePath) {
    $this->basePath = $basePath;
    $this->hasher = hacked_get_file_hasher();
  }

  /**
   * Return a new HackedFileGroup listing all files inside the given $path.
   */
  public static function fromDirectory($path) {
    $filegroup = new HackedFileGroup($path);
    // Find all the files in the path, and add them to the file group.
    $filegroup->scanBasePath();
    return $filegroup;
  }

  /**
   * Return a new HackedFileGroup listing all files specified.
   */
  public static function fromList($path, $files) {
    $filegroup = new HackedFileGroup($path);
    // Find all the files in the path, and add them to the file group.
    $filegroup->files = $files;
    return $filegroup;
  }

  /**
   * Locate all sensible files at the base path of the file group.
   */
  public function scanBasePath() {
    $files = hacked_file_scan_directory($this->basePath, '/.*/', [
      '.',
      '..',
      'CVS',
      '.svn',
      '.git',
    ]);
    foreach ($files as $file) {
      $filename = str_replace($this->basePath . '/', '', $file->filename);
      $this->files[] = $filename;
    }
  }

  /**
   * Hash all files listed in the file group.
   */
  public function computeHashes() {
    foreach ($this->files as $filename) {
      $this->filesHashes[$filename] = $this->hasher->hash($this->basePath . '/' . $filename);
    }
  }

  /**
   * Determine if the given file is readable.
   */
  public function isReadable($file) {
    return $this->basePath . '/' . $file;
  }

  /**
   * Determine if a file exists.
   */
  public function fileExists($file) {
    return \file_exists($this->basePath . '/' . $file);
  }

  /**
   * Determine if the given file is binary.
   */
  public function isNotBinary($file) {
    return \is_readable($this->basePath . '/' . $file) && !hacked_file_is_binary($this->basePath . '/' . $file);
  }

  /**
   * File get location.
   */
  public function fileGetLocation($file) {
    return $this->basePath . '/' . $file;
  }

}
