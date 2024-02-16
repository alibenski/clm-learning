<?php

namespace Drupal\hacked;

use Drupal\Core\File\FileSystemInterface;

/**
 * Downloads a project using a standard Drupal method.
 */
class HackedProjectWebFilesDownloader extends HackedProjectWebDownloader {

  /**
   * Download link.
   */
  public function downloadLink() {
    if (!empty($this->project->projectInfo['releases'][$this->project->existingVersion])) {
      $this_release = $this->project->projectInfo['releases'][$this->project->existingVersion];
      return $this_release['download_link'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function download() {
    $dir = $this->getDestination();
    if (!($release_url = $this->downloadLink())) {
      return FALSE;
    }

    // If our directory already exists, we can just return the path to this
    // cached version.
    if (file_exists($dir) && count(hacked_file_scan_directory($dir, '/.*/', [
      '.',
      '..',
      'CVS',
      '.svn',
      '.git',
    ]))) {
      return $dir;
    }

    // Build the destination folder tree if it doesn't already exists.
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY) && !mkdir($dir, 0775, TRUE)) {
      $message = $this->t('Failed to create temp directory: %dir', ['%dir' => $dir]);
      \Drupal::logger('hacked')->error($message->render());
      return FALSE;
    }

    if (!($local_file = $this->fileGet($release_url))) {
      $message = $this->t('Could not download the project: @name from URL: @url', [
        '@name' => $this->project->title(),
        '@url'  => $release_url,
      ]);
      \Drupal::logger('hacked')->error($message->render());
      return FALSE;
    }
    try {
      $this->archiveExtract($local_file, $dir);
    }
    catch (\Exception $e) {
      $message = $this->t('Could not extract the project: @name. Error was: !error', [
        '@name'  => $this->project->title(),
        '!error' => $e->getMessage(),
      ]);
      \Drupal::logger('hacked')->error($message->render());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Copies a file from $url to the temporary directory for updates.
   *
   * If the file has already been downloaded, returns the the local path.
   *
   * @param string $url
   *   The URL of the file on the server.
   *
   * @return string
   *   Path to local file.
   */
  public function fileGet(string $url) {
    $parsed_url = parse_url($url);
    $remote_schemes = ['http', 'https', 'ftp', 'ftps', 'smb', 'nfs'];
    if (!in_array($parsed_url['scheme'], $remote_schemes)) {
      // This is a local file, just return the path.
      return \Drupal::service('file_system')->realpath($url);
    }

    // Check the cache and download the file if needed.
    $cache_directory = 'temporary://hacked-cache';
    $local = $cache_directory . '/' . basename($parsed_url['path']);

    if (!file_exists($cache_directory)) {
      mkdir($cache_directory);
    }

    return system_retrieve_file($url, $local, FALSE, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Unpack a downloaded archive file.
   *
   * @param string $file
   *   The filename of the archive you wish to extract.
   * @param string $directory
   *   The directory you wish to extract the archive into.
   *
   * @return Archiver
   *   The Archiver object used to extract the archive.
   */
  public function archiveExtract($file, $directory) {
    $archiver = \Drupal::service('plugin.manager.archiver')->getInstance([
      'filepath' => $file,
    ]);
    if (!$archiver) {
      throw new \Exception('Cannot extract %file, not a valid archive.', ['%file' => $file]);
    }

    // Remove the directory if it exists, otherwise it might contain a mixture
    // of old files mixed with the new files (e.g. in cases where files were
    // removed from a later release).
    $files = $archiver->listContents();
    // Unfortunately, we can only use the directory name for this.
    $project = mb_substr($files[0], 0, -1);
    $extract_location = $directory . '/' . $project;
    if (file_exists($extract_location)) {
      \Drupal::service('file_system')->deleteRecursive($extract_location);
    }

    $archiver->extract($directory);
    return $archiver;
  }

}
