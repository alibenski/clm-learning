<?php

namespace Drupal\hacked;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for downloading remote versions of projects.
 */
class HackedProjectWebDownloader {
  use StringTranslationTrait;

  /**
   * Hacked project.
   *
   * @var \Drupal\hacked\HackedProject
   */
  protected $project;

  /**
   * Constructor, pass in the project this downloaded is expected to download.
   */
  public function __construct(&$project) {
    $this->project = $project;
  }

  /**
   * Returns a temp directory to work in.
   *
   * @param null $namespace
   *   The optional namespace of the temp directory, defaults to the classname.
   *
   * @return bool|string
   *   Get temp directory.
   */
  public function getTempDirectory($namespace = NULL) {
    if (is_null($namespace)) {
      $reflect = new \ReflectionClass($this);
      $namespace = $reflect->getShortName();
    }
    $segments = [
      \Drupal::service('file_system')->getTempDirectory(),
      'hacked-cache-' . get_current_user(),
      $namespace,
    ];
    $dir = implode('/', array_filter($segments));
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY) && !mkdir($dir, 0775, TRUE)) {
      $message = $this->t('Failed to create temp directory: %dir', ['%dir' => $dir]);
      \Drupal::logger('hacked')->error($message);
      return FALSE;
    }
    return $dir;
  }

  /**
   * Returns a directory to save the downloaded project into.
   */
  public function getDestination() {
    $type = $this->project->projectType;
    $name = $this->project->name;
    $version = $this->project->existingVersion;

    $dir = $this->getTempDirectory() . "/$type/$name";
    // Build the destination folder tree if it doesn't already exists.
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY) && !mkdir($dir, 0775, TRUE)) {
      $message = $this->t('Failed to create temp directory: %dir', ['%dir' => $dir]);
      \Drupal::logger('hacked')->error($message);
      return FALSE;
    }
    return "$dir/$version";
  }

  /**
   * Returns the final destination of the unpacked project.
   */
  public function getFinalDestination() {
    $dir = $this->getDestination();
    $name = $this->project->name;
    $version = $this->project->existingVersion;
    $type = $this->project->projectType;
    // More special handling for core:
    if ($type != 'core') {
      $module_dir = $dir . "/$name";
    }
    else {
      $module_dir = $dir . '/' . $name . '-' . $version;
    }
    return $module_dir;
  }

  /**
   * Download the remote files to the local filesystem.
   */
  public function download() {

  }

  /**
   * Recursively delete all files and folders in the specified filepath.
   *
   * Then delete the containing folder. Note that this only deletes visible
   * files with write permission.
   *
   * @param string $path
   *   A filepath relative to file_directory_path.
   */
  public function removeDir($path) {
    if (is_file($path) || is_link($path)) {
      unlink($path);
    }
    elseif (is_dir($path)) {
      $d = dir($path);
      while (($entry = $d->read()) !== FALSE) {
        if ($entry == '.' || $entry == '..') {
          continue;
        }
        $entry_path = $path . '/' . $entry;
        $this->removeDir($entry_path);
      }
      $d->close();
      rmdir($path);
    }
    else {
      $message = $this->t('Unknown file type(%path) stat: %stat', [
        '%path' => $path,
        '%stat' => print_r(stat($path), 1),
      ]);
      \Drupal::logger('hacked')->error($message);
    }
  }

}
